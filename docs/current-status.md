# Current Status

## Completed

- Created new workspace at `D:\Development\School-SaaS-Enterprise`.
- Created base folders: `apps`, `docs`, `database`, `.github/workflows`.
- Copied master plan into `docs/enterprise-plan.md`.
- Cloned legacy app into `legacy-reference`.
- Audited legacy routes, models, controllers, migrations, middleware, views, seeders, and PDF usage.
- Created Phase 0 documentation:
  - `docs/module-inventory.md`
  - `docs/database-model.md`
  - `docs/api-contract.md`
  - `docs/security-model.md`
  - `docs/audit-log-model.md`
  - `docs/local-development.md`
- Created `docs/engineering-log.md` for durable phase-by-phase code change logs.
- Scaffolded Laravel API app in `apps/api`.
- Composer resolved Laravel framework to `v13.5.0` with PHP 8.5.5.
- Scaffolded Nuxt web app in `apps/web`.
- Nuxt scaffold targets `nuxt:^4.4.2`, Vue 3, and Vue Router 5.
- Installed Nuxt dependencies and generated `package-lock.json`.
- Installed Laravel Sanctum `v4.3.1`.
- Published Laravel 13 API routing and Sanctum config/migration.
- Added enterprise foundation schema for schools, memberships, roles, permissions, role assignments, audit logs, academic classes, academic sections, academic years, subjects, class subjects, student groups, and shifts.
- Added foundation models and relationships.
- Added token auth endpoints:
  - `POST /api/auth/login`
  - `POST /api/auth/logout`
  - `GET /api/me`
- Added school endpoints:
  - `GET /api/schools`
  - `POST /api/schools`
- Added tenant-scoped Academic Classes CRUD under:
  - `/api/schools/{school}/academic-classes`
- Added tenant-scoped Academic Sections CRUD under:
  - `/api/schools/{school}/academic-sections`
- Added tenant-scoped Academic Years CRUD under:
  - `/api/schools/{school}/academic-years`
- Added tenant-scoped Subjects CRUD under:
  - `/api/schools/{school}/subjects`
- Added tenant-scoped Class Subjects CRUD under:
  - `/api/schools/{school}/class-subjects`
- Added tenant-scoped Student Groups CRUD under:
  - `/api/schools/{school}/student-groups`
- Added tenant-scoped Shifts CRUD under:
  - `/api/schools/{school}/shifts`
- Added tenant-scoped Designations CRUD under:
  - `/api/schools/{school}/designations`
- Added tenant-scoped Employees CRUD under:
  - `/api/schools/{school}/employees`
- Added tenant-scoped Guardians CRUD under:
  - `/api/schools/{school}/guardians`
- Added tenant-scoped Students CRUD under:
  - `/api/schools/{school}/students`
- Added tenant-scoped Student Enrollments CRUD under:
  - `/api/schools/{school}/student-enrollments`
- Added tenant-scoped Teacher Profiles CRUD under:
  - `/api/schools/{school}/teacher-profiles`
- Added tenant-scoped Student Attendance Records CRUD under:
  - `/api/schools/{school}/student-attendance-records`
- Academic Sections validate their Academic Class belongs to the same school.
- Academic Years enforce one current academic year per school.
- Added feature tests for login, profile lookup, school creation, Academic Classes CRUD, Academic Sections CRUD, Academic Years CRUD, Subjects CRUD, Class Subjects CRUD, Student Groups CRUD, Shifts CRUD, permission denial, and cross-school access denial.
- Added enterprise role/permission seeders.
- Added audit-log writes for Academic Classes, Academic Sections, and Academic Years create/update/delete.
- Added audit-log writes for Subjects create/update/delete.
- Added audit-log writes for Class Subjects create/update/delete.
- Added audit-log writes for Student Groups and Shifts create/update/delete.
- Added reusable `school.member` middleware for active school membership checks.
- Added Academic Class policy checks for `academic_classes.manage`.
- Added Academic Section policy checks for `sections.manage`.
- Added Academic Year policy checks for `academic_years.manage`.
- Added Subject policy checks for `subjects.manage`.
- Added Class Subject policy checks for `class_subjects.manage`.
- Added Student Group policy checks for `student_groups.manage`.
- Added Shift policy checks for `shifts.manage`.
- Added Designation policy checks for `designations.manage`.
- Added Employee policy checks for `employees.manage`.
- Added Student Attendance Record policy checks for `attendance.manage`.
- School creation now assigns the seeded `school-owner` role to the creator when the RBAC seed exists.
- Replaced the Nuxt welcome screen with the first app UI slice:
  - login page
  - dashboard shell
  - typed API/auth composables
  - Academic Classes workspace
  - Academic Sections workspace
  - shared CSS foundation
