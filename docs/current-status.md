# Current Status

Planning rule: `docs/enterprise-plan-v3.md` is the active plan. Whenever v3 mentions v2, it means `docs/enterprise-plan-v2.md`. All v2 baseline rules remain primary, and v3 adds to and extends them unless a section explicitly says otherwise.

## Latest Production Stabilization Status

Current page/module complete: Production Stabilization Checkpoint I, Final Stabilization Review.

- Checkpoint A roadmap is complete and pushed as `933a920 docs: add production stabilization roadmap`.
- Checkpoint B frontend foundation is complete locally and ready for checkpoint commit:
  - added Tailwind CSS module, Tailwind v3, Pinia, VueUse, Zod, PostCSS, and Autoprefixer dependencies.
  - added `apps/web/.nvmrc` with Node `20.11.0`.
  - updated Nuxt config for SPA mode, Pinia, Tailwind module, runtime API base, and project CSS entry.
  - added `apps/web/tailwind.config.ts`.
  - added Tailwind directives to `apps/web/app/assets/css/main.css` while preserving the Radiant-inspired custom UI layer.
  - documented all current frontend build warnings in `docs/KNOWN-BUILD-WARNINGS.md`.
- `npm install` initially exposed a Pinia peer conflict; it was resolved by using Pinia `^3.0.4` with `@pinia/nuxt ^0.11.3`, matching Vue Router 5.
- `npm run build` from `apps/web` passes with exit code `0`.
- Current Codex shell uses Node `v25.0.0`; the project target is locked to Node `20.11.0`.

- Checkpoint C API versioning is complete locally and ready for checkpoint commit:
  - all Laravel API routes are wrapped under `/api/v1`.
  - legacy `/api/user` is now versioned as `/api/v1/user`.
  - Nuxt `useApi()` appends `/v1` centrally while `NUXT_PUBLIC_API_BASE` stays at `/api`.
  - all existing feature-test URLs were updated to `/api/v1`.
  - `docs/api-contract.md` and `docs/ARCHITECTURE.md` document v1 compatibility and future v2 deprecation rules.
- `php artisan route:list --path=api/v1 --except-vendor` shows 228 versioned routes.
- `php artisan test` from `apps/api` passes with 79 tests / 547 assertions.
- `npm run build` from `apps/web` passes after the API client change.

- Checkpoint D Environment and Local Development cleanup is complete locally and ready for checkpoint commit:
  - `apps/web/.env.example` now includes `NUXT_PUBLIC_APP_NAME`.
  - `apps/api/.env.example` uses the product app name and keeps the current database-backed local drivers.
  - `docs/local-development.md` is rewritten with copy-paste setup for Herd, MySQL, API v1, fallback PHP server, Nuxt, seeded login, and quality gates.

- Checkpoint E Pinia State Migration is complete and pushed:
  - added `apps/web/app/stores/auth.ts`.
  - added `apps/web/app/stores/school.ts`.
  - added `apps/web/app/stores/index.ts`.
  - migrated `useAuth()` to delegate to the Pinia auth store while preserving the existing `auth.token.value`/`auth.schools.value` page contract.
  - auth, school selection, permissions, and localStorage token persistence still use the same Nuxt state keys.
- `npm run build` from `apps/web` passes after the Pinia migration.
- An app-caused duplicate store auto-import warning was fixed before commit; remaining frontend warnings are the classified Nuxt/Nitro/Node warnings in `docs/KNOWN-BUILD-WARNINGS.md`.

- Checkpoint F Permission and Tenant Isolation Tests is complete:
  - added `apps/api/tests/Feature/PermissionIsolationTest.php`.
  - covered cross-tenant denial for academic sections, students, invoices, audit logs, and employees.
  - covered same-school permission denial for sections, finance/invoices, and exam publication.
  - covered inactive membership and unauthenticated access behavior.
  - added a permission matrix for owner, teacher, and accountant roles.
- `php artisan test --filter=PermissionIsolation` passes with 12 tests / 26 assertions.
- `vendor\bin\pint --dirty` passes.
- `php artisan test` passes with 91 tests / 573 assertions.

- Checkpoint G PDF Rendering Reliability is complete:
  - added dedicated Blade PDF views for marksheets and invoice receipts.
  - updated `GenerateReportJob` to select dedicated views for `marksheet` and `invoice-receipt`, with `reports.generic` as fallback.
  - added report-specific payload assembly for enrollment/exam/marks/result-summary data and invoice/payment-history data.
  - added retry/backoff properties to `GenerateReportJob`.
  - added `apps/api/tests/Feature/PdfGenerationTest.php`.
- `php artisan test --filter=PdfGeneration` passes with 4 tests / 12 assertions.
- `vendor\bin\pint --dirty` fixed and passed for the PDF changes.
- `php artisan test` passes with 95 tests / 585 assertions.

