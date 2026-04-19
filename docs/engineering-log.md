# Engineering Log

Durable build log for the School SaaS Enterprise rebuild. Update this after each successful step or before ending a long Codex session.

## Checkpoint Rules

- Keep `docs/current-status.md` as the compact checkpoint.
- Keep `docs/session-context.md` as the low-token startup context.
- Add one entry here after each meaningful commit or phase slice.
- Include scope, verification, commit SHA, local environment notes, and next step.
- Before a context-heavy session ends, update this file plus the two checkpoint files above.
- Mention the current page/module in progress and phase completion notes.
- Use visible agent-browser checks during UI phases when the dev server is available.

## 2026-04-17

### `9ed9d92` - Legacy Audit Docs

Scope: legacy app audited and docs created for module inventory, database mapping, API contract, security, audit logs, local development, and enterprise plan.

Verification: documentation-only step.

Next: scaffold latest Laravel/Nuxt apps.

### `5778572` - Latest API And Web Scaffolds

Scope: Laravel API app in `apps/api`, Nuxt web app in `apps/web`, Laravel `v13.5.0`, PHP `8.5.5`, Nuxt `^4.4.2`.

Verification: initial Laravel and Nuxt scaffold checks passed.

Next: add enterprise backend foundation.

### `e65ef22` - Enterprise Backend Foundation

Scope: schools, memberships, roles, permissions, role assignments, audit logs, academic classes, token auth, schools API, and Academic Classes CRUD.

Verification: backend tests, Pint, and route list passed.

Next: seed RBAC and add audit writes.

### `b954a90` - RBAC Seeding And Class Audit

Scope: enterprise role/permission seeder and audit logging for Academic Class mutations.

Verification: backend tests, Pint, and route list passed.

Next: centralize active school membership guard.

### `2a9cf5b` - School Membership Guard

Scope: reusable `school.member` route middleware for nested school-owned routes.

Verification: backend tests, Pint, and route list passed.

Next: enforce permission policies for academic setup.

### `187da59` - Academic Class Permissions

Scope: Academic Class policy enforcing `academic_classes.manage`.

Verification: backend tests, Pint, and route list passed.

Next: assign owner role during school creation.

### `0aa0542` - Owner Role Assignment

Scope: school creation assigns seeded `school-owner` role to the creator.

Verification: backend tests, Pint, and route list passed.

Next: build first Nuxt authenticated app slice.

### `0437cf1` - Nuxt Auth Dashboard Shell

Scope: login page, dashboard shell, API/auth composables, Academic Classes workspace, and shared CSS.

Verification: `npm run build` passed.

Next: add route protection and tenant creation.

### `f573376` - Protected Routes And School Creation

Scope: Nuxt route protection, dashboard school creation, and active tenant selection.

Verification: `npm run build` passed.

Next: expose roles/permissions to Nuxt.

### `a3c03e7` - Permissions In Nuxt

Scope: `/api/me` returns per-school roles and permissions; dashboard navigation uses selected-school permissions.

Verification: `php artisan test` passed with 9 tests / 42 assertions; Pint passed.

Next: prepare Herd/MySQL environment templates.

### `d242503` - Herd MySQL Env Templates

Scope: API and web `.env.example` files updated for planned Herd/MySQL local development.

Verification: documentation/template-only step.

Next: link local API with Herd.

### `d091a0e` - Herd API Site Setup

Scope: linked `apps/api` in Herd as `https://school-api.test` on PHP 8.5.

Verification: API root returned 200; `/api/me` returned expected unauthenticated 401 JSON.

Next: smoke test live API slice.

### `b9d38f8` - Live API Smoke Test

Scope: live Herd API login, school creation, Academic Class creation/listing, and ignored Nuxt `.env` pointing to `https://school-api.test/api`.

Verification: live API smoke test passed; `npm run build` passed.

Next: start Phase 2 academic setup modules.

### `cd96952` - Academic Sections API

Scope: `academic_sections` migration, model, relationships, policy, controller, routes, same-school class validation, audit logs, and tests.

Verification: `php artisan test` passed with 12 tests / 60 assertions; Pint passed; route list showed 21 routes.

Local notes: applied local migration with `php artisan migrate` outside sandbox.

Next: add Nuxt Academic Sections workspace.

### `f25b6a0` - Nuxt Sections Workspace

Scope: `/schools/{schoolId}/academic-sections`, class filter, create/edit/archive flows, and dashboard navigation gated by `sections.manage`.

Verification: `npm run build` passed.

Next: add Academic Years backend.

### `2bfa94a` - Academic Years API

Scope: `academic_years` migration, model, relationship, permission, policy, controller, routes, tests, one-current-year-per-school behavior, and audit logs.

Verification: `php artisan test` passed with 15 tests / 78 assertions; Pint passed; route list showed 26 routes.

Local notes: applied local migration with `php artisan migrate`; refreshed local RBAC with `php artisan db:seed --class=EnterpriseRolePermissionSeeder`.

Next: add Nuxt Academic Years workspace and port the Radiant template into public/login pages as a dedicated visual pass.

### Tooling Checkpoint

Scope: installed `agent-browser@0.26.0` globally and added this engineering log.

Verification: `agent-browser --version` returned `agent-browser 0.26.0`.

Local notes: ran `agent-browser install`; Chrome `147.0.7727.57` was installed under `C:\Users\Moazzem\.agent-browser\browsers`.

Browser smoke check:
- Opened `http://127.0.0.1:3000/`.
- Confirmed the page was nonblank and rendered the login screen text.
- `agent-browser errors` returned no page errors.

Next: use `agent-browser` after starting dev servers for visual verification.

### `35be5b6` - Academic Years Frontend Workspace

Current page/module: Academic Years frontend workspace, route `/schools/{schoolId}/academic-years`.

Scope: added Nuxt Academic Years workspace with list filters, create/edit form, set-current action, archive action, typed `AcademicYear` API interface, and navigation from dashboard/classes/sections. Added explicit Laravel CORS config for local Nuxt origins and project `agent-browser.json` for headed browser checks with local Herd HTTPS certificates.

Verification: `php artisan test` passed with 15 tests / 78 assertions; `vendor\bin\pint --test` passed; `npm run build` passed with the existing Nuxt/Nitro warnings. Agent-browser confirmed the login page renders at `http://127.0.0.1:3000/`; authenticated automation needs the new browser config or a restarted browser session because the first run hit local Herd HTTPS fetch handling.

Next: push this phase, then start the Subjects module.

### Subjects API And Nuxt Workspace

Current page/module complete: Subjects API and Nuxt workspace, route `/schools/{schoolId}/subjects`.

