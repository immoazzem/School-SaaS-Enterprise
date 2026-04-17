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
            ['module' => 'academics', 'key' => 'student_groups.manage', 'description' => 'Manage student groups'],
            ['module' => 'academics', 'key' => 'shifts.manage', 'description' => 'Manage shifts'],
            ['module' => 'people', 'key' => 'students.manage', 'description' => 'Manage student records'],
            ['module' => 'people', 'key' => 'guardians.manage', 'description' => 'Manage guardian records'],
            ['module' => 'people', 'key' => 'teachers.manage', 'description' => 'Manage teacher records'],
            ['module' => 'attendance', 'key' => 'attendance.manage', 'description' => 'Manage attendance records'],
            ['module' => 'exams', 'key' => 'exams.manage', 'description' => 'Manage exams and results'],
            ['module' => 'finance', 'key' => 'finance.manage', 'description' => 'Manage fees and accounting'],
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
                    'student_groups.manage',
                    'shifts.manage',
                    'students.manage',
                    'guardians.manage',
                    'teachers.manage',
                    'attendance.manage',
                    'exams.manage',
                    'finance.manage',
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
                    'student_groups.manage',
                    'shifts.manage',
                    'students.manage',
                    'guardians.manage',
                    'teachers.manage',
                    'attendance.manage',
                    'exams.manage',
                    'reports.view',
                ],
            ],
            'teacher' => [
                'name' => 'Teacher',
                'permissions' => ['students.manage', 'attendance.manage', 'exams.manage', 'reports.view'],
            ],
            'accountant' => [
                'name' => 'Accountant',
                'permissions' => ['students.manage', 'guardians.manage', 'finance.manage', 'reports.view'],
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