- Checkpoint H Background Job Observability is complete:
  - confirmed `jobs`, `job_batches`, and `failed_jobs` already exist in the default Laravel jobs migration.
  - set database and Redis queue connections to dispatch after commit.
  - added `tries = 3` and `backoff = 60` to `BulkGenerateStudentInvoices`.
  - added super-admin endpoints:
    - `GET /api/v1/admin/jobs/status`
    - `POST /api/v1/admin/jobs/{id}/retry`
  - retry uses Laravel's `queue:retry` Artisan command path rather than manual payload reinsertion.
  - added `apps/api/tests/Feature/JobObservabilityTest.php`.
- `php artisan test --filter=JobObservability` passes with 4 tests / 15 assertions.
- `vendor\bin\pint --dirty` passes.
- `php artisan test` passes with 99 tests / 600 assertions.

- Checkpoint I Final Stabilization Review is complete:
  - `php artisan test` passes with 99 tests / 600 assertions.
  - `php artisan route:list --path=api/v1 --except-vendor` shows 230 versioned routes.
  - `npm run build` passes with exit code 0.
  - remaining frontend build warnings are the classified Nuxt/Nitro/Node warnings already documented in `docs/KNOWN-BUILD-WARNINGS.md`.
- `docs/session-context.md` and root context are refreshed for low-token session startup.

Current page/module complete: Post-dashboard QA hardening and full browser workflow smoke.

- Added a reusable Playwright smoke script at `apps/web/scripts/browser-workflow-smoke.mjs`.
- Added `npm run qa:browser` in `apps/web/package.json`.
- Browser smoke now covers 10 real workflows:
  - academic classes
  - academic sections
  - academic years
  - subjects
  - student groups, shifts, designations
  - guardians and students
  - attendance
  - calendar
  - finance category/structure/invoice/bulk queue
  - reports queue/check
- Latest passing browser artifact: `docs/browser-checks/workflow-smoke-20260420234054.png`.
- Fixed pagination/status issues that made newly created records disappear from active workspaces:
  - academic classes
  - academic sections
  - academic years
  - subjects
  - student groups
  - shifts
  - designations
- Fixed the students workspace so newly created guardians remain selectable for the student form even when guardian tables are paginated.
- Fixed the attendance workspace button text so edit mode shows `Update attendance`.
- Fixed finance form accessibility by wiring labels to controls with explicit `for`/`id`.
- Verification after QA hardening:
  - `npm run qa:browser` passed with 10 checks.
  - `npm run build` passed.
  - `php artisan test` passed with 117 tests / 702 assertions.
  - `vendor\bin\pint --test` passed.

Current page/module complete: Phase 7A Timetable / Routine backend foundation.

- Added tenant-scoped timetable periods under `/api/v1/schools/{school}/timetable-periods`.
- Added `timetable_periods` schema with academic year, class, optional shift, weekday, period number, time window, optional subject, optional teacher, room, status, timestamps, and soft deletes.
- Added `TimetablePeriod` model, policy, school/class/year/shift/subject relationships, and audit logging for create/update/delete.
- Seeded `timetable.manage` for owner-style/admin/principal roles through the enterprise RBAC baseline.
- Added validation for same-school references and active school-member teacher assignment.
- Added conflict protection for duplicate class slots, overlapping class periods, and overlapping teacher assignments.
- Added `apps/api/tests/Feature/PhaseSevenTimetableApiTest.php`.
- `php artisan test --filter=PhaseSevenTimetable` passed with 5 tests / 24 assertions.
- `vendor\bin\pint --dirty` passed.
- `php artisan route:list --path=timetable-periods --except-vendor` shows 5 timetable REST routes.
- `php artisan migrate --force` applied the timetable migration to the local database.
- `php artisan db:seed --class=EnterpriseRolePermissionSeeder --force` refreshed local RBAC with `timetable.manage`.
- `php artisan test` passed with 104 tests / 624 assertions.

Current page/module complete: Phase 7A Nuxt Timetable workspace.

- Added typed Nuxt `TimetablePeriod` API shape.
- Added dashboard navigation and action access for users with `timetable.manage`.
- Added `/schools/{schoolId}/timetable`.
- Added timetable filters for academic year, class, shift, day, and status.
- Added create/edit/archive flow for routine periods with year, class, optional shift, day, period number, time window, optional subject, room, and status.
- Added a weekly board grouped by Sunday through Saturday plus a register table.
- Added Timetable link from the Shifts workspace.
- `npm run build` from `apps/web` passed with the existing classified Nuxt/Nitro/Node warnings.
- Local browser verification passed at `http://127.0.0.1:3000/schools/1/timetable`.
- Browser created a real Sunday `08:00 to 08:45` `Mathematics` period for `Class One`, `Morning Shift`, `Room 204`.
- Screenshot saved at `docs/browser-checks/timetable-workspace.png`.

Local site link: `http://127.0.0.1:3000/schools/1/timetable`.

Current page/module complete: Phase 7B Homework and Assignments backend foundation.