Scope: added `subjects` migration/model/relationship, permission, policy, controller, tenant-scoped routes, tests, audit logs, dashboard navigation, typed Nuxt `Subject` API shape, and a Nuxt Subjects workspace with status/type/search filters plus create/edit/archive flows.

Verification: `php artisan test` passed with 17 tests / 95 assertions; `vendor\bin\pint --test` passed; `php artisan route:list --path=subjects` showed the Subjects routes; `npm run build` passed with existing Nuxt/Nitro warnings; local migration and RBAC seeding passed; agent-browser reached the Subjects page and verified creating `Mathematics / MATH-101` through the live Herd API.

Next: commit and push this checkpoint, then continue Phase 2 with Student Groups and Shifts.

### Student Groups And Shifts Workspace

Current page/module complete: Student Groups and Shifts API/Nuxt workspaces, routes `/schools/{schoolId}/student-groups` and `/schools/{schoolId}/shifts`.

Scope: added `student_groups` and `shifts` migrations/models/relationships, permissions, policies, controllers, tenant-scoped routes, tests, audit logs, dashboard navigation, typed Nuxt `StudentGroup` and `Shift` API shapes, and Nuxt workspaces with status/search filters plus create/edit/archive flows.

Verification: `php artisan test` passed with 21 tests / 126 assertions; `vendor\bin\pint --test` passed; `php artisan route:list` showed 41 routes; `npm run build` passed with existing Nuxt/Nitro warnings; local migrations and RBAC seeding passed; agent-browser verified creating `Science Group / SCI-01` and `Morning Shift / MOR-01` through the live Herd API.

Next: commit and push this checkpoint, then continue Phase 2 with Class Subject Assignments.

### Class Subject Assignments Workspace

Current page/module complete: Class Subject Assignments API/Nuxt workspace, route `/schools/{schoolId}/class-subjects`.

Scope: added `class_subjects` migration/model/relationship, permission, policy, controller, tenant-scoped routes, tests, audit logs, dashboard navigation, typed Nuxt `ClassSubject` API shape, and a Nuxt workspace with class/subject filters, mark rules, and create/edit/archive flows.

Verification: `php artisan test` passed with 24 tests / 145 assertions; `vendor\bin\pint --test` passed; `php artisan route:list --path=class-subjects` showed the assignment routes; `npm run build` passed with existing Nuxt/Nitro warnings; local migration and RBAC seeding passed; agent-browser verified assigning `Mathematics / MATH-101` to `Class One` with `40 / 100` marks through the live Herd API.

Next: commit and push this checkpoint, then continue Phase 2 with Designations.

### Designations Workspace

Current page/module complete: Designations API/Nuxt workspace, route `/schools/{schoolId}/designations`.

Scope: added `designations` migration/model/relationship, `designations.manage` permission, policy, controller, tenant-scoped routes, tests, audit logs, dashboard navigation, typed Nuxt `Designation` API shape, and a Nuxt workspace with status/search filters plus create/edit/archive flows.

Verification: `php artisan test` passed with 26 tests / 160 assertions; `php artisan test --filter=Designation` passed with 2 tests / 14 assertions; `vendor\bin\pint --test` passed; `php artisan route:list --path=designations` showed the designation routes; `npm run build` passed with existing Nuxt/Nitro warnings; local migration and RBAC seeding passed; agent-browser verified creating `Senior Teacher / SNR-TCHR` through the live Herd API.

Next: commit and push this checkpoint, then continue Phase 2 with Employees.

### Employees Workspace

Current page/module complete: Employees API/Nuxt workspace, route `/schools/{schoolId}/employees`.

Scope: added `employees` migration/model/relationship, `employees.manage` permission, policy, controller, tenant-scoped routes, tests, audit logs, dashboard navigation, typed Nuxt `Employee` API shape, and a Nuxt workspace with status/type/designation/search filters plus create/edit/archive flows.

Verification: `php artisan test` passed with 29 tests / 180 assertions; `php artisan test --filter=Employee` passed with 3 tests / 19 assertions; `vendor\bin\pint --test` passed; `php artisan route:list --path=employees` showed the employee routes; `npm run build` passed with existing Nuxt/Nitro warnings; local migration and RBAC seeding passed; agent-browser verified creating `Amina Rahman / EMP-2026-0001` through the live Herd API.

Next: commit and push this checkpoint, then continue Phase 2 with Students and Guardians.

### Students And Guardians Workspace

Current page/module complete: Students and Guardians API/Nuxt workspace, route `/schools/{schoolId}/students`.

Scope: added `guardians` and `students` migrations/models/relationships, `guardians.manage` and `students.manage` policy checks, tenant-scoped controllers/routes, tests, audit logs, dashboard navigation, typed Nuxt `Guardian` and `Student` API shapes, and a combined Nuxt workspace for guardian and student create/edit/archive flows.

Verification: `php artisan test` passed with 34 tests / 214 assertions; `vendor\bin\pint --test` passed; `php artisan migrate` and RBAC seeding passed locally; `php artisan route:list --path=students` and `php artisan route:list --path=guardians` showed the new routes; `npm run build` passed with existing Nuxt/Nitro warnings; agent-browser verified creating `Karim Rahman` and `Nadia Rahman / ADM-2026-0001` through the live Herd API. Browser screenshot saved at `docs/browser-checks/students-workspace.png`.

Next: commit and push this checkpoint, then continue Phase 2 with Enrollments.

### Enrollments Workspace

Current page/module complete: Enrollments API/Nuxt workspace, route `/schools/{schoolId}/enrollments`.

Scope: added `student_enrollments` migration/model/relationships, `enrollments.manage` permission and policy, tenant-scoped controller/routes, tests, audit logs, dashboard navigation, typed Nuxt `StudentEnrollment` API shape, and a Nuxt workspace for placing students into academic year, class, optional section, group, shift, roll number, and status.

Verification: `php artisan test` passed with 37 tests / 233 assertions; `vendor\bin\pint --test` passed; `php artisan migrate`, RBAC seeding, and `php artisan route:list --path=student-enrollments` passed; `npm run build` passed with existing Nuxt/Nitro warnings; agent-browser verified enrolling `Nadia Rahman / ADM-2026-0001` into `Class One` with roll `12` through the live Herd API. Browser screenshot saved at `docs/browser-checks/enrollments-workspace.png`.

Next: commit and push this checkpoint, then continue Phase 2 with Teacher Profiles foundation.

### Teacher Profiles Workspace

Current page/module complete: Teacher Profiles API/Nuxt workspace, route `/schools/{schoolId}/teacher-profiles`.

Scope: added `teacher_profiles` migration/model/relationship, `teachers.manage` policy, tenant-scoped controller/routes, tests, audit logs, dashboard navigation, typed Nuxt `TeacherProfile` API shape, and a Nuxt workspace for connecting employee records to teaching profiles.

