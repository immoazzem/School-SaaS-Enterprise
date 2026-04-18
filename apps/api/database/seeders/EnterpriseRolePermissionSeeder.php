<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class EnterpriseRolePermissionSeeder extends Seeder
{
    /**
     * Seed the enterprise RBAC baseline.
     */
    public function run(): void
    {
        $permissions = [
            ['module' => 'schools', 'key' => 'schools.manage', 'description' => 'Manage school profile and settings'],
            ['module' => 'users', 'key' => 'users.manage', 'description' => 'Manage users and memberships'],
            ['module' => 'roles', 'key' => 'roles.manage', 'description' => 'Manage roles and permissions'],
            ['module' => 'academics', 'key' => 'academic_years.manage', 'description' => 'Manage academic years'],
            ['module' => 'academics', 'key' => 'academic_classes.manage', 'description' => 'Manage academic classes'],
            ['module' => 'academics', 'key' => 'sections.manage', 'description' => 'Manage class sections'],
            ['module' => 'academics', 'key' => 'subjects.manage', 'description' => 'Manage subjects'],
            ['module' => 'academics', 'key' => 'class_subjects.manage', 'description' => 'Manage class subject assignments'],
            ['module' => 'academics', 'key' => 'student_groups.manage', 'description' => 'Manage student groups'],
            ['module' => 'academics', 'key' => 'shifts.manage', 'description' => 'Manage shifts'],
            ['module' => 'people', 'key' => 'designations.manage', 'description' => 'Manage staff designations'],
            ['module' => 'people', 'key' => 'employees.manage', 'description' => 'Manage employee records'],
            ['module' => 'people', 'key' => 'students.manage', 'description' => 'Manage student records'],
            ['module' => 'people', 'key' => 'guardians.manage', 'description' => 'Manage guardian records'],
            ['module' => 'people', 'key' => 'enrollments.manage', 'description' => 'Manage student enrollments'],
            ['module' => 'people', 'key' => 'teachers.manage', 'description' => 'Manage teacher records'],
            ['module' => 'attendance', 'key' => 'attendance.manage', 'description' => 'Manage attendance records'],
            ['module' => 'exams', 'key' => 'exams.manage', 'description' => 'Manage exams and results'],
            ['module' => 'exams', 'key' => 'exams.publish', 'description' => 'Publish exam results'],
            ['module' => 'exams', 'key' => 'marks.enter.own', 'description' => 'Enter marks for assigned classes and subjects'],
            ['module' => 'exams', 'key' => 'marks.enter.any', 'description' => 'Enter and verify marks for any class and subject'],
            ['module' => 'exams', 'key' => 'grades.manage', 'description' => 'Manage grade scales and GPA rules'],
            ['module' => 'finance', 'key' => 'finance.manage', 'description' => 'Manage fees and accounting'],
            ['module' => 'finance', 'key' => 'payroll.manage', 'description' => 'Manage employee salary records'],
            ['module' => 'attendance', 'key' => 'employee_attendance.manage', 'description' => 'Manage employee attendance records'],
            ['module' => 'people', 'key' => 'leave.manage', 'description' => 'Manage employee leave workflows'],
            ['module' => 'reports', 'key' => 'reports.view', 'description' => 'View operational reports'],
            ['module' => 'audit', 'key' => 'audit.view', 'description' => 'View audit logs'],
            ['module' => 'billing', 'key' => 'billing.manage', 'description' => 'Manage SaaS billing settings'],
        ];

        $permissionModels = collect($permissions)
            ->mapWithKeys(fn (array $permission): array => [
                $permission['key'] => Permission::query()->updateOrCreate(
                    ['key' => $permission['key']],
                    $permission
                ),
            ]);

        $roles = [
            'super-admin' => [
                'name' => 'Super Admin',
                'permissions' => $permissionModels->keys()->all(),
            ],
            'school-owner' => [
                'name' => 'School Owner',
                'permissions' => $permissionModels->keys()
                    ->reject(fn (string $key): bool => $key === 'billing.manage')
                    ->all(),
            ],
            'school-admin' => [
                'name' => 'School Admin',
                'permissions' => [
                    'schools.manage',
                    'users.manage',
                    'roles.manage',
                    'academic_years.manage',
                    'academic_classes.manage',
                    'sections.manage',
                    'subjects.manage',
                    'class_subjects.manage',
                    'student_groups.manage',
                    'shifts.manage',
                    'designations.manage',
                    'employees.manage',
                    'students.manage',
                    'guardians.manage',
                    'enrollments.manage',
                    'teachers.manage',
                    'attendance.manage',
                    'exams.manage',
                    'exams.publish',
                    'marks.enter.any',
                    'grades.manage',
                    'finance.manage',
                    'payroll.manage',
                    'employee_attendance.manage',
                    'leave.manage',
                    'reports.view',
                    'audit.view',
                ],
            ],
            'principal' => [
                'name' => 'Principal',
                'permissions' => [
                    'academic_years.manage',
                    'academic_classes.manage',
                    'sections.manage',
                    'subjects.manage',
                    'class_subjects.manage',
                    'student_groups.manage',
                    'shifts.manage',
                    'designations.manage',
                    'employees.manage',
                    'students.manage',
                    'guardians.manage',
                    'enrollments.manage',
                    'teachers.manage',
                    'attendance.manage',
                    'exams.manage',
                    'exams.publish',
                    'marks.enter.any',
                    'grades.manage',
                    'employee_attendance.manage',
                    'leave.manage',
                    'reports.view',
                ],
            ],
            'teacher' => [
                'name' => 'Teacher',
                'permissions' => ['students.manage', 'attendance.manage', 'exams.manage', 'marks.enter.own', 'reports.view'],
            ],
            'accountant' => [
                'name' => 'Accountant',
                'permissions' => ['students.manage', 'guardians.manage', 'finance.manage', 'payroll.manage', 'reports.view'],
            ],
            'student' => [
                'name' => 'Student',
                'permissions' => ['reports.view'],
            ],
            'parent' => [
                'name' => 'Parent',
                'permissions' => ['reports.view'],
            ],
            'read-only-auditor' => [
                'name' => 'Read-only Auditor',
                'permissions' => ['reports.view', 'audit.view'],
            ],
        ];

        foreach ($roles as $key => $definition) {
            $role = Role::query()->updateOrCreate(
                ['school_id' => null, 'key' => $key],
                [
                    'name' => $definition['name'],
                    'description' => "System role: {$definition['name']}",
                    'is_system' => true,
                ]
            );

            $role->permissions()->sync(
                $permissionModels
                    ->only($definition['permissions'])
                    ->pluck('id')
                    ->all()
            );
        }
    }
}
