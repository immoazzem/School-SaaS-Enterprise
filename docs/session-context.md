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

Planning rule:

```text
docs/enterprise-plan-v3.md is the active plan. Whenever v3 mentions v2, it means docs/enterprise-plan-v2.md. All v2 baseline rules remain primary, and v3 adds to and extends them unless a section explicitly says otherwise.
```

Target project folder:

```text
D:\Development\School-SaaS-Enterprise
```

Do not modify these reference folders unless explicitly instructed:

```text
D:\Development\tailwindui-radiant
D:\Development\Laravel-School-Management
D:\Development\Theme and templates\vuexy-admin-v10.11.1
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

The active frontend theme source is:

```text
D:\Development\Theme and templates\vuexy-admin-v10.11.1
```

Important: Vuexy is now the active admin design reference for the authenticated frontend rebuild. Keep the project frontend in Nuxt/Vue and port the visual system into the existing app architecture instead of trying to transplant unrelated demo app structure wholesale.

Radiant remains a historical reference for earlier marketing/login direction, but the authenticated frontend is now being rebuilt toward Vuexy/Vuetify.

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

- Latest checkpoint: Vuexy dashboard and high-traffic workspace migration.
- The main operator pages now sit deeper inside the Vuexy/Vuetify design system:
  - `apps/web/app/pages/dashboard.vue`
  - `apps/web/app/pages/schools/[schoolId]/students.vue`
  - `apps/web/app/pages/schools/[schoolId]/finance.vue`
  - `apps/web/app/pages/schools/[schoolId]/reports.vue`
  - `apps/web/app/pages/schools/[schoolId]/attendance.vue`
- Targeted verification after this slice:
  - `npm run build` passed
  - browser verification passed for dashboard, students, finance, reports, and attendance
- Latest useful browser artifacts:
  - `docs/browser-checks/vuexy-dashboard-polish-20260422.png`
  - `docs/browser-checks/vuexy-students-polish-20260422.png`
  - `docs/browser-checks/vuexy-finance-polish-20260422.png`
  - `docs/browser-checks/vuexy-reports-polish-20260422.png`
  - `docs/browser-checks/vuexy-attendance-polish-20260422.png`
- Current local frontend URL remains `http://127.0.0.1:3000`.
- Immediate next step:
  - continue the Vuexy rebuild across the next workflow cluster: enrollments, exams, marks, and staff operations.

- Latest checkpoint: Vuexy frontend rebuild foundation and shared shell migration.
- Frontend theme source is now `D:\Development\Theme and templates\vuexy-admin-v10.11.1`.
- `apps/web` now includes Vuetify/Vuexy foundation packages:
  - `vuetify`
  - `vite-plugin-vuetify`
  - `sass`
- Added `apps/web/app/plugins/vuetify.ts` with a Vuexy-style light theme palette and Vuetify defaults.
- `apps/web/nuxt.config.ts` now registers the Vuetify Vite plugin and transpiles Vuetify while preserving Tailwind, i18n, PWA, and existing API wiring.
- `apps/web/app/app.vue` now runs inside `VApp` / `VMain`.
- Shared authenticated shell files moved further toward Vuexy:
  - `apps/web/app/components/SchoolWorkspaceTemplate.vue`
  - `apps/web/app/components/SchoolWorkspaceRail.vue`
  - `apps/web/app/utils/schoolWorkspaceNav.ts`
  - `apps/web/app/assets/css/main.css`
- Focused verification after the Vuexy foundation pass:
  - `npm run build` passed
  - browser login/dashboard check passed
  - browser academic-classes page check passed
- Latest useful browser artifacts:
  - `docs/browser-checks/vuexy-dashboard-20260422.png`
  - `docs/browser-checks/vuexy-academic-classes-20260422.png`
- Current local frontend URL remains `http://127.0.0.1:3000`.
- Immediate next step:
  - continue rebuilding the dashboard and highest-traffic workspaces onto native Vuetify/Vuexy card/form/list patterns, then adapt the browser smoke harness to the rebuilt shell.

- Latest checkpoint: high-traffic workspace polish for Students, Finance, Reports, and Attendance.
- Added shared utility layout rules in `apps/web/app/assets/css/main.css` for filters, search forms, strip actions, insight grids, and mini lists.
- `apps/web/app/pages/schools/[schoolId]/students.vue` now exposes guardian/student status filters and uses the shared loading/table treatment.
- `apps/web/app/pages/schools/[schoolId]/finance.vue` now has clearer header actions and shared loading treatment.
- `apps/web/app/pages/schools/[schoolId]/reports.vue` now has clearer header actions and shared loading treatment.
- `apps/web/app/pages/schools/[schoolId]/attendance.vue` now uses the shared `record-form` / `record-list` / `summary-item` layout and includes a direct queue sync action.
- Latest verification:
  - `npm run build` passed
  - agent-browser verified:
    - `http://127.0.0.1:3000/schools/1/students`
    - `http://127.0.0.1:3000/schools/1/finance`
    - `http://127.0.0.1:3000/schools/1/reports`
    - `http://127.0.0.1:3000/schools/1/attendance`
  - screenshots saved at:
    - `docs/browser-checks/students-polish-20260421.png`
    - `docs/browser-checks/finance-polish-20260421.png`
    - `docs/browser-checks/reports-polish-20260421.png`
    - `docs/browser-checks/attendance-polish-20260421.png`
- Local frontend remains available at `http://127.0.0.1:3000`.

- Latest checkpoint: dashboard command-center polish and restore point after markdown cleanup.
- Removed stale markdown files that were not part of the active delivery path:
  - `apps/api/README.md`
  - `apps/web/README.md`
  - `docs/IMPROVEMENT-ROADMAP.md`
- `docs/session-context.md` now points new sessions to `docs/current-status.md` and `docs/engineering-log.md` instead of the removed roadmap file.
- `apps/web/app/pages/dashboard.vue` was polished into a tighter operator command center with:
  - workspace overview
  - access summary
  - quick-action launchpad
  - collections trend
  - attention-required queue
  - grouped workspace map
  - cleaner tenant create/access section
- Latest dashboard verification:
  - `npm run build` passed
  - agent-browser verified `http://127.0.0.1:3000/dashboard`
  - screenshot saved at `docs/browser-checks/dashboard-polish-20260421-110839.png`