Verification: `php artisan test` passed with 40 tests / 251 assertions; `vendor\bin\pint --test` passed; `php artisan migrate`, RBAC seeding, and `php artisan route:list --path=teacher-profiles` passed; `npm run build` passed with existing Nuxt/Nitro warnings; agent-browser verified creating `Amina Rahman / TCHR-2026-0001` through the live Herd API. Browser screenshot saved at `docs/browser-checks/teacher-profiles-workspace.png`.

Phase 2 status: complete for the current academic setup and people foundation.

Next: commit and push this checkpoint, then start Phase 3 Attendance.

### Attendance Workspace

Current page/module complete: Attendance API/Nuxt workspace, route `/schools/{schoolId}/attendance`.

Scope: added `student_attendance_records` migration/model/relationship, `attendance.manage` policy, tenant-scoped controller/routes, tests, audit logs, dashboard navigation, typed Nuxt `StudentAttendanceRecord` API shape, and a Nuxt daily attendance workspace for active enrollments with date/status/search filters, create/edit/delete flows, and status summaries.

Verification: `php artisan test` passed with 43 tests / 274 assertions; `vendor\bin\pint --test` passed; `php artisan migrate`, RBAC seeding, and `php artisan route:list --path=student-attendance-records` passed; `npm run build` passed with existing Nuxt/Nitro warnings; agent-browser opened `http://127.0.0.1:3000/schools/1/attendance`, confirmed no error overlay and nonblank content, then verified creating `Nadia Rahman / ADM-2026-0001` as `Present` on `2026-04-18` through the live Herd API. Browser screenshot saved at `docs/browser-checks/attendance-workspace.png`.

Phase 3 status: Attendance foundation complete.

Next: commit and push this checkpoint, then continue Phase 3 with Exams.

### Phase 3.0 Stabilization

Current page/module complete: Phase 3.0 Stabilization foundation, before Exams.

Scope: accepted `docs/enterprise-plan-v3.md` as the active enterprise plan, added a shared `AuditLogger` service, centralized controller audit logging, added capped pagination helpers, paginated all index API endpoints without changing the frontend-facing top-level `data` list shape, added School show/update endpoints with `schools.manage` enforcement and audit logging, and added named auth/API rate limiters.

Verification: `php artisan test` passed with 47 tests / 293 assertions; `vendor\bin\pint --test` passed; `php artisan route:list --path=api/schools` passed and showed School index/store/show/update routes; `npm run build` passed with the existing Nuxt/Nitro warnings; agent-browser opened `http://127.0.0.1:3000/dashboard`, confirmed nonblank content and no error overlay, opened Academic Classes, and saved screenshots at `docs/browser-checks/phase-3-stabilization-home.png` and `docs/browser-checks/phase-3-stabilization-academic-classes.png`.

MySQL note: local MySQL is running on `3306`, and the client exists at `C:\Program Files\MySQL\MySQL Server 8.0\bin\mysql.exe`, but `root` without a password is rejected with `ERROR 1045`. The API `.env` remains on SQLite until credentials are supplied or a passwordless/dev MySQL user is created.

Phase 3 status: Phase 3.0 Stabilization complete except the credential-blocked local MySQL switch.

Next: commit and push this checkpoint, then continue Phase 3 with Exams.

### MySQL Local Switch

Current page/module complete: Phase 3.0 MySQL local switch.

Scope: created/confirmed the local MySQL database `school_saas_enterprise`, switched ignored local `apps/api/.env` from SQLite to MySQL, and verified the Laravel API through Herd against MySQL.

Verification: MySQL accepted the provided root password and reported version `8.0.45`; `php artisan migrate:fresh --seed` passed against MySQL; `php artisan test` passed with 47 tests / 293 assertions; `vendor\bin\pint --test` passed; `php artisan route:list --path=api/schools` passed; live Herd API smoke passed for login, school creation, and paginated school listing; agent-browser login reached `http://127.0.0.1:3000/dashboard` and confirmed the MySQL-created school was visible. Browser screenshot saved at `docs/browser-checks/mysql-dashboard.png`.

Local notes: MySQL client path is `C:\Program Files\MySQL\MySQL Server 8.0\bin\mysql.exe`; local API `.env` uses `DB_CONNECTION=mysql`, database `school_saas_enterprise`, username `root`, and the user-provided local password.

Phase 3 status: Phase 3.0 Stabilization fully complete.

Next: commit and push this checkpoint, then continue Phase 3 with Exams.

### Exam Foundation API

Current page/module complete: Phase 3 Exams foundation API, routes `/api/schools/{school}/exam-types`, `/api/schools/{school}/exams`, and `/api/schools/{school}/exam-schedules`.

Scope: followed `docs/enterprise-plan-v3.md` Phase 3A by adding exam type weightage, exam publication fields, and class-subject exam schedules. Added migrations, models, school relationships, policies, tenant-scoped controllers, routes, audit logs, seeded `exams.publish`, and feature tests for CRUD, pagination, default unpublished exams, eager-loaded schedule relationships, and cross-school rejection.

Verification: `php artisan test` passed with 49 tests / 320 assertions; `vendor\bin\pint --test` passed; `php artisan route:list --path=api/schools --except-vendor` passed and showed 89 routes; `php artisan migrate:fresh --seed` passed against MySQL; live Herd API smoke passed for exam type, exam, and exam schedule creation plus paginated schedule listing.

Phase 3 status: Exam foundation API complete.

Next: commit and push this checkpoint, then add the Nuxt Exams workspace.

### Nuxt Exams Workspace

Current page/module complete: Nuxt Exams workspace, route `/schools/{schoolId}/exams`.

Scope: added typed Nuxt API shapes for exam types, exams, and exam schedules; added a combined Exams workspace for weighted exam types, exam windows, and class-subject schedules; added dashboard navigation and action button gated by `exams.manage`.

Verification: `npm run build` passed with existing Nuxt/Nitro warnings; agent-browser logged in, opened the dashboard, verified the Exams navigation item, opened `http://127.0.0.1:3000/schools/1/exams`, confirmed no error overlay, and confirmed live MySQL data rendered (`Midterm 2026`, `Room 101`). Browser screenshot saved at `docs/browser-checks/exams-workspace.png`.

Phase 3 status: Exams foundation API and Nuxt workspace complete.

Next: commit and push this checkpoint, then continue Phase 3 with Marks Entry.

### Phase 3 Operations Backend

Current page/module complete: Phase 3 Operations backend APIs.

