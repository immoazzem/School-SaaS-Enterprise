<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LeaveBalance;
use App\Models\School;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class LeaveBalanceController extends Controller
{
    public function index(Request $request, School $school): JsonResponse
    {
        $this->authorizeLeave($request, $school);

        return response()->json($this->paginated($school->leaveBalances()->with($this->relations())->orderByDesc('id')->paginate($this->perPage($request))));
    }

    public function store(Request $request, School $school): JsonResponse
    {
        $this->authorizeLeave($request, $school);
        $validated = $this->validatedPayload($request, $school);
        $validated['remaining_days'] = $validated['remaining_days'] ?? ($validated['total_days'] - ($validated['used_days'] ?? 0));
        $balance = $school->leaveBalances()->create($validated);

        return response()->json(['data' => $balance->load($this->relations())], 201);
    }

    public function show(Request $request, School $school, LeaveBalance $leaveBalance): JsonResponse
    {
        $this->authorizeLeave($request, $school);
        abort_unless($leaveBalance->school_id === $school->id, 404);

        return response()->json(['data' => $leaveBalance->load($this->relations())]);
    }

    public function update(Request $request, School $school, LeaveBalance $leaveBalance): JsonResponse
    {
        $this->authorizeLeave($request, $school);
        abort_unless($leaveBalance->school_id === $school->id, 404);
        $validated = $this->validatedPayload($request, $school, $leaveBalance);
        if (array_key_exists('total_days', $validated) || array_key_exists('used_days', $validated)) {
            $total = $validated['total_days'] ?? $leaveBalance->total_days;
            $used = $validated['used_days'] ?? $leaveBalance->used_days;
            $validated['remaining_days'] = $total - $used;
        }
        $leaveBalance->update($validated);

        return response()->json(['data' => $leaveBalance->fresh()->load($this->relations())]);
    }

    public function destroy(Request $request, School $school, LeaveBalance $leaveBalance): JsonResponse
    {
        $this->authorizeLeave($request, $school);
        abort_unless($leaveBalance->school_id === $school->id, 404);
        $leaveBalance->delete();

        return response()->json(status: 204);
    }

    private function authorizeLeave(Request $request, School $school): void
    {
        abort_unless($request->user()->hasSchoolPermission($school, 'leave.manage'), 403);
    }

    private function validatedPayload(Request $request, School $school, ?LeaveBalance $leaveBalance = null): array
    {
        return $request->validate([
            'employee_id' => [$leaveBalance ? 'sometimes' : 'required', 'integer', Rule::exists('employees', 'id')->where('school_id', $school->id)],
            'leave_type_id' => [$leaveBalance ? 'sometimes' : 'required', 'integer', Rule::exists('leave_types', 'id')->where('school_id', $school->id)],
            'academic_year_id' => [$leaveBalance ? 'sometimes' : 'required', 'integer', Rule::exists('academic_years', 'id')->where('school_id', $school->id)],
            'total_days' => [$leaveBalance ? 'sometimes' : 'required', 'integer', 'min:0', 'max:255'],
            'used_days' => ['nullable', 'integer', 'min:0', 'max:255'],
            'remaining_days' => ['nullable', 'integer', 'min:0', 'max:255'],
        ]);
    }

    private function relations(): array
    {
        return ['employee:id,employee_no,full_name', 'leaveType:id,name,code', 'academicYear:id,name'];
    }
}