- Local frontend remains available at `http://127.0.0.1:3000`.

- Latest checkpoint: compact enterprise shell refactor using Salient as the design resource.
- Added `apps/web/app/components/SchoolWorkspaceTemplate.vue` as the shared authenticated scaffold.
- Dashboard and all pages in `apps/web/app/pages/schools/[schoolId]` now use the shared scaffold rather than repeated shell wrappers.
- `apps/web/app/assets/css/main.css` was rewritten toward a compact enterprise UI:
  - tighter spacing
  - flatter surfaces
  - denser forms and tables
  - calmer module tiles
  - compact sidebar/mobile top bar
- `apps/web/app/components/SchoolWorkspaceRail.vue` was refined so the desktop sidebar no longer overlays page content.
- Verification after the compact-shell pass:
  - `npm run build` passed
  - `npm run qa:browser` passed with 10 workflow checks
- Latest browser artifacts:
  - `docs/browser-checks/workflow-smoke-20260421043826.png`
- Local frontend remains available at `http://127.0.0.1:3000`.

- Latest checkpoint: Antigravity frontend refresh reviewed and QA-verified.
- Antigravity refreshed the shared shell, login page, dashboard, and many school workspaces using the blue/slate design pass.
- The shared rail still lives at `apps/web/app/components/SchoolWorkspaceRail.vue`.
- Global design tokens and UI utility rules now primarily live in:
  - `apps/web/app/assets/css/main.css`
  - `apps/web/tailwind.config.ts`
- Browser QA status after review:
  - `npm run build` passed
  - `npm run qa:browser` passed with 10 workflow checks
- Latest useful artifacts:
  - `docs/browser-checks/antigravity-dashboard-qa-20260421.png`
  - `docs/browser-checks/antigravity-mobile-drawer-20260421.png`
  - `docs/browser-checks/workflow-smoke-20260421040744.png`
- `apps/web/scripts/browser-workflow-smoke.mjs` was updated so the smoke test works with Antigravity's login copy.
- Temporary cleanup helper scripts from the Antigravity pass were intentionally not kept in the repo.

- Latest checkpoint: Frontend shell stabilization before Antigravity dashboard redesign.
- Shared authenticated navigation now lives in `apps/web/app/components/SchoolWorkspaceRail.vue`.
- Module definitions for the rail live in `apps/web/app/utils/schoolWorkspaceNav.ts`.
- Dashboard and the main school workspace pages now share the same left navigation model.
- Latest shell verification artifact: `docs/browser-checks/dashboard-shell-20260421.png`
- Local frontend remains available at `http://127.0.0.1:3000`.
- This checkpoint is the intended restore point before Antigravity starts the design pass.

- Latest checkpoint: Post-dashboard QA hardening and full browser workflow smoke.
- Reusable browser QA script now lives at `apps/web/scripts/browser-workflow-smoke.mjs`.
- Run `cd apps/web && npm run qa:browser` to replay the 10-step workflow smoke.
- Latest passing browser artifact: `docs/browser-checks/workflow-smoke-20260420234054.png`.
- Recent hardening fixes:
  - active-list visibility and pagination cleanup for academic classes, sections, years, subjects, groups, shifts, and designations
  - guardian select options decoupled from paginated guardian table in Students
  - attendance edit button now says `Update attendance`
  - finance form labels are explicitly associated to inputs/selects
- Latest verification:
  - `npm run qa:browser` passed with 10 checks
  - `npm run build` passed
  - `php artisan test` passed with 117 tests / 702 assertions
  - `vendor\\bin\\pint --test` passed

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
- Phase 3.0 Stabilization is fully complete, including the local MySQL switch.
- Phase 3 Exams foundation API is complete: `exam_types`, `exams`, and `exam_schedules`.
- Exam types include `weightage_percent`.
- Exams include `is_published`, `published_at`, and `published_by`.
- Exam schedules link to `class_subjects` so future marks entry can source `full_marks` and `pass_marks` from class-subject assignments.
- Exam foundation routes:
  - `/api/schools/{school}/exam-types`
  - `/api/schools/{school}/exams`
  - `/api/schools/{school}/exam-schedules`
- Seeded permissions include `exams.manage` and `exams.publish`.
- Nuxt Exams workspace is complete:
  - typed `ExamType`, `Exam`, and `ExamSchedule` API shapes
  - route `/schools/{schoolId}/exams`
  - dashboard navigation gated by `exams.manage`
  - forms for weighted exam types, exam windows, and class-subject schedules
  - browser smoke confirmed live MySQL data rendered
- Phase 3 Operations backend APIs are complete:
  - marks entry and grade scales
  - fee categories, fee structures, discounts, invoices, payments, and bulk invoice job dispatch
  - salary records with computed gross/deductions/net amounts
  - employee attendance upsert records
  - leave types, leave balances, leave application approval/reject/cancel workflow
  - enhanced student attendance bulk upsert with late arrival, half-day, and leave references
- Phase 3 Operations Nuxt workspaces are complete:
  - `/schools/{schoolId}/marks`
  - `/schools/{schoolId}/finance`
  - `/schools/{schoolId}/staff-operations`
  - dashboard navigation and actions for Marks, Finance, and Staff Ops
- Latest backend verification after Phase 3 Operations backend:
  - `php artisan migrate:fresh --seed` passed against MySQL
  - `php artisan test` passed with 53 tests / 370 assertions
  - `vendor\bin\pint --test` passed
  - `php artisan route:list --path=api/schools --except-vendor` passed with 161 routes
- Latest frontend verification after Phase 3 Operations Nuxt workspaces:
  - `npm run build` passed with existing Nuxt/Nitro warnings
  - agent-browser rendered Marks, Finance, and Staff Operations pages with no Vite overlay
  - screenshots saved in `docs/browser-checks/`
- Phase 4 reporting backend foundation is complete:
  - `result_summaries`
  - result publish endpoint
  - result summary listing endpoint
  - employee attendance summary endpoint
  - in-app notification inbox endpoints
- Phase 4 calendar and notification hooks backend is complete:
  - `calendar_events`
  - calendar CRUD routes
  - holiday bulk import route
  - `calendar.manage` permission
  - `payment.received` notifications for matched student/guardian user accounts
  - `leave.approved` and `leave.rejected` notifications for matched employee user accounts
