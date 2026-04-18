<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\School;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class EmployeeAttendanceSummaryController extends Controller
{
    public function __invoke(Request $request, School $school): JsonResponse
    {
        abort_unless($request->user()->hasSchoolPermission($school, 'employee_attendance.manage'), 403);

        $validated = $request->validate([
            'employee_id' => ['nullable', 'integer', Rule::exists('employees', 'id')->where('school_id', $school->id)],
            'academic_year_id' => ['nullable', 'integer', Rule::exists('academic_years', 'id')->where('school_id', $school->id)],
            'month' => ['nullable', 'date_format:Y-m'],
        ]);

        $query = $school->employeeAttendanceRecords()
            ->select('employee_id', 'status', DB::raw('count(*) as total'))
            ->when($validated['employee_id'] ?? null, fn ($query, int $employeeId) => $query->where('employee_id', $employeeId))
            ->when($validated['month'] ?? null, function ($query, string $month): void {
                $startsOn = CarbonImmutable::createFromFormat('Y-m-d', "{$month}-01")->startOfMonth();
                $query->whereBetween('date', [$startsOn->toDateString(), $startsOn->endOfMonth()->toDateString()]);
            })
            ->when($validated['academic_year_id'] ?? null, function ($query, int $yearId) use ($school): void {
                $year = $school->academicYears()->findOrFail($yearId);
                $query->whereBetween('date', [$year->starts_on, $year->ends_on]);
            })
            ->groupBy('employee_id', 'status')
            ->with('employee:id,employee_no,full_name')
            ->get();

        $summary = $query
            ->groupBy('employee_id')
            ->map(function ($rows): array {
                $first = $rows->first();
                $counts = collect(['present', 'absent', 'late', 'half_day', 'on_leave'])
                    ->mapWithKeys(fn (string $status): array => [$status => (int) ($rows->firstWhere('status', $status)?->total ?? 0)])
                    ->all();

                return [
                    'employee' => $first->employee,
                    'counts' => $counts,
                ];
            })
            ->values();

        return response()->json(['data' => $summary]);
    }
}
