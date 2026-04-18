# School SaaS Enterprise: Codex Session Context

## Purpose

Use this file at the start of every new Codex session to minimize token usage and continue the project without re-explaining the full conversation.

Master plan:

```text
D:\Development\School-SaaS-Enterprise-PLAN.md
```

Active implementation plan:

```text
D:\Development\School-SaaS-Enterprise\docs\enterprise-plan-v3.md
```

Target project folder:

```text
D:\Development\School-SaaS-Enterprise
```

Do not modify these reference folders unless explicitly instructed:

```text
D:\Development\tailwindui-radiant
D:\Development\Laravel-School-Management
```

## Project Goal

Rebuild the existing Laravel school management app as an enterprise-grade, multi-school SaaS using the latest stack:

```text
Laravel 13.x
PHP 8.5
MySQL
Laravel Herd
Nuxt 4.4.x
Vue 3
TypeScript
Tailwind CSS 4
Laravel Sanctum SPA auth
```

The legacy GitHub repo is:

```text
https://github.com/immoazzem/Laravel-School-Management.git
```

Use it only as a functional reference.

## Local Environment

- OS/workspace: Windows, `D:\Development`
- Laravel Herd is installed and running.
- MySQL Workbench is installed.
- Use Herd-native local development, not Docker-first.
- Use MySQL database:

```text
school_saas_enterprise
```

Planned local URLs:

```text
API: https://school-api.test
Web: http://localhost:3000
```

## Design Source

The template folder is:

```text
D:\Development\tailwindui-radiant\radiant-ts
```

Important: Radiant is a Tailwind UI Next.js/React/Sanity template. The project frontend must remain Nuxt/Vue. Port Radiant's visual language manually into Nuxt. Do not copy React, Next.js, Sanity, or Framer Motion code directly unless intentionally replacing it with Vue/Nuxt equivalents.

Use Radiant for:

- public landing page style
- login page style
- pricing page shell
- typography, spacing, buttons, sections, screenshots, and marketing layout inspiration

Build authenticated dashboards as custom Nuxt enterprise admin screens.

## Key Decisions Already Made

- Use a new folder: `D:\Development\School-SaaS-Enterprise`.
- Frontend stack: Nuxt 4, not Next.js.
- Backend stack: Laravel 13 with PHP 8.5.
- Database: MySQL.
- Auth: Laravel Sanctum SPA.
- Tenancy: multi-school from day one.
- Production tenant routing: subdomains like `{school-slug}.yourdomain.com`.
- Local tenant fallback: active school selected after login.
- Enterprise priority: security and audit.
- Billing: later phase, not Phase 1.
- First implementation target: foundation plus one complete vertical slice.
- First vertical slice: Academic Classes CRUD.

## Current Implementation Checkpoints

