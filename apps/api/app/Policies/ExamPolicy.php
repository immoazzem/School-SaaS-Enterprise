<?php

namespace App\Policies;

use App\Models\Exam;
use App\Models\School;
use App\Models\User;

class ExamPolicy
{
    public function viewAny(User $user, School $school): bool
    {
        return $user->hasSchoolPermission($school, 'exams.manage');
    }

    public function view(User $user, Exam $exam, School $school): bool
    {
        return $exam->school_id === $school->id
            && $user->hasSchoolPermission($school, 'exams.manage');
    }

    public function create(User $user, School $school): bool
    {
        return $user->hasSchoolPermission($school, 'exams.manage');
    }

    public function update(User $user, Exam $exam, School $school): bool
    {
        return $exam->school_id === $school->id
            && $user->hasSchoolPermission($school, 'exams.manage');
    }

    public function delete(User $user, Exam $exam, School $school): bool
    {
        return $exam->school_id === $school->id
            && $user->hasSchoolPermission($school, 'exams.manage');
    }
}
