<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\School;
use App\Models\Subject;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class SubjectController extends Controller
{
    public function index(Request $request, School $school): JsonResponse
    {
        Gate::authorize('viewAny', [Subject::class, $school]);

        $validated = $request->validate([
            'status' => ['nullable', Rule::in(['active', 'archived'])],
            'type' => ['nullable', Rule::in(['core', 'elective', 'co_curricular'])],
            'search' => ['nullable', 'string', 'max:120'],
        ]);

        $subjects = $school->subjects()
            ->when($validated['status'] ?? null, fn ($query, string $status) => $query->where('status', $status))
            ->when($validated['type'] ?? null, fn ($query, string $type) => $query->where('type', $type))
            ->when($validated['search'] ?? null, function ($query, string $search): void {
                $query->where(function ($nestedQuery) use ($search): void {
                    $nestedQuery
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('code', 'like', "%{$search}%");
                });
            })
            ->orderBy('sort_order')
            ->orderBy('name')
            ->paginate($this->perPage($request));

        return response()->json($this->paginated($subjects));
    }

    public function store(Request $request, School $school): JsonResponse
    {
        Gate::authorize('create', [Subject::class, $school]);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'code' => [
                'required',
                'string',
                'max:40',
                Rule::unique('subjects')->where('school_id', $school->id),
            ],
            'type' => ['nullable', Rule::in(['core', 'elective', 'co_curricular'])],
            'description' => ['nullable', 'string', 'max:2000'],
            'credit_hours' => ['nullable', 'integer', 'min:1', 'max:65535'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:65535'],
            'status' => ['nullable', Rule::in(['active', 'archived'])],
        ]);

        $subject = $school->subjects()->create([
            ...$validated,
            'type' => $validated['type'] ?? 'core',
            'sort_order' => $validated['sort_order'] ?? 0,
            'status' => $validated['status'] ?? 'active',
        ]);

        $this->recordAudit($request, $school, 'subject.created', $subject, [
            'new' => $subject->only([
                'name',
                'code',
                'type',
                'description',
                'credit_hours',
                'sort_order',
                'status',
            ]),
        ]);

        return response()->json(['data' => $subject], 201);
    }

    public function show(Request $request, School $school, Subject $subject): JsonResponse
    {
        Gate::authorize('view', [$subject, $school]);

        return response()->json(['data' => $subject]);
    }

    public function update(Request $request, School $school, Subject $subject): JsonResponse
    {
        Gate::authorize('update', [$subject, $school]);

        $validated = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:120'],
            'code' => [
                'sometimes',
                'required',
                'string',
                'max:40',
                Rule::unique('subjects')
                    ->where('school_id', $school->id)
                    ->ignore($subject->id),
            ],
            'type' => ['sometimes', Rule::in(['core', 'elective', 'co_curricular'])],
            'description' => ['nullable', 'string', 'max:2000'],
            'credit_hours' => ['nullable', 'integer', 'min:1', 'max:65535'],
            'sort_order' => ['sometimes', 'integer', 'min:0', 'max:65535'],
            'status' => ['sometimes', Rule::in(['active', 'archived'])],
        ]);

        $oldValues = $subject->only(array_keys($validated));

        $subject->update($validated);

        $this->recordAudit($request, $school, 'subject.updated', $subject, [
            'old' => $oldValues,
            'new' => $subject->fresh()->only(array_keys($validated)),
        ]);

        return response()->json(['data' => $subject->fresh()]);
    }

    public function destroy(Request $request, School $school, Subject $subject): JsonResponse
    {
        Gate::authorize('delete', [$subject, $school]);

        $oldValues = $subject->only([
            'name',
            'code',
            'type',
            'description',
            'credit_hours',
            'sort_order',
            'status',
        ]);

        $subject->delete();

        $this->recordAudit($request, $school, 'subject.deleted', $subject, [
            'old' => $oldValues,
        ]);

        return response()->json(status: 204);
    }
}