- Phase 0 legacy audit docs are complete and committed.
- Laravel API scaffold exists in `D:\Development\School-SaaS-Enterprise\apps\api`.
- Nuxt web scaffold exists in `D:\Development\School-SaaS-Enterprise\apps\web`.
- Laravel framework resolved to `v13.5.0`.
- Nuxt app targets `nuxt:^4.4.2`.
- Laravel Sanctum `v4.3.1` is installed.
- API routing and Sanctum config/migration are published.
- Backend foundation schema exists for schools, memberships, RBAC, audit logs, academic classes, academic sections, academic years, subjects, class subjects, student groups, shifts, designations, employees, guardians, students, student enrollments, and teacher profiles.
- Token auth, school list/create, tenant-scoped Academic Classes CRUD, tenant-scoped Academic Sections CRUD, tenant-scoped Academic Years CRUD, tenant-scoped Subjects CRUD, tenant-scoped Class Subjects CRUD, tenant-scoped Student Groups CRUD, tenant-scoped Shifts CRUD, tenant-scoped Designations CRUD, tenant-scoped Employees CRUD, tenant-scoped Guardians CRUD, tenant-scoped Students CRUD, tenant-scoped Student Enrollments CRUD, tenant-scoped Teacher Profiles CRUD, and tenant-scoped Student Attendance Records CRUD endpoints exist.
- Enterprise role/permission seeders and Academic Classes/Sections/Years/Subjects/Class Subjects/Student Groups/Shifts/Designations/Employees/Guardians/Students/Student Enrollments/Teacher Profiles/Student Attendance audit-log writes exist.
- Active school membership checks use reusable `school.member` route middleware.
- Academic Class policy checks enforce `academic_classes.manage`.
- Academic Section policy checks enforce `sections.manage`.
- Academic Year policy checks enforce `academic_years.manage`.
- Subject policy checks enforce `subjects.manage`.
- Class Subject policy checks enforce `class_subjects.manage`.
- Student Group policy checks enforce `student_groups.manage`.
- Shift policy checks enforce `shifts.manage`.
- Designation policy checks enforce `designations.manage`.
- Employee policy checks enforce `employees.manage`.
- Guardian policy checks enforce `guardians.manage`.
- Student policy checks enforce `students.manage`.
- Student Enrollment policy checks enforce `enrollments.manage`.
- Teacher Profile policy checks enforce `teachers.manage`.
- Student Attendance Record policy checks enforce `attendance.manage`.
- School creation assigns the seeded `school-owner` role to the creator.
- Backend foundation tests pass after Academic Years: `php artisan test` reports 15 tests / 78 assertions.
- Pint, route list, and `php artisan migrate:fresh --seed` pass for the backend foundation.
- Nuxt has a first app UI slice: login page, dashboard shell, API/auth composables, Academic Classes workspace, and Academic Sections workspace.
- Nuxt has client route protection with stale-token cleanup.
- Dashboard can create a school through `POST /api/schools`, select the new tenant, and show active tenants.
- `/api/me` includes per-school role and permission details.
- Dashboard navigation uses the selected school's permissions for locked/enabled module states.
- Sections navigation is gated by `sections.manage`.
- Academic Sections workspace supports class filtering, create, edit, and archive flows.
- Academic Years workspace supports status/current filters, create, edit, set-current, and archive flows.
- Subjects workspace supports status/type/search filters, create, edit, and archive flows.
- Class Subjects workspace supports class/subject filters, mark rules, create, edit, and archive flows.
- Student Groups workspace supports status/search filters, create, edit, and archive flows.
- Shifts workspace supports status/search filters, time windows, create, edit, and archive flows.
- Designations workspace supports status/search filters, create, edit, and archive flows.
- Employees workspace supports status/type/designation/search filters, create, edit, and archive flows.
- Students and Guardians workspace supports guardian/student create, edit, and archive flows.
- Enrollments workspace supports student/year/class/section/group/shift placement plus create, edit, and archive flows.
- Teacher Profiles workspace supports employee-based teacher profile create, edit, and archive flows.
- Attendance workspace supports active enrollment selection, date/status/search filters, create/edit/delete flows, and status summaries.
- Dashboard, Academic Classes, and Academic Sections screens link to Academic Years.
- Explicit Laravel CORS config allows local Nuxt origins.
- Project `agent-browser.json` is present so browser checks can run headed and ignore Herd local HTTPS certificate errors.
- Nuxt build passes after the app UI slice and after route protection/school creation.
- Backend tests pass after the Attendance workspace: 43 tests / 274 assertions.
- Env examples are Herd/MySQL-ready:
  - `apps/api/.env.example`
  - `apps/web/.env.example`
- Herd API site is linked and secured:
  - `https://school-api.test`
  - path: `D:\Development\School-SaaS-Enterprise\apps\api`
  - PHP: 8.5
- Herd verification passed outside sandbox:
  - root API URL returns 200.
  - `/api/me` returns expected 401 JSON without a token.
- Live Herd API vertical-slice smoke test passed using the current local SQLite `.env`:
  - seeded login works.
  - school creation works.
  - Academic Class creation/listing works.
