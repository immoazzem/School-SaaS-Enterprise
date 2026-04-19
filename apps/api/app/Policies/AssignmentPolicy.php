<?php

namespace App\Policies;

use App\Models\Assignment;
use App\Models\School;
use App\Models\User;

class AssignmentPolicy
{
    public function viewAny(User $user, School $school): bool
    {
        return $user->hasSchoolPermission($school, 'assignments.manage');
    }

    public function view(User $user, Assignment $assignment, School $school): bool
    {
        return $assignment->school_id === $school->id
            && $user->hasSchoolPermission($school, 'assignments.manage');
    }

    public function create(User $user, School $school): bool
    {
        return $user->hasSchoolPermission($school, 'assignments.manage');
    }

    public function update(User $user, Assignment $assignment, School $school): bool
    {
        return $assignment->school_id === $school->id
            && $user->hasSchoolPermission($school, 'assignments.manage');
    }

    public function delete(User $user, Assignment $assignment, School $school): bool
    {
        return $assignment->school_id === $school->id
            && $user->hasSchoolPermission($school, 'assignments.manage');
    }
}