- Added `assignments` and `assignment_submissions`.
- Added `Assignment` and `AssignmentSubmission` models, policies, school/class/subject/enrollment relationships, and audit logging.
- Added staff-facing assignment REST routes under `/api/v1/schools/{school}/assignments`.
- Added assignment submission REST routes under `/api/v1/schools/{school}/assignment-submissions`.
- Seeded `assignments.manage`; teacher, school-admin, principal, school-owner, and super-admin flows can manage assignment workflows.
- Assignment validation rejects cross-school class/subject references.
- Submission validation rejects cross-school references, duplicate student submissions, and enrollments outside the assignment class.
- Added `apps/api/tests/Feature/PhaseSevenAssignmentsApiTest.php`.
- `php artisan test --filter=PhaseSevenAssignments` passed with 6 tests / 32 assertions.
- `vendor\bin\pint --dirty` passed.
- Assignment route checks show 10 REST routes across assignments and assignment submissions.
- `php artisan migrate --force` applied the assignment migration to the local database.
- `php artisan db:seed --class=EnterpriseRolePermissionSeeder --force` refreshed local RBAC.
- `php artisan test` passed with 110 tests / 656 assertions.

Current page/module complete: Phase 7B Nuxt Homework and Assignments workspace.

- Added typed Nuxt `Assignment` and `AssignmentSubmission` API shapes.
- Added `/schools/{schoolId}/assignments`.
- Added dashboard navigation and action access for users with `assignments.manage`.
- Added assignment filters for class, subject, published/draft state, and status.
- Added create/edit/archive flow for homework assignments with class, subject, title, description, due date, optional attachment path, published state, and status.
- Added submission filters for assignment and status.
- Added create/edit flow for assignment submissions with student enrollment, submitted timestamp, status, marks, optional attachment path, and feedback.
- Added assignment summary cards plus assignment and submission registers.
- Added Assignments links from Dashboard and Timetable.
- `npm run build` from `apps/web` passed with the existing classified Nuxt/Nitro/Node warnings.
- Local browser verification passed at `http://127.0.0.1:3000/schools/1/assignments`.
- Browser created a real published `Algebra practice browser check` assignment for `Class One / Mathematics`.
- Browser recorded a graded `87.00` submission for `Assignment Demo Student / ADM-ASSIGN-001 / Roll 21`.
- Screenshot saved at `docs/browser-checks/assignments-workspace.png`.

Local site link: `http://127.0.0.1:3000/schools/1/assignments`.

Current page/module complete: Phase 7C Payment Gateway Integration backend foundation.

- Added `payment_gateway_configs` for tenant-scoped gateway setup.
- Supported planned gateways: `bkash`, `nagad`, `sslcommerz`, and `stripe`.
- Added encrypted credential storage through Laravel encrypted array casts.
- Gateway credentials are never returned in API responses; responses expose only `credentials_configured` and sorted `credential_keys`.
- Added `PaymentGatewayConfig` model and `School::paymentGatewayConfigs()` relationship.
- Added tenant-scoped REST routes under `/api/v1/schools/{school}/payment-gateway-configs`.
- Added `payment_gateways.manage` permission and granted it to school-owner, school-admin, and accountant flows.
- Added audit logs for create/update/delete without storing plaintext secrets.
- Added duplicate active gateway validation per school.
- Added cross-tenant and missing-permission coverage in `apps/api/tests/Feature/PhaseSevenPaymentGatewayConfigApiTest.php`.
- `php artisan test --filter=PhaseSevenPaymentGatewayConfig` passed with 4 tests / 26 assertions.
- `vendor\bin\pint --dirty` passed.
- `php artisan route:list --path=payment-gateway-configs --except-vendor` shows 5 REST routes.
- `php artisan migrate --force` applied the payment gateway config migration to the local database.
- `php artisan db:seed --class=EnterpriseRolePermissionSeeder --force` refreshed local RBAC.
- `php artisan test` passed with 114 tests / 682 assertions.

Current page/module complete: Phase 7C Nuxt Payment Gateway Config workspace.

- Added typed Nuxt `PaymentGatewayConfig` API shape.
- Added `/schools/{schoolId}/payment-gateways`.
- Added dashboard navigation and action access for users with `payment_gateways.manage`.
- Added Finance workspace link to Payment Gateways.
- Added gateway setup form for bKash, Nagad, SSLCommerz, and Stripe.
- Kept credentials write-only in the UI; saved configs show only configured key names and encrypted status.
- Added active/test-mode controls plus configured/active/live/test summary cards.
- Added gateway register with edit and remove actions.
- `npm run build` from `apps/web` passed with the existing classified Nuxt/Nitro/Node warnings.
- Local browser verification passed at `http://127.0.0.1:3000/schools/1/payment-gateways`.
- Browser created a real bKash test-mode config and confirmed only credential keys were displayed.
- Screenshot saved at `docs/browser-checks/payment-gateways-workspace.png`.

Local site link: `http://127.0.0.1:3000/schools/1/payment-gateways`.

Current page/module complete: Phase 7D Multi-Language Support backend foundation.

- Added nullable `name_bn` fields to students and employees.
- Added localized `display_name` accessors to `Student` and `Employee`.
- Added request/school locale handling for school-scoped responses:
  - explicit `?locale=bn|en` wins.
  - Bengali `Accept-Language` can opt into Bengali responses.
  - otherwise the school locale is used, falling back to English.
