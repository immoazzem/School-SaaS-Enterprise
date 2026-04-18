<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ExamSchedule;
use App\Models\School;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class ExamScheduleController extends Controller
{
    public function index(Request $request, School $school): JsonResponse
    {
        Gate::authorize('viewAny', [ExamSchedule::class, $school]);

        $validated = $request->validate([
            'exam_id' => ['nullable', 'integer', Rule::exists('exams', 'id')->where('school_id', $school->id)],
            'class_subject_id' => ['nullable', 'integer', Rule::exists('class_subjects', 'id')->where('school_id', $school->id)],
            'status' => ['nullable', Rule::in(['scheduled', 'completed', 'cancelled'])],
            'exam_date' => ['nullable', 'date'],
        ]);

        $schedules = $school->examSchedules()
            ->with([
                'exam:id,name,code,starts_on,ends_on',
                'classSubject:id,academic_class_id,subject_id,full_marks,pass_marks',
                'classSubject.academicClass:id,name,code',
                'classSubject.subject:id,name,code,type',
            ])
            ->when($validated['exam_id'] ?? null, fn ($query, int $examId) => $query->where('exam_id', $examId))
            ->when($validated['class_subject_id'] ?? null, fn ($query, int $classSubjectId) => $query->where('class_subject_id', $classSubjectId))
            ->when($validated['status'] ?? null, fn ($query, string $status) => $query->where('status', $status))
            ->when($validated['exam_date'] ?? null, fn ($query, string $examDate) => $query->whereDate('exam_date', $examDate))
            ->orderBy('exam_date')
            ->orderBy('starts_at')
            ->paginate($this->perPage($request));

        return response()->json($this->paginated($schedules));
    }

    public function store(Request $request, School $school): JsonResponse
    {
        Gate::authorize('create', [ExamSchedule::class, $school]);

        $validated = $this->validatePayload($request, $school);

        $schedule = $school->examSchedules()->create([
            ...$validated,
            'status' => $validated['status'] ?? 'scheduled',
        ]);

        $this->recordAudit($request, $school, 'exam_schedule.created', $schedule, [
            'new' => $schedule->only($this->auditedFields()),
        ]);

        return response()->json(['data' => $this->loadSchedule($schedule)], 201);
    }

    public function show(Request $request, School $school, ExamSchedule $examSchedule): JsonResponse
    {
        Gate::authorize('view', [$examSchedule, $school]);

        return response()->json(['data' => $this->loadSchedule($examSchedule)]);
    }

    public function update(Request $request, School $school, ExamSchedule $examSchedule): JsonResponse
    {
        Gate::authorize('update', [$examSchedule, $school]);

        $validated = $this->validatePayload($request, $school, $examSchedule);
        $oldValues = $examSchedule->only(array_keys($validated));

        $examSchedule->update($validated);

        $this->recordAudit($request, $school, 'exam_schedule.updated', $examSchedule, [
            'old' => $oldValues,
            'new' => $examSchedule->fresh()->only(array_keys($validated)),
        ]);

        return response()->json(['data' => $this->loadSchedule($examSchedule->fresh())]);
    }

    public function destroy(Request $request, School $school, ExamSchedule $examSchedule): JsonResponse
    {
        Gate::authorize('delete', [$examSchedule, $school]);

        $oldValues = $examSchedule->only($this->auditedFields());

        $examSchedule->delete();

        $this->recordAudit($request, $school, 'exam_schedule.deleted', $examSchedule, [
            'old' => $oldValues,
        ]);

        return response()->json(status: 204);
    }

    /**
     * @return array<string, mixed>
     */
    private function validatePayload(Request $request, School $school, ?ExamSchedule $examSchedule = null): array
    {
        return $request->validate([
            'exam_id' => [
                $examSchedule ? 'sometimes' : 'required',
                'integer',
                Rule::exists('exams', 'id')->where('school_id', $school->id),
                Rule::unique('exam_schedules')
                    ->where('school_id', $school->id)
                    ->where('class_subject_id', $request->integer('class_subject_id', $examSchedule?->class_subject_id ?? 0))
                    ->ignore($examSchedule?->id),
            ],
            'class_subject_id' => [
                $examSchedule ? 'sometimes' : 'required',
                'integer',
                Rule::exists('class_subjects', 'id')->where('school_id', $school->id),
                Rule::unique('exam_schedules')
                    ->where('school_id', $school->id)
                    ->where('exam_id', $request->integer('exam_id', $examSchedule?->exam_id ?? 0))
                    ->ignore($examSchedule?->id),
            ],
            'exam_date' => [$examSchedule ? 'sometimes' : 'required', 'date'],
            'starts_at' => ['nullable', 'date_format:H:i'],
            'ends_at' => ['nullable', 'date_format:H:i', 'after:starts_at'],
            'room' => ['nullable', 'string', 'max:120'],
            'instructions' => ['nullable', 'string', 'max:4000'],
            'status' => ['nullable', Rule::in(['scheduled', 'completed', 'cancelled'])],
        ]);
    }

    private function loadSchedule(ExamSchedule $schedule): ExamSchedule
    {
        return $schedule->load([
            'exam:id,name,code,starts_on,ends_on',
            'classSubject:id,academic_class_id,subject_id,full_marks,pass_marks',
            'classSubject.academicClass:id,name,code',
            'classSubject.subject:id,name,code,type',
        ]);
    }

    /**
     * @return list<string>
     */
    private function auditedFields(): array
    {
        return [
            'exam_id',
            'class_subject_id',
            'exam_date',
            'starts_at',
            'ends_at',
            'room',
            'instructions',
            'status',
        ];
    }
}
