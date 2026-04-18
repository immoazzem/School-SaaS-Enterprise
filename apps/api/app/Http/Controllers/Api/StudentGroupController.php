<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\School;
use App\Models\StudentGroup;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class StudentGroupController extends Controller
{
    public function index(Request $request, School $school): JsonResponse
    {
        Gate::authorize('viewAny', [StudentGroup::class, $school]);

        $validated = $request->validate([
            'status' => ['nullable', Rule::in(['active', 'archived'])],
            'search' => ['nullable', 'string', 'max:120'],
        ]);

        $groups = $school->studentGroups()
            ->when($validated['status'] ?? null, fn ($query, string $status) => $query->where('status', $status))
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

        return response()->json($this->paginated($groups));
    }

    public function store(Request $request, School $school): JsonResponse
    {
        Gate::authorize('create', [StudentGroup::class, $school]);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'code' => [
                'required',
                'string',
                'max:40',
                Rule::unique('student_groups')->where('school_id', $school->id),
            ],
            'description' => ['nullable', 'string', 'max:2000'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:65535'],
            'status' => ['nullable', Rule::in(['active', 'archived'])],
        ]);

        $group = $school->studentGroups()->create([
            ...$validated,
            'sort_order' => $validated['sort_order'] ?? 0,
            'status' => $validated['status'] ?? 'active',
        ]);

        $this->recordAudit($request, $school, 'student_group.created', $group, [
            'new' => $group->only(['name', 'code', 'description', 'sort_order', 'status']),
        ]);

        return response()->json(['data' => $group], 201);
    }

    public function show(Request $request, School $school, StudentGroup $studentGroup): JsonResponse
    {
        Gate::authorize('view', [$studentGroup, $school]);

        return response()->json(['data' => $studentGroup]);
    }

    public function update(Request $request, School $school, StudentGroup $studentGroup): JsonResponse
    {
        Gate::authorize('update', [$studentGroup, $school]);

        $validated = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:120'],
            'code' => [
                'sometimes',
                'required',
                'string',
                'max:40',
                Rule::unique('student_groups')
                    ->where('school_id', $school->id)
                    ->ignore($studentGroup->id),
            ],
            'description' => ['nullable', 'string', 'max:2000'],
            'sort_order' => ['sometimes', 'integer', 'min:0', 'max:65535'],
            'status' => ['sometimes', Rule::in(['active', 'archived'])],
        ]);

        $oldValues = $studentGroup->only(array_keys($validated));

        $studentGroup->update($validated);

        $this->recordAudit($request, $school, 'student_group.updated', $studentGroup, [
            'old' => $oldValues,
            'new' => $studentGroup->fresh()->only(array_keys($validated)),
        ]);

        return response()->json(['data' => $studentGroup->fresh()]);
    }

    public function destroy(Request $request, School $school, StudentGroup $studentGroup): JsonResponse
    {
        Gate::authorize('delete', [$studentGroup, $school]);

        $oldValues = $studentGroup->only(['name', 'code', 'description', 'sort_order', 'status']);

        $studentGroup->delete();

        $this->recordAudit($request, $school, 'student_group.deleted', $studentGroup, [
            'old' => $oldValues,
        ]);

        return response()->json(status: 204);
    }
}
