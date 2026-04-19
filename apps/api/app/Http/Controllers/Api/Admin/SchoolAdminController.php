<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Role;
use App\Models\School;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class SchoolAdminController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'status' => ['nullable', 'string', 'max:40'],
            'subscription_status' => ['nullable', 'string', 'max:40'],
            'search' => ['nullable', 'string', 'max:120'],
        ]);

        $schools = School::query()
            ->withCount(['memberships', 'students', 'employees'])
            ->when($validated['status'] ?? null, fn ($query, string $status) => $query->where('status', $status))
            ->when($validated['subscription_status'] ?? null, fn ($query, string $status) => $query->where('subscription_status', $status))
            ->when($validated['search'] ?? null, function ($query, string $search): void {
                $query->where(fn ($nested) => $nested
                    ->where('name', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%"));
            })
            ->orderBy('name')
            ->paginate($this->perPage($request));

        return response()->json($this->paginated($schools));
    }

    public function show(School $school): JsonResponse
    {
        return response()->json([
            'data' => $school->loadCount(['memberships', 'students', 'employees', 'documents']),
        ]);
    }

    public function update(Request $request, School $school): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:160'],
            'status' => ['sometimes', Rule::in(['active', 'archived', 'suspended'])],
            'plan' => ['sometimes', 'required', 'string', 'max:40'],
            'subscription_status' => ['sometimes', Rule::in(['trialing', 'active', 'past_due', 'cancelled', 'suspended'])],
            'trial_ends_at' => ['sometimes', 'nullable', 'date'],
            'plan_limits' => ['sometimes', 'nullable', 'array'],
            'plan_limits.max_students' => ['nullable', 'integer', 'min:1'],
            'plan_limits.max_employees' => ['nullable', 'integer', 'min:1'],
            'plan_limits.max_storage_mb' => ['nullable', 'integer', 'min:1'],
            'plan_limits.reports_enabled' => ['nullable', 'boolean'],
            'plan_limits.api_access' => ['nullable', 'boolean'],
        ]);

        $oldValues = $school->only(array_keys($validated));
        $school->update($validated);

        AuditLog::query()->create([
            'school_id' => $school->id,
            'actor_id' => $request->user()?->id,
            'event' => 'admin.school.updated',
            'auditable_type' => $school->getMorphClass(),
            'auditable_id' => $school->id,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'metadata' => [
                'old' => $oldValues,
                'new' => $school->fresh()->only(array_keys($validated)),
            ],
        ]);

        return response()->json(['data' => $school->fresh()]);
    }

    public function destroy(Request $request, School $school): JsonResponse
    {
        $school->delete();

        AuditLog::query()->create([
            'school_id' => $school->id,
            'actor_id' => $request->user()?->id,
            'event' => 'admin.school.deleted',
            'auditable_type' => $school->getMorphClass(),
            'auditable_id' => $school->id,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'metadata' => ['old' => ['status' => $school->status]],
        ]);

        return response()->json(status: 204);
    }

    public function onboard(Request $request, School $school): JsonResponse
    {
        $school = DB::transaction(function () use ($request, $school): School {
            $school->update([
                'subscription_status' => 'trialing',
                'trial_ends_at' => now()->addDays(30),
                'plan_limits' => array_merge($this->defaultPlanLimits(), $school->plan_limits ?? []),
            ]);

            Role::query()
                ->whereNull('school_id')
                ->where('key', '!=', 'super-admin')
                ->with('permissions:id')
                ->get()
                ->each(function (Role $systemRole) use ($school): void {
                    $role = Role::query()->firstOrCreate(
                        ['school_id' => $school->id, 'key' => $systemRole->key],
                        [
                            'name' => $systemRole->name,
                            'description' => $systemRole->description,
                            'is_system' => true,
                        ]
                    );

                    $role->permissions()->sync($systemRole->permissions->pluck('id')->all());
                });

            AuditLog::query()->create([
                'school_id' => $school->id,
                'actor_id' => $request->user()?->id,
                'event' => 'school.onboarded',
                'auditable_type' => $school->getMorphClass(),
                'auditable_id' => $school->id,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'metadata' => [
                    'trial_ends_at' => $school->trial_ends_at?->toISOString(),
                    'welcome_email_queued' => false,
                ],
            ]);

            return $school->fresh();
        });

        return response()->json(['data' => $school]);
    }

    /**
     * @return array<string, mixed>
     */
    private function defaultPlanLimits(): array
    {
        return [
            'max_students' => 200,
            'max_employees' => 30,
            'max_storage_mb' => 512,
            'reports_enabled' => true,
            'api_access' => false,
        ];
    }
}