- Phase 4 document management backend is complete:
  - `school_documents`
  - upload/list/show/delete document APIs
  - signed download URL route
  - storage plan-limit check through `PlanLimitService`
  - `documents.manage` permission
- Phase 4 Reports, PDFs, and Dashboard Analytics backend is complete:
  - `report_exports`
  - queued PDF generation through `GenerateReportJob` and `barryvdh/laravel-dompdf`
  - signed report download polling and file routes
  - marksheet, result sheet, ID card, invoice, and salary report export endpoints
  - V2-compatible result and marksheet aliases
  - student attendance monthly summary endpoint
  - dashboard summary endpoint for admin, accountant, teacher, and auditor aggregates
- Latest backend verification after Phase 4 Reports/PDFs/Dashboard Analytics:
  - `php artisan migrate:fresh --seed` passed against MySQL
  - `php artisan test` passed with 65 tests / 445 assertions
  - `vendor\bin\pint --test` passed
  - `composer audit` passed with no advisories
  - `php artisan route:list --path=api/schools --except-vendor` passed with 190 routes
- Git note: Phase 3, Phase 4 reporting foundation, Phase 4 calendar/notification hooks, and Phase 4 document management are pushed to GitHub. Phase 4 Reports/PDFs/Dashboard Analytics is ready to commit and push.
- Phase 4 Nuxt workspaces are complete:
  - `/schools/{schoolId}/reports`
  - `/schools/{schoolId}/calendar`
  - `/schools/{schoolId}/documents`
  - dashboard navigation/actions for Reports, Calendar, and Documents
  - typed frontend API shapes for Phase 4 reports, calendar, documents, dashboard analytics, and attendance summaries
- Latest frontend verification after Phase 4 Nuxt workspaces:
  - `npm run build` passed with existing Nuxt/Nitro warnings
  - visible agent-browser rendered Reports, Calendar, and Documents pages with no Vite overlay
  - screenshots saved at `docs/browser-checks/phase-4-reports-workspace.png`, `docs/browser-checks/phase-4-calendar-workspace.png`, and `docs/browser-checks/phase-4-documents-workspace.png`
- Phase 4 status: complete for backend APIs, queued PDFs, analytics, result publication, notifications, calendar, document management, Nuxt workspaces, build, and browser smoke verification.
- Git note: Phase 4 Nuxt completion checkpoint is ready to commit and push. Next phase is Phase 5 SaaS administration and billing placeholders.
- Radiant UI correction is complete:
  - inspected `D:\Development\tailwindui-radiant\radiant-ts`
  - refreshed Nuxt global UI and dashboard/login shells toward Radiant's warm gradient, plus-grid, black pill-button, translucent-panel visual system
  - mechanically removed old green theme tokens from app source
  - `npm run build` passed with existing Nuxt/Nitro warnings
  - screenshots saved at `docs/browser-checks/radiant-login-refresh.png`, `docs/browser-checks/radiant-dashboard-refresh.png`, and `docs/browser-checks/radiant-reports-refresh.png`
- Phase 5 SaaS Administration backend foundation is complete:
  - new school SaaS columns: `plan`, `subscription_status`, `trial_ends_at`, `plan_limits`
  - typed `App\ValueObjects\SchoolSettings` matching the v3 `schools.settings` JSON shape
  - `GET/PATCH /api/schools/{school}/settings` with `SchoolSettingsRequest`
  - `super.admin` middleware backed by `User::hasSystemRole('super-admin')`
  - admin endpoints:
    - `GET /api/admin/schools`
    - `GET /api/admin/schools/{school}`
    - `PATCH /api/admin/schools/{school}`
    - `DELETE /api/admin/schools/{school}`
    - `POST /api/admin/schools/{school}/onboard`
    - `GET /api/admin/audit-logs`
    - `GET /api/admin/users`
    - `GET /api/admin/system/health`
    - `GET /api/admin/system/stats`
  - school audit viewer: `GET /api/schools/{school}/audit-logs`
  - onboarding sets trial status/date, default plan limits, and school-scoped default roles
  - `PlanLimitService` now reads `schools.plan_limits` first and enforces student/employee limits
  - seeded `student.portal.view` and `parent.portal.view`
  - local MySQL migration applied with `php artisan migrate --force`
  - full backend verification passed: `php artisan test` = 70 tests / 469 assertions
- Phase 5 remaining:
  - Nuxt administration screens for Phase 5 APIs, unless the next plan phase supersedes them
- Phase 5 User Invitation backend flow is complete:
  - `school_invitations` table and `SchoolInvitation` model
  - `School::invitations()` relationship
  - `POST /api/schools/{school}/invitations`
  - `GET /api/schools/{school}/invitations`
  - `DELETE /api/schools/{school}/invitations/{invitation}`
  - `POST /api/invitations/{token}/accept`
  - `users.manage` controls school invitation management
  - accept validates invitee email, pending status, and expiry, then activates membership and assigns the invited role
  - invitation audit logs are written for create, revoke, and accept
  - local MySQL migration applied with `php artisan migrate --force`
  - full backend verification passed: `php artisan test` = 72 tests / 487 assertions
- Phase 5 Parent and Student Portal backend endpoints are complete:
  - `App\Http\Controllers\Api\PortalController`
  - student portal uses `students.email` to link the authenticated user
  - parent portal uses `guardians.email` and existing guardian-to-student records
  - portal settings honor `allow_student_portal` and `allow_parent_portal`
  - routes:
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
  - full backend verification passed: `php artisan test` = 74 tests / 509 assertions
- Phase 5 Data Export and Student Anonymization backend is complete:
  - `data_export_jobs` table and `DataExportJob` model
  - `School::dataExportJobs()` and `School::auditLogs()` relationships
  - `POST /api/schools/{school}/data-export/request`
  - `GET /api/schools/{school}/data-export/{job_id}/download`
  - `POST /api/schools/{school}/students/{student}/anonymize`
  - data export writes JSON artifacts to local storage with school profile, guardians, students/enrollments, employees, invoices/payments, document metadata, and optional capped audit logs
  - student anonymization clears personal fields, detaches guardian linkage, archives the student, and writes `student.anonymized`
  - local MySQL migration applied with `php artisan migrate --force`
  - full backend verification passed: `php artisan test` = 76 tests / 522 assertions
