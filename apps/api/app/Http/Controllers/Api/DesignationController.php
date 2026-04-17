<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Designation;
use App\Models\School;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class DesignationController extends Controller
{
    public function index(Request $request, School $school): JsonResponse
    {
        Gate::authorize('viewAny', [Designation::class, $school]);

        $validated = $request->validate([
            'status' => ['nullable', Rule::in(['active', 'archived'])],
            'search' => ['nullable', 'string', 'max:120'],
        ]);

        $designations = $school->designations()
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
            ->get();

        return response()->json(['data' => $designations]);
    }

    public function store(Request $request, School $school): JsonResponse
    {
        Gate::authorize('create', [Designation::class, $school]);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'code' => [
                'required',
                'string',
                'max:40',
                Rule::unique('designations')->where('school_id', $school->id),
            ],
            'description' => ['nullable', 'string', 'max:2000'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:65535'],
            'status' => ['nullable', Rule::in(['active', 'archived'])],
        ]);

        $designation = $school->designations()->create([
            ...$validated,
            'sort_order' => $validated['sort_order'] ?? 0,
            'status' => $validated['status'] ?? 'active',
        ]);

        $this->recordAudit($request, $school, 'designation.created', $designation, [
            'new' => $designation->only($this->auditedFields()),
        ]);

        return response()->json(['data' => $designation], 201);
    }

    public function show(Request $request, School $school, Designation $designation): JsonResponse
    {
        Gate::authorize('view', [$designation, $school]);

        return response()->json(['data' => $designation]);
    }

    public function update(Request $request, School $school, Designation $designation): JsonResponse
    {
        Gate::authorize('update', [$designation, $school]);

        $validated = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:120'],
            'code' => [
                'sometimes',
                'required',
                'string',
                'max:40',
                Rule::unique('designations')
                    ->where('school_id', $school->id)
                    ->ignore($designation->id),
            ],
            'description' => ['nullable', 'string', 'max:2000'],
            'sort_order' => ['sometimes', 'integer', 'min:0', 'max:65535'],
            'status' => ['sometimes', Rule::in(['active', 'archived'])],
        ]);

        $oldValues = $designation->only(array_keys($validated));

        $designation->update($validated);

        $this->recordAudit($request, $school, 'designation.updated', $designation, [
            'old' => $oldValues,
            'new' => $designation->fresh()->only(array_keys($validated)),
        ]);

        return response()->json(['data' => $designation->fresh()]);
    }

    public function destroy(Request $request, School $school, Designation $designation): JsonResponse
    {
        Gate::authorize('delete', [$designation, $school]);

        $oldValues = $designation->only($this->auditedFields());

        $designation->delete();

        $this->recordAudit($request, $school, 'designation.deleted', $designation, [
            'old' => $oldValues,
        ]);

        return response()->json(status: 204);
    }

    /**
     * @return list<string>
     */
    private function auditedFields(): array
    {
        return ['name', 'code', 'description', 'sort_order', 'status'];
    }

    /**
     * @param  array<string, mixed>  $metadata
     */
    private function recordAudit(Request $request, School $school, string $event, Designation $designation, array $metadata): void
    {
        AuditLog::query()->create([
            'school_id' => $school->id,
            'actor_id' => $request->user()->id,
            'event' => $event,
            'auditable_type' => $designation->getMorphClass(),
            'auditable_id' => $designation->id,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'metadata' => $metadata,
        ]);
    }
}
