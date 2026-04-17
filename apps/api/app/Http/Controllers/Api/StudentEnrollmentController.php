<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\School;
use App\Models\StudentEnrollment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class StudentEnrollmentController extends Controller
{
    public function index(Request $request, School $school): JsonResponse
    {
        Gate::authorize('viewAny', [StudentEnrollment::class, $school]);

        $validated = $request->validate([
            'academic_year_id' => ['nullable', 'integer'],
            'academic_class_id' => ['nullable', 'integer'],
            'student_id' => ['nullable', 'integer'],
            'status' => ['nullable', Rule::in(['active', 'completed', 'transferred', 'archived'])],
            'search' => ['nullable', 'string', 'max:120'],
        ]);

        $enrollments = $school->studentEnrollments()
            ->with([
                'student:id,admission_no,full_name',
                'academicYear:id,name,code',
                'academicClass:id,name,code',
                'academicSection:id,name,code',
                'studentGroup:id,name,code',
                'shift:id,name,code',
            ])
            ->when($validated['academic_year_id'] ?? null, fn ($query, int $id) => $query->where('academic_year_id', $id))
            ->when($validated['academic_class_id'] ?? null, fn ($query, int $id) => $query->where('academic_class_id', $id))
            ->when($validated['student_id'] ?? null, fn ($query, int $id) => $query->where('student_id', $id))
            ->when($validated['status'] ?? null, fn ($query, string $status) => $query->where('status', $status))
            ->when($validated['search'] ?? null, function ($query, string $search): void {
                $query->where(function ($nestedQuery) use ($search): void {
                    $nestedQuery
                        ->where('roll_no', 'like', "%{$search}%")
                        ->orWhereHas('student', function ($studentQuery) use ($search): void {
                            $studentQuery
                                ->where('full_name', 'like', "%{$search}%")
                                ->orWhere('admission_no', 'like', "%{$search}%");
                        });
                });
            })
            ->orderByDesc('enrolled_on')
            ->orderBy('roll_no')
            ->get();

        return response()->json(['data' => $enrollments]);
    }

    public function store(Request $request, School $school): JsonResponse
    {
        Gate::authorize('create', [StudentEnrollment::class, $school]);

        $enrollment = $school->studentEnrollments()->create($this->validatedPayload($request, $school));

        $this->recordAudit($request, $school, 'student_enrollment.created', $enrollment, [
            'new' => $enrollment->only($this->auditedFields()),
        ]);

        return response()->json(['data' => $enrollment->load($this->relations())], 201);
    }

    public function show(Request $request, School $school, StudentEnrollment $studentEnrollment): JsonResponse
    {
        Gate::authorize('view', [$studentEnrollment, $school]);

        return response()->json(['data' => $studentEnrollment->load($this->relations())]);
    }

    public function update(Request $request, School $school, StudentEnrollment $studentEnrollment): JsonResponse
    {
        Gate::authorize('update', [$studentEnrollment, $school]);

        $validated = $this->validatedPayload($request, $school, $studentEnrollment);
        $oldValues = $studentEnrollment->only(array_keys($validated));

        $studentEnrollment->update($validated);

        $this->recordAudit($request, $school, 'student_enrollment.updated', $studentEnrollment, [
            'old' => $oldValues,
            'new' => $studentEnrollment->fresh()->only(array_keys($validated)),
        ]);

        return response()->json(['data' => $studentEnrollment->fresh()->load($this->relations())]);
    }

    public function destroy(Request $request, School $school, StudentEnrollment $studentEnrollment): JsonResponse
    {
        Gate::authorize('delete', [$studentEnrollment, $school]);

        $oldValues = $studentEnrollment->only($this->auditedFields());

        $studentEnrollment->delete();

        $this->recordAudit($request, $school, 'student_enrollment.deleted', $studentEnrollment, [
            'old' => $oldValues,
        ]);

        return response()->json(status: 204);
    }

    /**
     * @return array<string, mixed>
     */
    private function validatedPayload(Request $request, School $school, ?StudentEnrollment $enrollment = null): array
    {
        return $request->validate([
            'student_id' => [
                $enrollment ? 'sometimes' : 'required',
                Rule::exists('students', 'id')->where('school_id', $school->id),
            ],
            'academic_year_id' => [
                $enrollment ? 'sometimes' : 'required',
                Rule::exists('academic_years', 'id')->where('school_id', $school->id),
            ],
            'academic_class_id' => [
                $enrollment ? 'sometimes' : 'required',
                Rule::exists('academic_classes', 'id')->where('school_id', $school->id),
            ],
            'academic_section_id' => [
                'nullable',
                Rule::exists('academic_sections', 'id')
                    ->where('school_id', $school->id)
                    ->where(
                        'academic_class_id',
                        $request->input('academic_class_id', $enrollment?->academic_class_id)
                    ),
            ],
            'student_group_id' => [
                'nullable',
                Rule::exists('student_groups', 'id')->where('school_id', $school->id),
            ],
            'shift_id' => [
                'nullable',
                Rule::exists('shifts', 'id')->where('school_id', $school->id),
            ],
            'roll_no' => ['nullable', 'string', 'max:40'],
            'enrolled_on' => [$enrollment ? 'sometimes' : 'required', 'date'],
            'status' => ['nullable', Rule::in(['active', 'completed', 'transferred', 'archived'])],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);
    }

    /**
     * @return list<string>
     */
    private function auditedFields(): array
    {
        return [
            'student_id',
            'academic_year_id',
            'academic_class_id',
            'academic_section_id',
            'student_group_id',
            'shift_id',
            'roll_no',
            'enrolled_on',
            'status',
            'notes',
        ];
    }

    /**
     * @return list<string>
     */
    private function relations(): array
    {
        return [
            'student:id,admission_no,full_name',
            'academicYear:id,name,code',
            'academicClass:id,name,code',
            'academicSection:id,name,code',
            'studentGroup:id,name,code',
            'shift:id,name,code',
        ];
    }

    /**
     * @param  array<string, mixed>  $metadata
     */
    private function recordAudit(
        Request $request,
        School $school,
        string $event,
        StudentEnrollment $enrollment,
        array $metadata
    ): void {
        AuditLog::query()->create([
            'school_id' => $school->id,
            'actor_id' => $request->user()->id,
            'event' => $event,
            'auditable_type' => $enrollment->getMorphClass(),
            'auditable_id' => $enrollment->id,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'metadata' => $metadata,
        ]);
    }
}