Scope: followed `docs/enterprise-plan-v3.md` for Phase 3B-3I backend by adding schemas/models/routes/controllers/services for marks entry, grade scales, fee categories/structures, discount policies, student discounts, student invoices, invoice payments, salary records, employee attendance, leave types, leave balances, leave applications, and enhanced bulk student attendance.

Key implementation notes:
- Marks entry stores absent separately from zero marks and sources `full_marks`/`pass_marks` from `class_subjects`.
- Student invoices compute discounts from active student discount policies and expose bulk invoice generation through a queued job.
- Salary records compute gross, deductions, and net amounts in `SalaryService`.
- Employee attendance uses upsert behavior by employee/date.
- Leave approval decrements balance and creates `on_leave` employee attendance records; cancellation restores approved leave balance.
- Student attendance now supports `late_arrival_time`, `half_day`, `leave_reference`, and bulk upsert entry.
- Seeded v3 permissions: `marks.enter.own`, `marks.enter.any`, `grades.manage`, `payroll.manage`, `employee_attendance.manage`, and `leave.manage`.

Verification: `php artisan migrate:fresh --seed` passed against MySQL; `php artisan test` passed with 53 tests / 370 assertions; `vendor\bin\pint --test` passed; `php artisan route:list --path=api/schools --except-vendor` passed and showed 161 school routes.

Phase 3 status: Backend APIs are complete for Exams, Marks, Grades, Finance, Payroll, Employee Attendance, Leave, and enhanced Student Attendance. Nuxt workspace coverage still needs to be added for Marks/Grades, Finance, Payroll/Leave/Employee Attendance.

Next: commit and push this backend checkpoint, then add the remaining Phase 3 Nuxt workspaces and browser checks.

### Phase 3 Operations Nuxt Workspaces

Current page/module complete: Phase 3 Operations Nuxt workspaces.

Scope: added typed Nuxt API shapes and operational screens for:
- Marks and Grades at `/schools/{schoolId}/marks`
- Finance at `/schools/{schoolId}/finance`
- Staff Operations at `/schools/{schoolId}/staff-operations`

Implemented dashboard navigation/action buttons for Marks, Finance, and Staff Ops, with permissions gating for `marks.enter.any`, `marks.enter.own`, `finance.manage`, `payroll.manage`, `employee_attendance.manage`, and `leave.manage`.

Verification: `npm run build` passed with the existing Nuxt/Nitro warnings. Agent-browser opened:
- `http://127.0.0.1:3000/schools/1/marks`
- `http://127.0.0.1:3000/schools/1/finance`
- `http://127.0.0.1:3000/schools/1/staff-operations`

No Vite error overlay appeared, each page rendered its expected workspace heading, and screenshots were saved at:
- `docs/browser-checks/marks-workspace.png`
- `docs/browser-checks/finance-workspace.png`
- `docs/browser-checks/staff-operations-workspace.png`

Phase 3 status: complete for backend APIs, Nuxt workspaces, build, and browser smoke verification. Teacher-specific `marks.enter.own` assignment enforcement remains intentionally deferred until a teacher-to-class-subject assignment model is introduced.

Checkpoint: committed locally as `feat: add phase 3 operations workspaces`. Push to GitHub failed because `github.com:443` was unreachable from the machine after repeated attempts; `master` is one commit ahead of `origin/master`.

Next: push the local commit when GitHub connectivity returns, then begin Phase 4 reports, result publication, PDFs, calendar, notifications, and analytics.

### Phase 4 Result Publication and Reporting Backend Foundation

Current page/module complete: Phase 4 Result Publication and Reports backend foundation.

Scope: followed `docs/enterprise-plan-v3.md` with the v2 baseline now tracked in `docs/enterprise-plan-v2.md`. Added the first Phase 4 backend slice:
- `result_summaries` cache table for published exam totals, percentages, GPA, grade, pass/fail, and class position.
- In-app notification tables and models, with SMS log scaffolding for the optional Phase 4 communication add-on.
- Result publication service and `POST /api/schools/{school}/exams/{exam}/publish`, requiring `exams.publish`, setting publication fields, recomputing summaries, auditing `result.published`, and notifying active school members.
- Result summaries endpoint at `GET /api/schools/{school}/exams/{exam}/result-summaries`, protecting unpublished results unless the actor has `exams.manage`.
- Employee attendance summary endpoint at `GET /api/schools/{school}/attendance/employee-summary`.
- Notification inbox endpoints for list, unread count, and mark-read.

Verification: targeted `php artisan test --filter=PhaseFourReportingApiTest` passed with 4 tests / 26 assertions; full `php artisan test` passed with 57 tests / 396 assertions; `php artisan migrate:fresh --seed` passed against MySQL; `vendor\bin\pint --test` passed; `php artisan route:list --path=api/schools --except-vendor` passed and showed 167 school routes.

Phase 4 status: backend foundation started and checkpointed for result publication, result summary reports, employee attendance summary, and in-app notifications. Remaining Phase 4 work includes PDFs, school calendar, richer notification hooks, document management, Nuxt report/publication workspaces, browser checks, and analytics.

Checkpoint: committed and pushed as `8e6bc3a feat: add phase 4 reporting foundation`.

Next: continue with Phase 4 PDFs/calendar/notification hooks or the Nuxt reports workspace.

### Phase 4 Calendar and Notification Hooks Backend

Current page/module complete: Phase 4 Calendar and Notification Hooks backend.

Scope: continued `docs/enterprise-plan-v3.md` with the v2 baseline now tracked in `docs/enterprise-plan-v2.md`. Added:
- `calendar_events` with optional academic year, optional class scope, holiday flag, RRULE storage, creator tracking, soft deletes, and tenant indexes.
- Calendar CRUD routes under `/api/schools/{school}/calendar-events`.
- Holiday bulk import route at `/api/schools/{school}/calendar-events/bulk-import-holidays`.
- Seeded `calendar.manage` permission for owner/admin/principal-style management.
- In-app `payment.received` notifications for matched student/guardian user accounts using active school membership and email matching.
- In-app `leave.approved` and `leave.rejected` notifications for matched employee user accounts using active school membership and email matching.

Verification: targeted `php artisan test --filter=PhaseFourReportingApiTest` passed with 7 tests / 40 assertions; full `php artisan test` passed with 60 tests / 410 assertions; `php artisan migrate:fresh --seed` passed against MySQL; `vendor\bin\pint --test` passed; `php artisan route:list --path=api/schools --except-vendor` passed and showed 173 school routes.

Phase 4 status: result publication, cached summaries, employee attendance summary, in-app notification inbox, school calendar backend, holiday import, and payment/leave notification hooks are implemented. Remaining Phase 4 work includes PDFs, document management, dashboard analytics, Nuxt report/calendar/publication workspaces, browser checks, and richer recipient mapping once student/parent/employee account ownership models are introduced.

