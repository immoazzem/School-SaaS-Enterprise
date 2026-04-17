<?php

namespace App\Policies;

use App\Models\School;
use App\Models\StudentAttendanceRecord;
use App\Models\User;

class StudentAttendanceRecordPolicy
{
    public function viewAny(User $user, School $school): bool
    {
        return $user->hasSchoolPermission($school, 'attendance.manage');
    }

    public function view(User $user, StudentAttendanceRecord $record, School $school): bool
    {
        return $record->school_id === $school->id
            && $user->hasSchoolPermission($school, 'attendance.manage');
    }

    public function create(User $user, School $school): bool
    {
        return $user->hasSchoolPermission($school, 'attendance.manage');
    }

    public function update(User $user, StudentAttendanceRecord $record, School $school): bool
    {
        return $record->school_id === $school->id
            && $user->hasSchoolPermission($school, 'attendance.manage');
    }

    public function delete(User $user, StudentAttendanceRecord $record, School $school): bool
    {
        return $record->school_id === $school->id
            && $user->hasSchoolPermission($school, 'attendance.manage');
    }
}