- Phase 5 Self-hosted Operations are complete:
  - `docs/self-hosted-deployment.md`
  - `php artisan school:backup`
  - `php artisan school:backup --school={id}`
  - `php artisan school:restore {archive}`
  - `php artisan school:restore {archive} --force`
  - command discovery confirmed by `php artisan list school`
  - local backup command verified with `php artisan school:backup --school=1`
  - full backend verification passed: `php artisan test` = 76 tests / 522 assertions
- Phase 5 backend status: complete for SaaS admin foundation, settings, plan limits, onboarding, audit viewer, invitations, parent/student portals, data export/right-to-erasure, self-hosted deployment docs, and backup/restore commands.
- Phase 6 Student Promotion backend foundation is complete:
  - `promotion_batches`
  - `promotion_records`
  - `PromotionBatch` and `PromotionRecord` models
  - `School::promotionBatches()` relationship
  - `promotions.manage` permission seeded
  - `POST /api/schools/{school}/promotions/preview`
  - `POST /api/schools/{school}/promotions`
  - `PATCH /api/schools/{school}/promotions/{batch}/records/{record}`
  - `POST /api/schools/{school}/promotions/{batch}/execute`
  - `POST /api/schools/{school}/promotions/{batch}/rollback`
  - Preview suggests `retained` when a result summary has `is_pass=false`, otherwise `promoted`
  - Execute creates new enrollments, completes old enrollments, stores `new_enrollment_id`, and audits `promotion.executed`
  - Rollback works within 48 hours and audits `promotion.rolled_back`
  - local MySQL migration applied with `php artisan migrate --force`
  - full backend verification passed: `php artisan test` = 79 tests / 547 assertions
- API index endpoints now return paginated envelopes with top-level `data`, `meta`, and `links`; frontend list code can continue reading `data` as the record array.
- Shared audit logging lives in `App\Services\AuditLogger` and `App\Http\Controllers\Controller::recordAudit()`.
- School show/update endpoints exist at `GET/PATCH /api/schools/{school}` with `school.member` and `schools.manage` enforcement for update.
- Auth/API rate limiters are registered in `App\Providers\AppServiceProvider`.
- Local MySQL discovery:
  - server is listening on `3306`
  - client path is `C:\Program Files\MySQL\MySQL Server 8.0\bin\mysql.exe`
  - MySQL version is `8.0.45`
  - database is `school_saas_enterprise`
  - ignored local `apps/api/.env` now uses MySQL with username `root` and the user-provided local password
  - `php artisan migrate:fresh --seed` has passed against MySQL

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
4. Phase 3: Attendance foundation, Phase 3.0 Stabilization, Exams API/Nuxt workspace, and backend/Nuxt workspaces through Phase 3I are complete.
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

## Latest Checkpoint

Current page/module complete: Phase 7D Nuxt i18n frontend integration.

Latest Phase 7D frontend status:

- Installed and configured `@nuxtjs/i18n`.
- Added English/Bengali messages in `apps/web/i18n.config.ts`.
- Added reusable `LocaleSwitcher`.
- Added `useSchoolLocale()` to sync selected-school locale with Nuxt i18n and shared API locale state.
- Updated `useApi()` to send `Accept-Language`.
- Dashboard includes the language switcher.
- Students and Employees pages now collect `name_bn`, submit it to the API, and display localized `display_name`.
- People lists reload when the UI locale changes.

Latest Phase 7D frontend verification:

- `npm run build` from `apps/web`: passed with existing classified Nuxt/Nitro/Node warnings.
- Local web server: `http://127.0.0.1:3000`.
- Local API server for smoke check: `http://127.0.0.1:8030/api`.
- Browser smoke opened `http://127.0.0.1:3000/schools/1/students`.
- Browser switched to Bengali and confirmed translated labels.
- Browser confirmed a Bengali-name student renders as `ব্রাউজার বাংলা শিক্ষার্থী`, with English fallback shown underneath.
- Screenshot saved at `docs/browser-checks/localization-students-bn.png`.

Next page/module:

- Phase 7E Offline Support / PWA foundation is complete:
  - `@vite-pwa/nuxt` is installed and configured.
  - PWA manifest and `pwa-icon.svg` exist.
  - service worker generation passes in `npm run build`.
  - npm audit high-severity gate passes after forcing patched `serialize-javascript@7.0.5`.
  - Attendance and Marks routes use local offline drafts via `useOfflineDraft()`.
  - Browser screenshots: `docs/browser-checks/offline-attendance-draft.png`, `docs/browser-checks/offline-marks-draft.png`.
  - Full queued write replay is not implemented yet; follow `docs/phase-7e-offline-pwa-plan.md`.

Previous checkpoint:

Current page/module complete: Phase 7D Multi-Language Support backend foundation.

Latest Phase 7D backend status:

- Added nullable `name_bn` columns to students and employees.
- Added localized `display_name` accessors to `Student` and `Employee`.
- Added reusable school/request locale selection:
  - explicit `?locale=bn|en` wins.
  - Bengali `Accept-Language` can opt into Bengali responses.
  - otherwise the school locale is used, falling back to English.
- Student and employee APIs now apply school locale before serializing responses.
- Student and employee create/update validation accepts `name_bn`.
- Student and employee search includes Bengali names.
- Student and employee audit payloads include `name_bn`.
- Added Laravel JSON translations at `apps/api/lang/en.json` and `apps/api/lang/bn.json`.
- Added `apps/api/tests/Feature/PhaseSevenLocalizationApiTest.php`.

Latest Phase 7D backend verification:

- `php artisan test --filter=PhaseSevenLocalization`: 3 tests / 20 assertions passed.
- `vendor\bin\pint --dirty`: passed after import-order formatting.
- `php artisan migrate --force`: applied `2026_04_20_010000_add_multilingual_name_fields`.
- `php artisan test`: 117 tests / 702 assertions passed.

Next page/module:

- Phase 7D Nuxt i18n frontend integration.

Previous checkpoint:

Current page/module complete: Phase 7C Nuxt Payment Gateway Config workspace.

Latest Phase 7C frontend status:

