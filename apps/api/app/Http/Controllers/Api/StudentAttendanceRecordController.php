<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\School;
use App\Models\StudentAttendanceRecord;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class StudentAttendanceRecordController extends Controller
{
    public function index(Request $request, School $school): JsonResponse
    {
        Gate::authorize('viewAny', [StudentAttendanceRecord::class, $school]);

        $validated = $request->validate([
            'attendance_date' => ['nullable', 'date'],
            'status' => ['nullable', Rule::in(['present', 'absent', 'late', 'excused'])],
            'search' => ['nullable', 'string', 'max:120'],
        ]);

        $records = $school->studentAttendanceRecords()
            ->with([
                'studentEnrollment:id,student_id,academic_class_id,roll_no',
                'studentEnrollment.student:id,admission_no,full_name',
                'studentEnrollment.academicClass:id,name,code',
            ])
            ->when($validated['attendance_date'] ?? null, fn ($query, string $date) => $query->whereDate('attendance_date', $date))
            ->when($validated['status'] ?? null, fn ($query, string $status) => $query->where('status', $status))
            ->when($validated['search'] ?? null, function ($query, string $search): void {
                $query->whereHas('studentEnrollment.student', function ($studentQuery) use ($search): void {
                    $studentQuery
                        ->where('full_name', 'like', "%{$search}%")
                        ->orWhere('admission_no', 'like', "%{$search}%");
                });
            })
            ->orderByDesc('attendance_date')
            ->orderBy('id')
            ->get();

        return response()->json(['data' => $records]);
    }

    public function store(Request $request, School $school): JsonResponse
    {
        Gate::authorize('create', [StudentAttendanceRecord::class, $school]);

        $record = $school->studentAttendanceRecords()->create($this->validatedPayload($request, $school));

        $this->recordAudit($request, $school, 'student_attendance.created', $record, [
            'new' => $record->only($this->auditedFields()),
        ]);

        return response()->json(['data' => $record->load($this->relations())], 201);
    }

    public function show(Request $request, School $school, StudentAttendanceRecord $studentAttendanceRecord): JsonResponse
    {
        Gate::authorize('view', [$studentAttendanceRecord, $school]);

        return response()->json(['data' => $studentAttendanceRecord->load($this->relations())]);
    }

    public function update(Request $request, School $school, StudentAttendanceRecord $studentAttendanceRecord): JsonResponse
    {
        Gate::authorize('update', [$studentAttendanceRecord, $school]);

        $validated = $this->validatedPayload($request, $school, $studentAttendanceRecord);
        $oldValues = $studentAttendanceRecord->only(array_keys($validated));

        $studentAttendanceRecord->update($validated);

        $this->recordAudit($request, $school, 'student_attendance.updated', $studentAttendanceRecord, [
            'old' => $oldValues,
            'new' => $studentAttendanceRecord->fresh()->only(array_keys($validated)),
        ]);

        return response()->json(['data' => $studentAttendanceRecord->fresh()->load($this->relations())]);
    }

    public function destroy(Request $request, School $school, StudentAttendanceRecord $studentAttendanceRecord): JsonResponse
    {
        Gate::authorize('delete', [$studentAttendanceRecord, $school]);

        $oldValues = $studentAttendanceRecord->only($this->auditedFields());

        $studentAttendanceRecord->delete();

        $this->recordAudit($request, $school, 'student_attendance.deleted', $studentAttendanceRecord, [
            'old' => $oldValues,
        ]);

        return response()->json(status: 204);
    }

    /**
     * @return array<string, mixed>
     */
    private function validatedPayload(Request $request, School $school, ?StudentAttendanceRecord $record = null): array
    {
        $validated = $request->validate([
            'student_enrollment_id' => [
                $record ? 'sometimes' : 'required',
                Rule::exists('student_enrollments', 'id')->where('school_id', $school->id),
            ],
            'attendance_date' => [$record ? 'sometimes' : 'required', 'date'],
            'status' => [$record ? 'sometimes' : 'required', Rule::in(['present', 'absent', 'late', 'excused'])],
            'remarks' => ['nullable', 'string', 'max:2000'],
        ]);

        $enrollmentId = $validated['student_enrollment_id'] ?? $record?->student_enrollment_id;
        $attendanceDate = $validated['attendance_date'] ?? $record?->attendance_date?->format('Y-m-d');

        $duplicateExists = StudentAttendanceRecord::query()
            ->where('school_id', $school->id)
            ->where('student_enrollment_id', $enrollmentId)
            ->whereDate('attendance_date', $attendanceDate)
            ->when($record, fn ($query) => $query->whereKeyNot($record->id))
            ->exists();

        if ($duplicateExists) {
            throw ValidationException::withMessages([
                'attendance_date' => 'Attendance is already recorded for this enrollment on this date.',
            ]);
        }

        return $validated;
    }

    /**
     * @return list<string>
     */
    private function auditedFields(): array
    {
        return ['student_enrollment_id', 'attendance_date', 'status', 'remarks'];
    }

    /**
     * @return list<string>
     */
    private function relations(): array
    {
        return [
            'studentEnrollment:id,student_id,academic_class_id,roll_no',
            'studentEnrollment.student:id,admission_no,full_name',
            'studentEnrollment.academicClass:id,name,code',
        ];
    }

    /**
     * @param  array<string, mixed>  $metadata
     */
    private function recordAudit(Request $request, School $school, string $event, StudentAttendanceRecord $record, array $metadata): void
    {
        AuditLog::query()->create([
            'school_id' => $school->id,
            'actor_id' => $request->user()->id,
            'event' => $event,
            'auditable_type' => $record->getMorphClass(),
            'auditable_id' => $record->id,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'metadata' => $metadata,
        ]);
    }
}
