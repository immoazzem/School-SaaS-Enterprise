<?php

namespace App\Policies;

use App\Models\ClassSubject;
use App\Models\School;
use App\Models\User;

class ClassSubjectPolicy
{
    public function viewAny(User $user, School $school): bool
    {
        return $user->hasSchoolPermission($school, 'class_subjects.manage');
    }

    public function view(User $user, ClassSubject $classSubject, School $school): bool
    {
        return $classSubject->school_id === $school->id
            && $user->hasSchoolPermission($school, 'class_subjects.manage');
    }

    public function create(User $user, School $school): bool
    {
        return $user->hasSchoolPermission($school, 'class_subjects.manage');
    }

    public function update(User $user, ClassSubject $classSubject, School $school): bool
    {
        return $classSubject->school_id === $school->id
            && $user->hasSchoolPermission($school, 'class_subjects.manage');
    }

    public function delete(User $user, ClassSubject $classSubject, School $school): bool
    {
        return $classSubject->school_id === $school->id
            && $user->hasSchoolPermission($school, 'class_subjects.manage');
    }
}
