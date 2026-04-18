<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ClassSubject;
use App\Models\MarksEntry;
use App\Models\School;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class MarksEntryController extends Controller
{
    public function index(Request $request, School $school): JsonResponse
    {
        $this->authorizeMarks($request, $school);
        $validated = $request->validate([
            'exam_id' => ['nullable', 'integer', Rule::exists('exams', 'id')->where('school_id', $school->id)],
            'class_subject_id' => ['nullable', 'integer', Rule::exists('class_subjects', 'id')->where('school_id', $school->id)],
        ]);

        $entries = $school->marksEntries()
            ->with($this->relations())
            ->when($validated['exam_id'] ?? null, fn ($query, int $id) => $query->where('exam_id', $id))
            ->when($validated['class_subject_id'] ?? null, fn ($query, int $id) => $query->where('class_subject_id', $id))
            ->orderByDesc('id')
            ->paginate($this->perPage($request));

        return response()->json($this->paginated($entries));
    }

    public function store(Request $request, School $school): JsonResponse
    {
        $this->authorizeMarks($request, $school);
        $validated = $this->validatedPayload($request, $school);
        $entry = $this->persist($request, $school, $validated);
        $this->recordAudit($request, $school, 'marks_entry.created', $entry, ['new' => $entry->toArray()]);

        return response()->json(['data' => $entry->load($this->relations())], 201);
    }

    public function show(Request $request, School $school, MarksEntry $marksEntry): JsonResponse
    {
        $this->authorizeMarks($request, $school);
        abort_unless($marksEntry->school_id === $school->id, 404);

        return response()->json(['data' => $marksEntry->load($this->relations())]);
    }

    public function update(Request $request, School $school, MarksEntry $marksEntry): JsonResponse
    {
        $this->authorizeMarks($request, $school);
        abort_unless($marksEntry->school_id === $school->id, 404);
        $validated = $this->validatedPayload($request, $school, $marksEntry);
        $old = $marksEntry->only(array_keys($validated));
        $entry = $this->persist($request, $school, $validated, $marksEntry);
        $this->recordAudit($request, $school, 'marks_entry.updated', $entry, ['old' => $old, 'new' => $entry->only(array_keys($validated))]);

        return response()->json(['data' => $entry->load($this->relations())]);
    }

    public function destroy(Request $request, School $school, MarksEntry $marksEntry): JsonResponse
    {
        $this->authorizeMarks($request, $school);
        abort_unless($marksEntry->school_id === $school->id, 404);
        $marksEntry->update(['voided' => true, 'voided_at' => now(), 'voided_by' => $request->user()->id, 'void_reason' => $request->input('void_reason')]);
        $marksEntry->delete();
        $this->recordAudit($request, $school, 'marks_entry.voided', $marksEntry, ['old' => $marksEntry->toArray()]);

        return response()->json(status: 204);
    }

    public function bulk(Request $request, School $school): JsonResponse
    {
        $this->authorizeMarks($request, $school);
        $validated = $request->validate([
            'exam_id' => ['required', 'integer', Rule::exists('exams', 'id')->where('school_id', $school->id)],
            'class_subject_id' => ['required', 'integer', Rule::exists('class_subjects', 'id')->where('school_id', $school->id)],
            'records' => ['required', 'array', 'min:1'],
            'records.*.student_enrollment_id' => ['required', 'integer', Rule::exists('student_enrollments', 'id')->where('school_id', $school->id)],
            'records.*.marks_obtained' => ['nullable', 'numeric', 'min:0'],
            'records.*.is_absent' => ['nullable', 'boolean'],
            'records.*.absent_reason' => ['nullable', 'string', 'max:255'],
            'records.*.remarks' => ['nullable', 'string', 'max:2000'],
        ]);

        $entries = DB::transaction(function () use ($request, $school, $validated) {
            return collect($validated['records'])->map(function (array $record) use ($request, $school, $validated): MarksEntry {
                return $this->persist($request, $school, [
                    ...$record,
                    'exam_id' => $validated['exam_id'],
                    'class_subject_id' => $validated['class_subject_id'],
                ]);
            })->values();
        });

        return response()->json(['data' => $entries->map->load($this->relations())], 201);
    }

    public function verify(Request $request, School $school, MarksEntry $marksEntry): JsonResponse
    {
        abort_unless($request->user()->hasSchoolPermission($school, 'marks.enter.any'), 403);
        abort_unless($marksEntry->school_id === $school->id, 404);
        $validated = $request->validate([
            'verification_status' => ['required', Rule::in(['verified', 'rejected'])],
            'remarks' => ['nullable', 'string', 'max:2000'],
        ]);
        $marksEntry->update([
            'verification_status' => $validated['verification_status'],
            'verified_by' => $request->user()->id,
            'verified_at' => now(),
            'remarks' => $validated['remarks'] ?? $marksEntry->remarks,
        ]);

        return response()->json(['data' => $marksEntry->fresh()->load($this->relations())]);
    }

    private function authorizeMarks(Request $request, School $school): void
    {
        abort_unless(
            $request->user()->hasSchoolPermission($school, 'marks.enter.any')
            || $request->user()->hasSchoolPermission($school, 'marks.enter.own'),
            403
        );
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    private function persist(Request $request, School $school, array $validated, ?MarksEntry $entry = null): MarksEntry
    {
        $classSubject = ClassSubject::query()
            ->where('school_id', $school->id)
            ->findOrFail($validated['class_subject_id'] ?? $entry?->class_subject_id);

        if (($validated['is_absent'] ?? false) === true) {
            $validated['marks_obtained'] = null;
        } elseif (array_key_exists('marks_obtained', $validated) && $validated['marks_obtained'] > $classSubject->full_marks) {
            throw ValidationException::withMessages(['marks_obtained' => 'Marks obtained cannot exceed full marks configured for the class subject.']);
        }

        $payload = [
            ...$validated,
            'full_marks' => $classSubject->full_marks,
            'pass_marks' => $classSubject->pass_marks,
            'entered_by' => $request->user()->id,
            'verification_status' => $entry?->verification_status ?? 'pending',
        ];

        if ($entry) {
            $entry->update($payload);

            return $entry->fresh();
        }

        return $school->marksEntries()->updateOrCreate(
            [
                'exam_id' => $payload['exam_id'],
                'class_subject_id' => $payload['class_subject_id'],
                'student_enrollment_id' => $payload['student_enrollment_id'],
            ],
            $payload
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function validatedPayload(Request $request, School $school, ?MarksEntry $entry = null): array
    {
        return $request->validate([
            'exam_id' => [$entry ? 'sometimes' : 'required', 'integer', Rule::exists('exams', 'id')->where('school_id', $school->id)],
            'class_subject_id' => [$entry ? 'sometimes' : 'required', 'integer', Rule::exists('class_subjects', 'id')->where('school_id', $school->id)],
            'student_enrollment_id' => [$entry ? 'sometimes' : 'required', 'integer', Rule::exists('student_enrollments', 'id')->where('school_id', $school->id)],
            'marks_obtained' => ['nullable', 'numeric', 'min:0'],
            'is_absent' => ['nullable', 'boolean'],
            'absent_reason' => ['nullable', 'string', 'max:255'],
            'remarks' => ['nullable', 'string', 'max:2000'],
        ]);
    }

    /**
     * @return list<string>
     */
    private function relations(): array
    {
        return [
            'exam:id,name,academic_year_id,exam_type_id',
            'classSubject:id,academic_class_id,subject_id,full_marks,pass_marks',
            'classSubject.subject:id,name,code',
            'studentEnrollment:id,student_id,academic_class_id,roll_no',
            'studentEnrollment.student:id,full_name,admission_no',
        ];
    }
}
