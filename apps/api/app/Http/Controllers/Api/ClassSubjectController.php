<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\ClassSubject;
use App\Models\School;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class ClassSubjectController extends Controller
{
    public function index(Request $request, School $school): JsonResponse
    {
        Gate::authorize('viewAny', [ClassSubject::class, $school]);

        $validated = $request->validate([
            'academic_class_id' => ['nullable', 'integer', Rule::exists('academic_classes', 'id')->where('school_id', $school->id)],
            'subject_id' => ['nullable', 'integer', Rule::exists('subjects', 'id')->where('school_id', $school->id)],
            'status' => ['nullable', Rule::in(['active', 'archived'])],
            'search' => ['nullable', 'string', 'max:120'],
        ]);

        $assignments = $school->classSubjects()
            ->with(['academicClass:id,name,code', 'subject:id,name,code,type'])
            ->when($validated['academic_class_id'] ?? null, fn ($query, int $classId) => $query->where('academic_class_id', $classId))
            ->when($validated['subject_id'] ?? null, fn ($query, int $subjectId) => $query->where('subject_id', $subjectId))
            ->when($validated['status'] ?? null, fn ($query, string $status) => $query->where('status', $status))
            ->when($validated['search'] ?? null, function ($query, string $search): void {
                $query->where(function ($nestedQuery) use ($search): void {
                    $nestedQuery
                        ->whereHas('academicClass', fn ($classQuery) => $classQuery
                            ->where('name', 'like', "%{$search}%")
                            ->orWhere('code', 'like', "%{$search}%"))
                        ->orWhereHas('subject', fn ($subjectQuery) => $subjectQuery
                            ->where('name', 'like', "%{$search}%")
                            ->orWhere('code', 'like', "%{$search}%"));
                });
            })
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        return response()->json(['data' => $assignments]);
    }

    public function store(Request $request, School $school): JsonResponse
    {
        Gate::authorize('create', [ClassSubject::class, $school]);

        $validated = $this->validatePayload($request, $school);

        $assignment = $school->classSubjects()->create([
            ...$validated,
            'full_marks' => $validated['full_marks'] ?? 100,
            'pass_marks' => $validated['pass_marks'] ?? 33,
            'sort_order' => $validated['sort_order'] ?? 0,
            'status' => $validated['status'] ?? 'active',
        ]);

        $this->recordAudit($request, $school, 'class_subject.created', $assignment, [
            'new' => $assignment->only($this->auditedFields()),
        ]);

        return response()->json(['data' => $assignment->load(['academicClass:id,name,code', 'subject:id,name,code,type'])], 201);
    }

    public function show(Request $request, School $school, ClassSubject $classSubject): JsonResponse
    {
        Gate::authorize('view', [$classSubject, $school]);

        return response()->json(['data' => $classSubject->load(['academicClass:id,name,code', 'subject:id,name,code,type'])]);
    }

    public function update(Request $request, School $school, ClassSubject $classSubject): JsonResponse
    {
        Gate::authorize('update', [$classSubject, $school]);

        $validated = $this->validatePayload($request, $school, $classSubject);
        $oldValues = $classSubject->only(array_keys($validated));

        $classSubject->update($validated);

        $this->recordAudit($request, $school, 'class_subject.updated', $classSubject, [
            'old' => $oldValues,
            'new' => $classSubject->fresh()->only(array_keys($validated)),
        ]);

        return response()->json(['data' => $classSubject->fresh()->load(['academicClass:id,name,code', 'subject:id,name,code,type'])]);
    }

    public function destroy(Request $request, School $school, ClassSubject $classSubject): JsonResponse
    {
        Gate::authorize('delete', [$classSubject, $school]);

        $oldValues = $classSubject->only($this->auditedFields());

        $classSubject->delete();

        $this->recordAudit($request, $school, 'class_subject.deleted', $classSubject, [
            'old' => $oldValues,
        ]);

        return response()->json(status: 204);
    }

    /**
     * @return array<string, mixed>
     */
    private function validatePayload(Request $request, School $school, ?ClassSubject $classSubject = null): array
    {
        return $request->validate([
            'academic_class_id' => [
                $classSubject ? 'sometimes' : 'required',
                'integer',
                Rule::exists('academic_classes', 'id')->where('school_id', $school->id),
                Rule::unique('class_subjects')
                    ->where('school_id', $school->id)
                    ->where('subject_id', $request->integer('subject_id', $classSubject?->subject_id ?? 0))
                    ->ignore($classSubject?->id),
            ],
            'subject_id' => [
                $classSubject ? 'sometimes' : 'required',
                'integer',
                Rule::exists('subjects', 'id')->where('school_id', $school->id),
                Rule::unique('class_subjects')
                    ->where('school_id', $school->id)
                    ->where('academic_class_id', $request->integer('academic_class_id', $classSubject?->academic_class_id ?? 0))
                    ->ignore($classSubject?->id),
            ],
            'full_marks' => ['nullable', 'integer', 'min:1', 'max:65535'],
            'pass_marks' => ['nullable', 'integer', 'min:1', 'lte:full_marks', 'max:65535'],
            'subjective_marks' => ['nullable', 'integer', 'min:0', 'lte:full_marks', 'max:65535'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:65535'],
            'status' => ['nullable', Rule::in(['active', 'archived'])],
        ]);
    }

    /**
     * @return list<string>
     */
    private function auditedFields(): array
    {
        return [
            'academic_class_id',
            'subject_id',
            'full_marks',
            'pass_marks',
            'subjective_marks',
            'sort_order',
            'status',
        ];
    }

    /**
     * @param  array<string, mixed>  $metadata
     */
    private function recordAudit(Request $request, School $school, string $event, ClassSubject $assignment, array $metadata): void
    {
        AuditLog::query()->create([
            'school_id' => $school->id,
            'actor_id' => $request->user()->id,
            'event' => $event,
            'auditable_type' => $assignment->getMorphClass(),
            'auditable_id' => $assignment->id,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'metadata' => $metadata,
        ]);
    }
}
