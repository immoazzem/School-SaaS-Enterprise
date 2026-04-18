<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\EmployeeAttendanceRecord;
use App\Models\School;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class EmployeeAttendanceRecordController extends Controller
{
    public function index(Request $request, School $school): JsonResponse
    {
        $this->authorizeAttendance($request, $school);

        return response()->json($this->paginated($school->employeeAttendanceRecords()->with($this->relations())->orderByDesc('date')->paginate($this->perPage($request))));
    }

    public function store(Request $request, School $school): JsonResponse
    {
        $this->authorizeAttendance($request, $school);
        $validated = [...$this->validatedPayload($request, $school), 'recorded_by' => $request->user()->id];
        $record = $school->employeeAttendanceRecords()->updateOrCreate(
            ['employee_id' => $validated['employee_id'], 'date' => $validated['date']],
            $validated
        );

        return response()->json(['data' => $record->load($this->relations())], 201);
    }

    public function show(Request $request, School $school, EmployeeAttendanceRecord $employeeAttendanceRecord): JsonResponse
    {
        $this->authorizeAttendance($request, $school);
        abort_unless($employeeAttendanceRecord->school_id === $school->id, 404);

        return response()->json(['data' => $employeeAttendanceRecord->load($this->relations())]);
    }

    public function update(Request $request, School $school, EmployeeAttendanceRecord $employeeAttendanceRecord): JsonResponse
    {
        $this->authorizeAttendance($request, $school);
        abort_unless($employeeAttendanceRecord->school_id === $school->id, 404);
        $employeeAttendanceRecord->update([...$this->validatedPayload($request, $school, $employeeAttendanceRecord), 'recorded_by' => $request->user()->id]);

        return response()->json(['data' => $employeeAttendanceRecord->fresh()->load($this->relations())]);
    }

    public function destroy(Request $request, School $school, EmployeeAttendanceRecord $employeeAttendanceRecord): JsonResponse
    {
        $this->authorizeAttendance($request, $school);
        abort_unless($employeeAttendanceRecord->school_id === $school->id, 404);
        $employeeAttendanceRecord->delete();

        return response()->json(status: 204);
    }

    private function authorizeAttendance(Request $request, School $school): void
    {
        abort_unless($request->user()->hasSchoolPermission($school, 'employee_attendance.manage'), 403);
    }

    private function validatedPayload(Request $request, School $school, ?EmployeeAttendanceRecord $record = null): array
    {
        return $request->validate([
            'employee_id' => [$record ? 'sometimes' : 'required', 'integer', Rule::exists('employees', 'id')->where('school_id', $school->id)],
            'date' => [$record ? 'sometimes' : 'required', 'date'],
            'status' => [$record ? 'sometimes' : 'required', Rule::in(['present', 'absent', 'late', 'half_day', 'on_leave'])],
            'check_in_time' => ['nullable', 'date_format:H:i'],
            'check_out_time' => ['nullable', 'date_format:H:i'],
            'notes' => ['nullable', 'string', 'max:255'],
        ]);
    }

    private function relations(): array
    {
        return ['employee:id,employee_no,full_name', 'recorder:id,name'];
    }
}
