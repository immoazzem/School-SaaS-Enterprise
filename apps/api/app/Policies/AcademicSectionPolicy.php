<?php

namespace App\Policies;

use App\Models\AcademicSection;
use App\Models\School;
use App\Models\User;

class AcademicSectionPolicy
{
    public function viewAny(User $user, School $school): bool
    {
        return $user->hasSchoolPermission($school, 'sections.manage');
    }

    public function view(User $user, AcademicSection $academicSection, School $school): bool
    {
        return $academicSection->school_id === $school->id
            && $user->hasSchoolPermission($school, 'sections.manage');
    }

    public function create(User $user, School $school): bool
    {
        return $user->hasSchoolPermission($school, 'sections.manage');
    }

    public function update(User $user, AcademicSection $academicSection, School $school): bool
    {
        return $academicSection->school_id === $school->id
            && $user->hasSchoolPermission($school, 'sections.manage');
    }

    public function delete(User $user, AcademicSection $academicSection, School $school): bool
    {
        return $academicSection->school_id === $school->id
            && $user->hasSchoolPermission($school, 'sections.manage');
    }
}
