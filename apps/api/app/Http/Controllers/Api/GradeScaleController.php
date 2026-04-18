<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\GradeScale;
use App\Models\School;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class GradeScaleController extends Controller
{
    public function index(Request $request, School $school): JsonResponse
    {
        $this->authorizePermission($request, $school);

        $scales = $school->gradeScales()->orderByDesc('min_percent')->paginate($this->perPage($request));

        return response()->json($this->paginated($scales));
    }

    public function store(Request $request, School $school): JsonResponse
    {
        $this->authorizePermission($request, $school);
        $scale = $school->gradeScales()->create($this->validatedPayload($request, $school));
        $this->recordAudit($request, $school, 'grade_scale.created', $scale, ['new' => $scale->toArray()]);

        return response()->json(['data' => $scale], 201);
    }

    public function show(Request $request, School $school, GradeScale $gradeScale): JsonResponse
    {
        $this->authorizePermission($request, $school);
        abort_unless($gradeScale->school_id === $school->id, 404);

        return response()->json(['data' => $gradeScale]);
    }

    public function update(Request $request, School $school, GradeScale $gradeScale): JsonResponse
    {
        $this->authorizePermission($request, $school);
        abort_unless($gradeScale->school_id === $school->id, 404);
        $validated = $this->validatedPayload($request, $school, $gradeScale);
        $old = $gradeScale->only(array_keys($validated));
        $gradeScale->update($validated);
        $this->recordAudit($request, $school, 'grade_scale.updated', $gradeScale, ['old' => $old, 'new' => $gradeScale->fresh()->only(array_keys($validated))]);

        return response()->json(['data' => $gradeScale->fresh()]);
    }

    public function destroy(Request $request, School $school, GradeScale $gradeScale): JsonResponse
    {
        $this->authorizePermission($request, $school);
        abort_unless($gradeScale->school_id === $school->id, 404);
        $gradeScale->delete();
        $this->recordAudit($request, $school, 'grade_scale.deleted', $gradeScale, ['old' => $gradeScale->toArray()]);

        return response()->json(status: 204);
    }

    private function authorizePermission(Request $request, School $school): void
    {
        abort_unless($request->user()->hasSchoolPermission($school, 'grades.manage'), 403);
    }

    /**
     * @return array<string, mixed>
     */
    private function validatedPayload(Request $request, School $school, ?GradeScale $gradeScale = null): array
    {
        return $request->validate([
            'name' => [$gradeScale ? 'sometimes' : 'required', 'string', 'max:120'],
            'code' => [$gradeScale ? 'sometimes' : 'required', 'string', 'max:40', Rule::unique('grade_scales')->where('school_id', $school->id)->ignore($gradeScale?->id)],
            'min_percent' => [$gradeScale ? 'sometimes' : 'required', 'numeric', 'min:0', 'max:100'],
            'max_percent' => [$gradeScale ? 'sometimes' : 'required', 'numeric', 'min:0', 'max:100', 'gte:min_percent'],
            'grade_point' => [$gradeScale ? 'sometimes' : 'required', 'numeric', 'min:0', 'max:5'],
            'fail_below_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'gpa_calculation_method' => ['nullable', Rule::in(['weighted', 'simple_average'])],
            'status' => ['nullable', Rule::in(['active', 'archived'])],
        ]);
    }
}
