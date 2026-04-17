<?php

namespace App\Policies;

use App\Models\School;
use App\Models\Subject;
use App\Models\User;

class SubjectPolicy
{
    public function viewAny(User $user, School $school): bool
    {
        return $user->hasSchoolPermission($school, 'subjects.manage');
    }

    public function view(User $user, Subject $subject, School $school): bool
    {
        return $subject->school_id === $school->id
            && $user->hasSchoolPermission($school, 'subjects.manage');
    }

    public function create(User $user, School $school): bool
    {
        return $user->hasSchoolPermission($school, 'subjects.manage');
    }

    public function update(User $user, Subject $subject, School $school): bool
    {
        return $subject->school_id === $school->id
            && $user->hasSchoolPermission($school, 'subjects.manage');
    }

    public function delete(User $user, Subject $subject, School $school): bool
    {
        return $subject->school_id === $school->id
            && $user->hasSchoolPermission($school, 'subjects.manage');
    }
}
