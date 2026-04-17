<?php

namespace App\Policies;

use App\Models\School;
use App\Models\Shift;
use App\Models\User;

class ShiftPolicy
{
    public function viewAny(User $user, School $school): bool
    {
        return $user->hasSchoolPermission($school, 'shifts.manage');
    }

    public function view(User $user, Shift $shift, School $school): bool
    {
        return $shift->school_id === $school->id
            && $user->hasSchoolPermission($school, 'shifts.manage');
    }

    public function create(User $user, School $school): bool
    {
        return $user->hasSchoolPermission($school, 'shifts.manage');
    }

    public function update(User $user, Shift $shift, School $school): bool
    {
        return $shift->school_id === $school->id
            && $user->hasSchoolPermission($school, 'shifts.manage');
    }

    public function delete(User $user, Shift $shift, School $school): bool
    {
        return $shift->school_id === $school->id
            && $user->hasSchoolPermission($school, 'shifts.manage');
    }
}
