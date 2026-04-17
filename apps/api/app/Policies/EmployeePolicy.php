<?php

namespace App\Policies;

use App\Models\Employee;
use App\Models\School;
use App\Models\User;

class EmployeePolicy
{
    public function viewAny(User $user, School $school): bool
    {
        return $user->hasSchoolPermission($school, 'employees.manage');
    }

    public function view(User $user, Employee $employee, School $school): bool
    {
        return $employee->school_id === $school->id
            && $user->hasSchoolPermission($school, 'employees.manage');
    }

    public function create(User $user, School $school): bool
    {
        return $user->hasSchoolPermission($school, 'employees.manage');
    }

    public function update(User $user, Employee $employee, School $school): bool
    {
        return $employee->school_id === $school->id
            && $user->hasSchoolPermission($school, 'employees.manage');
    }

    public function delete(User $user, Employee $employee, School $school): bool
    {
        return $employee->school_id === $school->id
            && $user->hasSchoolPermission($school, 'employees.manage');
    }
}
