<?php

namespace App\Policies;

use App\Models\ExamSchedule;
use App\Models\School;
use App\Models\User;

class ExamSchedulePolicy
{
    public function viewAny(User $user, School $school): bool
    {
        return $user->hasSchoolPermission($school, 'exams.manage');
    }

    public function view(User $user, ExamSchedule $examSchedule, School $school): bool
    {
        return $examSchedule->school_id === $school->id
            && $user->hasSchoolPermission($school, 'exams.manage');
    }

    public function create(User $user, School $school): bool
    {
        return $user->hasSchoolPermission($school, 'exams.manage');
    }

    public function update(User $user, ExamSchedule $examSchedule, School $school): bool
    {
        return $examSchedule->school_id === $school->id
            && $user->hasSchoolPermission($school, 'exams.manage');
    }

    public function delete(User $user, ExamSchedule $examSchedule, School $school): bool
    {
        return $examSchedule->school_id === $school->id
            && $user->hasSchoolPermission($school, 'exams.manage');
    }
}