- Student and employee create/update validation now accepts `name_bn`.
- Student and employee search now includes Bengali names.
- Student and employee audit payloads include `name_bn`.
- Added Laravel JSON translation files for `en` and `bn`.
- Added `apps/api/tests/Feature/PhaseSevenLocalizationApiTest.php`.
- `php artisan test --filter=PhaseSevenLocalization` passed with 3 tests / 20 assertions.
- `vendor\bin\pint --dirty` passed after import-order formatting.
- `php artisan migrate --force` applied the multilingual-name migration locally.
- `php artisan test` passed with 117 tests / 702 assertions.

Current page/module complete: Phase 7D Nuxt i18n frontend integration.

- Installed and configured `@nuxtjs/i18n`.
- Added English and Bengali message catalogs through `apps/web/i18n.config.ts`.
- Added a reusable `LocaleSwitcher` component.
- Added school-locale sync through `useSchoolLocale()`.
- Nuxt API requests now send `Accept-Language` from shared locale state.
- Dashboard now exposes the language switcher.
- Students and Employees workspaces now:
  - include Bengali-name inputs.
  - submit `name_bn` to the API.
  - display localized `display_name`, with English fallback underneath when different.
  - reload people lists when the UI locale changes.
- `npm run build` from `apps/web` passed with the existing classified Nuxt/Nitro/Node warnings.
- Browser verification passed at `http://127.0.0.1:3000/schools/1/students`.
- Browser created/loaded a Bengali-name student and showed `ব্রাউজার বাংলা শিক্ষার্থী` after switching to Bengali.
- Screenshot saved at `docs/browser-checks/localization-students-bn.png`.

Local site link: `http://127.0.0.1:3000/schools/1/students`.

Current page/module complete: Phase 7E Offline Support / PWA foundation.

- Installed and configured `@vite-pwa/nuxt`.
- Added PWA manifest metadata and `apps/web/public/pwa-icon.svg`.
- Generated a service worker during `npm run build`.
- Added network-first runtime caching for attendance and marks workspace routes.
- Added shared `useNetworkStatus()` and `useOfflineDraft()` composables.
- Added reusable `OfflineNotice`.
- Attendance workspace now supports local draft save/restore/clear and saves a draft instead of attempting API writes while offline.
- Marks workspace now supports local draft save/restore/clear and saves a draft instead of attempting API writes while offline.
- Added `docs/phase-7e-offline-pwa-plan.md` for the remaining production-grade queue/replay/conflict design.
- Added an npm override for `serialize-javascript` so the PWA/Workbox dependency tree uses the patched `7.0.5` release.
- `npm run build` from `apps/web` passed with the existing classified Nuxt/Nitro/Node warnings.
- `npm audit --audit-level=high` from `apps/web` reports `found 0 vulnerabilities`.
- Browser verification passed at:
  - `http://127.0.0.1:3000/schools/1/attendance`
  - `http://127.0.0.1:3000/schools/1/marks`
- Screenshots saved:
  - `docs/browser-checks/offline-attendance-draft.png`
  - `docs/browser-checks/offline-marks-draft.png`

Phase 7E status: PWA and offline draft foundation is complete. Full queued write replay remains the next dedicated offline slice.

Local site link: `http://127.0.0.1:3000/schools/1/attendance`.

Next page/module: Phase 7E queued offline sync implementation or the next v3 priority.

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
- Accepted `docs/enterprise-plan-v3.md` as the active enterprise plan.
- Added Phase 3.0 Stabilization before Exams:
  - shared `AuditLogger` service
  - centralized controller audit logging
  - capped `per_page` helper
  - consistent paginated API envelope with top-level `data`, `meta`, and `links`
  - paginated all index endpoints
  - School show/update endpoints
  - `schools.manage` enforcement and audit logging for school updates
  - auth and API rate limiters
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

## Milestone Notes

- MySQL database creation and local API switch are complete.
- Phase 3 Exams foundation API is complete.
- Nuxt Exams workspace is complete.
- Phase 3 Operations backend APIs are complete for Marks Entry, Grade Configuration, Fee Structure, Student Invoices and Payments, Salary Records, Employee Attendance, Employee Leave, and enhanced Student Attendance bulk entry.
- Phase 3 Operations Nuxt workspaces are complete:
  - `/schools/{schoolId}/marks`
  - `/schools/{schoolId}/finance`
  - `/schools/{schoolId}/staff-operations`

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
- `php artisan test` from `apps/api`: passed after Phase 3.0 Stabilization, 47 tests / 293 assertions.
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
- `vendor\bin\pint --test` from `apps/api`: passed after Phase 3.0 Stabilization.
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
- `npm run build` from `apps/web`: passed after Phase 3.0 Stabilization, with existing Nuxt/Nitro warnings.
- `php artisan route:list --path=api/schools` from `apps/api`: passed after Phase 3.0 Stabilization.
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
- Agent-browser opened `http://127.0.0.1:3000/dashboard`, confirmed nonblank content and no error overlay, then opened Academic Classes after Phase 3.0 Stabilization.
- Saved browser screenshots at `docs/browser-checks/phase-3-stabilization-home.png` and `docs/browser-checks/phase-3-stabilization-academic-classes.png`.
- Local MySQL check found `mysql.exe` at `C:\Program Files\MySQL\MySQL Server 8.0\bin\mysql.exe`; the provided root password worked, MySQL reported version `8.0.45`, and `school_saas_enterprise` was created/confirmed.
- `php artisan migrate:fresh --seed` from `apps/api`: passed against MySQL.
- Added exam foundation backend:
  - `exam_types` with `weightage_percent`
  - `exams` with `is_published`, `published_at`, and `published_by`
  - `exam_schedules` linked to `class_subjects`
  - tenant-scoped Exam Type, Exam, and Exam Schedule APIs
  - `exams.manage` policies and `exams.publish` seeded permission
  - audit logs for create/update/delete
