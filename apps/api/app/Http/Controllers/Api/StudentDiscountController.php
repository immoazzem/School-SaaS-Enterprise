<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\School;
use App\Models\StudentDiscount;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class StudentDiscountController extends Controller
{
    public function index(Request $request, School $school): JsonResponse
    {
        $this->authorizeFinance($request, $school);

        return response()->json($this->paginated($school->studentDiscounts()->with($this->relations())->orderByDesc('id')->paginate($this->perPage($request))));
    }

    public function store(Request $request, School $school): JsonResponse
    {
        $this->authorizeFinance($request, $school);
        $discount = $school->studentDiscounts()->create([...$this->validatedPayload($request, $school), 'approved_by' => $request->user()->id]);

        return response()->json(['data' => $discount->load($this->relations())], 201);
    }

    public function show(Request $request, School $school, StudentDiscount $studentDiscount): JsonResponse
    {
        $this->authorizeFinance($request, $school);
        abort_unless($studentDiscount->school_id === $school->id, 404);

        return response()->json(['data' => $studentDiscount->load($this->relations())]);
    }

    public function update(Request $request, School $school, StudentDiscount $studentDiscount): JsonResponse
    {
        $this->authorizeFinance($request, $school);
        abort_unless($studentDiscount->school_id === $school->id, 404);
        $studentDiscount->update($this->validatedPayload($request, $school, $studentDiscount));

        return response()->json(['data' => $studentDiscount->fresh()->load($this->relations())]);
    }

    public function destroy(Request $request, School $school, StudentDiscount $studentDiscount): JsonResponse
    {
        $this->authorizeFinance($request, $school);
        abort_unless($studentDiscount->school_id === $school->id, 404);
        $studentDiscount->delete();

        return response()->json(status: 204);
    }

    private function authorizeFinance(Request $request, School $school): void
    {
        abort_unless($request->user()->hasSchoolPermission($school, 'finance.manage'), 403);
    }

    private function validatedPayload(Request $request, School $school, ?StudentDiscount $studentDiscount = null): array
    {
        return $request->validate([
            'student_enrollment_id' => [$studentDiscount ? 'sometimes' : 'required', 'integer', Rule::exists('student_enrollments', 'id')->where('school_id', $school->id)],
            'discount_policy_id' => [$studentDiscount ? 'sometimes' : 'required', 'integer', Rule::exists('discount_policies', 'id')->where('school_id', $school->id)],
            'academic_year_id' => [$studentDiscount ? 'sometimes' : 'required', 'integer', Rule::exists('academic_years', 'id')->where('school_id', $school->id)],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);
    }

    private function relations(): array
    {
        return ['studentEnrollment:id,student_id,academic_class_id,roll_no', 'studentEnrollment.student:id,full_name,admission_no', 'discountPolicy:id,name,code,discount_type,amount', 'academicYear:id,name'];
    }
}