- Added Nuxt client route protection with stale-token cleanup.
- Added dashboard school creation, tenant selection list, and first-school empty state.
- Added role and permission details to the `/api/me` user payload.
- Added role-aware dashboard navigation states based on selected-school permissions.
- Added Sections dashboard navigation gated by `sections.manage`.
- Added Nuxt Academic Sections workspace with class filtering, create, edit, and archive flows.
- Added Nuxt Academic Years workspace with status/current filters, create, edit, set-current, and archive flows.
- Added dashboard, Academic Classes, and Academic Sections navigation links for Academic Years.
- Added typed Nuxt `AcademicYear` API shape.
- Added Nuxt Subjects workspace with status/type/search filters, create, edit, and archive flows.
- Added dashboard and Academic Years navigation links for Subjects.
- Added typed Nuxt `Subject` API shape.
- Added Nuxt Class Subjects workspace with class/subject filters, mark rules, create, edit, and archive flows.
- Added dashboard navigation/action button for Class Subjects.
- Added typed Nuxt `ClassSubject` API shape.
- Added Nuxt Student Groups workspace with status/search filters, create, edit, and archive flows.
- Added Nuxt Shifts workspace with status/search filters, time windows, create, edit, and archive flows.
- Added dashboard navigation/action buttons for Student Groups and Shifts.
- Added typed Nuxt `StudentGroup` and `Shift` API shapes.
- Added Nuxt Designations workspace with status/search filters, create, edit, and archive flows.
- Added dashboard navigation/action button for Designations.
- Added typed Nuxt `Designation` API shape.
- Added Nuxt Employees workspace with status/type/designation/search filters, create, edit, and archive flows.
- Added dashboard navigation/action button for Employees.
- Added typed Nuxt `Employee` API shape.
- Added Nuxt Students and Guardians workspace with guardian/student create, edit, and archive flows.
- Added dashboard navigation/action button for Students.
- Added typed Nuxt `Guardian` and `Student` API shapes.
- Added Nuxt Enrollments workspace with student/year/class/section/group/shift placement, create, edit, and archive flows.
- Added dashboard navigation/action button for Enrollments.
- Added typed Nuxt `StudentEnrollment` API shape.
- Added Nuxt Teacher Profiles workspace with employee-based teacher profile create, edit, and archive flows.
- Added dashboard navigation/action button for Teachers.
- Added typed Nuxt `TeacherProfile` API shape.
- Added Nuxt Attendance workspace with active enrollment selection, date/status/search filters, create/edit/delete flows, and status summaries.
- Added dashboard navigation/action button for Attendance.
- Added typed Nuxt `StudentAttendanceRecord` API shape.
- Added explicit Laravel CORS config for local Nuxt origins.
- Added project `agent-browser.json` so future browser checks can run visibly and tolerate Herd local HTTPS certificates.
- Updated Laravel and Nuxt env examples for Herd/MySQL local development.
- Updated local development docs for the current bearer-token Sanctum flow.
- Linked the Laravel API folder in Herd as secured `https://school-api.test` on PHP 8.5.
- Verified Herd serves the API app:
  - `https://school-api.test` returns 200.
  - `https://school-api.test/api/me` returns the expected unauthenticated 401 JSON response without a bearer token.
- Verified the live Herd API vertical slice against the current local SQLite `.env`:
  - login with seeded `test@example.com` user returns 200.
  - school creation returns a tenant id.
  - Academic Class creation/listing works through `https://school-api.test/api`.