- Live Herd API smoke against MySQL passed:
  - login as `test@example.com`
  - school creation
  - paginated `GET /api/schools?per_page=1`
- Agent-browser login reached `http://127.0.0.1:3000/dashboard` and confirmed the MySQL-created school was visible.
- Saved browser screenshot at `docs/browser-checks/mysql-dashboard.png`.
- `php artisan test` from `apps/api`: passed after Exam foundation API, 49 tests / 320 assertions.
- `php artisan test` from `apps/api`: passed after Phase 3 Operations backend, 53 tests / 370 assertions.
- `vendor\bin\pint --test` from `apps/api`: passed after Exam foundation API.
- `vendor\bin\pint --test` from `apps/api`: passed after Phase 3 Operations backend.
- `php artisan route:list --path=api/schools --except-vendor` from `apps/api`: passed after Exam foundation API, 89 routes.
- `php artisan route:list --path=api/schools --except-vendor` from `apps/api`: passed after Phase 3 Operations backend, 161 routes.
- `php artisan migrate:fresh --seed` from `apps/api`: passed against MySQL after Exam foundation API.
- `php artisan migrate:fresh --seed` from `apps/api`: passed against MySQL after Phase 3 Operations backend.
- Live Herd API smoke passed for exam type, exam, exam schedule creation, and paginated schedule listing.
- Added Nuxt Exams workspace:
  - typed `ExamType`, `Exam`, and `ExamSchedule` API shapes
  - `/schools/{schoolId}/exams`
  - weighted exam type form
  - exam window form
  - class-subject schedule form
  - exam and schedule tables
  - dashboard navigation gated by `exams.manage`
- `npm run build` from `apps/web`: passed after Nuxt Exams workspace, with existing Nuxt/Nitro warnings.
- `npm run build` from `apps/web`: passed after Phase 3 Operations Nuxt workspaces, with existing Nuxt/Nitro warnings.
- Agent-browser opened `http://127.0.0.1:3000/schools/1/exams`, confirmed no error overlay, and confirmed live MySQL data rendered (`Midterm 2026`, `Room 101`).
- Saved browser screenshot at `docs/browser-checks/exams-workspace.png`.
- Agent-browser opened `http://127.0.0.1:3000/schools/1/marks`, confirmed no error overlay, and confirmed the Marks and Grades workspace rendered.
- Agent-browser opened `http://127.0.0.1:3000/schools/1/finance`, confirmed no error overlay, and confirmed the Finance workspace rendered.
- Agent-browser opened `http://127.0.0.1:3000/schools/1/staff-operations`, confirmed no error overlay, and confirmed the Staff Operations workspace rendered.
- Saved browser screenshots at `docs/browser-checks/marks-workspace.png`, `docs/browser-checks/finance-workspace.png`, and `docs/browser-checks/staff-operations-workspace.png`.
- Nuxt dev server startup from this Codex shell did not become reachable on port 3000; production build remains valid.
- Initial sandbox runs hit Windows permission/process limits, then passed outside the sandbox with approval.

## Next Step

Continue Phase 6 implementation:

1. Commit and push the Phase 6 Promotion backend foundation checkpoint.
2. Build the Nuxt promotion workflow UI.

