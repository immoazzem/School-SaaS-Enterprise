<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\School;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class AcademicYearController extends Controller
{
    public function index(Request $request, School $school): JsonResponse
    {
        Gate::authorize('viewAny', [AcademicYear::class, $school]);

        $validated = $request->validate([
            'status' => ['nullable', Rule::in(['active', 'archived'])],
            'is_current' => ['nullable', 'boolean'],
        ]);

        $years = $school->academicYears()
            ->when($validated['status'] ?? null, fn ($query, string $status) => $query->where('status', $status))
            ->when(
                array_key_exists('is_current', $validated),
                fn ($query) => $query->where('is_current', (bool) $validated['is_current'])
            )
            ->orderByDesc('starts_on')
            ->orderBy('name')
            ->paginate($this->perPage($request));

        return response()->json($this->paginated($years));
    }

    public function store(Request $request, School $school): JsonResponse
    {
        Gate::authorize('create', [AcademicYear::class, $school]);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'code' => [
                'required',
                'string',
                'max:40',
                Rule::unique('academic_years')->where('school_id', $school->id),
            ],
            'starts_on' => ['required', 'date'],
            'ends_on' => ['required', 'date', 'after_or_equal:starts_on'],
            'is_current' => ['nullable', 'boolean'],
            'status' => ['nullable', Rule::in(['active', 'archived'])],
        ]);

        $academicYear = $school->academicYears()->create([
            ...$validated,
            'is_current' => $validated['is_current'] ?? false,
            'status' => $validated['status'] ?? 'active',
        ]);

        $this->syncCurrentYear($school, $academicYear);

        $this->recordAudit($request, $school, 'academic_year.created', $academicYear, [
            'new' => $academicYear->fresh()->only([
                'name',
                'code',
                'starts_on',
                'ends_on',
                'is_current',
                'status',
            ]),
        ]);

        return response()->json(['data' => $academicYear->fresh()], 201);
    }

    public function show(Request $request, School $school, AcademicYear $academicYear): JsonResponse
    {
        Gate::authorize('view', [$academicYear, $school]);

        return response()->json(['data' => $academicYear]);
    }

    public function update(Request $request, School $school, AcademicYear $academicYear): JsonResponse
    {
        Gate::authorize('update', [$academicYear, $school]);

        $validated = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:120'],
            'code' => [
                'sometimes',
                'required',
                'string',
                'max:40',
                Rule::unique('academic_years')
                    ->where('school_id', $school->id)
                    ->ignore($academicYear->id),
            ],
            'starts_on' => ['sometimes', 'required', 'date'],
            'ends_on' => ['sometimes', 'required', 'date', 'after_or_equal:starts_on'],
            'is_current' => ['sometimes', 'boolean'],
            'status' => ['sometimes', Rule::in(['active', 'archived'])],
        ]);

        if (isset($validated['ends_on']) && ! isset($validated['starts_on'])) {
            $request->validate([
                'ends_on' => ['after_or_equal:'.$academicYear->starts_on->toDateString()],
            ]);
        }

        if (isset($validated['starts_on']) && ! isset($validated['ends_on'])) {
            $request->validate([
                'starts_on' => ['before_or_equal:'.$academicYear->ends_on->toDateString()],
            ]);
        }

        $oldValues = $academicYear->only(array_keys($validated));

        $academicYear->update($validated);
        $this->syncCurrentYear($school, $academicYear);

        $this->recordAudit($request, $school, 'academic_year.updated', $academicYear, [
            'old' => $oldValues,
            'new' => $academicYear->fresh()->only(array_keys($validated)),
        ]);

        return response()->json(['data' => $academicYear->fresh()]);
    }

    public function destroy(Request $request, School $school, AcademicYear $academicYear): JsonResponse
    {
        Gate::authorize('delete', [$academicYear, $school]);

        $oldValues = $academicYear->only(['name', 'code', 'starts_on', 'ends_on', 'is_current', 'status']);

        $academicYear->delete();

        $this->recordAudit($request, $school, 'academic_year.deleted', $academicYear, [
            'old' => $oldValues,
        ]);

        return response()->json(status: 204);
    }

    private function syncCurrentYear(School $school, AcademicYear $academicYear): void
    {
        if (! $academicYear->is_current) {
            return;
        }

        $school->academicYears()
            ->whereKeyNot($academicYear->id)
            ->where('is_current', true)
            ->update(['is_current' => false]);
    }
}