Checkpoint: committed and pushed as `ae0fe9f feat: add phase 4 calendar notifications`.

Next: continue with PDFs, document management, analytics, or the Nuxt Phase 4 workspace.

### Phase 4 Document Management Backend

Current page/module complete: Phase 4 Document Management backend.

Scope: continued `docs/enterprise-plan-v3.md` with `docs/enterprise-plan-v2.md` as the v2 baseline. Added:
- `docs/enterprise-plan-v2.md` as the explicit v2 reference used by v3.
- `school_documents` with category, uploader, stored file metadata, public/private visibility, optional related model reference, upload timestamp, and soft deletes.
- Document upload/list/show/delete routes under `/api/schools/{school}/documents`.
- Signed document download route at `/api/schools/{school}/documents/{document}/download`.
- `PlanLimitService` storage check using `school.settings.plan_limits.max_storage_mb` until formal Phase 5 plan columns land.
- Seeded `documents.manage` permission for owner/admin/principal-style document management.

Verification: targeted `php artisan test --filter=PhaseFourReportingApiTest` passed with 9 tests / 55 assertions; full `php artisan test` passed with 62 tests / 425 assertions; `php artisan migrate:fresh --seed` passed against MySQL; `vendor\bin\pint --test` passed; `php artisan route:list --path=api/schools --except-vendor` passed and showed 178 school routes.

Phase 4 status: result publication, cached summaries, employee attendance summary, in-app notification inbox, school calendar backend, holiday import, payment/leave notification hooks, and document management backend are implemented. Remaining Phase 4 work includes PDFs, dashboard analytics, Nuxt report/calendar/publication/document workspaces, browser checks, and richer recipient mapping once account ownership models are introduced.

Next: commit and push this Phase 4 document checkpoint, then continue with PDFs, dashboard analytics, or the Nuxt Phase 4 workspace.

### Phase 4 Reports, PDFs, and Dashboard Analytics Backend

Current page/module complete: Phase 4 Reports, PDFs, and Dashboard Analytics backend.

Scope: continued `docs/enterprise-plan-v3.md` with `docs/enterprise-plan-v2.md` as the v2 baseline. Added:
- `report_exports` with job id, requester, school, report type, target morph, parameters, status, signed file metadata, completion timestamp, and failure details.
- `ReportExport` model and `School::reportExports()` relationship.
- `GenerateReportJob` using `barryvdh/laravel-dompdf` to render queued PDF exports into local storage.
- Generic Blade PDF view for official school report exports.
- Report export endpoints for marksheets, result sheets, ID cards, invoices, and salary records.
- Report download polling endpoint that returns status plus a signed download URL once the export is complete.
- Signed report file endpoint that keeps storage paths hidden.
- V2-compatible result aliases for exam results and marksheets.
- Student attendance summary endpoint with monthly present/absent/late/half-day counts and attendance percentage.
- Dashboard summary endpoint with admin, accountant, teacher, and auditor aggregates.
- Composer security maintenance by updating `phpunit/phpunit` from `12.5.21` to `12.5.23` after `composer audit` reported the advisory on the dev dependency.

Verification: `php artisan test` passed with 65 tests / 445 assertions; `vendor\bin\pint --test` passed; `composer audit` passed with no advisories; `php artisan route:list --path=api/schools --except-vendor` passed and showed 190 school routes; `php artisan migrate:fresh --seed` passed against MySQL.

Phase 4 status: backend is implemented for result publication, cached result summaries, employee/student attendance summaries, in-app notification inbox, school calendar and holidays, payment/leave notification hooks, document management, queued PDF report exports, signed report downloads, and dashboard analytics. Remaining Phase 4 work is the Nuxt Reports, Calendar, Documents, and Publication workspace plus build/browser verification.

Next: commit and push this backend checkpoint, then finish the Phase 4 Nuxt workspace.

### Phase 4 Nuxt Reports, Calendar, and Documents Workspaces

Current page/module complete: Phase 4 Nuxt Reports, Calendar, and Documents workspaces.

Scope: finished the Phase 4 user-facing operations layer:
- Added typed Nuxt API shapes for result summaries, report exports, report download polling, dashboard summary aggregates, calendar events, school documents, and attendance summary rows.
- Added `/schools/{schoolId}/reports` for dashboard analytics, result publication, result summary review, attendance summaries, and PDF export queueing.
- Added `/schools/{schoolId}/calendar` for event entry, school/class scoped calendar records, holiday counts, and holiday bulk import.
- Added `/schools/{schoolId}/documents` for document upload, visibility filtering, file metadata review, and signed download link retrieval.
- Updated the dashboard navigation/action strip with Reports, Calendar, and Documents entries.
- Added shared textarea styling and FormData-compatible API request body typing for document uploads.
- Guarded empty-state actions so reports are not queued without required exam/student data and holiday imports require an academic year.

Verification: `npm run build` passed with the existing Nuxt/Nitro warnings. A visible agent-browser run logged in, created a local browser-check school after the MySQL refresh, opened:
- `http://127.0.0.1:3000/schools/1/reports`
- `http://127.0.0.1:3000/schools/1/calendar`
- `http://127.0.0.1:3000/schools/1/documents`

Each page rendered nonblank workspace content with no Vite error overlay. Screenshots were saved at:
- `docs/browser-checks/phase-4-reports-workspace.png`
- `docs/browser-checks/phase-4-calendar-workspace.png`
- `docs/browser-checks/phase-4-documents-workspace.png`

Phase 4 status: complete for backend APIs, queued PDFs, analytics, result publication, notifications, calendar, document management, Nuxt workspaces, build, and browser smoke verification.

Next: commit and push this Phase 4 completion checkpoint, then begin Phase 5 SaaS administration and billing placeholders.

### Radiant UI Theme Correction

Current page/module complete: Radiant-inspired UI correction pass across the Nuxt frontend.

Scope: corrected the frontend visual direction to follow the `D:\Development\tailwindui-radiant\radiant-ts` theme reference more closely while keeping the app in Nuxt/Vue. Ported Radiant traits into the existing product UI:
- warm yellow/fuchsia/purple gradient plane
- subtle plus-grid style background lines
- black primary pill buttons
- translucent secondary controls with warm accent rings
- softer glass-like panels
- larger tight-tracked headings
- Radiant-inspired sidebar and workspace chrome
- old green color tokens removed from app source

Verification: `npm run build` passed with the existing Nuxt/Nitro warnings. Visible agent-browser screenshots were refreshed at:
- `docs/browser-checks/radiant-login-refresh.png`
- `docs/browser-checks/radiant-dashboard-refresh.png`
- `docs/browser-checks/radiant-reports-refresh.png`