Current page/module complete: Phase 4 Nuxt Reports, Calendar, Documents, and Publication workspace.
Latest UI correction: the Nuxt frontend has been refreshed to follow `D:\Development\tailwindui-radiant\radiant-ts` more closely, including Radiant-style warm gradients, plus-grid background lines, black pill buttons, translucent secondary controls, glass-like panels, and larger tight-tracked headings. The old green theme tokens were removed from app source.
Phase 2 status: complete for the current academic setup and people foundation.
Phase 3 status: complete for backend APIs, Nuxt workspaces, build, and browser smoke checks.
Phase 4 status: complete for backend APIs, queued PDFs, signed report downloads, analytics, result publication, cached summaries, employee/student attendance summaries, in-app notification inbox, school calendar, holiday import, payment/leave notification hooks, document management, Nuxt workspaces, build, and browser smoke checks.
Latest Phase 4 backend verification: `php artisan test` passed with 65 tests / 445 assertions; `vendor\bin\pint --test` passed; `composer audit` passed with no advisories; `php artisan route:list --path=api/schools --except-vendor` passed with 190 routes; `php artisan migrate:fresh --seed` passed against MySQL.
Latest Phase 4 frontend verification: `npm run build` passed with existing Nuxt/Nitro warnings; agent-browser rendered `/schools/1/reports`, `/schools/1/calendar`, and `/schools/1/documents` with no Vite error overlay; screenshots are saved under `docs/browser-checks/phase-4-*-workspace.png`.
Latest UI verification: `npm run build` passed with existing Nuxt/Nitro warnings; agent-browser refreshed screenshots at `docs/browser-checks/radiant-login-refresh.png`, `docs/browser-checks/radiant-dashboard-refresh.png`, and `docs/browser-checks/radiant-reports-refresh.png`.
Current page/module complete: Phase 5 SaaS Administration backend foundation.
Latest Phase 5 backend implementation: added SaaS school columns (`plan`, `subscription_status`, `trial_ends_at`, `plan_limits`), typed v3 school settings stored in `schools.settings`, `GET/PATCH /api/schools/{school}/settings`, `super.admin` middleware, super-admin school/user/audit/system endpoints, school-scoped audit log viewer, onboarding trial/default role setup, and plan-limit enforcement for students/employees/documents.
Latest Phase 5 backend verification: `php artisan test --filter=PhaseFiveSaasAdminApiTest` passed with 5 tests / 24 assertions; full `php artisan test` passed with 70 tests / 469 assertions; `vendor\bin\pint --dirty` completed; `php artisan migrate --force` applied the migration to local MySQL.
Current page/module complete: Phase 5 User Invitation backend flow.
Latest Phase 5 invitation implementation: added `school_invitations`, `SchoolInvitation`, tenant invitation create/list/revoke endpoints, authenticated `POST /api/invitations/{token}/accept`, email ownership/expiry validation, membership activation, invited role assignment, and invitation audit logs.
Latest Phase 5 invitation verification: `php artisan test --filter=PhaseFiveSaasAdminApiTest` passed with 7 tests / 42 assertions; full `php artisan test` passed with 72 tests / 487 assertions; `vendor\bin\pint --dirty` completed; `php artisan migrate --force` applied the invitation migration to local MySQL.
Current page/module complete: Phase 5 Parent and Student Portal backend endpoints.
Latest Phase 5 portal implementation: added all v3 student and parent portal routes through `PortalController`, linking students by `students.email` and parents by `guardians.email`; endpoints return profile/children, attendance, results, invoices, and notifications while enforcing `student.portal.view`/`parent.portal.view` and school portal settings.
Latest Phase 5 portal verification: `php artisan test --filter=PhaseFiveSaasAdminApiTest` passed with 9 tests / 64 assertions; full `php artisan test` passed with 74 tests / 509 assertions; `vendor\bin\pint --dirty` passed; route list confirmed 10 portal routes.
Current page/module complete: Phase 5 Data Export and Student Anonymization backend.
Latest Phase 5 data export implementation: added `data_export_jobs`, `DataExportJob`, data export request/download endpoints, JSON export artifact generation, and student anonymization endpoint that clears personal fields, detaches guardian linkage, archives the student, and writes audit logs.
Latest Phase 5 data export verification: `php artisan test --filter=PhaseFiveSaasAdminApiTest` passed with 11 tests / 77 assertions; full `php artisan test` passed with 76 tests / 522 assertions; `vendor\bin\pint --dirty` completed; `php artisan migrate --force` applied the export job migration to local MySQL.
Current page/module complete: Phase 5 Self-hosted deployment and backup/restore operations.
Latest Phase 5 operations implementation: added `docs/self-hosted-deployment.md`, `php artisan school:backup`, `php artisan school:backup --school={id}`, and `php artisan school:restore {archive}` with confirmation plus `--force`.
Latest Phase 5 operations verification: `php artisan list school` showed both commands; `php artisan school:backup --help` passed; `php artisan school:backup --school=1` wrote a local backup archive; `vendor\bin\pint --dirty` passed; full `php artisan test` passed with 76 tests / 522 assertions.
Git status: Phase 5 Self-hosted Operations checkpoint is ready to commit and push.
Next page/module: next planned phase or Phase 5 Nuxt administration screens.
Phase 5 status: backend Phase 5 is complete for SaaS admin foundation, settings, plan limits, onboarding, audit viewer, invitations, parent/student portals, data export/right-to-erasure, self-hosted deployment docs, and backup/restore commands.
Current page/module complete: Phase 6 Student Promotion backend foundation.
Latest Phase 6 implementation: added promotion batch/record tables, models, `promotions.manage`, preview/create/record override/execute/rollback APIs, failed-result retention suggestions, enrollment creation/completion, rollback within 48 hours, and promotion audit logs.
Latest Phase 6 verification: `php artisan migrate --force` applied the promotion migration to local MySQL after shortening a MySQL index name; `php artisan test --filter=PhaseSixPromotionApiTest` passed with 3 tests / 25 assertions; `vendor\bin\pint --dirty` passed; full `php artisan test` passed with 79 tests / 547 assertions.
Git status: Phase 6 Promotion backend foundation is ready to commit and push.
Next page/module: Phase 6 Nuxt promotion workflow UI.

