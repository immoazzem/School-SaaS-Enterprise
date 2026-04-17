<?php

namespace App\Policies;

use App\Models\AcademicYear;
use App\Models\School;
use App\Models\User;

class AcademicYearPolicy
{
    public function viewAny(User $user, School $school): bool
    {
        return $user->hasSchoolPermission($school, 'academic_years.manage');
    }

    public function view(User $user, AcademicYear $academicYear, School $school): bool
    {
        return $academicYear->school_id === $school->id
            && $user->hasSchoolPermission($school, 'academic_years.manage');
    }

    public function create(User $user, School $school): bool
    {
        return $user->hasSchoolPermission($school, 'academic_years.manage');
    }

    public function update(User $user, AcademicYear $academicYear, School $school): bool
    {
        return $academicYear->school_id === $school->id
            && $user->hasSchoolPermission($school, 'academic_years.manage');
    }

    public function delete(User $user, AcademicYear $academicYear, School $school): bool
    {
        return $academicYear->school_id === $school->id
            && $user->hasSchoolPermission($school, 'academic_years.manage');
    }
}
