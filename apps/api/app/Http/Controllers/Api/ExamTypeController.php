<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ExamType;
use App\Models\School;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class ExamTypeController extends Controller
{
    public function index(Request $request, School $school): JsonResponse
    {
        Gate::authorize('viewAny', [ExamType::class, $school]);

        $validated = $request->validate([
            'status' => ['nullable', Rule::in(['active', 'archived'])],
            'search' => ['nullable', 'string', 'max:120'],
        ]);

        $types = $school->examTypes()
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

        return response()->json($this->paginated($types));
    }

    public function store(Request $request, School $school): JsonResponse
    {
        Gate::authorize('create', [ExamType::class, $school]);

        $validated = $this->validatePayload($request, $school);

        $examType = $school->examTypes()->create([
            ...$validated,
            'sort_order' => $validated['sort_order'] ?? 0,
            'status' => $validated['status'] ?? 'active',
        ]);

        $this->recordAudit($request, $school, 'exam_type.created', $examType, [
            'new' => $examType->only($this->auditedFields()),
        ]);

        return response()->json(['data' => $examType], 201);
    }

    public function show(Request $request, School $school, ExamType $examType): JsonResponse
    {
        Gate::authorize('view', [$examType, $school]);

        return response()->json(['data' => $examType]);
    }

    public function update(Request $request, School $school, ExamType $examType): JsonResponse
    {
        Gate::authorize('update', [$examType, $school]);

        $validated = $this->validatePayload($request, $school, $examType);
        $oldValues = $examType->only(array_keys($validated));

        $examType->update($validated);

        $this->recordAudit($request, $school, 'exam_type.updated', $examType, [
            'old' => $oldValues,
            'new' => $examType->fresh()->only(array_keys($validated)),
        ]);

        return response()->json(['data' => $examType->fresh()]);
    }

    public function destroy(Request $request, School $school, ExamType $examType): JsonResponse
    {
        Gate::authorize('delete', [$examType, $school]);

        $oldValues = $examType->only($this->auditedFields());

        $examType->delete();

        $this->recordAudit($request, $school, 'exam_type.deleted', $examType, [
            'old' => $oldValues,
        ]);

        return response()->json(status: 204);
    }

    /**
     * @return array<string, mixed>
     */
    private function validatePayload(Request $request, School $school, ?ExamType $examType = null): array
    {
        return $request->validate([
            'name' => [$examType ? 'sometimes' : 'required', 'string', 'max:120'],
            'code' => [
                $examType ? 'sometimes' : 'required',
                'string',
                'max:40',
                Rule::unique('exam_types')
                    ->where('school_id', $school->id)
                    ->ignore($examType?->id),
            ],
            'weightage_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'description' => ['nullable', 'string', 'max:2000'],
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
            'name',
            'code',
            'weightage_percent',
            'description',
            'sort_order',
            'status',
        ];
    }
}