- Added `PaymentGatewayConfig` type to `apps/web/app/composables/useApi.ts`.
- Added `/schools/{schoolId}/payment-gateways`.
- Dashboard now includes Payment Gateways navigation/action for `payment_gateways.manage`.
- Finance workspace links to Payment Gateways.
- Payment gateway UI supports bKash, Nagad, SSLCommerz, and Stripe config creation.
- Credentials are write-only in the UI; saved configs display only credential key names and encrypted status.
- UI supports active/test-mode controls plus edit/remove actions.

Latest Phase 7C frontend verification:

- `npm run build` from `apps/web`: passed with existing classified Nuxt/Nitro/Node warnings.
- Local web server: `http://127.0.0.1:3000`.
- Local API server: `http://127.0.0.1:8010/api`.
- Browser smoke opened `http://127.0.0.1:3000/schools/1/payment-gateways`.
- Browser confirmed no Vite/Nuxt error overlay.
- Browser created a bKash test-mode config.
- Browser confirmed only credential key names were displayed; secret values were not displayed after save.
- Screenshot saved at `docs/browser-checks/payment-gateways-workspace.png`.

Next page/module:

- Phase 7D Multi-Language Support planning/backend foundation.

Previous checkpoint:

Current page/module complete: Phase 7C Payment Gateway Integration backend foundation.

Latest Phase 7C backend status:

- Added `payment_gateway_configs`.
- Added `App\Models\PaymentGatewayConfig`.
- Added `App\Http\Controllers\Api\PaymentGatewayConfigController`.
- Added `School::paymentGatewayConfigs()`.
- Seeded `payment_gateways.manage`; school-owner receives it through the non-billing permission set, and school-admin/accountant receive it explicitly.
- Added routes:
  - `GET /api/v1/schools/{school}/payment-gateway-configs`
  - `POST /api/v1/schools/{school}/payment-gateway-configs`
  - `GET /api/v1/schools/{school}/payment-gateway-configs/{paymentGatewayConfig}`
  - `PATCH /api/v1/schools/{school}/payment-gateway-configs/{paymentGatewayConfig}`
  - `DELETE /api/v1/schools/{school}/payment-gateway-configs/{paymentGatewayConfig}`
- Supported gateways: `bkash`, `nagad`, `sslcommerz`, and `stripe`.
- Credentials are stored in `credentials_encrypted` using Laravel encrypted array casting.
- API responses hide `credentials_encrypted` and never return plaintext credentials.
- Responses expose only `credentials_configured` and sorted `credential_keys`.
- Audit events include `payment_gateway_config.created`, `payment_gateway_config.updated`, and `payment_gateway_config.deleted`, without credential values.
- Duplicate live configs for the same school/gateway are rejected.
- Added `apps/api/tests/Feature/PhaseSevenPaymentGatewayConfigApiTest.php`.

Latest Phase 7C backend verification:

- `php artisan test --filter=PhaseSevenPaymentGatewayConfig`: 4 tests / 26 assertions passed.
- `vendor\bin\pint --dirty`: passed.
- `php artisan route:list --path=payment-gateway-configs --except-vendor`: 5 routes.
- `php artisan migrate --force`: applied `2026_04_19_070000_create_payment_gateway_configs_table`.
- `php artisan db:seed --class=EnterpriseRolePermissionSeeder --force`: refreshed local RBAC.
- `php artisan test`: 114 tests / 682 assertions passed.

Next page/module:

- Phase 7C Nuxt Payment Gateway Config workspace.

Previous checkpoint:

Current page/module complete: Phase 7B Nuxt Homework and Assignments workspace.

Latest Phase 7B frontend status:

- Added `Assignment` and `AssignmentSubmission` types to `apps/web/app/composables/useApi.ts`.
- Added `/schools/{schoolId}/assignments`.
- Dashboard now includes Assignments navigation/action for `assignments.manage`.
- Timetable workspace links to Assignments.
- Assignments UI supports filters for class, subject, published/draft state, and status.
- Assignments UI supports create/edit/archive for homework assignments.
- Submission UI supports create/edit for student submissions, marks, status, attachment path, and feedback.
- Registers show visible assignments and submissions with summary counters.

Latest Phase 7B frontend verification:

- `npm run build` from `apps/web`: passed with existing classified Nuxt/Nitro/Node warnings.
- Local web server: `http://127.0.0.1:3000`.
- Local API server: `http://127.0.0.1:8010/api`.
- Browser smoke opened `http://127.0.0.1:3000/schools/1/assignments`.
- Browser confirmed no Vite/Nuxt error overlay.
- Browser created a published `Algebra practice browser check` assignment for Class One / Mathematics.
- Browser recorded a graded `87.00` submission for `Assignment Demo Student / ADM-ASSIGN-001 / Roll 21`.
- Screenshot saved at `docs/browser-checks/assignments-workspace.png`.

Next page/module:

- Phase 7C Payment Gateway Integration planning/backend foundation.

Previous checkpoint:

Current page/module complete: Phase 7A Timetable / Routine backend foundation.

Latest Phase 7A backend status:

- Added `timetable_periods`.
- Added `App\Models\TimetablePeriod`.
- Added `App\Policies\TimetablePeriodPolicy`.
- Added `App\Http\Controllers\Api\TimetablePeriodController`.
- Added relationships from School, AcademicYear, AcademicClass, Shift, and Subject.
- Seeded `timetable.manage`; school-admin and principal receive it explicitly, school-owner receives it through the non-billing permission set.
- Added routes:
  - `GET /api/v1/schools/{school}/timetable-periods`
  - `POST /api/v1/schools/{school}/timetable-periods`
  - `GET /api/v1/schools/{school}/timetable-periods/{timetablePeriod}`
  - `PATCH /api/v1/schools/{school}/timetable-periods/{timetablePeriod}`
  - `DELETE /api/v1/schools/{school}/timetable-periods/{timetablePeriod}`
- Controller validates same-school academic year/class/shift/subject references.
- Teacher assignment requires an active school membership.
- Conflict protection blocks duplicate class slots, overlapping class periods, and overlapping teacher bookings.
- Audit events are `timetable_period.created`, `timetable_period.updated`, and `timetable_period.deleted`.
- Added `apps/api/tests/Feature/PhaseSevenTimetableApiTest.php`.

Latest verification:

