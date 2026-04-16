<?php

namespace App\Policies;

use App\Models\AcademicClass;
use App\Models\School;
use App\Models\User;

class AcademicClassPolicy
{
    public function viewAny(User $user, School $school): bool
    {
        return $user->hasSchoolPermission($school, 'academic_classes.manage');
    }

    public function view(User $user, AcademicClass $academicClass, School $school): bool
    {
        return $academicClass->school_id === $school->id
            && $user->hasSchoolPermission($school, 'academic_classes.manage');
    }

    public function create(User $user, School $school): bool
    {
        return $user->hasSchoolPermission($school, 'academic_classes.manage');
    }

    public function update(User $user, AcademicClass $academicClass, School $school): bool
    {
        return $academicClass->school_id === $school->id
            && $user->hasSchoolPermission($school, 'academic_classes.manage');
    }

    public function delete(User $user, AcademicClass $academicClass, School $school): bool
    {
        return $academicClass->school_id === $school->id
            && $user->hasSchoolPermission($school, 'academic_classes.manage');
    }
}
