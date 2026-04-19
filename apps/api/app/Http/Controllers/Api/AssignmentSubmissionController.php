<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Assignment;
use App\Models\AssignmentSubmission;
use App\Models\School;
use App\Models\StudentEnrollment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class AssignmentSubmissionController extends Controller
{
    public function index(Request $request, School $school): JsonResponse
    {
        Gate::authorize('viewAny', [AssignmentSubmission::class, $school]);

        $validated = $request->validate([
            'assignment_id' => ['nullable', Rule::exists('assignments', 'id')->where('school_id', $school->id)],
            'student_enrollment_id' => ['nullable', Rule::exists('student_enrollments', 'id')->where('school_id', $school->id)],
            'status' => ['nullable', Rule::in(['submitted', 'graded', 'returned', 'late'])],
        ]);

        $submissions = $school->assignmentSubmissions()
            ->with([
                'assignment:id,title,due_date,academic_class_id,subject_id',
                'assignment.subject:id,name,code',
                'studentEnrollment:id,student_id,academic_class_id,roll_no,status',
                'studentEnrollment.student:id,admission_no,full_name',
            ])
            ->when($validated['assignment_id'] ?? null, fn ($query, int $assignmentId) => $query->where('assignment_id', $assignmentId))
            ->when($validated['student_enrollment_id'] ?? null, fn ($query, int $enrollmentId) => $query->where('student_enrollment_id', $enrollmentId))
            ->when($validated['status'] ?? null, fn ($query, string $status) => $query->where('status', $status))
            ->latest('submitted_at')
            ->latest()
            ->paginate($this->perPage($request));

        return response()->json($this->paginated($submissions));
    }

    public function store(Request $request, School $school): JsonResponse
    {
        Gate::authorize('create', [AssignmentSubmission::class, $school]);

        $validated = $this->validatedPayload($request, $school);
        $this->assertEnrollmentMatchesAssignment($school, $validated['assignment_id'], $validated['student_enrollment_id']);
        $this->assertNoDuplicateSubmission($school, $validated['assignment_id'], $validated['student_enrollment_id']);

        $submission = $school->assignmentSubmissions()->create([
            ...$validated,
            'submitted_at' => $validated['submitted_at'] ?? now(),
            'status' => $validated['status'] ?? 'submitted',
        ]);

        $this->recordAudit($request, $school, 'assignment_submission.created', $submission, [
            'new' => $submission->only($this->auditedFields()),
        ]);

        return response()->json(['data' => $submission->load(['assignment:id,title,due_date', 'studentEnrollment.student:id,admission_no,full_name'])], 201);
    }

    public function show(Request $request, School $school, AssignmentSubmission $assignmentSubmission): JsonResponse
    {
        Gate::authorize('view', [$assignmentSubmission, $school]);

        return response()->json([
            'data' => $assignmentSubmission->load([
                'assignment:id,title,due_date,academic_class_id,subject_id',
                'assignment.subject:id,name,code',
                'studentEnrollment:id,student_id,academic_class_id,roll_no,status',
                'studentEnrollment.student:id,admission_no,full_name',
            ]),
        ]);
    }

    public function update(Request $request, School $school, AssignmentSubmission $assignmentSubmission): JsonResponse
    {
        Gate::authorize('update', [$assignmentSubmission, $school]);

        $validated = $this->validatedPayload($request, $school, true, $assignmentSubmission);
        $candidateAssignmentId = $validated['assignment_id'] ?? $assignmentSubmission->assignment_id;
        $candidateEnrollmentId = $validated['student_enrollment_id'] ?? $assignmentSubmission->student_enrollment_id;
        $this->assertEnrollmentMatchesAssignment($school, $candidateAssignmentId, $candidateEnrollmentId);
        $this->assertNoDuplicateSubmission($school, $candidateAssignmentId, $candidateEnrollmentId, $assignmentSubmission->id);

        $oldValues = $assignmentSubmission->only(array_keys($validated));
        $assignmentSubmission->update($validated);

        $this->recordAudit($request, $school, 'assignment_submission.updated', $assignmentSubmission, [
            'old' => $oldValues,
            'new' => $assignmentSubmission->fresh()->only(array_keys($validated)),
        ]);

        return response()->json(['data' => $assignmentSubmission->fresh()->load(['assignment:id,title,due_date', 'studentEnrollment.student:id,admission_no,full_name'])]);
    }

    public function destroy(Request $request, School $school, AssignmentSubmission $assignmentSubmission): JsonResponse
    {
        Gate::authorize('delete', [$assignmentSubmission, $school]);

        $oldValues = $assignmentSubmission->only($this->auditedFields());
        $assignmentSubmission->delete();

        $this->recordAudit($request, $school, 'assignment_submission.deleted', $assignmentSubmission, [
            'old' => $oldValues,
        ]);

        return response()->json(status: 204);
    }

    /**
     * @return array<string, mixed>
     */
    private function validatedPayload(Request $request, School $school, bool $isUpdate = false, ?AssignmentSubmission $submission = null): array
    {
        $required = $isUpdate ? 'sometimes' : 'required';

        return $request->validate([
            'assignment_id' => [
                $required,
                Rule::exists('assignments', 'id')->where('school_id', $school->id),
            ],
            'student_enrollment_id' => [$required, Rule::exists('student_enrollments', 'id')->where('school_id', $school->id)],
            'submitted_at' => ['nullable', 'date'],
            'attachment_path' => ['nullable', 'string', 'max:2048'],
            'marks_awarded' => ['nullable', 'numeric', 'min:0', 'max:999999.99'],
            'feedback' => ['nullable', 'string', 'max:5000'],
            'status' => ['sometimes', Rule::in(['submitted', 'graded', 'returned', 'late'])],
        ]);
    }

    private function assertEnrollmentMatchesAssignment(School $school, int $assignmentId, int $enrollmentId): void
    {
        $assignment = Assignment::query()
            ->where('school_id', $school->id)
            ->findOrFail($assignmentId);

        $enrollment = StudentEnrollment::query()
            ->where('school_id', $school->id)
            ->findOrFail($enrollmentId);

        if ($enrollment->academic_class_id !== $assignment->academic_class_id) {
            throw ValidationException::withMessages([
                'student_enrollment_id' => ['The student enrollment is not in the assignment class.'],
            ]);
        }
    }

    private function assertNoDuplicateSubmission(School $school, int $assignmentId, int $enrollmentId, ?int $ignoreId = null): void
    {
        $exists = $school->assignmentSubmissions()
            ->where('assignment_id', $assignmentId)
            ->where('student_enrollment_id', $enrollmentId)
            ->when($ignoreId, fn ($query, int $id) => $query->whereKeyNot($id))
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages([
                'student_enrollment_id' => ['This student already has a submission for the assignment.'],
            ]);
        }
    }

    /**
     * @return array<int, string>
     */
    private function auditedFields(): array
    {
        return [
            'assignment_id',
            'student_enrollment_id',
            'submitted_at',
            'attachment_path',
            'marks_awarded',
            'feedback',
            'status',
        ];
    }
}