- `php artisan test --filter=PhaseSevenTimetable`: 5 tests / 24 assertions passed.
- `vendor\bin\pint --dirty`: passed.
- `php artisan route:list --path=timetable-periods --except-vendor`: 5 routes.
- `php artisan migrate --force`: applied `2026_04_19_050000_create_timetable_periods_table`.
- `php artisan db:seed --class=EnterpriseRolePermissionSeeder --force`: refreshed local RBAC.
- `php artisan test`: 104 tests / 624 assertions passed.

Next page/module:

- Phase 7B Nuxt Homework and Assignments workspace.

Current page/module complete: Phase 7B Homework and Assignments backend foundation.

Latest Phase 7B backend status:

- Added `assignments`.
- Added `assignment_submissions`.
- Added `Assignment` and `AssignmentSubmission` models.
- Added assignment/submission policies using `assignments.manage`.
- Added relationships from School, AcademicClass, Subject, and StudentEnrollment.
- Seeded `assignments.manage`; teacher, school-admin, principal, school-owner, and super-admin flows can manage assignment workflows.
- Added routes:
  - `GET /api/v1/schools/{school}/assignments`
  - `POST /api/v1/schools/{school}/assignments`
  - `GET /api/v1/schools/{school}/assignments/{assignment}`
  - `PATCH /api/v1/schools/{school}/assignments/{assignment}`
  - `DELETE /api/v1/schools/{school}/assignments/{assignment}`
  - `GET /api/v1/schools/{school}/assignment-submissions`
  - `POST /api/v1/schools/{school}/assignment-submissions`
  - `GET /api/v1/schools/{school}/assignment-submissions/{assignmentSubmission}`
  - `PATCH /api/v1/schools/{school}/assignment-submissions/{assignmentSubmission}`
  - `DELETE /api/v1/schools/{school}/assignment-submissions/{assignmentSubmission}`
- Assignment validation rejects cross-school class/subject references.
- Submission validation rejects duplicate assignment/enrollment pairs and enrollments outside the assignment class.
- Audit events include `assignment.created`, `assignment.updated`, `assignment.deleted`, `assignment_submission.created`, `assignment_submission.updated`, and `assignment_submission.deleted`.
- Added `apps/api/tests/Feature/PhaseSevenAssignmentsApiTest.php`.

Latest Phase 7B backend verification:

- `php artisan test --filter=PhaseSevenAssignments`: 6 tests / 32 assertions passed.
- `vendor\bin\pint --dirty`: passed.
- Assignment route checks: 10 REST routes.
- `php artisan migrate --force`: applied `2026_04_19_060000_create_assignment_tables`.
- `php artisan db:seed --class=EnterpriseRolePermissionSeeder --force`: refreshed local RBAC.
- `php artisan test`: 110 tests / 656 assertions passed.

Current page/module complete: Phase 7A Nuxt Timetable workspace.

Latest Phase 7A frontend status:

- Added `TimetablePeriod` type to `apps/web/app/composables/useApi.ts`.
- Added `/schools/{schoolId}/timetable`.
- Dashboard now includes Timetable navigation/action for `timetable.manage`.
- Shifts workspace links to Timetable.
- Timetable UI supports filters for academic year, class, shift, day, and status.
- Timetable UI supports create/edit/archive for periods.
- Weekly board groups periods Sunday through Saturday.
- Register table lists visible timetable periods.
- Teacher assignment is not exposed in the UI yet because there is no school member/user picker in the current frontend; backend already supports `teacher_user_id`.

Latest Phase 7A frontend verification:

- `npm run build` from `apps/web`: passed with existing classified Nuxt/Nitro/Node warnings.
- Local web server: `http://127.0.0.1:3000`.
- Local API server: `http://127.0.0.1:8010/api`.
- Browser smoke opened `http://127.0.0.1:3000/schools/1/timetable`.
- Browser created a Sunday `08:00 to 08:45` Mathematics period for Class One / Morning Shift / Room 204.
- Screenshot saved at `docs/browser-checks/timetable-workspace.png`.

Previous checkpoint:

Current page/module complete: Production Stabilization Checkpoint I, Final Stabilization Review.

Production stabilization status:

- Pre-Phase 7 stabilization is complete; use `docs/current-status.md` and `docs/engineering-log.md` for the durable record instead of a separate roadmap file.
- Checkpoint A roadmap was committed and pushed as `933a920 docs: add production stabilization roadmap`.
- Checkpoint B frontend foundation was committed and pushed as `a6bcff8 feat: stabilize frontend foundation`.
- Nuxt frontend dependencies now include Tailwind CSS module, Tailwind v3, Pinia, VueUse, Zod, PostCSS, and Autoprefixer.
- Pinia is intentionally `^3.0.4` with `@pinia/nuxt ^0.11.3` because Vue Router 5 declares optional Pinia 3 peer compatibility.
- `apps/web/.nvmrc` locks Node to `20.11.0`.
- `apps/web/nuxt.config.ts` is SPA mode and loads `@nuxtjs/tailwindcss` plus `@pinia/nuxt`.
- Tailwind module uses `cssPath: '~/assets/css/main.css'`, so the existing Radiant-inspired CSS file is the Tailwind entry.
- `apps/web/tailwind.config.ts` exists.
- `docs/KNOWN-BUILD-WARNINGS.md` classifies the remaining frontend build warnings.
- `npm install` passed.
- `npm run build` from `apps/web` passed with exit code `0`.
- Current Codex shell reports Node `v25.0.0`; project target is Node `20.11.0`.
- Checkpoint C API versioning was committed and pushed as `73cf83b feat: version api routes`.
- Laravel routes are wrapped under `Route::prefix('v1')`; current application routes are under `/api/v1`.
- Nuxt `useApi()` appends `/v1` centrally while `.env` keeps `NUXT_PUBLIC_API_BASE` at the `/api` level.
- Feature tests now target `/api/v1/...`.
- `docs/api-contract.md` and `docs/ARCHITECTURE.md` document API versioning and future v2 deprecation rules.
- `php artisan route:list --path=api/v1 --except-vendor` showed 228 versioned routes.
- `php artisan test` passed with 79 tests / 547 assertions.
- `npm run build` passed after the API client change.
- Checkpoint D environment docs were committed and pushed as `c1b5f44 docs: refresh local development setup`.
- `apps/web/.env.example` includes `NUXT_PUBLIC_API_BASE` and `NUXT_PUBLIC_APP_NAME`.
- `apps/api/.env.example` uses the product app name and keeps database-backed local queue/cache/session defaults.
- `docs/local-development.md` is copy-paste ready for Herd, MySQL, API v1, fallback PHP server, Nuxt, seeded login, and quality gates.
- Checkpoint E Pinia state migration was committed and pushed as `3271652 feat: add pinia auth stores`.
- Pinia stores exist at `apps/web/app/stores/auth.ts` and `apps/web/app/stores/school.ts`.
- `useAuth()` delegates to the Pinia auth store while preserving the old page contract (`auth.token.value`, `auth.schools.value`, etc.).
- The auth store uses the same Nuxt `useState` keys as before, so `useApi()` still reads the bearer token correctly.
- `npm run build` passed after Pinia migration.
- Checkpoint F Permission and Tenant Isolation Tests is complete.
- `apps/api/tests/Feature/PermissionIsolationTest.php` exists.
- Permission isolation coverage now includes:
  - cross-tenant denial for academic sections, students, invoices, school audit logs, and employees.
  - same-school missing-permission denial for section creation, invoice creation, and exam publication.
  - inactive membership denial and unauthenticated school-resource access.
  - owner, teacher, and accountant permission matrix assertions.
