<?php

namespace App\Policies;

use App\Models\ExamType;
use App\Models\School;
use App\Models\User;

class ExamTypePolicy
{
    public function viewAny(User $user, School $school): bool
    {
        return $user->hasSchoolPermission($school, 'exams.manage');
    }

    public function view(User $user, ExamType $examType, School $school): bool
    {
        return $examType->school_id === $school->id
            && $user->hasSchoolPermission($school, 'exams.manage');
    }

    public function create(User $user, School $school): bool
    {
        return $user->hasSchoolPermission($school, 'exams.manage');
    }

    public function update(User $user, ExamType $examType, School $school): bool
    {
        return $examType->school_id === $school->id
            && $user->hasSchoolPermission($school, 'exams.manage');
    }

    public function delete(User $user, ExamType $examType, School $school): bool
    {
        return $examType->school_id === $school->id
            && $user->hasSchoolPermission($school, 'exams.manage');
    }
}
