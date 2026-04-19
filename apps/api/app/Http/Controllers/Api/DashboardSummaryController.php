<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\School;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardSummaryController extends Controller
{
    public function __invoke(Request $request, School $school): JsonResponse
    {
        abort_unless($request->user()->hasSchoolPermission($school, 'reports.view'), 403);

        $today = CarbonImmutable::today();
        $monthStart = $today->startOfMonth();
        $lastMonthStart = $monthStart->subMonth();
        $lastMonthEnd = $monthStart->subDay();

        $collectionsThisMonth = (float) $school->invoicePayments()
            ->whereBetween('paid_on', [$monthStart->toDateString(), $today->toDateString()])
            ->sum('amount');
        $collectionsLastMonth = (float) $school->invoicePayments()
            ->whereBetween('paid_on', [$lastMonthStart->toDateString(), $lastMonthEnd->toDateString()])
            ->sum('amount');

        $attendanceTotal = $school->studentAttendanceRecords()
            ->whereDate('attendance_date', $today->toDateString())
            ->count();
        $attendancePresent = $school->studentAttendanceRecords()
            ->whereDate('attendance_date', $today->toDateString())
            ->whereIn('status', ['present', 'late'])
            ->count();

        $collectionTrend = $school->invoicePayments()
            ->select(DB::raw($this->monthExpression('paid_on').' as month'), DB::raw('sum(amount) as total'))
            ->where('paid_on', '>=', $monthStart->subMonths(5)->toDateString())
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        $outstandingByClass = $school->studentInvoices()
            ->join('student_enrollments', 'student_invoices.student_enrollment_id', '=', 'student_enrollments.id')
            ->join('academic_classes', 'student_enrollments.academic_class_id', '=', 'academic_classes.id')
            ->select('academic_classes.id', 'academic_classes.name', DB::raw('sum(student_invoices.total - student_invoices.paid_amount) as outstanding'))
            ->where('student_invoices.school_id', $school->id)
            ->groupBy('academic_classes.id', 'academic_classes.name')
            ->orderBy('academic_classes.name')
            ->get();

        return response()->json([
            'data' => [
                'admin' => [
                    'student_count' => $school->students()->count(),
                    'employee_count' => $school->employees()->count(),
                    'today_attendance_rate' => $attendanceTotal > 0 ? round(($attendancePresent / $attendanceTotal) * 100, 2) : 0,
                    'fee_collection_this_month' => round($collectionsThisMonth, 2),
                    'fee_collection_last_month' => round($collectionsLastMonth, 2),
                    'pending_leave_applications' => $school->leaveApplications()->where('status', 'pending')->count(),
                    'upcoming_exams' => $school->exams()->whereDate('starts_on', '>=', $today->toDateString())->orderBy('starts_on')->limit(5)->get(['id', 'name', 'starts_on']),
                    'attendance_concerns' => $this->attendanceConcerns($school, $monthStart, $today),
                ],
                'accountant' => [
                    'collection_trend' => $collectionTrend,
                    'outstanding_by_class' => $outstandingByClass,
                    'unpaid_invoices' => $school->studentInvoices()->whereIn('status', ['unpaid', 'partial'])->count(),
                    'pending_salaries' => $school->salaryRecords()->where('status', 'pending')->count(),
                ],
                'teacher' => [
                    'pending_marks_entries' => $school->marksEntries()->where('verification_status', 'pending')->count(),
                    'upcoming_exams' => $school->exams()->whereDate('starts_on', '>=', $today->toDateString())->orderBy('starts_on')->limit(5)->get(['id', 'name', 'starts_on']),
                ],
                'auditor' => [
                    'recent_audit_logs' => AuditLog::query()->where('school_id', $school->id)->latest()->limit(5)->get(['id', 'event', 'actor_id', 'created_at']),
                ],
            ],
        ]);
    }

    private function monthExpression(string $column): string
    {
        return DB::getDriverName() === 'sqlite'
            ? "strftime('%Y-%m', {$column})"
            : "date_format({$column}, '%Y-%m')";
    }

    private function attendanceConcerns(School $school, CarbonImmutable $from, CarbonImmutable $to)
    {
        return $school->studentAttendanceRecords()
            ->select('student_enrollment_id', DB::raw("sum(case when status in ('present', 'late') then 1 else 0 end) as attended"), DB::raw('count(*) as total'))
            ->whereBetween('attendance_date', [$from->toDateString(), $to->toDateString()])
            ->with('studentEnrollment.student:id,full_name,admission_no')
            ->groupBy('student_enrollment_id')
            ->havingRaw('(attended / total) < 0.75')
            ->limit(20)
            ->get()
            ->map(fn ($row): array => [
                'student_enrollment' => $row->studentEnrollment,
                'attendance_percentage' => (int) $row->total > 0 ? round(((float) $row->attended / (float) $row->total) * 100, 2) : 0,
            ]);
    }
}
