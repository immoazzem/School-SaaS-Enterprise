<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DiscountPolicy;
use App\Models\School;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DiscountPolicyController extends Controller
{
    public function index(Request $request, School $school): JsonResponse
    {
        $this->authorizeFinance($request, $school);

        return response()->json($this->paginated($school->discountPolicies()->orderBy('name')->paginate($this->perPage($request))));
    }

    public function store(Request $request, School $school): JsonResponse
    {
        $this->authorizeFinance($request, $school);
        $policy = $school->discountPolicies()->create($this->validatedPayload($request, $school));

        return response()->json(['data' => $policy], 201);
    }

    public function show(Request $request, School $school, DiscountPolicy $discountPolicy): JsonResponse
    {
        $this->authorizeFinance($request, $school);
        abort_unless($discountPolicy->school_id === $school->id, 404);

        return response()->json(['data' => $discountPolicy]);
    }

    public function update(Request $request, School $school, DiscountPolicy $discountPolicy): JsonResponse
    {
        $this->authorizeFinance($request, $school);
        abort_unless($discountPolicy->school_id === $school->id, 404);
        $discountPolicy->update($this->validatedPayload($request, $school, $discountPolicy));

        return response()->json(['data' => $discountPolicy->fresh()]);
    }

    public function destroy(Request $request, School $school, DiscountPolicy $discountPolicy): JsonResponse
    {
        $this->authorizeFinance($request, $school);
        abort_unless($discountPolicy->school_id === $school->id, 404);
        $discountPolicy->delete();

        return response()->json(status: 204);
    }

    private function authorizeFinance(Request $request, School $school): void
    {
        abort_unless($request->user()->hasSchoolPermission($school, 'finance.manage'), 403);
    }

    private function validatedPayload(Request $request, School $school, ?DiscountPolicy $discountPolicy = null): array
    {
        return $request->validate([
            'name' => [$discountPolicy ? 'sometimes' : 'required', 'string', 'max:120'],
            'code' => [$discountPolicy ? 'sometimes' : 'required', 'string', 'max:40', Rule::unique('discount_policies')->where('school_id', $school->id)->ignore($discountPolicy?->id)],
            'discount_type' => [$discountPolicy ? 'sometimes' : 'required', Rule::in(['flat', 'percent'])],
            'amount' => [$discountPolicy ? 'sometimes' : 'required', 'numeric', 'min:0'],
            'applies_to_category_ids' => ['nullable', 'array'],
            'applies_to_category_ids.*' => ['integer', Rule::exists('fee_categories', 'id')->where('school_id', $school->id)],
            'is_stackable' => ['nullable', 'boolean'],
            'status' => ['nullable', Rule::in(['active', 'archived'])],
        ]);
    }
}