Next: review the refreshed UI in the browser, then continue the next product phase.

### Phase 5 SaaS Administration Foundation

Current page/module complete: Phase 5 SaaS Administration backend foundation.

Scope: began Phase 5 from `docs/enterprise-plan-v3.md`, using `docs/enterprise-plan-v2.md` as the v2 baseline. Added:
- SaaS school columns for `plan`, `subscription_status`, `trial_ends_at`, and `plan_limits`.
- `SchoolSettings` value object with the v3 settings shape stored in `schools.settings`.
- `SchoolSettingsRequest` and `GET/PATCH /api/schools/{school}/settings`.
- `super.admin` middleware backed by `User::hasSystemRole('super-admin')`.
- Super-admin console endpoints for schools, onboarding, audit logs, users, system health, and system stats.
- School-scoped audit log viewer at `GET /api/schools/{school}/audit-logs`.
- Plan-limit checks for student and employee creation, plus document storage limits now read the new `plan_limits` column first.
- Seeded Phase 5 portal permissions `student.portal.view` and `parent.portal.view`.

Verification: `php artisan test --filter=PhaseFiveSaasAdminApiTest` passed with 5 tests / 24 assertions; full `php artisan test` passed with 70 tests / 469 assertions; `vendor\bin\pint --dirty` formatted the changed API routes; `php artisan migrate --force` applied the new Phase 5 SaaS migration to the local MySQL database.

Phase 5 status: backend foundation is started for v2 5A/5B/5C/5E and v3 5F. Remaining Phase 5 work includes invitation flows, parent/student portal endpoints, data export/right-to-erasure, self-hosted deployment docs, and backup/restore artisan commands.

Next: commit and push this Phase 5 foundation checkpoint, then continue with Phase 5 invitations and portal/data-export APIs.

### Phase 5 Invitation Flow

Current page/module complete: Phase 5 User Invitation backend flow.

Scope: added the v2 invitation system:
- `school_invitations` table with UUID token, email/name, invited/accepted users, role, status, expiry, and accepted timestamp.
- `SchoolInvitation` model and `School::invitations()` relationship.
- Tenant-scoped invitation management endpoints:
  - `POST /api/schools/{school}/invitations`
  - `GET /api/schools/{school}/invitations`
  - `DELETE /api/schools/{school}/invitations/{invitation}`
- Authenticated token accept endpoint:
  - `POST /api/invitations/{token}/accept`
- `users.manage` enforcement for invitation management.
- Accept flow that validates email ownership, rejects expired/revoked/non-pending tokens, activates school membership, assigns the invited role, and writes audit logs.

Verification: `php artisan test --filter=PhaseFiveSaasAdminApiTest` passed with 7 tests / 42 assertions; full `php artisan test` passed with 72 tests / 487 assertions; `vendor\bin\pint --dirty` formatted the invitation controller; `php artisan migrate --force` applied the invitation migration to local MySQL.

Phase 5 status: invitations are complete. Remaining Phase 5 work includes parent/student portal endpoints, data export/right-to-erasure, self-hosted deployment docs, and backup/restore artisan commands.

Next: commit and push this invitation checkpoint, then continue with parent/student portal endpoints.

### Phase 5 Parent and Student Portal Endpoints

Current page/module complete: Phase 5 Parent and Student Portal backend endpoints.

Scope: added the v3 portal endpoints using the current email-based links already present in the domain model:
- Student portal links authenticated users to `students.email`.
- Parent portal links authenticated users to `guardians.email` and the guardian's students.
- Portal access also respects `allow_student_portal` and `allow_parent_portal` from `SchoolSettings`.
- Added all v3 portal routes:
  - `GET /api/schools/{school}/portal/student/profile`
  - `GET /api/schools/{school}/portal/student/attendance`
  - `GET /api/schools/{school}/portal/student/results`
  - `GET /api/schools/{school}/portal/student/invoices`
  - `GET /api/schools/{school}/portal/student/notifications`
  - `GET /api/schools/{school}/portal/parent/children`
  - `GET /api/schools/{school}/portal/parent/children/{enrollment}/attendance`
  - `GET /api/schools/{school}/portal/parent/children/{enrollment}/results`
  - `GET /api/schools/{school}/portal/parent/children/{enrollment}/invoices`
  - `GET /api/schools/{school}/portal/parent/notifications`

Verification: `php artisan test --filter=PhaseFiveSaasAdminApiTest` passed with 9 tests / 64 assertions; full `php artisan test` passed with 74 tests / 509 assertions; `vendor\bin\pint --dirty` passed; `php artisan route:list --path=api/schools --except-vendor` confirmed the 10 portal routes.

Phase 5 status: SaaS admin foundation, invitations, and parent/student portal backend endpoints are complete. Remaining Phase 5 work includes data export/right-to-erasure, self-hosted deployment docs, and backup/restore artisan commands.

Next: commit and push this portal checkpoint, then continue with data export and right-to-erasure.

### Phase 5 Data Export and Right to Erasure

Current page/module complete: Phase 5 Data Export and Student Anonymization backend.

Scope: implemented v3 data export and right-to-erasure endpoints:
- `data_export_jobs` table and `DataExportJob` model.
- `School::dataExportJobs()` and `School::auditLogs()` relationships.
- `POST /api/schools/{school}/data-export/request` creates a completed JSON export artifact for the school.
- `GET /api/schools/{school}/data-export/{job_id}/download` downloads the generated JSON artifact.
- `POST /api/schools/{school}/students/{student}/anonymize` removes personal student fields, detaches guardian linkage, archives the student, and records `student.anonymized` audit logs.
- Export payload includes school profile, guardians, students with enrollments, employees, invoices with payments, document metadata, and optional capped audit logs.

Verification: `php artisan test --filter=PhaseFiveSaasAdminApiTest` passed with 11 tests / 77 assertions; full `php artisan test` passed with 76 tests / 522 assertions; `vendor\bin\pint --dirty` formatted changed imports; `php artisan migrate --force` applied the data export job migration to local MySQL.

Phase 5 status: SaaS admin foundation, invitations, parent/student portal endpoints, data export, and student anonymization are complete. Remaining Phase 5 work is self-hosted deployment docs plus backup/restore artisan commands.

Next: commit and push this data export checkpoint, then continue with self-hosted deployment and backup/restore commands.

### Phase 5 Self-Hosted Operations

Current page/module complete: Phase 5 Self-hosted deployment and backup/restore operations.

