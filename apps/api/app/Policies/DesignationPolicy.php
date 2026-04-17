<?php

namespace App\Policies;

use App\Models\Designation;
use App\Models\School;
use App\Models\User;

class DesignationPolicy
{
    public function viewAny(User $user, School $school): bool
    {
        return $user->hasSchoolPermission($school, 'designations.manage');
    }

    public function view(User $user, Designation $designation, School $school): bool
    {
        return $designation->school_id === $school->id
            && $user->hasSchoolPermission($school, 'designations.manage');
    }

    public function create(User $user, School $school): bool
    {
        return $user->hasSchoolPermission($school, 'designations.manage');
    }

    public function update(User $user, Designation $designation, School $school): bool
    {
        return $designation->school_id === $school->id
            && $user->hasSchoolPermission($school, 'designations.manage');
    }

    public function delete(User $user, Designation $designation, School $school): bool
    {
        return $designation->school_id === $school->id
            && $user->hasSchoolPermission($school, 'designations.manage');
    }
}
