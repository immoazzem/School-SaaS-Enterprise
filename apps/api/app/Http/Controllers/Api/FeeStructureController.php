<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FeeStructure;
use App\Models\School;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class FeeStructureController extends Controller
{
    public function index(Request $request, School $school): JsonResponse
    {
        $this->authorizeFinance($request, $school);

        return response()->json($this->paginated($school->feeStructures()->with($this->relations())->orderByDesc('id')->paginate($this->perPage($request))));
    }

    public function store(Request $request, School $school): JsonResponse
    {
        $this->authorizeFinance($request, $school);
        $structure = $school->feeStructures()->create($this->validatedPayload($request, $school));

        return response()->json(['data' => $structure->load($this->relations())], 201);
    }

    public function show(Request $request, School $school, FeeStructure $feeStructure): JsonResponse
    {
        $this->authorizeFinance($request, $school);
        abort_unless($feeStructure->school_id === $school->id, 404);

        return response()->json(['data' => $feeStructure->load($this->relations())]);
    }

    public function update(Request $request, School $school, FeeStructure $feeStructure): JsonResponse
    {
        $this->authorizeFinance($request, $school);
        abort_unless($feeStructure->school_id === $school->id, 404);
        $feeStructure->update($this->validatedPayload($request, $school, $feeStructure));

        return response()->json(['data' => $feeStructure->fresh()->load($this->relations())]);
    }

    public function destroy(Request $request, School $school, FeeStructure $feeStructure): JsonResponse
    {
        $this->authorizeFinance($request, $school);
        abort_unless($feeStructure->school_id === $school->id, 404);
        $feeStructure->delete();

        return response()->json(status: 204);
    }

    private function authorizeFinance(Request $request, School $school): void
    {
        abort_unless($request->user()->hasSchoolPermission($school, 'finance.manage'), 403);
    }

    private function validatedPayload(Request $request, School $school, ?FeeStructure $feeStructure = null): array
    {
        return $request->validate([
            'fee_category_id' => [$feeStructure ? 'sometimes' : 'required', 'integer', Rule::exists('fee_categories', 'id')->where('school_id', $school->id)],
            'academic_year_id' => [$feeStructure ? 'sometimes' : 'required', 'integer', Rule::exists('academic_years', 'id')->where('school_id', $school->id)],
            'academic_class_id' => ['nullable', 'integer', Rule::exists('academic_classes', 'id')->where('school_id', $school->id)],
            'student_group_id' => ['nullable', 'integer', Rule::exists('student_groups', 'id')->where('school_id', $school->id)],
            'amount' => [$feeStructure ? 'sometimes' : 'required', 'numeric', 'min:0'],
            'due_day_of_month' => ['nullable', 'integer', 'min:1', 'max:31'],
            'months_applicable' => ['nullable', 'array'],
            'months_applicable.*' => ['string', 'date_format:Y-m'],
            'is_recurring' => ['nullable', 'boolean'],
            'status' => ['nullable', Rule::in(['active', 'archived'])],
        ]);
    }

    private function relations(): array
    {
        return ['feeCategory:id,name,code,billing_type', 'academicYear:id,name', 'academicClass:id,name,code', 'studentGroup:id,name,code'];
    }
}
