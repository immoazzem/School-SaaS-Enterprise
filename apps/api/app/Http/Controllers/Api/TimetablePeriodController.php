<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\School;
use App\Models\TimetablePeriod;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class TimetablePeriodController extends Controller
{
    public function index(Request $request, School $school): JsonResponse
    {
        Gate::authorize('viewAny', [TimetablePeriod::class, $school]);

        $validated = $request->validate([
            'academic_year_id' => ['nullable', Rule::exists('academic_years', 'id')->where('school_id', $school->id)],
            'academic_class_id' => ['nullable', Rule::exists('academic_classes', 'id')->where('school_id', $school->id)],
            'shift_id' => ['nullable', Rule::exists('shifts', 'id')->where('school_id', $school->id)],
            'day_of_week' => ['nullable', 'integer', 'min:0', 'max:6'],
            'status' => ['nullable', Rule::in(['active', 'archived'])],
        ]);

        $periods = $school->timetablePeriods()
            ->with(['academicYear', 'academicClass', 'shift', 'subject', 'teacherUser'])
            ->when($validated['academic_year_id'] ?? null, fn ($query, int $academicYearId) => $query->where('academic_year_id', $academicYearId))
            ->when($validated['academic_class_id'] ?? null, fn ($query, int $academicClassId) => $query->where('academic_class_id', $academicClassId))
            ->when(array_key_exists('shift_id', $validated), fn ($query) => $query->where('shift_id', $validated['shift_id']))
            ->when(array_key_exists('day_of_week', $validated), fn ($query) => $query->where('day_of_week', $validated['day_of_week']))
            ->when($validated['status'] ?? null, fn ($query, string $status) => $query->where('status', $status))
            ->orderBy('day_of_week')
            ->orderBy('period_number')
            ->orderBy('start_time')
            ->paginate($this->perPage($request));

        return response()->json($this->paginated($periods));
    }

    public function store(Request $request, School $school): JsonResponse
    {
        Gate::authorize('create', [TimetablePeriod::class, $school]);

        $validated = $this->validatedPayload($request, $school);
        $validated['status'] ??= 'active';

        $this->assertNoConflicts($school, $validated);

        $period = $school->timetablePeriods()->create($validated);

        $this->recordAudit($request, $school, 'timetable_period.created', $period, [
            'new' => $period->only($this->auditedFields()),
        ]);

        return response()->json(['data' => $period->load(['academicYear', 'academicClass', 'shift', 'subject', 'teacherUser'])], 201);
    }

    public function show(Request $request, School $school, TimetablePeriod $timetablePeriod): JsonResponse
    {
        Gate::authorize('view', [$timetablePeriod, $school]);

        return response()->json(['data' => $timetablePeriod->load(['academicYear', 'academicClass', 'shift', 'subject', 'teacherUser'])]);
    }

    public function update(Request $request, School $school, TimetablePeriod $timetablePeriod): JsonResponse
    {
        Gate::authorize('update', [$timetablePeriod, $school]);

        $validated = $this->validatedPayload($request, $school, true);
        $candidate = [
            ...$timetablePeriod->only($this->auditedFields()),
            ...$validated,
        ];

        $this->assertNoConflicts($school, $candidate, $timetablePeriod->id);

        $oldValues = $timetablePeriod->only(array_keys($validated));
        $timetablePeriod->update($validated);

        $this->recordAudit($request, $school, 'timetable_period.updated', $timetablePeriod, [
            'old' => $oldValues,
            'new' => $timetablePeriod->fresh()->only(array_keys($validated)),
        ]);

        return response()->json(['data' => $timetablePeriod->fresh()->load(['academicYear', 'academicClass', 'shift', 'subject', 'teacherUser'])]);
    }

    public function destroy(Request $request, School $school, TimetablePeriod $timetablePeriod): JsonResponse
    {
        Gate::authorize('delete', [$timetablePeriod, $school]);

        $oldValues = $timetablePeriod->only($this->auditedFields());
        $timetablePeriod->delete();

        $this->recordAudit($request, $school, 'timetable_period.deleted', $timetablePeriod, [
            'old' => $oldValues,
        ]);

        return response()->json(status: 204);
    }

    /**
     * @return array<string, mixed>
     */
    private function validatedPayload(Request $request, School $school, bool $isUpdate = false): array
    {
        $required = $isUpdate ? 'sometimes' : 'required';

        return $request->validate([
            'academic_year_id' => [$required, Rule::exists('academic_years', 'id')->where('school_id', $school->id)],
            'academic_class_id' => [$required, Rule::exists('academic_classes', 'id')->where('school_id', $school->id)],
            'shift_id' => ['nullable', Rule::exists('shifts', 'id')->where('school_id', $school->id)],
            'day_of_week' => [$required, 'integer', 'min:0', 'max:6'],
            'period_number' => [$required, 'integer', 'min:1', 'max:20'],
            'start_time' => [$required, 'date_format:H:i'],
            'end_time' => [$required, 'date_format:H:i', 'after:start_time'],
            'subject_id' => ['nullable', Rule::exists('subjects', 'id')->where('school_id', $school->id)],
            'teacher_user_id' => [
                'nullable',
                Rule::exists('school_memberships', 'user_id')
                    ->where('school_id', $school->id)
                    ->where('status', 'active'),
            ],
            'room' => ['nullable', 'string', 'max:120'],
            'status' => ['sometimes', Rule::in(['active', 'archived'])],
        ]);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function assertNoConflicts(School $school, array $payload, ?int $ignoreId = null): void
    {
        if (($payload['status'] ?? 'active') !== 'active') {
            return;
        }

        $baseClassQuery = $school->timetablePeriods()
            ->where('academic_year_id', $payload['academic_year_id'])
            ->where('academic_class_id', $payload['academic_class_id'])
            ->where('day_of_week', $payload['day_of_week'])
            ->where('status', 'active')
            ->when($ignoreId, fn ($query, int $id) => $query->whereKeyNot($id));

        $this->applyShiftConstraint($baseClassQuery, $payload['shift_id'] ?? null);

        if ((clone $baseClassQuery)->where('period_number', $payload['period_number'])->exists()) {
            throw ValidationException::withMessages([
                'period_number' => ['This class already has a period at this position for the selected day.'],
            ]);
        }

        if ((clone $baseClassQuery)
            ->where('start_time', '<', $payload['end_time'])
            ->where('end_time', '>', $payload['start_time'])
            ->exists()) {
            throw ValidationException::withMessages([
                'start_time' => ['This class already has an overlapping timetable period.'],
            ]);
        }

        if (! empty($payload['teacher_user_id'])) {
            $teacherConflictExists = $school->timetablePeriods()
                ->where('academic_year_id', $payload['academic_year_id'])
                ->where('day_of_week', $payload['day_of_week'])
                ->where('teacher_user_id', $payload['teacher_user_id'])
                ->where('status', 'active')
                ->where('start_time', '<', $payload['end_time'])
                ->where('end_time', '>', $payload['start_time'])
                ->when($ignoreId, fn ($query, int $id) => $query->whereKeyNot($id))
                ->exists();

            if ($teacherConflictExists) {
                throw ValidationException::withMessages([
                    'teacher_user_id' => ['This teacher is already assigned during the selected time.'],
                ]);
            }
        }
    }

    private function applyShiftConstraint(mixed $query, mixed $shiftId): void
    {
        if ($shiftId === null) {
            $query->whereNull('shift_id');

            return;
        }

        $query->where('shift_id', $shiftId);
    }

    /**
     * @return array<int, string>
     */
    private function auditedFields(): array
    {
        return [
            'academic_year_id',
            'academic_class_id',
            'shift_id',
            'day_of_week',
            'period_number',
            'start_time',
            'end_time',
            'subject_id',
            'teacher_user_id',
            'room',
            'status',
        ];
    }
}