- `php artisan test --filter=PermissionIsolation` passed with 12 tests / 26 assertions.
- `vendor\bin\pint --dirty` passed.
- Full `php artisan test` passed with 91 tests / 573 assertions.
- Checkpoint G PDF Rendering Reliability is complete.
- Dedicated report views exist at:
  - `apps/api/resources/views/reports/marksheet.blade.php`
  - `apps/api/resources/views/reports/invoice.blade.php`
- `GenerateReportJob` now routes `marksheet` and `invoice-receipt` exports to dedicated views and falls back to `reports.generic` for other report types.
- Marksheet payloads include enrollment/student/class/section/year, exam/type, marks entries, and result summary data.
- Invoice payloads include student/class/year, invoice totals, and payment history.
- `GenerateReportJob` has `tries = 3` and `backoff = 30`.
- `apps/api/tests/Feature/PdfGenerationTest.php` exists.
- `php artisan test --filter=PdfGeneration` passed with 4 tests / 12 assertions.
- `vendor\bin\pint --dirty` passed after formatting the PDF changes.
- Full `php artisan test` passed with 95 tests / 585 assertions.
- Checkpoint H Background Job Observability is complete.
- The default Laravel jobs migration already creates `jobs`, `job_batches`, and `failed_jobs`.
- Queue config now sets database and Redis queue connections to `after_commit = true`.
- `BulkGenerateStudentInvoices` has `tries = 3` and `backoff = 60`.
- Super-admin job endpoints exist:
  - `GET /api/v1/admin/jobs/status`
  - `POST /api/v1/admin/jobs/{id}/retry`
- Retry delegates to Laravel's `queue:retry` Artisan command, not manual payload reinsertion.
- `apps/api/tests/Feature/JobObservabilityTest.php` exists.
- `php artisan test --filter=JobObservability` passed with 4 tests / 15 assertions.
- `vendor\bin\pint --dirty` passed.
- Full `php artisan test` passed with 99 tests / 600 assertions.
- Checkpoint I Final Stabilization Review is complete.
- `php artisan route:list --path=api/v1 --except-vendor` showed 230 versioned routes.
- `npm run build` from `apps/web` passed with exit code 0.
- Remaining frontend build warnings are classified in `docs/KNOWN-BUILD-WARNINGS.md`:
  - duplicated `useAppConfig` from Nuxt/Nitro internals.
  - `nuxt:module-preload-polyfill` sourcemap warning.
  - Node `DEP0155` under this shell's Node 25 runtime.
- Production stabilization before Phase 7 is complete.

Next page/module:

- Phase 7 planning and implementation from `docs/enterprise-plan-v3.md`.

Previous product checkpoint:

Current page/module complete: Phase 6 Student Promotion workflow UI.

Latest Phase 6 status:

- Backend promotion foundation is complete for preview, draft creation, record override, execute, rollback, and duplicate-execute protection.
- Nuxt promotion workflow UI is complete at `/schools/{schoolId}/promotions`.
- Dashboard now includes Promotions navigation/action access for users with `promotions.manage`.
- Typed promotion API shapes were added to the Nuxt API composable.
- The promotion screen can load year/class options, preview promotion candidates, create draft batches, update record actions/notes, execute, and rollback.

Latest verification:

- `npm run build` from `apps/web` passed with existing Nuxt/Nitro warnings.
- `php artisan db:seed --class=EnterpriseRolePermissionSeeder` refreshed local RBAC permissions.
- Agent-browser logged in locally and opened `http://127.0.0.1:3000/schools/1/promotions`.
- Browser check confirmed nonblank content and no Vite/Nuxt error overlay.
- Screenshot saved at `docs/browser-checks/promotions-workflow.png`.

Local server note:

- Herd Desktop was not running in this Codex shell during the last smoke test.
- Browser verification used Nuxt on `http://127.0.0.1:3000`.
- API verification used direct PHP 8.5 built-in server on `http://127.0.0.1:8010/api`.

Next page/module:

- Phase 6 production hardening for large promotion batches/job dispatch, lifecycle guardrails, and richer seeded/demo data for full browser execution checks.

Current page/module complete: Phase 7E queued offline sync foundation.

Latest Phase 7E status:

- PWA install/service-worker/offline draft foundation is already complete.
- Added durable IndexedDB queue support through `apps/web/app/composables/useOfflineQueue.ts`.
- Added visible queue review/sync UI through `apps/web/app/components/OfflineQueuePanel.vue`.
- Attendance offline submissions now queue locally, replay on "Sync now" or when online, and remove themselves after successful sync.
- Marks offline submissions now use the same queue foundation.
- Queue records keep school id, label, method, endpoint path, payload, status, attempts, timestamps, and sync errors.
- Failed and conflicted queue records remain visible; they are not silently deleted.
- `docs/phase-7e-offline-pwa-plan.md` now tracks implemented queue behavior plus remaining hardening.

Latest verification:

