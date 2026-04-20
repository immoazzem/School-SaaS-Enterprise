<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AcademicSection;
use App\Models\School;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class AcademicSectionController extends Controller
{
    public function index(Request $request, School $school): JsonResponse
    {
        Gate::authorize('viewAny', [AcademicSection::class, $school]);

        $validated = $request->validate([
            'academic_class_id' => [
                'nullable',
                'integer',
                Rule::exists('academic_classes', 'id')
                    ->where('school_id', $school->id)
                    ->whereNull('deleted_at'),
            ],
            'status' => ['nullable', Rule::in(['active', 'archived'])],
        ]);

        $sections = $school->academicSections()
            ->when(
                $validated['academic_class_id'] ?? null,
                fn ($query, int $academicClassId) => $query->where('academic_class_id', $academicClassId)
            )
            ->when($validated['status'] ?? null, fn ($query, string $status) => $query->where('status', $status))
            ->with('academicClass:id,name,code')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->paginate($this->perPage($request));

        return response()->json($this->paginated($sections));
    }

    public function store(Request $request, School $school): JsonResponse
    {
        Gate::authorize('create', [AcademicSection::class, $school]);

        $validated = $request->validate([
            'academic_class_id' => [
                'required',
                'integer',
                Rule::exists('academic_classes', 'id')
                    ->where('school_id', $school->id)
                    ->whereNull('deleted_at'),
            ],
            'name' => ['required', 'string', 'max:120'],
            'code' => [
                'required',
                'string',
                'max:40',
                Rule::unique('academic_sections')
                    ->where('school_id', $school->id)
                    ->where('academic_class_id', $request->integer('academic_class_id')),
            ],
            'capacity' => ['nullable', 'integer', 'min:1', 'max:65535'],
            'room' => ['nullable', 'string', 'max:120'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:65535'],
            'status' => ['nullable', Rule::in(['active', 'archived'])],
        ]);

        $section = $school->academicSections()->create([
            ...$validated,
            'sort_order' => $validated['sort_order'] ?? 0,
            'status' => $validated['status'] ?? 'active',
        ]);

        $this->recordAudit($request, $school, 'academic_section.created', $section, [
            'new' => $section->only([
                'academic_class_id',
                'name',
                'code',
                'capacity',
                'room',
                'sort_order',
                'status',
            ]),
        ]);

        return response()->json(['data' => $section->load('academicClass:id,name,code')], 201);
    }

    public function show(Request $request, School $school, AcademicSection $academicSection): JsonResponse
    {
        Gate::authorize('view', [$academicSection, $school]);

        return response()->json(['data' => $academicSection->load('academicClass:id,name,code')]);
    }

    public function update(Request $request, School $school, AcademicSection $academicSection): JsonResponse
    {
        Gate::authorize('update', [$academicSection, $school]);

        $validated = $request->validate([
            'academic_class_id' => [
                'sometimes',
                'required',
                'integer',
                Rule::exists('academic_classes', 'id')
                    ->where('school_id', $school->id)
                    ->whereNull('deleted_at'),
            ],
            'name' => ['sometimes', 'required', 'string', 'max:120'],
            'code' => [
                'sometimes',
                'required',
                'string',
                'max:40',
                Rule::unique('academic_sections')
                    ->where('school_id', $school->id)
                    ->where('academic_class_id', $request->integer('academic_class_id') ?: $academicSection->academic_class_id)
                    ->ignore($academicSection->id),
            ],
            'capacity' => ['nullable', 'integer', 'min:1', 'max:65535'],
            'room' => ['nullable', 'string', 'max:120'],
            'sort_order' => ['sometimes', 'integer', 'min:0', 'max:65535'],
            'status' => ['sometimes', Rule::in(['active', 'archived'])],
        ]);

        $oldValues = $academicSection->only(array_keys($validated));

        $academicSection->update($validated);

        $this->recordAudit($request, $school, 'academic_section.updated', $academicSection, [
            'old' => $oldValues,
            'new' => $academicSection->fresh()->only(array_keys($validated)),
        ]);

        return response()->json(['data' => $academicSection->fresh()->load('academicClass:id,name,code')]);
    }

    public function destroy(Request $request, School $school, AcademicSection $academicSection): JsonResponse
    {
        Gate::authorize('delete', [$academicSection, $school]);

        $oldValues = $academicSection->only([
            'academic_class_id',
            'name',
            'code',
            'capacity',
            'room',
            'sort_order',
            'status',
        ]);

        $academicSection->delete();

        $this->recordAudit($request, $school, 'academic_section.deleted', $academicSection, [
            'old' => $oldValues,
        ]);

        return response()->json(status: 204);
    }
}