- Created ignored local Nuxt `.env` pointing to `https://school-api.test/api`.
- Installed `agent-browser@0.26.0` globally for browser automation checks.
- Installed agent-browser Chrome runtime `147.0.7727.57`.

## Not Started

- MySQL database creation.

## Verification

- `php artisan test` from `apps/api`: passed after Academic Years API, 15 tests / 78 assertions.
- `php artisan test` from `apps/api`: passed after Academic Years frontend/CORS phase, 15 tests / 78 assertions.
- `php artisan test` from `apps/api`: passed after Subjects workspace, 17 tests / 95 assertions.
- `php artisan test` from `apps/api`: passed after Student Groups and Shifts workspace, 21 tests / 126 assertions.
- `php artisan test` from `apps/api`: passed after Class Subject Assignments workspace, 24 tests / 145 assertions.
- `php artisan test` from `apps/api`: passed after Designations workspace, 26 tests / 160 assertions.
- `php artisan test --filter=Designation` from `apps/api`: passed, 2 tests / 14 assertions.
- `php artisan test` from `apps/api`: passed after Employees workspace, 29 tests / 180 assertions.
- `php artisan test --filter=Employee` from `apps/api`: passed, 3 tests / 19 assertions.
- `php artisan test` from `apps/api`: passed after Students and Guardians workspace, 34 tests / 214 assertions.
- `php artisan test` from `apps/api`: passed after Enrollments workspace, 37 tests / 233 assertions.
- `php artisan test` from `apps/api`: passed after Teacher Profiles workspace, 40 tests / 251 assertions.
- `php artisan test` from `apps/api`: passed after Attendance workspace, 43 tests / 274 assertions.
- `vendor\bin\pint --test` from `apps/api`: passed after Academic Years API.
- `vendor\bin\pint --test` from `apps/api`: passed after Academic Years frontend/CORS phase.
- `vendor\bin\pint --test` from `apps/api`: passed after Subjects workspace.
- `vendor\bin\pint --test` from `apps/api`: passed after Student Groups and Shifts workspace.
- `vendor\bin\pint --test` from `apps/api`: passed after Class Subject Assignments workspace.
- `vendor\bin\pint --test` from `apps/api`: passed after Designations workspace.
- `vendor\bin\pint --test` from `apps/api`: passed after Employees workspace.
- `vendor\bin\pint --test` from `apps/api`: passed after Students and Guardians workspace.
- `vendor\bin\pint --test` from `apps/api`: passed after Enrollments workspace.
- `vendor\bin\pint --test` from `apps/api`: passed after Teacher Profiles workspace.
- `vendor\bin\pint --test` from `apps/api`: passed after Attendance workspace.
- `php artisan route:list` from `apps/api`: passed, 41 routes.
- `agent-browser --version`: passed, `agent-browser 0.26.0`.
- `agent-browser` local web smoke check passed:
  - opened `http://127.0.0.1:3000/`
  - confirmed nonblank login page content
  - no page errors reported
