<?php

namespace App\Policies;

use App\Models\School;
use App\Models\TimetablePeriod;
use App\Models\User;

class TimetablePeriodPolicy
{
    public function viewAny(User $user, School $school): bool
    {
        return $user->hasSchoolPermission($school, 'timetable.manage');
    }

    public function view(User $user, TimetablePeriod $timetablePeriod, School $school): bool
    {
        return $timetablePeriod->school_id === $school->id
            && $user->hasSchoolPermission($school, 'timetable.manage');
    }

    public function create(User $user, School $school): bool
    {
        return $user->hasSchoolPermission($school, 'timetable.manage');
    }

    public function update(User $user, TimetablePeriod $timetablePeriod, School $school): bool
    {
        return $timetablePeriod->school_id === $school->id
            && $user->hasSchoolPermission($school, 'timetable.manage');
    }

    public function delete(User $user, TimetablePeriod $timetablePeriod, School $school): bool
    {
        return $timetablePeriod->school_id === $school->id
            && $user->hasSchoolPermission($school, 'timetable.manage');
    }
}