- Ignored local Nuxt `.env` points to `https://school-api.test/api`.
- Nuxt production build passes with that env.
- Nuxt dev server is reachable on `http://127.0.0.1:3000` when started from this workspace with `npm run dev -- --host 127.0.0.1 --port 3000`.
- MySQL database config is still pending; tests currently use SQLite in memory.
- Current page/module complete: Attendance API/Nuxt workspace.
- Phase 2 status: complete for the current academic setup and people foundation.
- Phase 3 status: Attendance foundation complete.
- Next app slice: Phase 3 Exams.
- Agent-browser authenticated against the live app, reached the dashboard, opened `http://127.0.0.1:3000/schools/1/subjects`, and verified creating `Mathematics / MATH-101` through the live Herd API.
- Agent-browser opened `http://127.0.0.1:3000/schools/1/student-groups` and verified creating `Science Group / SCI-01` through the live Herd API.
- Agent-browser opened `http://127.0.0.1:3000/schools/1/shifts` and verified creating `Morning Shift / MOR-01` with `08:00 to 12:30` through the live Herd API.
- Agent-browser opened `http://127.0.0.1:3000/schools/1/class-subjects` and verified assigning `Mathematics / MATH-101` to `Class One` with `40 / 100` marks through the live Herd API.
- Agent-browser opened `http://127.0.0.1:3000/schools/1/designations` and verified creating `Senior Teacher / SNR-TCHR` through the live Herd API.
- Agent-browser opened `http://127.0.0.1:3000/schools/1/employees` and verified creating `Amina Rahman / EMP-2026-0001` through the live Herd API.
- Agent-browser opened `http://127.0.0.1:3000/schools/1/students` and verified creating `Karim Rahman` plus `Nadia Rahman / ADM-2026-0001` through the live Herd API.
- Browser screenshot saved at `docs/browser-checks/students-workspace.png`.
- Agent-browser opened `http://127.0.0.1:3000/schools/1/enrollments` and verified enrolling `Nadia Rahman / ADM-2026-0001` into `Class One` with roll `12` through the live Herd API.
- Browser screenshot saved at `docs/browser-checks/enrollments-workspace.png`.
- Agent-browser opened `http://127.0.0.1:3000/schools/1/teacher-profiles` and verified creating `Amina Rahman / TCHR-2026-0001` through the live Herd API.
- Browser screenshot saved at `docs/browser-checks/teacher-profiles-workspace.png`.
- Agent-browser opened `http://127.0.0.1:3000/schools/1/attendance`, confirmed no error overlay and nonblank content, and verified creating `Nadia Rahman / ADM-2026-0001` as `Present` on `2026-04-18` through the live Herd API.
- Browser screenshot saved at `docs/browser-checks/attendance-workspace.png`.
- `agent-browser@0.26.0` is installed globally with Chrome runtime `147.0.7727.57` and should be used after dev server starts.
- Show/use the browser during UI phases at natural checkpoints: after a page is added, after login/navigation changes, and before committing a successful phase.
- Maintain `docs/engineering-log.md` after each successful step, and update `docs/current-status.md` plus this file before ending long sessions.
- Phase 3.0 Stabilization is complete except for the credential-blocked local MySQL switch.
- API index endpoints now return paginated envelopes with top-level `data`, `meta`, and `links`; frontend list code can continue reading `data` as the record array.
- Shared audit logging lives in `App\Services\AuditLogger` and `App\Http\Controllers\Controller::recordAudit()`.
- School show/update endpoints exist at `GET/PATCH /api/schools/{school}` with `school.member` and `schools.manage` enforcement for update.
- Auth/API rate limiters are registered in `App\Providers\AppServiceProvider`.
- Local MySQL discovery:
  - server is listening on `3306`
  - client path is `C:\Program Files\MySQL\MySQL Server 8.0\bin\mysql.exe`
  - `root` without password fails with `ERROR 1045`
  - `apps/api/.env` remains SQLite until credentials are available or a dev MySQL user is created

## Required Project Structure