- `npm run build` from `apps/web` passed with the known classified Nuxt/Nitro/Node warnings.
- Browser used `http://127.0.0.1:3000` for Nuxt and `http://127.0.0.1:8030/api` for Laravel.
- Agent-browser queued Attendance offline for `Assignment Demo Student` on `2026-04-21`, saved `docs/browser-checks/offline-attendance-queue.png`, returned online, synced, and confirmed IndexedDB queue records were empty.
- Marks route loaded with queue integration present; complete marks sync was not smoke-tested because the current seeded school has no exam/class-subject options in that page.

Next page/module:

- Commit and push the Phase 7E queue foundation checkpoint, then continue Phase 7E hardening: conflict review UI, auth-expiry stop flow, service worker update docs, and queue tests.

Current page/module in progress: Phase 7E queue failure/auth hardening.

Current page/module complete: Phase 7E queue failure/auth hardening.

Latest changes:

- `useOfflineQueue()` now has an `auth_required` status for API `401`.
- Queue replay stops after the first `401`, keeping the record and avoiding repeated stale-token writes.
- `syncEntries()` returns attempted/synced/failed/conflict/auth-required summary counts.
- Attendance and Marks show sync outcome messages from the summary.
- `OfflineQueuePanel` shows per-status counts, friendly labels, attempt counts, and error messages.

Latest verification:

- `npm run build` from `apps/web` passed with the known classified Nuxt/Nitro/Node warnings.
- Browser used `http://127.0.0.1:3000` for Nuxt and `http://127.0.0.1:8030/api` for Laravel.
- Agent-browser queued a duplicate offline Attendance record for `Assignment Demo Student` on `2026-04-20`, synced online, confirmed the queue retained it as `conflict`, confirmed the page showed “1 attendance record need review before they can sync,” and saved `docs/browser-checks/offline-attendance-conflict.png`.

Next immediate steps:

- Commit and push the Phase 7E queue failure/auth hardening checkpoint.
- Continue Phase 7E with one-click sign-in-again path, richer conflict review UI, service worker update docs, or queue tests.

Current checkpoint: frontend dashboard/design handoff to Antigravity.

Handoff status:

- Codex product work is paused after Phase 7E queue failure/auth hardening.
- Latest pushed code checkpoint before handoff: `f95a4bd`.
- Antigravity will now work on frontend design and dashboard refinement.

Important frontend files for Antigravity:

- Dashboard: `apps/web/app/pages/dashboard.vue`.
- Shared CSS/theme entry: `apps/web/app/assets/css/main.css`.
- Typed API client: `apps/web/app/composables/useApi.ts`.
- Auth facade/store: `apps/web/app/composables/useAuth.ts`, `apps/web/app/stores/auth.ts`.
- Offline queue: `apps/web/app/composables/useOfflineQueue.ts`, `apps/web/app/components/OfflineQueuePanel.vue`.
- Queue-enabled pages: `apps/web/app/pages/schools/[schoolId]/attendance.vue`, `apps/web/app/pages/schools/[schoolId]/marks.vue`.
- Theme reference: `D:\Development\tailwindui-radiant\radiant-ts`.

Design handoff guardrails:

- Keep `NUXT_PUBLIC_API_BASE` at `/api`; `useApi()` appends `/v1`.
- Preserve bearer token and `Accept-Language` headers in `useApi()`.
- Preserve visible offline queue behavior. Failed, conflicted, and auth-required records must remain visible and must not be silently discarded.
- Keep route paths stable unless backend/API docs are updated.

Next Codex resume options:

- Review Antigravity frontend/dashboard changes.
- Continue Phase 7E remaining hardening.
- Continue the next `enterprise-plan-v3.md` priority.

Current checkpoint: demo-data browser verification.

Latest changes:

- Added `apps/api/database/seeders/DemoDataSeeder.php`.
- Added local dev docs for running `php artisan db:seed --class=DemoDataSeeder --force`.
- Demo data now covers the local school across academic setup, people, attendance, timetable, assignments, exams, marks, finance, payment gateways, staff operations, reports, calendar, and documents.

Latest verification:

- `php artisan db:seed --class=DemoDataSeeder --force`: passed.
- Live API login through Herd passed at `https://school-api.test/api/v1/auth/login`.
- Agent-browser logged into `http://127.0.0.1:3000` and smoke-tested all current school workspace routes.
- Re-smoke confirmed the data-dependent modules render populated demo records after adding the seeder.
- Screenshot saved at `docs/browser-checks/demo-data-reports.png`.
- `vendor\bin\pint --test`: passed.
- `php artisan test`: passed with 117 tests / 702 assertions.
- `npm run build`: passed with the existing classified Nuxt/Nitro/Node warnings.

Environment note:

- Laravel Herd is the working local API path for this machine: `https://school-api.test/api`.
- In this shell, `php artisan serve` could not bind to tested local ports, so browser testing used Herd.

Current checkpoint: five-year demo data and dashboard QA.

Latest changes:

- Expanded `apps/api/database/seeders/DemoDataSeeder.php` into a deterministic 2022-2026 school simulation.
- Rebuilt `apps/web/app/pages/dashboard.vue` into a grouped command-center dashboard with live dashboard-summary KPIs and cleaner module navigation.
- Saved final browser evidence at `docs/browser-checks/dashboard-after-five-year-loaded.png`.

Latest verification:

- `php artisan db:seed --class=DemoDataSeeder --force`: passed against local MySQL/Herd.
- Data spot check after seeding: 5 academic years, 5 classes, 50 students, 241 enrollments, 4,322 student attendance records, 8 employees, 10 exams, 4,801 marks, 1,561 invoices, 416 salary records, and 96 promotion records.
- Agent-browser route smoke loaded all current school workspace routes without visible error copy.
- `vendor\bin\pint --test`: passed.
- `php artisan test`: passed with 117 tests / 702 assertions.
- `npm run build`: passed with the existing classified Nuxt/Nitro/Node warnings.

## Recommended First Prompt For A New Session

```text
Read D:\Development\School-SaaS-Enterprise-CONTEXT.md first.
Then continue the School SaaS Enterprise rebuild from the current workspace state.
Use D:\Development\School-SaaS-Enterprise\docs\enterprise-plan-v3.md as the active plan.
When v3 mentions v2, read D:\Development\School-SaaS-Enterprise\docs\enterprise-plan-v2.md as the v2 baseline.
Minimize token usage by summarizing large files instead of pasting them.
```
