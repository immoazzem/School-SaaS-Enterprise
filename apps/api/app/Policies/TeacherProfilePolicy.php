<?php

namespace App\Policies;

use App\Models\School;
use App\Models\TeacherProfile;
use App\Models\User;

class TeacherProfilePolicy
{
    public function viewAny(User $user, School $school): bool
    {
        return $user->hasSchoolPermission($school, 'teachers.manage');
    }

    public function view(User $user, TeacherProfile $teacherProfile, School $school): bool
    {
        return $teacherProfile->school_id === $school->id
            && $user->hasSchoolPermission($school, 'teachers.manage');
    }

    public function create(User $user, School $school): bool
    {
        return $user->hasSchoolPermission($school, 'teachers.manage');
    }

    public function update(User $user, TeacherProfile $teacherProfile, School $school): bool
    {
        return $teacherProfile->school_id === $school->id
            && $user->hasSchoolPermission($school, 'teachers.manage');
    }

    public function delete(User $user, TeacherProfile $teacherProfile, School $school): bool
    {
        return $teacherProfile->school_id === $school->id
            && $user->hasSchoolPermission($school, 'teachers.manage');
    }
}
