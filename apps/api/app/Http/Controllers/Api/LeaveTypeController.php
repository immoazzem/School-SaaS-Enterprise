<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LeaveType;
use App\Models\School;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class LeaveTypeController extends Controller
{
    public function index(Request $request, School $school): JsonResponse
    {
        $this->authorizeLeave($request, $school);

        return response()->json($this->paginated($school->leaveTypes()->orderBy('name')->paginate($this->perPage($request))));
    }

    public function store(Request $request, School $school): JsonResponse
    {
        $this->authorizeLeave($request, $school);
        $type = $school->leaveTypes()->create($this->validatedPayload($request, $school));

        return response()->json(['data' => $type], 201);
    }

    public function show(Request $request, School $school, LeaveType $leaveType): JsonResponse
    {
        $this->authorizeLeave($request, $school);
        abort_unless($leaveType->school_id === $school->id, 404);

        return response()->json(['data' => $leaveType]);
    }

    public function update(Request $request, School $school, LeaveType $leaveType): JsonResponse
    {
        $this->authorizeLeave($request, $school);
        abort_unless($leaveType->school_id === $school->id, 404);
        $leaveType->update($this->validatedPayload($request, $school, $leaveType));

        return response()->json(['data' => $leaveType->fresh()]);
    }

    public function destroy(Request $request, School $school, LeaveType $leaveType): JsonResponse
    {
        $this->authorizeLeave($request, $school);
        abort_unless($leaveType->school_id === $school->id, 404);
        $leaveType->delete();

        return response()->json(status: 204);
    }

    private function authorizeLeave(Request $request, School $school): void
    {
        abort_unless($request->user()->hasSchoolPermission($school, 'leave.manage'), 403);
    }

    private function validatedPayload(Request $request, School $school, ?LeaveType $leaveType = null): array
    {
        return $request->validate([
            'name' => [$leaveType ? 'sometimes' : 'required', 'string', 'max:120'],
            'code' => [$leaveType ? 'sometimes' : 'required', 'string', 'max:40', Rule::unique('leave_types')->where('school_id', $school->id)->ignore($leaveType?->id)],
            'max_days_per_year' => [$leaveType ? 'sometimes' : 'required', 'integer', 'min:0', 'max:255'],
            'is_paid' => ['nullable', 'boolean'],
            'requires_approval' => ['nullable', 'boolean'],
            'status' => ['nullable', Rule::in(['active', 'archived'])],
        ]);
    }
}
