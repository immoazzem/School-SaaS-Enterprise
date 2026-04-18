<?php

namespace App\Services;

use App\Models\AcademicYear;
use App\Models\LeaveApplication;
use App\Models\School;
use App\Models\User;
use Carbon\CarbonPeriod;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class LeaveWorkflowService
{
    public function approve(School $school, LeaveApplication $application, User $actor, ?string $note = null): LeaveApplication
    {
        return DB::transaction(function () use ($school, $application, $actor, $note): LeaveApplication {
            $application = $application->lockForUpdate()->firstWhere('id', $application->id);

            if ($application->status !== 'pending') {
                throw ValidationException::withMessages(['status' => 'Only pending leave applications can be approved.']);
            }

            $balance = $school->leaveBalances()
                ->where('employee_id', $application->employee_id)
                ->where('leave_type_id', $application->leave_type_id)
                ->where('academic_year_id', $this->academicYearFor($school, $application->from_date)->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($balance->remaining_days < $application->total_days) {
                throw ValidationException::withMessages(['leave_balance' => 'Insufficient leave balance.']);
            }

            $balance->update([
                'used_days' => $balance->used_days + $application->total_days,
                'remaining_days' => $balance->remaining_days - $application->total_days,
            ]);

            $application->update([
                'status' => 'approved',
                'reviewed_by' => $actor->id,
                'reviewed_at' => now(),
                'review_note' => $note,
            ]);

            foreach (CarbonPeriod::create($application->from_date, $application->to_date) as $date) {
                $school->employeeAttendanceRecords()->updateOrCreate(
                    [
                        'employee_id' => $application->employee_id,
                        'date' => $date->toDateString(),
                    ],
                    [
                        'status' => 'on_leave',
                        'recorded_by' => $actor->id,
                        'notes' => "Leave application #{$application->id}",
                    ]
                );
            }

            return $application->fresh();
        });
    }

    public function reject(LeaveApplication $application, User $actor, ?string $note = null): LeaveApplication
    {
        return DB::transaction(function () use ($application, $actor, $note): LeaveApplication {
            if ($application->status !== 'pending') {
                throw ValidationException::withMessages(['status' => 'Only pending leave applications can be rejected.']);
            }

            $application->update([
                'status' => 'rejected',
                'reviewed_by' => $actor->id,
                'reviewed_at' => now(),
                'review_note' => $note,
            ]);

            return $application->fresh();
        });
    }

    public function cancel(School $school, LeaveApplication $application, User $actor): LeaveApplication
    {
        return DB::transaction(function () use ($school, $application, $actor): LeaveApplication {
            if (! in_array($application->status, ['pending', 'approved'], true)) {
                throw ValidationException::withMessages(['status' => 'Only pending or approved leave applications can be cancelled.']);
            }

            if ($application->status === 'approved') {
                $balance = $school->leaveBalances()
                    ->where('employee_id', $application->employee_id)
                    ->where('leave_type_id', $application->leave_type_id)
                    ->where('academic_year_id', $this->academicYearFor($school, $application->from_date)->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                $balance->update([
                    'used_days' => max(0, $balance->used_days - $application->total_days),
                    'remaining_days' => min($balance->total_days, $balance->remaining_days + $application->total_days),
                ]);

                $school->employeeAttendanceRecords()
                    ->where('employee_id', $application->employee_id)
                    ->whereBetween('date', [$application->from_date, $application->to_date])
                    ->where('status', 'on_leave')
                    ->delete();
            }

            $application->update([
                'status' => 'cancelled',
                'reviewed_by' => $actor->id,
                'reviewed_at' => now(),
            ]);

            return $application->fresh();
        });
    }

    private function academicYearFor(School $school, mixed $date): AcademicYear
    {
        return $school->academicYears()
            ->whereDate('starts_on', '<=', $date)
            ->whereDate('ends_on', '>=', $date)
            ->first()
            ?? $school->academicYears()->where('is_current', true)->firstOrFail();
    }
}
