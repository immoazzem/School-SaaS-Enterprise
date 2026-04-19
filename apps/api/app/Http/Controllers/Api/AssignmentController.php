<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Assignment;
use App\Models\School;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class AssignmentController extends Controller
{
    public function index(Request $request, School $school): JsonResponse
    {
        Gate::authorize('viewAny', [Assignment::class, $school]);

        $validated = $request->validate([
            'academic_class_id' => ['nullable', Rule::exists('academic_classes', 'id')->where('school_id', $school->id)],
            'subject_id' => ['nullable', Rule::exists('subjects', 'id')->where('school_id', $school->id)],
            'is_published' => ['nullable', 'boolean'],
            'status' => ['nullable', Rule::in(['active', 'archived'])],
            'due_from' => ['nullable', 'date'],
            'due_to' => ['nullable', 'date', 'after_or_equal:due_from'],
        ]);

        $assignments = $school->assignments()
            ->with(['academicClass:id,name,code', 'subject:id,name,code,type', 'assigner:id,name,email'])
            ->withCount('submissions')
            ->when($validated['academic_class_id'] ?? null, fn ($query, int $classId) => $query->where('academic_class_id', $classId))
            ->when($validated['subject_id'] ?? null, fn ($query, int $subjectId) => $query->where('subject_id', $subjectId))
            ->when(array_key_exists('is_published', $validated), fn ($query) => $query->where('is_published', $validated['is_published']))
            ->when($validated['status'] ?? null, fn ($query, string $status) => $query->where('status', $status))
            ->when($validated['due_from'] ?? null, fn ($query, string $date) => $query->whereDate('due_date', '>=', $date))
            ->when($validated['due_to'] ?? null, fn ($query, string $date) => $query->whereDate('due_date', '<=', $date))
            ->orderBy('due_date')
            ->orderBy('title')
            ->paginate($this->perPage($request));

        return response()->json($this->paginated($assignments));
    }

    public function store(Request $request, School $school): JsonResponse
    {
        Gate::authorize('create', [Assignment::class, $school]);

        $validated = $this->validatedPayload($request, $school);
        $assignment = $school->assignments()->create([
            ...$validated,
            'assigned_by' => $request->user()?->id,
            'is_published' => $validated['is_published'] ?? false,
            'status' => $validated['status'] ?? 'active',
        ]);

        $this->recordAudit($request, $school, 'assignment.created', $assignment, [
            'new' => $assignment->only($this->auditedFields()),
        ]);

        return response()->json(['data' => $assignment->load(['academicClass:id,name,code', 'subject:id,name,code,type', 'assigner:id,name,email'])], 201);
    }

    public function show(Request $request, School $school, Assignment $assignment): JsonResponse
    {
        Gate::authorize('view', [$assignment, $school]);

        return response()->json([
            'data' => $assignment->load([
                'academicClass:id,name,code',
                'subject:id,name,code,type',
                'assigner:id,name,email',
                'submissions.studentEnrollment.student:id,admission_no,full_name',
            ]),
        ]);
    }

    public function update(Request $request, School $school, Assignment $assignment): JsonResponse
    {
        Gate::authorize('update', [$assignment, $school]);

        $validated = $this->validatedPayload($request, $school, true);
        $oldValues = $assignment->only(array_keys($validated));

        $assignment->update($validated);

        $this->recordAudit($request, $school, 'assignment.updated', $assignment, [
            'old' => $oldValues,
            'new' => $assignment->fresh()->only(array_keys($validated)),
        ]);

        return response()->json(['data' => $assignment->fresh()->load(['academicClass:id,name,code', 'subject:id,name,code,type', 'assigner:id,name,email'])]);
    }

    public function destroy(Request $request, School $school, Assignment $assignment): JsonResponse
    {
        Gate::authorize('delete', [$assignment, $school]);

        $oldValues = $assignment->only($this->auditedFields());
        $assignment->delete();

        $this->recordAudit($request, $school, 'assignment.deleted', $assignment, [
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
            'academic_class_id' => [$required, Rule::exists('academic_classes', 'id')->where('school_id', $school->id)],
            'subject_id' => [$required, Rule::exists('subjects', 'id')->where('school_id', $school->id)],
            'title' => [$required, 'string', 'max:180'],
            'description' => ['nullable', 'string', 'max:5000'],
            'due_date' => [$required, 'date'],
            'attachment_path' => ['nullable', 'string', 'max:2048'],
            'is_published' => ['sometimes', 'boolean'],
            'status' => ['sometimes', Rule::in(['active', 'archived'])],
        ]);
    }

    /**
     * @return array<int, string>
     */
    private function auditedFields(): array
    {
        return [
            'academic_class_id',
            'subject_id',
            'assigned_by',
            'title',
            'description',
            'due_date',
            'attachment_path',
            'is_published',
            'status',
        ];
    }
}