Scope: completed the v3 self-hosted operations requirement:
- Added `docs/self-hosted-deployment.md` covering runtime, API deploy, web deploy, queue worker, scheduler, environment, backups, and operational checklist.
- Added `php artisan school:backup` command.
- Added `php artisan school:backup --school={id}` tenant-filtered backup mode.
- Added `php artisan school:restore {archive}` with confirmation.
- Added `php artisan school:restore {archive} --force` for controlled automation.
- Backup archives are JSON files under `storage/app/backups` and use `school-saas-enterprise-backup-v1` format.

Verification: `php artisan list school` showed both commands; `php artisan school:backup --help` passed; `php artisan school:backup --school=1` wrote a local backup archive; `vendor\bin\pint --dirty` passed; full `php artisan test` passed with 76 tests / 522 assertions.

Phase 5 status: backend Phase 5 is complete for SaaS admin foundation, settings, plan limits, onboarding, audit viewer, invitations, parent/student portals, data export/right-to-erasure, self-hosted deployment docs, and backup/restore commands.

Next: commit and push this Phase 5 completion checkpoint, then begin the next planned phase or add Nuxt admin screens for the Phase 5 APIs.

### Phase 6 Promotion Backend Foundation

Current page/module complete: Phase 6 Student Promotion and Academic Year Transition backend foundation.

Scope: started Phase 6 from `docs/enterprise-plan-v3.md`:
- Added `promotion_batches` and `promotion_records`.
- Added `PromotionBatch` and `PromotionRecord` models.
- Added `School::promotionBatches()` relationship.
- Seeded `promotions.manage` permission for super-admin, school-owner, and school-admin flows.
- Added promotion endpoints:
  - `POST /api/schools/{school}/promotions/preview`
  - `POST /api/schools/{school}/promotions`
  - `PATCH /api/schools/{school}/promotions/{batch}/records/{record}`
  - `POST /api/schools/{school}/promotions/{batch}/execute`
  - `POST /api/schools/{school}/promotions/{batch}/rollback`
- Preview suggests `retained` when a student has a failed result summary and `promoted` otherwise.
- Execute creates new enrollments for promoted/retained students, marks old enrollments completed, stores new enrollment links, and audits `promotion.executed`.
- Rollback is available within the 48-hour window, deletes generated enrollments, reactivates old enrollments, clears generated links, and audits `promotion.rolled_back`.

Verification: `php artisan migrate --force` applied the promotion migration to local MySQL after shortening the MySQL index name; `php artisan test --filter=PhaseSixPromotionApiTest` passed with 3 tests / 25 assertions; `vendor\bin\pint --dirty` passed; full `php artisan test` passed with 79 tests / 547 assertions.

Phase 6 status: backend foundation is complete for preview, draft creation, record override, execute, rollback, and duplicate-execute protection. Remaining Phase 6 work is the Nuxt promotion workflow UI and deeper production hardening for large batches/job dispatch.

Next: commit and push this Phase 6 backend checkpoint, then build the Nuxt promotion workflow.

### Phase 6 Nuxt Promotion Workflow UI

Current page/module complete: Phase 6 Student Promotion workflow UI.

Scope: added the Nuxt promotion workflow for `promotions.manage` users:
- Added typed `PromotionAction`, `PromotionPreviewRow`, `PromotionRecord`, and `PromotionBatch` API shapes.
- Added dashboard navigation/action access for Promotions.
- Added `/schools/{schoolId}/promotions` using the existing Radiant-inspired operation shell.
- The page loads active academic years/classes, previews promotion candidates, creates draft batches, edits record actions/notes, executes batches, and rolls back completed batches through the Phase 6 backend endpoints.
- Added promotion summary metrics, source/target transition panel, draft controls, and preview/draft record tables.

Verification: `npm run build` from `apps/web` passed with the existing Nuxt/Nitro warnings; `php artisan db:seed --class=EnterpriseRolePermissionSeeder` refreshed local RBAC permissions; agent-browser logged in locally, opened `http://127.0.0.1:3000/schools/1/promotions`, confirmed no Vite/Nuxt error overlay, confirmed nonblank content, and saved `docs/browser-checks/promotions-workflow.png`.

Local smoke-test note: Herd Desktop was not running in this shell, so browser verification used PHP 8.5 directly with `php -S 127.0.0.1:8010 -t public public\index.php` and Nuxt pointed at `http://127.0.0.1:8010/api` through the ignored local `apps/web/.env`.

Phase 6 status: backend foundation and Nuxt promotion workflow UI are complete. Remaining Phase 6 work is deeper production hardening for large batches/job dispatch, stronger lifecycle guards, and richer seeded demo data for full browser execution tests.

Next: commit and push this Phase 6 UI checkpoint, then continue with Phase 6 hardening or the next v3 phase.

## 2026-04-19

### `933a920` - Production Stabilization Roadmap

Current page/module complete: Production Stabilization Checkpoint A.

Scope: added `docs/IMPROVEMENT-ROADMAP.md` as the repo-specific stabilization plan before Phase 7. The roadmap keeps `docs/enterprise-plan-v3.md` as active, treats `docs/enterprise-plan-v2.md` as the v2 reference when v3 mentions v2, and orders the work through frontend foundation, API versioning, env docs, Pinia migration, permission tests, PDF reliability, queue observability, and final review.

Verification: documentation-only checkpoint.

Next: complete frontend dependency and warning stabilization.

### Production Stabilization Frontend Foundation

Current page/module complete: Production Stabilization Checkpoint B, Frontend Foundation and Build Warning Classification.

Scope: updated the Nuxt frontend foundation before Phase 7:
- added Tailwind CSS module, Tailwind v3, Pinia, VueUse, Zod, PostCSS, and Autoprefixer dependencies.
- aligned Pinia to `^3.0.4` and `@pinia/nuxt` to `^0.11.3` after npm exposed Vue Router 5's optional Pinia 3 peer requirement.
- added Node engine metadata and `apps/web/.nvmrc` with `20.11.0`.
- configured Nuxt for SPA mode, Pinia, Tailwind module loading from `~/assets/css/main.css`, and the existing runtime API base.
- added `apps/web/tailwind.config.ts`.
- added Tailwind directives to the existing Radiant-inspired `main.css`.
- added `docs/KNOWN-BUILD-WARNINGS.md` with classifications for Nuxt/Nitro duplicated `useAppConfig`, Nuxt module-preload sourcemap, Node `DEP0155`, and transitive npm install deprecations.

Verification: `npm install` passed; `npm run build` from `apps/web` passed with exit code `0`. Tailwind now reports `Using Tailwind CSS from ~/assets/css/main.css`.

Local notes: the Codex shell currently reports Node `v25.0.0` and npm `11.11.0`; the project target is locked to Node `20.11.0` and npm `>=10.2.0`.

Next: commit and push this checkpoint, then continue with Production Stabilization Checkpoint C: API versioning to `/api/v1`.

### Production Stabilization API Versioning

