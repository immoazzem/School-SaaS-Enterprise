<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\School;
use App\Models\TeacherProfile;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class TeacherProfileController extends Controller
{
    public function index(Request $request, School $school): JsonResponse
    {
        Gate::authorize('viewAny', [TeacherProfile::class, $school]);

        $validated = $request->validate([
            'status' => ['nullable', Rule::in(['active', 'archived'])],
            'search' => ['nullable', 'string', 'max:120'],
        ]);

        $profiles = $school->teacherProfiles()
            ->with('employee:id,employee_no,full_name,email,phone')
            ->when($validated['status'] ?? null, fn ($query, string $status) => $query->where('status', $status))
            ->when($validated['search'] ?? null, function ($query, string $search): void {
                $query->where(function ($nestedQuery) use ($search): void {
                    $nestedQuery
                        ->where('teacher_no', 'like', "%{$search}%")
                        ->orWhere('specialization', 'like', "%{$search}%")
                        ->orWhereHas('employee', function ($employeeQuery) use ($search): void {
                            $employeeQuery
                                ->where('full_name', 'like', "%{$search}%")
                                ->orWhere('employee_no', 'like', "%{$search}%");
                        });
                });
            })
            ->orderBy('teacher_no')
            ->get();

        return response()->json(['data' => $profiles]);
    }

    public function store(Request $request, School $school): JsonResponse
    {
        Gate::authorize('create', [TeacherProfile::class, $school]);

        $profile = $school->teacherProfiles()->create($this->validatedPayload($request, $school));

        $this->recordAudit($request, $school, 'teacher_profile.created', $profile, [
            'new' => $profile->only($this->auditedFields()),
        ]);

        return response()->json(['data' => $profile->load('employee:id,employee_no,full_name,email,phone')], 201);
    }

    public function show(Request $request, School $school, TeacherProfile $teacherProfile): JsonResponse
    {
        Gate::authorize('view', [$teacherProfile, $school]);

        return response()->json(['data' => $teacherProfile->load('employee:id,employee_no,full_name,email,phone')]);
    }

    public function update(Request $request, School $school, TeacherProfile $teacherProfile): JsonResponse
    {
        Gate::authorize('update', [$teacherProfile, $school]);

        $validated = $this->validatedPayload($request, $school, $teacherProfile);
        $oldValues = $teacherProfile->only(array_keys($validated));

        $teacherProfile->update($validated);

        $this->recordAudit($request, $school, 'teacher_profile.updated', $teacherProfile, [
            'old' => $oldValues,
            'new' => $teacherProfile->fresh()->only(array_keys($validated)),
        ]);

        return response()->json(['data' => $teacherProfile->fresh()->load('employee:id,employee_no,full_name,email,phone')]);
    }

    public function destroy(Request $request, School $school, TeacherProfile $teacherProfile): JsonResponse
    {
        Gate::authorize('delete', [$teacherProfile, $school]);

        $oldValues = $teacherProfile->only($this->auditedFields());

        $teacherProfile->delete();

        $this->recordAudit($request, $school, 'teacher_profile.deleted', $teacherProfile, [
            'old' => $oldValues,
        ]);

        return response()->json(status: 204);
    }

    /**
     * @return array<string, mixed>
     */
    private function validatedPayload(Request $request, School $school, ?TeacherProfile $teacherProfile = null): array
    {
        return $request->validate([
            'employee_id' => [
                $teacherProfile ? 'sometimes' : 'required',
                Rule::exists('employees', 'id')->where('school_id', $school->id),
                Rule::unique('teacher_profiles')
                    ->where('school_id', $school->id)
                    ->ignore($teacherProfile?->id),
            ],
            'teacher_no' => [
                $teacherProfile ? 'sometimes' : 'required',
                'string',
                'max:40',
                Rule::unique('teacher_profiles')
                    ->where('school_id', $school->id)
                    ->ignore($teacherProfile?->id),
            ],
            'specialization' => ['nullable', 'string', 'max:160'],
            'qualification' => ['nullable', 'string', 'max:160'],
            'experience_years' => ['nullable', 'integer', 'min:0', 'max:80'],
            'joined_teaching_on' => ['nullable', 'date'],
            'bio' => ['nullable', 'string', 'max:2000'],
            'status' => ['nullable', Rule::in(['active', 'archived'])],
        ]);
    }

    /**
     * @return list<string>
     */
    private function auditedFields(): array
    {
        return [
            'employee_id',
            'teacher_no',
            'specialization',
            'qualification',
            'experience_years',
            'joined_teaching_on',
            'bio',
            'status',
        ];
    }

    /**
     * @param  array<string, mixed>  $metadata
     */
    private function recordAudit(Request $request, School $school, string $event, TeacherProfile $profile, array $metadata): void
    {
        AuditLog::query()->create([
            'school_id' => $school->id,
            'actor_id' => $request->user()->id,
            'event' => $event,
            'auditable_type' => $profile->getMorphClass(),
            'auditable_id' => $profile->id,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'metadata' => $metadata,
        ]);
    }
}