Current page/module complete: Phase 6 Student Promotion workflow UI.
Latest Phase 6 frontend implementation: added typed promotion API shapes, dashboard Promotions navigation/action, and `/schools/1/promotions` workflow UI for previewing candidates, creating draft batches, overriding promotion actions/notes, executing batches, and rolling back completed batches.
Latest Phase 6 frontend verification: `npm run build` from `apps/web` passed with existing Nuxt/Nitro warnings; `php artisan db:seed --class=EnterpriseRolePermissionSeeder` refreshed local RBAC permissions; agent-browser logged in locally, opened `http://127.0.0.1:3000/schools/1/promotions`, confirmed nonblank content and no Vite/Nuxt error overlay, and saved `docs/browser-checks/promotions-workflow.png`.
Local server note: Herd Desktop was not running in this Codex shell, so the browser smoke used Nuxt at `http://127.0.0.1:3000` and PHP 8.5's built-in server at `http://127.0.0.1:8010/api`.
Git status: Phase 6 Promotion workflow UI checkpoint is ready to commit and push.
Next page/module: Phase 6 promotion hardening for large batches/job dispatch, lifecycle guardrails, and fuller browser execution demo data.

### Phase 7E IndexedDB Offline Queue Foundation

Current page/module complete: Phase 7E queued offline sync foundation, routes `/schools/{schoolId}/attendance` and `/schools/{schoolId}/marks`.

Scope: extended the PWA/offline slice from local drafts into a visible IndexedDB-backed write queue:
- added `useOfflineQueue()` for durable queued writes with status, attempts, timestamps, endpoint, method, and payload metadata.
- added `OfflineQueuePanel` for pending, failed, and conflicted local records.
- Attendance now queues offline submissions and can replay them manually or when the browser returns online.
- Marks now queues offline submissions and can replay them manually or when the browser returns online.
- successful sync removes queue records; validation/duplicate failures remain visible as conflicts; other sync errors remain visible as failed records.
- updated `docs/phase-7e-offline-pwa-plan.md` from planned queue design to implemented queue foundation plus remaining hardening.

Verification: `npm run build` from `apps/web` passed with the existing classified Nuxt/Nitro/Node warnings. Browser smoke used API `http://127.0.0.1:8030/api` and web `http://127.0.0.1:3000`; agent-browser opened `/schools/1/attendance`, queued an offline attendance record for `Assignment Demo Student` on `2026-04-21`, saved `docs/browser-checks/offline-attendance-queue.png`, returned online, synced the queue, and confirmed IndexedDB queue records were cleared. The Marks page loaded with queue wiring present; the current seeded school has no exam/class-subject options for a complete marks submission smoke test.

Phase 7E status: PWA, offline drafts, and first queued write replay foundation are complete for Attendance and Marks. Remaining Phase 7E hardening: login-expiry stop flow, richer local-vs-server conflict review, service worker update deployment notes, and automated queue tests.

Next page/module: continue Phase 7E hardening or move to the next `enterprise-plan-v3.md` priority after checkpointing this queue foundation.

### Phase 7E Queue Failure/Auth Hardening

Current page/module complete: Phase 7E queue failure/auth hardening, shared by Attendance and Marks.

Scope: tightened the offline queue replay behavior:
- added `auth_required` queue status for API `401` responses.
- sync now stops after the first `401` instead of repeatedly sending stale-token writes.
- sync returns a summary with attempted, synced, failed, conflict, and auth-required counts.
- Attendance and Marks now show accurate success/error messages based on that summary.
- `OfflineQueuePanel` now shows ready/failed/conflict/auth-required counts, friendly status labels, attempt counts, and retained error text.

Verification: `npm run build` from `apps/web` passed with the existing classified Nuxt/Nitro/Node warnings. Browser smoke used API `http://127.0.0.1:8030/api` and web `http://127.0.0.1:3000`; agent-browser queued a duplicate offline Attendance record for `Assignment Demo Student` on `2026-04-20`, synced online, confirmed the record stayed in IndexedDB as `conflict` with one attempt and retained error text, confirmed the page showed “1 attendance record need review before they can sync,” and saved `docs/browser-checks/offline-attendance-conflict.png`.

Phase 7E remaining hardening: one-click sign-in-again path from queue records, richer conflict review UI, service worker update deployment notes, and queue-focused automated tests.

### Frontend Design Handoff Checkpoint

Current checkpoint: frontend dashboard/design handoff to Antigravity.

Scope:
- Codex backend/product work is paused after Phase 7E queue failure/auth hardening.
- The current Nuxt frontend has working routes, API wiring, Radiant-inspired styling, PWA/offline support, i18n foundation, and many school workspaces.
- Antigravity will now take over frontend design and dashboard refinement.
- Preserve existing API contracts in `apps/web/app/composables/useApi.ts` unless a coordinated backend change is made.
- Preserve auth/session behavior through `useAuth()`, Pinia auth store, and `useApi()` bearer token injection.
- Preserve the Phase 7E queue behavior in Attendance and Marks while redesigning UI surfaces:
  - queued writes are stored in IndexedDB.
  - conflicts and auth-required records must remain visible.
  - records must not be silently discarded.