Current page/module complete: Production Stabilization Checkpoint C, API Versioning to `/api/v1`.

Scope: moved the current Laravel API surface under a v1 route prefix and kept the frontend call sites stable:
- wrapped `apps/api/routes/api.php` in `Route::prefix('v1')`.
- kept login throttling and authenticated `auth:sanctum`/`throttle:api` behavior intact.
- versioned the legacy `/user` route as `/api/v1/user`.
- updated all feature-test request URLs from `/api/...` to `/api/v1/...`.
- updated `apps/web/app/composables/useApi.ts` to append `/v1` centrally.
- updated the login page API status text to show the effective `/api/v1` endpoint.
- documented the v1 compatibility and future v2 deprecation policy in `docs/api-contract.md`.
- added `docs/ARCHITECTURE.md` with the API version strategy.

Verification: `vendor\bin\pint --dirty` passed after formatting `routes/api.php`; `php artisan route:list --path=api/v1 --except-vendor` showed 228 versioned routes; `php artisan test` passed with 79 tests / 547 assertions; `npm run build` from `apps/web` passed after the API client change.

Next: commit and push this checkpoint, then continue with Production Stabilization Checkpoint D: environment examples and local-development docs.

### Production Stabilization Environment Docs

Current page/module complete: Production Stabilization Checkpoint D, Environment and Local Development cleanup.

Scope: made local setup clearer for the current Herd/MySQL/Nuxt shape:
- expanded `apps/web/.env.example` with `NUXT_PUBLIC_APP_NAME`.
- updated `apps/api/.env.example` to use the product app name while keeping database-backed queue/cache/session defaults.
- rewrote `docs/local-development.md` with prerequisites, Herd setup, MySQL setup, API v1 checks, direct PHP server fallback, Nuxt setup, seeded login, and backend/frontend quality gates.
- documented that `NUXT_PUBLIC_API_BASE` stays at `/api` and `useApi()` appends `/v1`.

Verification: documentation and env-template checkpoint. Previous Checkpoint C verification remains current: `php artisan test` passed with 79 tests / 547 assertions and `npm run build` passed.

Next: commit and push this checkpoint, then continue with Production Stabilization Checkpoint E: Pinia state migration.

### Production Stabilization Pinia State Migration

Current page/module complete: Production Stabilization Checkpoint E, Pinia State Migration.

Scope: introduced Pinia as the frontend state layer without breaking existing page contracts:
- added `apps/web/app/stores/auth.ts` with token, user, schools, selected school, selected school computed state, permissions, `can()`, login, profile refresh, school refresh/create, school selection, and logout.
- added `apps/web/app/stores/school.ts` as a small shared school list/loading/error store.
- added `apps/web/app/stores/index.ts` as a store directory marker without duplicate auto-import exports.
- migrated `useAuth()` to delegate to `useAuthStore()` and return the same refs/actions existing pages use.
- kept the same Nuxt `useState` keys so `useApi()` still reads the active bearer token correctly.

Verification: `npm run build` from `apps/web` passed. A new app-caused duplicated store auto-import warning was found and fixed by importing the auth store directly from `~/stores/auth` and keeping `stores/index.ts` free of duplicate `use*Store` exports. Remaining frontend warnings match `docs/KNOWN-BUILD-WARNINGS.md`.

Next: commit and push this checkpoint, then continue with Production Stabilization Checkpoint F: permission and tenant isolation tests.

### Production Stabilization Permission Isolation Tests

Current page/module complete: Production Stabilization Checkpoint F, Permission and Tenant Isolation Tests.

Scope: added dedicated backend isolation coverage before Phase 7:
- created `apps/api/tests/Feature/PermissionIsolationTest.php` in the existing PHPUnit feature-test style.
- added helpers that seed the enterprise RBAC baseline, create schools, active/inactive memberships, and role assignments through the real model relationships.
- covered cross-tenant denial for academic sections, students, invoices, school audit logs, and employees.
- covered same-school missing-permission denial for section creation, invoice creation, and exam publication.
- covered inactive membership denial and unauthenticated school-resource access.
- added a compact permission matrix for school owner, teacher, and accountant permissions.

Verification: `php artisan test --filter=PermissionIsolation` passed with 12 tests / 26 assertions; `vendor\bin\pint --dirty` passed; full `php artisan test` passed with 91 tests / 573 assertions.

Next: continue with Production Stabilization Checkpoint G: PDF rendering reliability.

### Production Stabilization PDF Rendering Reliability

Current page/module complete: Production Stabilization Checkpoint G, PDF Rendering Reliability.

Scope: hardened queued report PDFs before Phase 7:
- added dedicated Blade views for marksheets and invoice receipts.
- updated `GenerateReportJob` to route `marksheet` and `invoice-receipt` exports to dedicated views while preserving `reports.generic` fallback for other report types.
- enriched marksheet payloads with enrollment, student, class, section, academic year, exam, subject marks, and result summary data.
- enriched invoice payloads with student, class, academic year, invoice totals, and payment history data.
- added retry/backoff settings to `GenerateReportJob`.
- added `apps/api/tests/Feature/PdfGenerationTest.php` covering marksheet PDF generation, invoice PDF generation with payments, failed export state for missing required data, and report permission denial.

Verification: `php artisan test --filter=PdfGeneration` passed with 4 tests / 12 assertions; `vendor\bin\pint --dirty` fixed formatting and passed; full `php artisan test` passed with 95 tests / 585 assertions.

Next: continue with Production Stabilization Checkpoint H: background job observability.

### Production Stabilization Background Job Observability

Current page/module complete: Production Stabilization Checkpoint H, Background Job Observability.

Scope: added queue visibility and retry controls before Phase 7:
- confirmed the default Laravel jobs migration already creates `jobs`, `job_batches`, and `failed_jobs`.
- changed database and Redis queue connections to run after transaction commit.
- added retry policy to `BulkGenerateStudentInvoices` with 3 tries and 60-second backoff.
- added `App\Http\Controllers\Api\Admin\JobStatusController`.
- added super-admin routes for `GET /api/v1/admin/jobs/status` and `POST /api/v1/admin/jobs/{id}/retry`.
- implemented retry through Laravel's `queue:retry` Artisan command instead of manually copying payloads back into `jobs`.
- added `apps/api/tests/Feature/JobObservabilityTest.php` for super-admin status visibility, non-super-admin denial, retry command delegation, and queue config/job retry policy.

Verification: `php artisan test --filter=JobObservability` passed with 4 tests / 15 assertions; `vendor\bin\pint --dirty` passed; full `php artisan test` passed with 99 tests / 600 assertions.

Next: continue with Production Stabilization Checkpoint I: final stabilization review.
