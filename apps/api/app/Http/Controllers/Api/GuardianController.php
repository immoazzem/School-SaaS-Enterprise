<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Guardian;
use App\Models\School;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class GuardianController extends Controller
{
    public function index(Request $request, School $school): JsonResponse
    {
        Gate::authorize('viewAny', [Guardian::class, $school]);

        $validated = $request->validate([
            'status' => ['nullable', Rule::in(['active', 'archived'])],
            'search' => ['nullable', 'string', 'max:120'],
        ]);

        $guardians = $school->guardians()
            ->withCount('students')
            ->when($validated['status'] ?? null, fn ($query, string $status) => $query->where('status', $status))
            ->when($validated['search'] ?? null, function ($query, string $search): void {
                $query->where(function ($nestedQuery) use ($search): void {
                    $nestedQuery
                        ->where('full_name', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->orderBy('full_name')
            ->get();

        return response()->json(['data' => $guardians]);
    }

    public function store(Request $request, School $school): JsonResponse
    {
        Gate::authorize('create', [Guardian::class, $school]);

        $guardian = $school->guardians()->create($this->validatedPayload($request));

        $this->recordAudit($request, $school, 'guardian.created', $guardian, [
            'new' => $guardian->only($this->auditedFields()),
        ]);

        return response()->json(['data' => $guardian->loadCount('students')], 201);
    }

    public function show(Request $request, School $school, Guardian $guardian): JsonResponse
    {
        Gate::authorize('view', [$guardian, $school]);

        return response()->json(['data' => $guardian->loadCount('students')]);
    }

    public function update(Request $request, School $school, Guardian $guardian): JsonResponse
    {
        Gate::authorize('update', [$guardian, $school]);

        $validated = $this->validatedPayload($request, true);
        $oldValues = $guardian->only(array_keys($validated));

        $guardian->update($validated);

        $this->recordAudit($request, $school, 'guardian.updated', $guardian, [
            'old' => $oldValues,
            'new' => $guardian->fresh()->only(array_keys($validated)),
        ]);

        return response()->json(['data' => $guardian->fresh()->loadCount('students')]);
    }

    public function destroy(Request $request, School $school, Guardian $guardian): JsonResponse
    {
        Gate::authorize('delete', [$guardian, $school]);

        $oldValues = $guardian->only($this->auditedFields());

        $guardian->delete();

        $this->recordAudit($request, $school, 'guardian.deleted', $guardian, [
            'old' => $oldValues,
        ]);

        return response()->json(status: 204);
    }

    /**
     * @return array<string, mixed>
     */
    private function validatedPayload(Request $request, bool $partial = false): array
    {
        return $request->validate([
            'full_name' => [$partial ? 'sometimes' : 'required', 'string', 'max:160'],
            'relationship' => ['nullable', 'string', 'max:80'],
            'phone' => ['nullable', 'string', 'max:40'],
            'email' => ['nullable', 'email', 'max:160'],
            'occupation' => ['nullable', 'string', 'max:120'],
            'address' => ['nullable', 'string', 'max:2000'],
            'status' => ['nullable', Rule::in(['active', 'archived'])],
        ]);
    }

    /**
     * @return list<string>
     */
    private function auditedFields(): array
    {
        return ['full_name', 'relationship', 'phone', 'email', 'occupation', 'address', 'status'];
    }

    /**
     * @param  array<string, mixed>  $metadata
     */
    private function recordAudit(Request $request, School $school, string $event, Guardian $guardian, array $metadata): void
    {
        AuditLog::query()->create([
            'school_id' => $school->id,
            'actor_id' => $request->user()->id,
            'event' => $event,
            'auditable_type' => $guardian->getMorphClass(),
            'auditable_id' => $guardian->id,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'metadata' => $metadata,
        ]);
    }
}
