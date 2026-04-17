<?php

namespace App\Policies;

use App\Models\Guardian;
use App\Models\School;
use App\Models\User;

class GuardianPolicy
{
    public function viewAny(User $user, School $school): bool
    {
        return $user->hasSchoolPermission($school, 'guardians.manage');
    }

    public function view(User $user, Guardian $guardian, School $school): bool
    {
        return $guardian->school_id === $school->id
            && $user->hasSchoolPermission($school, 'guardians.manage');
    }

    public function create(User $user, School $school): bool
    {
        return $user->hasSchoolPermission($school, 'guardians.manage');
    }

    public function update(User $user, Guardian $guardian, School $school): bool
    {
        return $guardian->school_id === $school->id
            && $user->hasSchoolPermission($school, 'guardians.manage');
    }

    public function delete(User $user, Guardian $guardian, School $school): bool
    {
        return $guardian->school_id === $school->id
            && $user->hasSchoolPermission($school, 'guardians.manage');
    }
}
