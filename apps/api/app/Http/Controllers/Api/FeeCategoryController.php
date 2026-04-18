<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FeeCategory;
use App\Models\School;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class FeeCategoryController extends Controller
{
    public function index(Request $request, School $school): JsonResponse
    {
        $this->authorizeFinance($request, $school);

        return response()->json($this->paginated($school->feeCategories()->orderBy('sort_order')->orderBy('name')->paginate($this->perPage($request))));
    }

    public function store(Request $request, School $school): JsonResponse
    {
        $this->authorizeFinance($request, $school);
        $category = $school->feeCategories()->create($this->validatedPayload($request, $school));
        $this->recordAudit($request, $school, 'fee_category.created', $category, ['new' => $category->toArray()]);

        return response()->json(['data' => $category], 201);
    }

    public function show(Request $request, School $school, FeeCategory $feeCategory): JsonResponse
    {
        $this->authorizeFinance($request, $school);
        abort_unless($feeCategory->school_id === $school->id, 404);

        return response()->json(['data' => $feeCategory]);
    }

    public function update(Request $request, School $school, FeeCategory $feeCategory): JsonResponse
    {
        $this->authorizeFinance($request, $school);
        abort_unless($feeCategory->school_id === $school->id, 404);
        $validated = $this->validatedPayload($request, $school, $feeCategory);
        $feeCategory->update($validated);

        return response()->json(['data' => $feeCategory->fresh()]);
    }

    public function destroy(Request $request, School $school, FeeCategory $feeCategory): JsonResponse
    {
        $this->authorizeFinance($request, $school);
        abort_unless($feeCategory->school_id === $school->id, 404);
        $feeCategory->delete();

        return response()->json(status: 204);
    }

    private function authorizeFinance(Request $request, School $school): void
    {
        abort_unless($request->user()->hasSchoolPermission($school, 'finance.manage'), 403);
    }

    private function validatedPayload(Request $request, School $school, ?FeeCategory $feeCategory = null): array
    {
        return $request->validate([
            'name' => [$feeCategory ? 'sometimes' : 'required', 'string', 'max:120'],
            'code' => [$feeCategory ? 'sometimes' : 'required', 'string', 'max:40', Rule::unique('fee_categories')->where('school_id', $school->id)->ignore($feeCategory?->id)],
            'description' => ['nullable', 'string', 'max:2000'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:65535'],
            'billing_type' => ['nullable', Rule::in(['monthly', 'one_time', 'per_exam', 'optional'])],
            'status' => ['nullable', Rule::in(['active', 'archived'])],
        ]);
    }
}
