<?php

namespace App\Policies;

use App\Models\School;
use App\Models\StudentGroup;
use App\Models\User;

class StudentGroupPolicy
{
    public function viewAny(User $user, School $school): bool
    {
        return $user->hasSchoolPermission($school, 'student_groups.manage');
    }

    public function view(User $user, StudentGroup $studentGroup, School $school): bool
    {
        return $studentGroup->school_id === $school->id
            && $user->hasSchoolPermission($school, 'student_groups.manage');
    }

    public function create(User $user, School $school): bool
    {
        return $user->hasSchoolPermission($school, 'student_groups.manage');
    }

    public function update(User $user, StudentGroup $studentGroup, School $school): bool
    {
        return $studentGroup->school_id === $school->id
            && $user->hasSchoolPermission($school, 'student_groups.manage');
    }

    public function delete(User $user, StudentGroup $studentGroup, School $school): bool
    {
        return $studentGroup->school_id === $school->id
            && $user->hasSchoolPermission($school, 'student_groups.manage');
    }
}
