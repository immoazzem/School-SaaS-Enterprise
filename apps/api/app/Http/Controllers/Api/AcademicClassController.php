<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AcademicClass;
use App\Models\AuditLog;
use App\Models\School;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AcademicClassController extends Controller
{
    public function index(Request $request, School $school): JsonResponse
    {
        $this->authorizeSchoolAccess($request, $school);

        $classes = $school->academicClasses()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return response()->json(['data' => $classes]);
    }

    public function store(Request $request, School $school): JsonResponse
    {
        $this->authorizeSchoolAccess($request, $school);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'code' => [
                'required',
                'string',
                'max:40',
                Rule::unique('academic_classes')->where('school_id', $school->id),
            ],
            'description' => ['nullable', 'string'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:65535'],
            'status' => ['nullable', Rule::in(['active', 'archived'])],
        ]);

        $academicClass = $school->academicClasses()->create([
            ...$validated,
            'sort_order' => $validated['sort_order'] ?? 0,
            'status' => $validated['status'] ?? 'active',
        ]);

        $this->recordAudit($request, $school, 'academic_class.created', $academicClass, [
            'new' => $academicClass->only(['name', 'code', 'description', 'sort_order', 'status']),
        ]);

        return response()->json(['data' => $academicClass], 201);
    }

    public function show(Request $request, School $school, AcademicClass $academicClass): JsonResponse
    {
        $this->authorizeSchoolAccess($request, $school);
        $this->ensureClassBelongsToSchool($school, $academicClass);

        return response()->json(['data' => $academicClass]);
    }

    public function update(Request $request, School $school, AcademicClass $academicClass): JsonResponse
    {
        $this->authorizeSchoolAccess($request, $school);
        $this->ensureClassBelongsToSchool($school, $academicClass);

        $validated = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:120'],
            'code' => [
                'sometimes',
                'required',
                'string',
                'max:40',
                Rule::unique('academic_classes')
                    ->where('school_id', $school->id)
                    ->ignore($academicClass->id),
            ],
            'description' => ['nullable', 'string'],
            'sort_order' => ['sometimes', 'integer', 'min:0', 'max:65535'],
            'status' => ['sometimes', Rule::in(['active', 'archived'])],
        ]);

        $oldValues = $academicClass->only(array_keys($validated));

        $academicClass->update($validated);

        $this->recordAudit($request, $school, 'academic_class.updated', $academicClass, [
            'old' => $oldValues,
            'new' => $academicClass->fresh()->only(array_keys($validated)),
        ]);

        return response()->json(['data' => $academicClass->fresh()]);
    }

    public function destroy(Request $request, School $school, AcademicClass $academicClass): JsonResponse
    {
        $this->authorizeSchoolAccess($request, $school);
        $this->ensureClassBelongsToSchool($school, $academicClass);

        $oldValues = $academicClass->only(['name', 'code', 'description', 'sort_order', 'status']);

        $academicClass->delete();

        $this->recordAudit($request, $school, 'academic_class.deleted', $academicClass, [
            'old' => $oldValues,
        ]);

        return response()->json(status: 204);
    }

    private function authorizeSchoolAccess(Request $request, School $school): void
    {
        abort_unless(
            $request->user()->schoolMemberships()
                ->where('school_id', $school->id)
                ->where('status', 'active')
                ->exists(),
            403
        );
    }

    private function ensureClassBelongsToSchool(School $school, AcademicClass $academicClass): void
    {
        abort_unless($academicClass->school_id === $school->id, 404);
    }

    /**
     * @param  array<string, mixed>  $metadata
     */
    private function recordAudit(
        Request $request,
        School $school,
        string $event,
        AcademicClass $academicClass,
        array $metadata
    ): void {
        AuditLog::query()->create([
            'school_id' => $school->id,
            'actor_id' => $request->user()->id,
            'event' => $event,
            'auditable_type' => $academicClass->getMorphClass(),
            'auditable_id' => $academicClass->id,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'metadata' => $metadata,
        ]);
    }
}