```text
School-SaaS-Enterprise/
  legacy-reference/        cloned Laravel 8 app, read-only reference
  apps/
    api/                   Laravel 13 API
    web/                   Nuxt 4 frontend
  docs/
    enterprise-plan.md
    module-inventory.md
    database-model.md
    api-contract.md
    security-model.md
    audit-log-model.md
    local-development.md
    engineering-log.md
  database/
    exports/
    import-scripts/
  .github/
    workflows/
  README.md
```

## Phase Order

1. Phase 0: Audit the legacy app and write docs.
2. Phase 1: Build enterprise foundation and Academic Classes vertical slice.
3. Phase 2: Academic setup and people modules is complete for the current foundation: Academic Classes, Sections, Years, Subjects, Class Subjects, Student Groups, Shifts, Designations, Employees, Guardians, Students, Enrollments, and Teacher Profiles.
4. Phase 3: Attendance foundation and Phase 3.0 Stabilization are complete except the credential-blocked MySQL switch; exams and finance remain.
5. Phase 4: Reports, PDFs, calendar, and operations.
6. Phase 5: SaaS administration and billing placeholders.

## Phase 0 Requirements

Before writing new app code, inspect the legacy app and create:

```text
docs/module-inventory.md
docs/database-model.md
docs/api-contract.md
docs/security-model.md
docs/audit-log-model.md
docs/enterprise-plan.md
docs/local-development.md
```

The docs must map legacy modules to the new SaaS architecture.

## Phase 1 Requirements

Backend foundation:

- Laravel 13 API in `apps/api`
- PHP 8.5
- MySQL connection
- Sanctum SPA auth
- schools
- users
- school memberships
- roles
- permissions
- audit logs
- academic classes
- active school context middleware
- tenant-scoped access pattern
- policies
- rate limits
- seeders
- tests

Frontend foundation:

- Nuxt 4 app in `apps/web`
- TypeScript
- Tailwind CSS 4
- Pinia
- validation
- API client
- Radiant-inspired landing page
- login page
- protected dashboard shell
- sidebar
- top bar
- school switcher
- role-aware menu shell
- Academic Classes CRUD

Acceptance:

- login works
- user can enter/select school context
- Academic Classes CRUD works
- cross-school access is blocked
- class mutations create audit logs
- backend tests pass
- Nuxt build passes

## Enterprise Security Defaults

- Every school-owned model must include `school_id`.
- Every school-owned query must be scoped by active school.
- Cross-school access must be denied.
- Use RBAC with permissions.
- Initial roles:

```text
Super Admin
School Owner
School Admin
Principal
Teacher
Accountant
Student
Parent
Read-only Auditor
```

Audit logs must record:

- school id
- actor id
- action
- target type
- target id
- old values where safe
- new values where safe
- IP address
- user agent
- timestamp

Never store plaintext passwords, secrets, payment card data, or full sensitive documents in audit logs.

## Quality Gates

Backend:

```bash
php artisan test
vendor/bin/pint --test
php artisan route:list
php artisan migrate:fresh --seed
```

Frontend:

```bash
npm run build
npm run typecheck
npm run lint
```

## Session Rules For Codex

- Read this context file first.
- Read the master plan only when more detail is needed.
- Keep `legacy-reference` read-only.
- Work phase by phase.
- Commit after each meaningful phase.
- Update `docs/engineering-log.md` after each meaningful phase or successful commit.
- Before a session gets long, checkpoint by updating `docs/current-status.md`, `docs/session-context.md`, and `docs/engineering-log.md`.
- Mention the current page/module at the start of work and in each phase finish note.
- Use visible `agent-browser` checks during UI phases when a dev server is available.
- Pause after each phase with:
  - files changed
  - commands run
  - test/build result
  - what the user should review in VS Code
  - next recommended phase

## Recommended First Prompt For A New Session

```text
Read D:\Development\School-SaaS-Enterprise-CONTEXT.md first.
Then continue the School SaaS Enterprise rebuild from the current workspace state.
Use D:\Development\School-SaaS-Enterprise-PLAN.md only when you need full detail.
Minimize token usage by summarizing large files instead of pasting them.
```
