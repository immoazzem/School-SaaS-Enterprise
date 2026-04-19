<?php

namespace App\Policies;

use App\Models\AssignmentSubmission;
use App\Models\School;
use App\Models\User;

class AssignmentSubmissionPolicy
{
    public function viewAny(User $user, School $school): bool
    {
        return $user->hasSchoolPermission($school, 'assignments.manage');
    }

    public function view(User $user, AssignmentSubmission $assignmentSubmission, School $school): bool
    {
        return $assignmentSubmission->school_id === $school->id
            && $user->hasSchoolPermission($school, 'assignments.manage');
    }

    public function create(User $user, School $school): bool
    {
        return $user->hasSchoolPermission($school, 'assignments.manage');
    }

    public function update(User $user, AssignmentSubmission $assignmentSubmission, School $school): bool
    {
        return $assignmentSubmission->school_id === $school->id
            && $user->hasSchoolPermission($school, 'assignments.manage');
    }

    public function delete(User $user, AssignmentSubmission $assignmentSubmission, School $school): bool
    {
        return $assignmentSubmission->school_id === $school->id
            && $user->hasSchoolPermission($school, 'assignments.manage');
    }
}