- `php artisan migrate:fresh --seed` from `apps/api`: passed outside sandbox after sandbox SQLite disk I/O failure.
- `npm run build` from `apps/web`: passed after Nuxt app UI slice and again after route protection/school creation.
- Env template/docs update does not require code execution.
- `herd link school-api --secure --isolate=8.5` from `apps/api`: passed.
- `Invoke-WebRequest https://school-api.test`: passed, 200 OK.
- `Invoke-WebRequest https://school-api.test/api/me`: passed, 401 Unauthorized JSON.
- Live Herd API login/school/class smoke test: passed.
- `npm run build` from `apps/web`: passed with local `.env` set to `https://school-api.test/api`.
- `npm run build` from `apps/web`: passed after Academic Sections workspace, with existing Nuxt/Nitro warnings.
- `npm run build` from `apps/web`: passed after Academic Years workspace, with existing Nuxt/Nitro warnings.
- `npm run build` from `apps/web`: passed after Subjects workspace, with existing Nuxt/Nitro warnings.
- `npm run build` from `apps/web`: passed after Student Groups and Shifts workspace, with existing Nuxt/Nitro warnings.
- `npm run build` from `apps/web`: passed after Class Subject Assignments workspace, with existing Nuxt/Nitro warnings.
- `npm run build` from `apps/web`: passed after Designations workspace, with existing Nuxt/Nitro warnings.
- `npm run build` from `apps/web`: passed after Employees workspace, with existing Nuxt/Nitro warnings.
- `npm run build` from `apps/web`: passed after Students and Guardians workspace, with existing Nuxt/Nitro warnings.
- `npm run build` from `apps/web`: passed after Enrollments workspace, with existing Nuxt/Nitro warnings.
- `npm run build` from `apps/web`: passed after Teacher Profiles workspace, with existing Nuxt/Nitro warnings.
- `npm run build` from `apps/web`: passed after Attendance workspace, with existing Nuxt/Nitro warnings.
- Agent-browser opened `http://127.0.0.1:3000/` and confirmed the login page rendered. Authenticated browser login was blocked inside the automation browser by local Herd HTTPS fetch handling before the project browser config was added; continue visual checks with the new `agent-browser.json`.
- Agent-browser authenticated against the live app, reached `http://127.0.0.1:3000/dashboard`, opened `http://127.0.0.1:3000/schools/1/subjects`, and verified creating `Mathematics / MATH-101` through the live Herd API.
- Agent-browser opened `http://127.0.0.1:3000/schools/1/student-groups` and verified creating `Science Group / SCI-01` through the live Herd API.
- Agent-browser opened `http://127.0.0.1:3000/schools/1/shifts` and verified creating `Morning Shift / MOR-01` with `08:00 to 12:30` through the live Herd API.
- Agent-browser opened `http://127.0.0.1:3000/schools/1/class-subjects` and verified assigning `Mathematics / MATH-101` to `Class One` with `40 / 100` marks through the live Herd API.
- Agent-browser opened `http://127.0.0.1:3000/schools/1/designations` and verified creating `Senior Teacher / SNR-TCHR` through the live Herd API.
- Agent-browser opened `http://127.0.0.1:3000/schools/1/employees` and verified creating `Amina Rahman / EMP-2026-0001` through the live Herd API.
- Agent-browser opened `http://127.0.0.1:3000/schools/1/students` and verified creating `Karim Rahman` plus `Nadia Rahman / ADM-2026-0001` through the live Herd API.
- Saved browser screenshot at `docs/browser-checks/students-workspace.png`.
- Agent-browser opened `http://127.0.0.1:3000/schools/1/enrollments` and verified enrolling `Nadia Rahman / ADM-2026-0001` into `Class One` with roll `12` through the live Herd API.
- Saved browser screenshot at `docs/browser-checks/enrollments-workspace.png`.
- Agent-browser opened `http://127.0.0.1:3000/schools/1/teacher-profiles` and verified creating `Amina Rahman / TCHR-2026-0001` through the live Herd API.
- Saved browser screenshot at `docs/browser-checks/teacher-profiles-workspace.png`.
- Agent-browser opened `http://127.0.0.1:3000/schools/1/attendance`, confirmed no error overlay and nonblank content, and verified creating `Nadia Rahman / ADM-2026-0001` as `Present` on `2026-04-18` through the live Herd API.
- Saved browser screenshot at `docs/browser-checks/attendance-workspace.png`.
- Nuxt dev server startup from this Codex shell did not become reachable on port 3000; production build remains valid.
- Initial sandbox runs hit Windows permission/process limits, then passed outside the sandbox with approval.

## Next Step

Continue Phase 3 implementation:

1. Configure Laravel API for local MySQL once DB credentials are confirmed.
2. Continue browser walkthroughs with visible `agent-browser` as each page/module lands.
3. Continue Phase 3 with Exams.

Current page/module complete: Attendance API/Nuxt workspace.
Phase 2 status: complete for the current academic setup and people foundation.
Phase 3 status: Attendance foundation complete.
Next page/module: Phase 3 Exams.

## New Session Startup Prompt

```text
Read D:\Development\School-SaaS-Enterprise-CONTEXT.md and D:\Development\School-SaaS-Enterprise\docs\current-status.md.
Continue from the current status.
Only read D:\Development\School-SaaS-Enterprise-PLAN.md or docs\enterprise-plan.md if more detail is needed.
Minimize token usage.
```
