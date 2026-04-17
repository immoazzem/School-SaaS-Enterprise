<?php

namespace App\Policies;

use App\Models\School;
use App\Models\StudentEnrollment;
use App\Models\User;

class StudentEnrollmentPolicy
{
    public function viewAny(User $user, School $school): bool
    {
        return $user->hasSchoolPermission($school, 'enrollments.manage');
    }

    public function view(User $user, StudentEnrollment $studentEnrollment, School $school): bool
    {
        return $studentEnrollment->school_id === $school->id
            && $user->hasSchoolPermission($school, 'enrollments.manage');
    }

    public function create(User $user, School $school): bool
    {
        return $user->hasSchoolPermission($school, 'enrollments.manage');
    }

    public function update(User $user, StudentEnrollment $studentEnrollment, School $school): bool
    {
        return $studentEnrollment->school_id === $school->id
            && $user->hasSchoolPermission($school, 'enrollments.manage');
    }

    public function delete(User $user, StudentEnrollment $studentEnrollment, School $school): bool
    {
        return $studentEnrollment->school_id === $school->id
            && $user->hasSchoolPermission($school, 'enrollments.manage');
    }
}
