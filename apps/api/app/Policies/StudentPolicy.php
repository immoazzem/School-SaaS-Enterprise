<?php

namespace App\Policies;

use App\Models\School;
use App\Models\Student;
use App\Models\User;

class StudentPolicy
{
    public function viewAny(User $user, School $school): bool
    {
        return $user->hasSchoolPermission($school, 'students.manage');
    }

    public function view(User $user, Student $student, School $school): bool
    {
        return $student->school_id === $school->id
            && $user->hasSchoolPermission($school, 'students.manage');
    }

    public function create(User $user, School $school): bool
    {
        return $user->hasSchoolPermission($school, 'students.manage');
    }

    public function update(User $user, Student $student, School $school): bool
    {
        return $student->school_id === $school->id
            && $user->hasSchoolPermission($school, 'students.manage');
    }

    public function delete(User $user, Student $student, School $school): bool
    {
        return $student->school_id === $school->id
            && $user->hasSchoolPermission($school, 'students.manage');
    }
}