- Dashboard design entry point: `apps/web/app/pages/dashboard.vue`.
- Shared frontend CSS entry point: `apps/web/app/assets/css/main.css`.
- Theme reference remains `D:\Development\tailwindui-radiant\radiant-ts`.

Last verified checkpoint before handoff:
- Git commit: `f95a4bd`.
- `npm run build` from `apps/web`: passed with known classified Nuxt/Nitro/Node warnings.
- `npm audit --audit-level=high`: `found 0 vulnerabilities`.
- Browser queue conflict smoke passed at `http://127.0.0.1:3000/schools/1/attendance` using API `http://127.0.0.1:8030/api`.

Next frontend/design owner: Antigravity.

### Demo Data Verification Checkpoint

Current page/module complete: full local demo-data smoke foundation and live browser audit.

Scope:
- Added `apps/api/database/seeders/DemoDataSeeder.php`.
- The seeder is idempotent and fills the local demo tenant with active records for academic setup, sections, groups, shifts, class subjects, employee/teacher profile, student/enrollment, attendance, timetable, assignments, exams/schedules, verified marks, grade scale, finance, payment gateway, salary, staff attendance, leave, calendar, documents, and report summaries.
- Updated `docs/local-development.md` with the demo seeder command.

Verification:
- `php artisan db:seed --class=DemoDataSeeder --force`: passed against local MySQL/Herd.
- Live API login passed at `https://school-api.test/api/v1/auth/login` with `test@example.com` / `password`.
- Agent-browser logged into `http://127.0.0.1:3000` and smoke-tested every Nuxt page under the demo school:
  - dashboard
  - academic classes, sections, years, subjects, class subjects, groups, shifts
  - timetable, assignments, students, enrollments, teacher profiles, attendance
  - exams, marks, designations, employees, finance, payment gateways, staff operations
  - reports, promotions, calendar, documents
- Empty-module gaps found in the first smoke were fixed by demo data; re-smoke confirmed records render in the data-dependent modules.
- Screenshot saved: `docs/browser-checks/demo-data-reports.png`.
- `vendor\bin\pint --test`: passed.
- `php artisan test`: passed with 117 tests / 702 assertions.
- `npm run build` from `apps/web`: passed with the existing classified Nuxt/Nitro/Node warnings.

Known environment note:
- `php artisan serve` could not bind to tested local ports in this shell, but Laravel Herd served the API correctly at `https://school-api.test`.

### Five-Year Demo Data And Dashboard QA Checkpoint

Current page/module complete: five-year operating data simulation, dashboard layout/menu refresh, and full module route smoke.

Scope:
- Expanded `apps/api/database/seeders/DemoDataSeeder.php` from one-record-per-module data into a deterministic five-year school dataset.
- The demo school now has academic years 2022-2026, five classes, section mapping, student groups, shifts, subjects, class-subject assignments, employees, teacher profiles, guardians, students, enrollments, attendance history, timetable periods, assignments/submissions, exams/schedules, marks/results, invoices/payments, salary records, leave, promotions, calendar events, documents, and audit logs.
- Rebuilt `apps/web/app/pages/dashboard.vue` into a command-center dashboard with grouped module navigation, live dashboard-summary KPIs, collections trend, attention counters, and a cleaner tenant setup area.

Verification:
- `php artisan db:seed --class=DemoDataSeeder --force`: passed against local MySQL/Herd.
- Demo data count spot check after seeding included 5 academic years, 5 classes, 50 students, 241 enrollments, 4,322 student attendance records, 8 employees, 10 exams, 4,801 marks, 1,561 invoices, 416 salary records, 96 promotion records, and 21 calendar events.
- Agent-browser loaded the new dashboard at `http://127.0.0.1:3000/dashboard`; final screenshot saved at `docs/browser-checks/dashboard-after-five-year-loaded.png`.
- Agent-browser route smoke loaded every current module page without visible error copy:
  - dashboard
  - academic years, classes, sections, subjects, class subjects, groups, shifts
  - timetable, assignments, students, enrollments, teacher profiles, attendance
  - designations, employees, exams, marks, reports, promotions, calendar, documents
  - finance, payment gateways, staff operations
- `vendor\bin\pint --test`: passed.
- `php artisan test`: passed with 117 tests / 702 assertions.
- `npm run build`: passed with the existing classified Nuxt/Nitro/Node warnings.

## New Session Startup Prompt

```text
Read D:\Development\School-SaaS-Enterprise-CONTEXT.md and D:\Development\School-SaaS-Enterprise\docs\current-status.md.
Continue from the current status.
Use D:\Development\School-SaaS-Enterprise\docs\enterprise-plan-v3.md as the active plan.
When v3 mentions v2, read D:\Development\School-SaaS-Enterprise\docs\enterprise-plan-v2.md as the v2 baseline.
Minimize token usage.
```
