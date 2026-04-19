<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\School;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class StudentAttendanceSummaryController extends Controller
{
    public function __invoke(Request $request, School $school): JsonResponse
    {
        abort_unless($request->user()->hasSchoolPermission($school, 'reports.view'), 403);

        $validated = $request->validate([
            'academic_class_id' => ['nullable', 'integer', Rule::exists('academic_classes', 'id')->where('school_id', $school->id)],
            'academic_year_id' => ['nullable', 'integer', Rule::exists('academic_years', 'id')->where('school_id', $school->id)],
            'month' => ['nullable', 'date_format:Y-m'],
        ]);

        $month = $validated['month'] ?? now()->format('Y-m');
        $startsOn = CarbonImmutable::createFromFormat('Y-m-d', "{$month}-01")->startOfMonth();
        $endsOn = $startsOn->endOfMonth();

        $rows = $school->studentAttendanceRecords()
            ->select('student_enrollment_id', 'status', DB::raw('count(*) as total'))
            ->whereBetween('attendance_date', [$startsOn->toDateString(), $endsOn->toDateString()])
            ->whereHas('studentEnrollment', function ($query) use ($validated): void {
                $query
                    ->when($validated['academic_class_id'] ?? null, fn ($query, int $classId) => $query->where('academic_class_id', $classId))
                    ->when($validated['academic_year_id'] ?? null, fn ($query, int $yearId) => $query->where('academic_year_id', $yearId));
            })
            ->with([
                'studentEnrollment:id,student_id,academic_class_id,roll_no',
                'studentEnrollment.student:id,admission_no,full_name',
                'studentEnrollment.academicClass:id,name,code',
            ])
            ->groupBy('student_enrollment_id', 'status')
            ->get();

        $summary = $rows
            ->groupBy('student_enrollment_id')
            ->map(function ($studentRows): array {
                $first = $studentRows->first();
                $counts = collect(['present', 'absent', 'late', 'half_day'])
                    ->mapWithKeys(fn (string $status): array => [$status => (int) ($studentRows->firstWhere('status', $status)?->total ?? 0)])
                    ->all();
                $total = array_sum($counts);
                $attended = $counts['present'] + $counts['late'] + ($counts['half_day'] * 0.5);

                return [
                    'student_enrollment' => $first->studentEnrollment,
                    'counts' => $counts,
                    'total_days' => $total,
                    'attendance_percentage' => $total > 0 ? round(($attended / $total) * 100, 2) : 0,
                ];
            })
            ->values();

        return response()->json(['data' => $summary]);
    }
}
