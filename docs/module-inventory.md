# Legacy Module Inventory

## Legacy Stack

The cloned reference app is a Laravel 8 application using Jetstream, Livewire 2, Sanctum 2, Laravel Mix, Tailwind 3, Alpine, and `niklasravnsborg/laravel-pdf`.

Key evidence:

- `composer.json` requires `laravel/framework:^8.75`, `laravel/jetstream:^2.5`, `livewire/livewire:^2.5`, `laravel/sanctum:^2.11`.
- `package.json` uses Laravel Mix and Tailwind 3.
- `routes/api.php` only exposes `/api/user`; core functionality is web/Blade based.
- `routes/web.php` contains all main modules behind `auth` and `userpermission:*` middleware.

## Legacy Permission Groups

The old app uses `user_permissions` with broad string flags:

- `dashboard`
- `manage_profile`
- `setup_management`
- `student_management`
- `employee_management`
- `mark_management`
- `account_management`
- `result`
- `report`

The new app must replace this with normalized enterprise RBAC: roles, permissions, role permissions, school memberships, and user role assignments.

## Legacy Modules And New SaaS Mapping

| Legacy area | Legacy files/routes | New SaaS module |
| --- | --- | --- |
| Users | `UserController`, `userPremissionController`, `users`, `user_permissions` | identity users, school memberships, roles, permissions |
| Profile | `ProfileController`, Jetstream profile views | profile API, account settings, password management |
| Dashboard | `DashboardController` | role-aware SaaS dashboard APIs and Nuxt dashboards |
| Student classes | `StudentClassController`, `student_classes` | `academic_classes` scoped by `school_id` |
| Academic years | `StudentYearController`, `student_years` | `academic_years` scoped by school |
| Groups | `StudentGroupController`, `student_groups` | `student_groups` or `academic_groups` scoped by school |
| Sections | `StudentSectionController`, `student_sections` | `academic_sections` scoped by school and academic class |
| Shifts | `StudentShiftController`, `student_shifts` | `shifts` scoped by school |
| Fee categories | `FeeCategoryController`, `fee_categories` | `fee_categories` scoped by school |
| Fee amounts | `FeeCategoryAmountController`, `fee_category_amounts` | `fee_structures` or `fee_amounts` scoped by school/class |
| Exam types | `ExamTypeController`, `exam_types` | `exam_terms` or `exam_types` scoped by school |
| Subjects | `SchoolSubjectController`, `school_subjects` | `subjects` scoped by school |
| Assigned subjects | `AssignSubjectController`, `assign_subjects` | `class_subjects` with grading rules |
| Designations | `DesignationController`, `designations` | employee designations scoped by school |
| Routines | `RoutineController`, `routines` | class schedules/routines |
| Lessons | `LessonController`, `lessons` | timetable lessons with teacher/class/subject links |
| Calendar | `CalendarController`, `calendar` route | school calendar events |
| Student registration | `StudentRegistrationController`, `assign_students`, `users` | students, guardians, enrollments |
| Student promotion | registration promotion routes | enrollment history and promotion workflow |
| Registration fees | `RegistrationFeeController` | invoice generation for admission/registration fees |
| Monthly fees | `MonthlyFeeController` | recurring student invoices |
| Exam fees | `ExamFeeController` | exam fee invoices |
| Student attendance | `StudentAttendanceController`, `student_attendances` | student attendance records |
| Employee registration | `EmployeeRegistrationController`, `users` | employees and teacher profiles |
| Employee salary | `EmployeeSalaryController`, `employee_salary_logs` | salary profiles and salary history |
| Employee leave | `EmployeeLeaveController`, `employee_leaves` | leave requests/records |
| Employee attendance | `EmployeeAttendanceController`, `employee_attendances` | employee attendance records |
| Monthly salary | `EmployeeMonthlySalaryController`, `account_employee_salaries` | payroll runs and payslips |
| Marks | `MarksController`, `student_marks` | marks entries by exam/class/subject/student |
| Grades | `GradeController`, `marks_grades` | grading scales |
| Student fees | `StudentFeeController`, `account_student_fees` | payments and invoice allocations |
| Other accounts | `OtherAccountController`, `other_account_costs` | expenses and income records |
| Profit reports | `ProfitController` | finance reports |
| Marksheet reports | `MarksheetController` | marksheet report API and PDF |
| Attendance reports | `AttendanceReportController` | attendance report API and PDF |
| Result reports | `ResultReportController` | result report API and PDF |
| Student ID cards | `StudentIdCardController` | student ID card PDF/export |

## Legacy Design Notes

The legacy app is a server-rendered Blade admin panel. It should not be visually preserved. The new public UI should port the Radiant template visual direction to Nuxt/Vue. The authenticated application should be a custom enterprise dashboard optimized for data-heavy workflows.

## Phase 1 Vertical Slice

The first implementation slice is Academic Classes because it is small, foundational, and validates the full architecture:

- tenant-scoped migration/model
- policy authorization
- request validation
- REST API
- audit logging
- Nuxt list/create/edit/delete UI
- backend tests and frontend build

Academic Sections now extend the backend academic setup slice with:

- tenant-scoped migration/model
- class ownership validation
- `sections.manage` policy authorization
- REST API nested below schools
- audit logging
- backend tests for CRUD, missing permission, and cross-school class rejection
