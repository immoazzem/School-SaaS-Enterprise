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

## 2026-04-23

### Pending Checkpoint - Nested Routes Repaired And Setup Mutation QA Passing

Current page/module complete: nested admin and school workspace routes now resolve correctly, and the core academic setup modules passed super-admin create/archive browser QA.

Scope:
- repaired Nuxt route nesting by moving:
  - `apps/web/pages/admin.vue` -> `apps/web/pages/admin/index.vue`
  - `apps/web/pages/schools.vue` -> `apps/web/pages/schools/index.vue`
- fixed `apps/web/pages/admin/index.vue` metric notes so health, active-school, and failed-job values render through bound strings instead of literal moustache text.
- re-verified the nested route families that had been silently rendering the wrong parent screens:
  - `/admin`
  - `/admin/schools`
  - `/admin/users`
  - `/schools/1/academic-classes`
  - `/schools/1/academic-sections`
  - `/schools/1/academic-years`
- completed super-admin mutation QA for the school setup flows:
  - academic classes create/archive
  - academic sections create/archive
  - academic years create/archive
  - subjects create/archive
  - student groups create/archive
  - shifts create/archive
  - designations create/archive

Verification:
- `cd apps/web && npm run build`: passed.
- browser verification passed with no console errors and no `4xx/5xx` responses for:
  - `/admin`
  - `/admin/schools`
  - `/admin/users`
  - `/schools/1/academic-classes`
  - `/schools/1/academic-sections`
  - `/schools/1/academic-years`
- browser artifacts saved at:
  - `docs/browser-checks/admin-schools-20260423.png`
  - `docs/browser-checks/nested-routes-qa-20260423.png`

Notes:
- this fixed a meaningful hidden defect: child pages existed, but parent route files were swallowing them because the file-based structure was wrong.
- a few initial failed screenshots were generated before the route repair and can be ignored; they were selector/routing diagnostics, not the final state.
- build still emits the previously known Nuxt/Nitro warnings already tracked in `docs/KNOWN-BUILD-WARNINGS.md`.

Next:
- continue deeper mutation QA on the remaining school-scoped flows, starting with enrollments, students, employees, exams, reports, and finance exception cases.

## 2026-04-22

### Pending Checkpoint - Root Operator Pages Moved Off Mock Data

Current page/module complete: root operator pages now read from live API data instead of `schoolDashboardData.ts`.

Scope:
- replaced the remaining mock-driven root pages with live API-backed views:
  - `apps/web/pages/index.vue`
  - `apps/web/pages/analytics.vue`
  - `apps/web/pages/students.vue`
  - `apps/web/pages/classes.vue`
  - `apps/web/pages/attendance.vue`
  - `apps/web/pages/marks.vue`
  - `apps/web/pages/reports.vue`
  - `apps/web/pages/finance/fees.vue`
- redirected `apps/web/pages/second-page.vue` to `/` so the old template stub no longer ships.
- deleted `apps/web/utils/schoolDashboardData.ts` because the rebuilt root operator surfaces no longer depend on curated mock arrays.

Verification:
- `cd apps/web && npm run build`: passed.
- super-admin browser sweep passed with no console errors and no `4xx/5xx` responses for:
  - `/`
  - `/analytics`
  - `/students`
  - `/classes`
  - `/attendance`
  - `/marks`
  - `/reports`
  - `/finance/fees`
- browser artifacts saved at:
  - `docs/browser-checks/root-dashboard-live-20260422.png`
  - `docs/browser-checks/route-_analytics-20260422.png`
  - `docs/browser-checks/route-_students-20260422.png`
  - `docs/browser-checks/route-_classes-20260422.png`
  - `docs/browser-checks/route-_attendance-20260422.png`
  - `docs/browser-checks/route-_marks-20260422.png`
  - `docs/browser-checks/route-_reports-20260422.png`
  - `docs/browser-checks/route-_finance_fees-20260422.png`

Notes:
- one runtime defect surfaced during the first browser pass: the root reports page called a nonexistent `/report-exports` endpoint. That was removed and the page now derives its queue from live report-capable modules.
- build still emits the previously known Nuxt/Nitro warnings already tracked in `docs/KNOWN-BUILD-WARNINGS.md`.

Next:
- continue school-scoped mutation QA using the super-admin and role accounts on the ten-year seeded environment.
- focus next on deeper create/update/delete workflows and cross-module regressions rather than adding more broad overview pages.

### Pending Checkpoint - Enterprise Route Coverage And Ten-Year QA Baseline

Current page/module complete: missing route-family frontend coverage, role-aware admin UX, and ten-year seeded QA baseline.

Scope:
- added the remaining explicit frontend surfaces for backend route families that were still missing:
  - `apps/web/pages/admin.vue`
  - `apps/web/pages/admin/schools.vue`
  - `apps/web/pages/admin/users.vue`
  - `apps/web/pages/admin/jobs.vue`
  - `apps/web/pages/admin/audit-logs.vue`
  - `apps/web/pages/schools/[schoolId]/notifications.vue`
  - `apps/web/pages/schools/[schoolId]/invitations.vue`
  - `apps/web/pages/schools/[schoolId]/settings.vue`
  - `apps/web/pages/schools/[schoolId]/discounts.vue`
  - `apps/web/pages/schools/[schoolId]/invoice-payments.vue`
  - `apps/web/pages/schools/[schoolId]/portal-student.vue`
  - `apps/web/pages/schools/[schoolId]/portal-parent.vue`
- added `apps/web/composables/useEnterpriseAccess.ts`.
- updated admin pages to stop calling enterprise-only APIs for non-super-admin users and render a clean informational access state instead.
- expanded `apps/api/database/seeders/DemoDataSeeder.php` into a deterministic ten-year demo environment:
  - years `2017-2026`
  - role accounts for super admin, school owner, school admin, principal, teacher, accountant, student, parent, and read-only auditor
  - seeded discounts, invitations, notifications, and portal-compatible users
- reset local database with:
  - `cd apps/api && php artisan migrate:fresh --seed --seeder=DemoDataSeeder`

Verification:
- `cd apps/api && php artisan migrate:fresh --seed --seeder=DemoDataSeeder`: passed.
- API login verified for:
  - `superadmin@example.com`
  - `test@example.com`
  - `sadia.islam@example.com`
  - `farhana.kabir@example.com`
  - `mahmud.alam@example.com`
  - `student001@example.com`
  - `guardian001@example.com`
  - `auditor@example.com`
- browser role sweep passed with no console errors for:
  - `/admin`, `/admin/schools`, `/admin/users`, `/admin/jobs`, `/admin/audit-logs`
  - `/dashboard`, `/schools/1/students`, `/schools/1/finance`, `/schools/1/reports`, `/schools/1/settings`
  - `/schools/1/portal-student`, `/schools/1/notifications`
  - `/schools/1/portal-parent`, `/schools/1/notifications`
  - `/schools/1/invoice-payments`, `/schools/1/discounts`
- browser artifacts saved at:
  - `docs/browser-checks/super-admin-20260422-role-qa-v2.png`
  - `docs/browser-checks/school-owner-20260422-role-qa-v2.png`
  - `docs/browser-checks/student-20260422-role-qa-v2.png`
  - `docs/browser-checks/parent-20260422-role-qa-v2.png`
  - `docs/browser-checks/accountant-20260422-role-qa-v2.png`
  - `docs/browser-checks/login-live-inspect-20260422.png`
- `cd apps/api && php artisan test`: passed with `117 tests / 702 assertions`.
- `cd apps/web && npm run build`: passed.

Notes:
- build still emits the previously known Nuxt/Nitro warnings already tracked in `docs/KNOWN-BUILD-WARNINGS.md`.
- this pass materially improves route coverage and QA readiness, but it is not the final claim that every module has been mutation-tested end to end.

Next:
- continue role-by-role, module-by-module mutation QA under the ten-year seed.
- fix defects uncovered in the remaining less-traveled routes before the next checkpoint commit.

### Pending Checkpoint - Live Portfolio, Communications, And Settings Surfaces

Current page/module complete: `schools`, `notices`, and `settings` converted from placeholders into live management pages.

Scope:
- rebuilt `apps/web/pages/schools.vue` to:
  - refresh real school membership context
  - create schools through `/api/v1/schools`
  - switch selected school context
  - open live school-scoped workspaces
- rebuilt `apps/web/pages/notices.vue` to:
  - read `/api/v1/schools/{school}/notifications`
  - read unread counts
  - mark notifications read
  - list/create/revoke invitations through `/api/v1/schools/{school}/invitations`
- rebuilt `apps/web/pages/settings.vue` to:
  - read and save `/api/v1/schools/{school}/settings`
  - conditionally surface enterprise health, jobs, and audit data only for users with enterprise admin access
- kept the restored school-scoped pages compiling and loading alongside the rebuilt root shell.

Verification:
- `cd apps/web && npm run build`: passed.
- browser verification passed for:
  - `http://127.0.0.1:3000/schools`
  - `http://127.0.0.1:3000/notices`
  - `http://127.0.0.1:3000/settings`
  - `http://127.0.0.1:3000/schools/1/students`
- browser artifacts saved at:
  - `docs/browser-checks/-schools-qa-20260422.png`
  - `docs/browser-checks/-notices-qa-20260422.png`
  - `docs/browser-checks/-settings-qa-20260422.png`
  - `docs/browser-checks/settings-qa-20260422-v2.png`

Notes:
- the frontend now covers more of the live backend surface without pretending everything is finished.
- the next meaningful slice is explicit UI for the remaining backend-only domains: admin schools/users/jobs/audit, finance exceptions, and portal-facing visibility.

Next:
- continue filling frontend coverage gaps against the backend route inventory.
- then move into broader seeded-data QA and defect fixing across the restored school workspaces.

### Pending Checkpoint - Root-Level Operator Page Redesign Cluster

Current page/module complete: analytics, attendance, marks, finance, reports, classes, notices, schools, and settings redesigned on the new shell.

Scope:
- extended the rebuilt root-level frontend language through:
  - `apps/web/pages/analytics.vue`
  - `apps/web/pages/attendance.vue`
  - `apps/web/pages/marks.vue`
  - `apps/web/pages/finance/fees.vue`
  - `apps/web/pages/reports.vue`
  - `apps/web/pages/classes.vue`
  - `apps/web/pages/notices.vue`
  - `apps/web/pages/schools.vue`
  - `apps/web/pages/settings.vue`
- expanded `apps/web/utils/schoolDashboardData.ts` with the supporting operational signals for those screens:
  - analytics signals and cohort signals
  - attendance escalations
  - marks release-readiness states
  - finance pressure points
  - report-library groups
  - class-planning signals
  - communication-performance signals
  - portfolio signals
  - settings-domain ownership labels
- pushed the pages away from plain table-only layouts and toward a consistent pattern of:
  - page header with actions
  - summary/decision cards
  - one primary register or queue

Verification:
- `cd apps/web && npm run build`: passed.
- Playwright browser verification passed for:
  - `http://127.0.0.1:3000/analytics`
  - `http://127.0.0.1:3000/attendance`
  - `http://127.0.0.1:3000/marks`
  - `http://127.0.0.1:3000/finance/fees`
  - `http://127.0.0.1:3000/reports`
  - `http://127.0.0.1:3000/settings`
- screenshots saved at:
  - `docs/browser-checks/frontend-rebuild-analytics-20260422.png`
  - `docs/browser-checks/frontend-rebuild-attendance-20260422.png`
  - `docs/browser-checks/frontend-rebuild-marks-20260422.png`
  - `docs/browser-checks/frontend-rebuild-finance-20260422.png`
  - `docs/browser-checks/frontend-rebuild-reports-20260422.png`
  - `docs/browser-checks/frontend-rebuild-settings-20260422.png`

Notes:
- this cluster gives the rebuild a shared product voice across the core route set instead of leaving only login/dashboard/students polished.
- the shell now reads much more like one operations suite, though a final tightening pass is still needed before checkpointing.

Next:
- continue through any remaining root-level routes that still feel underdesigned.
- then create a restore-point commit for the frontend rebuild state.

### Pending Checkpoint - Frontend Redesign Reset And Signal-Informed Shell

Current page/module complete: frontend redesign reset, shell rebuild, and first rendered page pass.

Scope:
- treated the current frontend as a fresh rebuild target and ignored the prior Antigravity visual direction.
- rebuilt the new root-level Nuxt frontend around the current admin-theme app structure instead of the older `apps/web/app/*` tree.
- rewrote the authenticated shell through:
  - `apps/web/layouts/components/DefaultLayoutWithVerticalNav.vue`
  - `apps/web/navigation/vertical/index.ts`
  - `apps/web/assets/styles/styles.scss`
  - `apps/web/themeConfig.ts`
  - `apps/web/plugins/webfontloader.client.ts`
- added new school-operations mock data and reusable page primitives:
  - `apps/web/utils/schoolDashboardData.ts`
  - `apps/web/components/SchoolMetricCard.vue`
  - `apps/web/components/SchoolPageHeader.vue`
- rebuilt core pages:
  - `apps/web/pages/login.vue`
  - `apps/web/pages/index.vue`
  - `apps/web/pages/students.vue`
  - plus the matching route set for analytics, attendance, marks, classes, reports, notices, schools, settings, and finance
- aligned the look with the requested Signal reference:
  - Inter + JetBrains Mono
  - dark left rail
  - compact top bar
  - calm pale workspace canvas
  - restrained green emphasis
- fixed live boot/runtime issues found during browser work:
  - corrected Sass forwarding in `apps/web/assets/styles/variables/_template.scss`
  - removed the fragile Google Fonts Sass import from `apps/web/assets/styles/styles.scss`
  - replaced invalid `definePage(...)` usage with `definePageMeta(...)` across rebuilt pages
  - reduced top-bar/page-header duplication
  - improved login hero contrast

Verification:
- `cd apps/web && npm run build`: passed.
- live browser verification passed with Playwright screenshots for:
  - `http://127.0.0.1:3000/login`
  - `http://127.0.0.1:3000/`
  - `http://127.0.0.1:3000/students`
- screenshots saved at:
  - `docs/browser-checks/frontend-rebuild-login-playwright-20260422-v2.png`
  - `docs/browser-checks/frontend-rebuild-dashboard-playwright-20260422-v2.png`
  - `docs/browser-checks/frontend-rebuild-students-playwright-20260422.png`

Notes:
- dev-only agent-browser screenshots were misleading until the Sass font import was removed; Playwright screenshots are the reliable artifacts for this slice.
- the current repo state is still a large frontend replacement diff because the old `apps/web/app/*` structure has effectively been superseded by the new root-level app structure.
- Nuxt still emits the same duplicated `useAppConfig` / Node runtime warnings already seen earlier; they were not introduced by this redesign pass.

Next:
- continue the same redesign pass across analytics, attendance, marks, reports, finance, and settings.
- then update the low-token session context and create the next restore-point commit.

### Pending Checkpoint - Frontend Dashboard and High-Traffic Workspace Migration

Current page/module complete: dashboard, students, finance, reports, and attendance moved deeper into the rebuilt frontend system.

Scope:
- updated `apps/web/app/pages/dashboard.vue` to use more native Vuetify building blocks:
  - `VCard`
  - `VCardText`
  - `VRow`
  - `VCol`
  - `VChip`
  - `VBtn`
  - `VAlert`
  - `VSheet`
  - `VProgressCircular`
- preserved the existing dashboard business logic while replacing the older surface language with a more enterprise admin presentation.
- updated `apps/web/app/pages/schools/[schoolId]/students.vue` so actions, loading states, and feedback messaging align with the rebuilt shell.
- updated `apps/web/app/pages/schools/[schoolId]/finance.vue` so header actions, queue actions, and loading states now match the rebuilt interface system.
- updated `apps/web/app/pages/schools/[schoolId]/reports.vue` so export/open/refresh actions and feedback states use Vuetify components instead of older ad hoc buttons.
- updated `apps/web/app/pages/schools/[schoolId]/attendance.vue` so action bars, alerts, and loading treatments are now consistent with the rebuilt shell.

Verification:
- `cd apps/web && npm run build`: passed.
- direct Playwright browser verification passed for:
  - `http://127.0.0.1:3000/dashboard`
  - `http://127.0.0.1:3000/schools/1/students`
  - `http://127.0.0.1:3000/schools/1/finance`
  - `http://127.0.0.1:3000/schools/1/reports`
  - `http://127.0.0.1:3000/schools/1/attendance`
- screenshots saved at:
  - `docs/browser-checks/frontend-dashboard-polish-20260422.png`
  - `docs/browser-checks/frontend-students-polish-20260422.png`
  - `docs/browser-checks/frontend-finance-polish-20260422.png`
  - `docs/browser-checks/frontend-reports-polish-20260422.png`
  - `docs/browser-checks/frontend-attendance-polish-20260422.png`

Notes:
- the long `npm run qa:browser` harness is still due for a shell-specific adaptation, but direct route verification passed and no browser console errors were recorded on the checked pages.
- the known Nuxt/Nitro/Node warnings remain unchanged and are still the classified warnings tracked in `docs/KNOWN-BUILD-WARNINGS.md`.

Next:
- checkpoint and push this page-migration slice.
- continue with the next rebuild cluster: enrollments, exams, marks, and staff-facing operations.

## 2026-04-21

### Pending Checkpoint - Frontend Rebuild Foundation

Current page/module complete: frontend rebuild foundation and shared shell migration.

Scope:
- switched the frontend design source to the current admin theme reference in `D:\Development\Theme and templates`.
- installed the core frontend packages in `apps/web`:
  - `vuetify@3.10.8`
  - `vite-plugin-vuetify@2.1.2`
  - `sass@1.76.0`
- updated `apps/web/nuxt.config.ts` to:
  - register the Vuetify Vite plugin
  - transpile Vuetify
  - preserve the existing Nuxt/Tailwind/i18n/API setup
- added `apps/web/app/plugins/vuetify.ts` with the current light theme palette and component defaults.
- updated `apps/web/app/app.vue` to mount the app inside `VApp` / `VMain`.
- migrated the shared authenticated shell through:
  - `apps/web/app/components/SchoolWorkspaceTemplate.vue`
  - `apps/web/app/components/SchoolWorkspaceRail.vue`
  - `apps/web/app/utils/schoolWorkspaceNav.ts`
  - `apps/web/app/assets/css/main.css`
- kept the backend/API contracts, auth composables, and route paths intact while changing the frontend frame.

Verification:
- `cd apps/web && npm install`: passed after adding Vuetify packages.
- `cd apps/web && npm run build`: passed.
- focused browser verification passed for:
  - login to dashboard
  - `http://127.0.0.1:3000/dashboard`
  - `http://127.0.0.1:3000/schools/1/academic-classes`
- screenshots saved at:
  - `docs/browser-checks/frontend-dashboard-20260422.png`
  - `docs/browser-checks/frontend-academic-classes-20260422.png`

Notes:
- the long `npm run qa:browser` flow still needs another adjustment pass for the rebuilt shell. The app itself builds and the targeted browser checks passed.
- known Nuxt/Nitro/Node warnings remain the same classified warnings in `docs/KNOWN-BUILD-WARNINGS.md`.

Next:
- continue the frontend rebuild by migrating the dashboard and the highest-traffic school workspaces onto more native card/form/list patterns.
- then adapt the browser smoke harness to the rebuilt shell and checkpoint the phase cleanly.

### Pending Checkpoint - High-Traffic Workspace Polish

Current page/module complete: Students, Finance, Reports, and Attendance frontend polish.

Scope:
- added shared utility layout rules in `apps/web/app/assets/css/main.css` for:
  - `filters`
  - `search-form`
  - `strip-actions`
  - `insight-grid`
  - `mini-list`
- updated `apps/web/app/pages/schools/[schoolId]/students.vue`:
  - loading state now uses a shared surface
  - guardian list moved into the shared `table-wrap`
  - surfaced actual status filters for guardians and students
- updated `apps/web/app/pages/schools/[schoolId]/finance.vue`:
  - improved header actions
  - added supporting header copy
  - moved loading into the shared surface treatment
  - made outstanding totals read as BDT
- updated `apps/web/app/pages/schools/[schoolId]/reports.vue`:
  - improved header actions
  - added supporting header copy
  - moved loading into the shared surface treatment
- updated `apps/web/app/pages/schools/[schoolId]/attendance.vue`:
  - replaced older `panel`/`table-header` usage with `record-form`, `record-list`, and `list-header`
  - upgraded summary cards to the shared `summary-item` treatment
  - added a direct queue sync action in the header
  - moved the records table into the shared `table-wrap`

Verification:
- `cd apps/web && npm run build`: passed.
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

Notes:
- this pass stayed focused on consistency, operator clarity, and shared layout rules rather than introducing a fresh visual system.
- the existing classified Nuxt/Nitro/Node warnings remain unchanged.

Next:
- commit and push this page-polish checkpoint.
- continue with the next workspace polish slice: enrollments, exams, and staff operations.

### Pending Checkpoint - Dashboard Command Center Polish

Current page/module complete: dashboard command-center polish and restore point after markdown cleanup.

Scope:
- removed stale markdown files that were not part of the active execution path:
  - `apps/api/README.md`
  - `apps/web/README.md`
  - `docs/IMPROVEMENT-ROADMAP.md`
- updated `docs/session-context.md` so new sessions use the durable status/log files instead of the removed roadmap.
- refined `apps/web/app/pages/dashboard.vue` into a clearer operator dashboard:
  - top overview surface with tenant context
  - access summary counts
  - quick-action launchpad for the highest-frequency work
  - collections trend and attention-required queue
  - grouped domain map for the full workspace
  - cleaner tenant creation and access management area

Verification:
- `cd apps/web && npm run build`: passed.
- agent-browser verified `http://127.0.0.1:3000/dashboard`.
- screenshot saved at `docs/browser-checks/dashboard-polish-20260421-110839.png`.

Notes:
- this pass stayed within the shared compact shell rather than introducing a one-off dashboard style.
- the same classified Nuxt/Nitro/Node build warnings remain and were not changed by this checkpoint.

Next:
- commit and push this dashboard restore point.
- continue with page-level UI polish on the highest-traffic workspaces after the dashboard baseline.

### Pending Checkpoint - Compact Salient-Informed Workspace Shell

Current page/module complete: shared compact enterprise shell, compact surfaces, and full school-page scaffold migration.

Scope:
- used `D:\Development\tailwindui-salient` as the design resource for spacing, restraint, and calmer visual hierarchy.
- added `apps/web/app/components/SchoolWorkspaceTemplate.vue` as the shared authenticated page scaffold.
- migrated the dashboard plus every page in `apps/web/app/pages/schools/[schoolId]` onto the shared scaffold.
- rebuilt `apps/web/app/assets/css/main.css` around a compact enterprise UI system:
  - denser button sizing
  - flatter cards/surfaces
  - tighter forms
  - cleaner tables
  - calmer module tiles
  - compact mobile/desktop shell behavior
- refined `apps/web/app/components/SchoolWorkspaceRail.vue` so the sidebar behaves as a desktop column and a mobile drawer without intercepting workspace interactions.
- fixed a regression found by browser QA where the sidebar overlay blocked form clicks on desktop.

Verification:
- `cd apps/web && npm run build`: passed.
- `cd apps/web && npm run qa:browser`: passed with 10 workflow checks.
- local frontend reachable at `http://127.0.0.1:3000`.
- latest browser artifact: `docs/browser-checks/workflow-smoke-20260421043826.png`.

Notes:
- this pass intentionally improved structure first, not decorative styling. The frontend is now on a stronger shared shell for future visual polish.
- Nuxt still reports the same pre-existing build warnings already known in `docs/KNOWN-BUILD-WARNINGS.md`.

Next:
- review the compact shell visually with the user’s next design direction.
- make a clean checkpoint commit once this frontend pass is approved.

### Pending Checkpoint - Antigravity Frontend Refresh Review

Current page/module complete: Antigravity dashboard and workspace visual refresh with verified browser QA.

Scope:
- reviewed Antigravity's uncommitted frontend pass across the dashboard, login page, shared workspace rail, design tokens, and core school workspaces.
- kept the shared-shell architecture in place while accepting the new visual system centered on:
  - `apps/web/app/assets/css/main.css`
  - `apps/web/tailwind.config.ts`
  - `apps/web/app/components/SchoolWorkspaceRail.vue`
  - `apps/web/app/pages/dashboard.vue`
  - `apps/web/app/pages/index.vue`
- confirmed the new mobile drawer behavior in the shared rail.
- updated `apps/web/scripts/browser-workflow-smoke.mjs` so the QA suite works with both the old login copy and the new Antigravity login copy.
- excluded one-off helper cleanup scripts from the checkpoint:
  - `normalize_layouts.mjs`
  - `apps/web/clean_orphans.ps1`
  - `apps/web/app/pages/schools/[schoolId]/clean_orphans.ps1`

Verification:
- `cd apps/web && npm run build`: passed.
- direct Playwright browser QA passed for:
  - login
  - dashboard
  - mobile drawer
  - `/schools/1/academic-classes`
  - `/schools/1/students`
  - `/schools/1/reports`
  - `/schools/1/attendance`
- `cd apps/web && npm run qa:browser`: passed with 10 workflow checks.
- useful artifacts saved at:
  - `docs/browser-checks/antigravity-dashboard-qa-20260421.png`
  - `docs/browser-checks/antigravity-mobile-drawer-20260421.png`
  - `docs/browser-checks/workflow-smoke-20260421040744.png`

Notes:
- the big frontend diff is real product work, not just style noise; core workflows still pass after the UI pass.
- known Nuxt/Nitro/Node warnings remain the same classified warnings from `docs/KNOWN-BUILD-WARNINGS.md`.

Next:
- commit and push the Antigravity frontend checkpoint.
- continue with deeper visual review or focused UI polish on the remaining less-traveled pages.

### Pending Checkpoint - Frontend Shell Stabilization Before Antigravity

Current page/module complete: dashboard and workspace navigation shell unification.

Scope:
- added `apps/web/app/components/SchoolWorkspaceRail.vue` as the shared left navigation rail for authenticated workspaces.
- added `apps/web/app/utils/schoolWorkspaceNav.ts` to centralize enterprise module definitions and grouping.
- updated `apps/web/app/pages/dashboard.vue` to use the shared rail instead of its old bespoke sidebar.
- updated the main school workspace pages to use the same rail with contextual links:
  - assignments
  - attendance
  - calendar
  - documents
  - enrollments
  - exams
  - finance
  - marks
  - payment gateways
  - promotions
  - reports
  - staff operations
  - teacher profiles
  - timetable
- improved school switching behavior inside the shared rail so changing schools from a school workspace keeps the user in the same module path.

Verification:
- `cd apps/web && npm run build`: passed.
- local frontend reachable at `http://127.0.0.1:3000`.
- browser checks:
  - sign-in to dashboard succeeded.
  - dashboard shell rendered with the shared rail and module groups.
  - no browser error overlay detected.
  - screenshot artifact saved at `docs/browser-checks/dashboard-shell-20260421.png`.

Notes:
- the known Nuxt/Nitro warnings from `docs/KNOWN-BUILD-WARNINGS.md` still appear on build and were not introduced by this checkpoint.
- this is intended as the restore point before Antigravity begins the visual redesign pass.
- a direct browser jump to `/schools/1/finance` in the current session returned to dashboard, so only the dashboard shell is claimed as visually verified in this checkpoint.

Next:
- create the git restore-point commit and push it.
- hand the stabilized shell to Antigravity for dashboard and design-system improvements.

### Pending Checkpoint - Post-Dashboard QA Hardening

Current page/module complete: cross-module browser workflow smoke, pagination hardening, and form accessibility cleanup.

Scope:
- added `apps/web/scripts/browser-workflow-smoke.mjs` as a reusable Playwright-driven QA pass.
- added `npm run qa:browser`.
- covered 10 workflows end to end: academic classes, academic sections, academic years, subjects, groups/shifts/designations, guardians/students, attendance, calendar, finance, and reports.
- fixed active-list visibility issues by requesting larger active datasets and adding active-status filtering where the UI is meant to show current operational rows:
  - `apps/api/app/Http/Controllers/Api/AcademicClassController.php`
  - `apps/api/app/Http/Controllers/Api/AcademicSectionController.php`
  - `apps/web/app/pages/schools/[schoolId]/academic-classes.vue`
  - `apps/web/app/pages/schools/[schoolId]/academic-sections.vue`
  - `apps/web/app/pages/schools/[schoolId]/academic-years.vue`
  - `apps/web/app/pages/schools/[schoolId]/subjects.vue`
  - `apps/web/app/pages/schools/[schoolId]/student-groups.vue`
  - `apps/web/app/pages/schools/[schoolId]/shifts.vue`
  - `apps/web/app/pages/schools/[schoolId]/designations.vue`
- fixed student/guardian workflow scalability in `apps/web/app/pages/schools/[schoolId]/students.vue` by separating guardian select options from the paginated guardian table.
- fixed attendance edit-state button text in `apps/web/app/pages/schools/[schoolId]/attendance.vue`.
- fixed finance form label/input associations in `apps/web/app/pages/schools/[schoolId]/finance.vue`.

Verification:
- `npm run qa:browser`: passed with 10 checks.
- `npm run build`: passed.
- `php artisan test`: passed with 117 tests / 702 assertions.
- `vendor\bin\pint --test`: passed.
- browser artifact saved at `docs/browser-checks/workflow-smoke-20260420234054.png`.

Next:
- commit and push this QA hardening checkpoint.
- continue with deeper module-by-module mutation coverage or the next `enterprise-plan-v3.md` slice.

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

### Production Stabilization Final Review

Current page/module complete: Production Stabilization Checkpoint I, Final Stabilization Review.

Scope: closed the pre-Phase 7 stabilization gate:
- ran final backend regression tests.
- listed versioned API routes after queue observability routes were added.
- ran the Nuxt production build after all stabilization code changes.
- confirmed remaining frontend build warnings are the known/classified Nuxt/Nitro/Node warnings in `docs/KNOWN-BUILD-WARNINGS.md`.
- refreshed `docs/current-status.md`, `docs/session-context.md`, `docs/engineering-log.md`, and root `D:\Development\School-SaaS-Enterprise-CONTEXT.md`.

Verification: `php artisan test` passed with 99 tests / 600 assertions; `php artisan route:list --path=api/v1 --except-vendor` showed 230 versioned routes; `npm run build` passed with exit code 0.

Next: begin Phase 7 planning and implementation from `docs/enterprise-plan-v3.md`.

### Phase 7A Timetable Backend Foundation

Current page/module complete: Phase 7A Timetable / Routine backend foundation.

Scope: started Phase 7 from `docs/enterprise-plan-v3.md` with the backend routine module:
- added `timetable_periods` with school, academic year, class, optional shift, weekday, period number, start/end time, optional subject, optional teacher, room, status, timestamps, and soft deletes.
- added `TimetablePeriod` model, policy, and relationships from School, AcademicYear, AcademicClass, Shift, and Subject.
- added tenant-scoped timetable REST routes under `/api/v1/schools/{school}/timetable-periods`.
- seeded `timetable.manage` and granted it to school-admin/principal flows; school-owner inherits it through the full non-billing permission set.
- added audit logs for create, update, and delete events.
- added same-school validation for academic year/class/shift/subject references and active school-member validation for assigned teachers.
- added conflict checks for duplicate class period slots, overlapping class periods, and overlapping teacher bookings.
- added focused feature coverage in `apps/api/tests/Feature/PhaseSevenTimetableApiTest.php`.

Verification: `php artisan test --filter=PhaseSevenTimetable` passed with 5 tests / 24 assertions; `vendor\bin\pint --dirty` passed; `php artisan route:list --path=timetable-periods --except-vendor` showed 5 timetable routes; `php artisan migrate --force` applied the timetable migration to the local database; `php artisan db:seed --class=EnterpriseRolePermissionSeeder --force` refreshed local RBAC; full `php artisan test` passed with 104 tests / 624 assertions.

Phase 7 status: timetable backend foundation is complete. Remaining Phase 7A work is the Nuxt timetable workspace and browser verification.

Next: commit and push this Phase 7A backend checkpoint, then build the Nuxt timetable workspace.

### Phase 7A Nuxt Timetable Workspace

Current page/module complete: Phase 7A Nuxt Timetable workspace, route `/schools/{schoolId}/timetable`.

Scope: completed the school-facing timetable UI:
- added typed `TimetablePeriod` API shape.
- added dashboard navigation/action access for `timetable.manage`.
- added `/schools/{schoolId}/timetable` with Radiant-inspired operation shell styling.
- added filters for academic year, class, shift, day, and status.
- added create/edit/archive flow for routine periods.
- added weekly Sunday through Saturday board plus register table.
- linked Timetable from the Shifts workspace.

Verification: `npm run build` from `apps/web` passed with existing classified Nuxt/Nitro/Node warnings. Local browser smoke used API `http://127.0.0.1:8010/api` and web `http://127.0.0.1:3000`; agent-browser opened `/schools/1/timetable`, confirmed no Vite/Nuxt error overlay, created a Sunday `08:00 to 08:45` Mathematics period for Class One / Morning Shift / Room 204, and saved `docs/browser-checks/timetable-workspace.png`.

Phase 7A status: backend and Nuxt workspace are complete for timetable/routine scheduling. Teacher assignment is intentionally not exposed yet because the current frontend lacks a school member/user picker; the backend already accepts `teacher_user_id` once a member directory is added.

Next: commit and push this Phase 7A UI checkpoint, then continue with Phase 7B Homework and Assignments backend foundation.

### Phase 7B Assignments Backend Foundation

Current page/module complete: Phase 7B Homework and Assignments backend foundation.

Scope: implemented the v3 homework/assignment backend:
- added `assignments` and `assignment_submissions`.
- added `Assignment` and `AssignmentSubmission` models.
- added relationships from School, AcademicClass, Subject, and StudentEnrollment.
- added `assignments.manage` permission and granted it to teacher/admin/principal owner flows.
- added assignment REST routes under `/api/v1/schools/{school}/assignments`.
- added assignment submission REST routes under `/api/v1/schools/{school}/assignment-submissions`.
- added policies for assignment and submission management.
- added audit logs for assignment and submission create/update/delete.
- validated same-school class, subject, assignment, and enrollment references.
- rejected duplicate assignment submissions and submissions for enrollments outside the assignment class.
- added focused coverage in `apps/api/tests/Feature/PhaseSevenAssignmentsApiTest.php`.

Verification: `php artisan test --filter=PhaseSevenAssignments` passed with 6 tests / 32 assertions; `vendor\bin\pint --dirty` passed; route checks showed 5 assignment routes and 5 assignment-submission routes; `php artisan migrate --force` applied the assignment migration locally; `php artisan db:seed --class=EnterpriseRolePermissionSeeder --force` refreshed local RBAC; full `php artisan test` passed with 110 tests / 656 assertions.

Phase 7B status: backend foundation is complete. Remaining Phase 7B work is the Nuxt assignments workspace and browser verification.

Next: commit and push this Phase 7B backend checkpoint, then build the Nuxt homework/assignments workspace.

### Phase 7B Nuxt Assignments Workspace

Current page/module complete: Phase 7B Nuxt Homework and Assignments workspace, route `/schools/{schoolId}/assignments`.

Scope: completed the school-facing homework and assignment UI:
- added typed `Assignment` and `AssignmentSubmission` API shapes.
- added dashboard navigation/action access for `assignments.manage`.
- added `/schools/{schoolId}/assignments` with the shared Radiant-inspired operation shell.
- added filters for assignment class, subject, published state, and status.
- added create/edit/archive flow for homework assignments.
- added create/edit flow for student submissions, marks, and feedback.
- added assignment/submission summary cards, assignment register, and submission register.
- linked Assignments from the Timetable workspace.

Verification: `npm run build` from `apps/web` passed with the existing classified Nuxt/Nitro/Node warnings. Local browser smoke used API `http://127.0.0.1:8010/api` and web `http://127.0.0.1:3000`; agent-browser opened `/schools/1/assignments`, confirmed no Vite/Nuxt error overlay, created a published `Algebra practice browser check` assignment for Class One / Mathematics, recorded a graded `87.00` submission for `Assignment Demo Student / ADM-ASSIGN-001 / Roll 21`, and saved `docs/browser-checks/assignments-workspace.png`.

Phase 7B status: backend and Nuxt workspace are complete for homework/assignment workflows.

Next: commit and push this Phase 7B UI checkpoint, then continue with Phase 7C Payment Gateway Integration planning/backend foundation.

### Phase 7C Payment Gateway Config Backend Foundation

Current page/module complete: Phase 7C Payment Gateway Integration backend foundation.

Scope: added the enterprise gateway configuration layer from `docs/enterprise-plan-v3.md`:
- added `payment_gateway_configs` with school, gateway, encrypted credentials, active flag, test mode flag, timestamps, and soft deletes.
- supported `bkash`, `nagad`, `sslcommerz`, and `stripe` as planned gateways.
- added `PaymentGatewayConfig` with Laravel encrypted array casting for `credentials_encrypted`.
- hid plaintext credential payloads from API responses and exposed only `credentials_configured` plus sorted `credential_keys`.
- added `School::paymentGatewayConfigs()`.
- added tenant-scoped REST routes under `/api/v1/schools/{school}/payment-gateway-configs`.
- added `payment_gateways.manage` to the enterprise RBAC baseline and granted it to school-owner, school-admin, and accountant flows.
- added create/update/delete audit logging that records gateway state and credential key names, never credential values.
- added validation for one live config per school/gateway and tenant-scoped access.
- added focused coverage in `apps/api/tests/Feature/PhaseSevenPaymentGatewayConfigApiTest.php`.

Verification: `php artisan test --filter=PhaseSevenPaymentGatewayConfig` passed with 4 tests / 26 assertions; `vendor\bin\pint --dirty` passed; `php artisan route:list --path=payment-gateway-configs --except-vendor` showed 5 REST routes; `php artisan migrate --force` applied the gateway migration locally; `php artisan db:seed --class=EnterpriseRolePermissionSeeder --force` refreshed local RBAC; full `php artisan test` passed with 114 tests / 682 assertions.

Phase 7C status: backend gateway-config foundation is complete. The next slice is the Nuxt payment gateway configuration workspace; actual provider checkout/webhook handshakes remain future work after credentials and operational UI are in place.

Next: commit and push this Phase 7C backend checkpoint, then build the Nuxt gateway config workspace.

### Phase 7C Nuxt Payment Gateway Config Workspace

Current page/module complete: Phase 7C Nuxt Payment Gateway Config workspace, route `/schools/{schoolId}/payment-gateways`.

Scope: completed the school-facing gateway configuration UI:
- added typed `PaymentGatewayConfig` API shape.
- added dashboard navigation/action access for `payment_gateways.manage`.
- added `/schools/{schoolId}/payment-gateways` with the shared Radiant-inspired operation shell.
- added gateway setup for bKash, Nagad, SSLCommerz, and Stripe.
- kept credentials write-only; saved configs display only `credential_keys` and encrypted status.
- added active/test-mode controls and summary counters.
- added gateway register with edit/remove actions.
- linked Payment Gateways from the Finance workspace.

Verification: `npm run build` from `apps/web` passed with existing classified Nuxt/Nitro/Node warnings. Local browser smoke used API `http://127.0.0.1:8010/api` and web `http://127.0.0.1:3000`; agent-browser opened `/schools/1/payment-gateways`, confirmed no Vite/Nuxt error overlay, created a bKash test-mode config, confirmed only credential key names were displayed, and saved `docs/browser-checks/payment-gateways-workspace.png`.

Phase 7C status: backend and Nuxt workspace are complete for gateway configuration. Provider checkout/webhook execution remains future work.

Next: commit and push this Phase 7C UI checkpoint, then continue with Phase 7D Multi-Language Support planning/backend foundation.

### Phase 7D Multi-Language Backend Foundation

Current page/module complete: Phase 7D Multi-Language Support backend foundation.

Scope: started the v3 multi-language support layer on the Laravel side:
- added nullable `name_bn` fields to students and employees.
- added localized `display_name` accessors for `Student` and `Employee`.
- added reusable school/request locale selection for API controllers.
- updated student and employee controllers to apply locale, accept `name_bn`, search Bengali names, and audit Bengali name changes.
- added Laravel JSON translation files for English and Bengali under `apps/api/lang`.
- added focused coverage in `apps/api/tests/Feature/PhaseSevenLocalizationApiTest.php`.

Verification: `php artisan test --filter=PhaseSevenLocalization` passed with 3 tests / 20 assertions; `vendor\bin\pint --dirty` passed after import-order formatting; `php artisan migrate --force` applied `2026_04_20_010000_add_multilingual_name_fields`; full `php artisan test` passed with 117 tests / 702 assertions.

Phase 7D status: backend localization foundation is complete for student and employee display names. Remaining Phase 7D work is the Nuxt i18n frontend integration and visible language switching.

Next: commit and push this Phase 7D backend checkpoint, then continue with the Nuxt i18n frontend slice.

### Phase 7D Nuxt i18n Frontend Integration

Current page/module complete: Phase 7D Nuxt i18n frontend integration, route verified at `/schools/{schoolId}/students`.

Scope: wired the frontend multi-language foundation:
- installed `@nuxtjs/i18n`.
- configured Nuxt i18n for English and Bengali with no route prefix.
- added `apps/web/i18n.config.ts` with the first English/Bengali message catalog.
- added `LocaleSwitcher`.
- added `useSchoolLocale()` to sync the selected school locale into Nuxt i18n and API locale state.
- updated `useApi()` to send `Accept-Language`.
- added language switching to the dashboard.
- updated Students and Employees workspaces to collect `name_bn`, submit it to the API, and display localized `display_name`.

Verification: `npm run build` from `apps/web` passed with the existing classified Nuxt/Nitro/Node warnings. Browser smoke used API `http://127.0.0.1:8030/api` and web `http://127.0.0.1:3000`; agent-browser opened `/schools/1/students`, switched to Bengali, verified translated labels, and confirmed `ব্রাউজার বাংলা শিক্ষার্থী` displayed for a Bengali-name student. Screenshot saved at `docs/browser-checks/localization-students-bn.png`.

Phase 7D status: backend and Nuxt frontend foundations are complete for multi-language support. Translation coverage is intentionally partial; the foundation is now in place for expanding strings screen by screen.

Next: commit and push this Phase 7D frontend checkpoint, then continue with Phase 7E Offline Support / PWA planning or the next v3 priority.

### Phase 7E Offline Support / PWA Foundation

Current page/module complete: Phase 7E Offline Support / PWA foundation, routes `/schools/{schoolId}/attendance` and `/schools/{schoolId}/marks`.

Scope: added the first production-safe offline support slice from `docs/enterprise-plan-v3.md`:
- installed `@vite-pwa/nuxt`.
- configured Nuxt PWA manifest metadata, app icon, service worker generation, and network-first caching for Attendance and Marks routes.
- added `useNetworkStatus()` for online/offline state.
- added `useOfflineDraft()` for local device draft persistence.
- added `OfflineNotice` for visible offline/draft state.
- wired Attendance to save, restore, and clear local drafts.
- wired Marks to save, restore, and clear local drafts.
- guarded Attendance and Marks saves so offline submissions are kept as drafts instead of failing against the API.
- added an npm override for `serialize-javascript` to keep the PWA/Workbox tree on the patched `7.0.5` release.
- documented the remaining IndexedDB queue/replay/conflict design in `docs/phase-7e-offline-pwa-plan.md`.

Verification: `npm run build` from `apps/web` passed and generated `sw.js`; `npm audit --audit-level=high` reported `found 0 vulnerabilities`. Browser smoke used API `http://127.0.0.1:8030/api` and web `http://127.0.0.1:3000`; agent-browser verified offline draft notices on Attendance and Marks and saved `docs/browser-checks/offline-attendance-draft.png` plus `docs/browser-checks/offline-marks-draft.png`.

Phase 7E status: PWA and offline draft foundation is complete. Full automatic queued write replay remains planned because it needs conflict handling, auth expiry behavior, and user-visible sync recovery before it is safe.

Next: commit and push this Phase 7E foundation checkpoint, then continue with queued sync or the next v3 priority.

### Phase 7E IndexedDB Offline Queue Foundation

Current page/module complete: Phase 7E queued offline sync foundation, routes `/schools/{schoolId}/attendance` and `/schools/{schoolId}/marks`.

Scope: implemented the first durable offline write queue:
- added `apps/web/app/composables/useOfflineQueue.ts` backed by IndexedDB.
- added `apps/web/app/components/OfflineQueuePanel.vue`.
- wired Attendance offline saves into queued `POST /schools/{school}/student-attendance-records` requests.
- wired Marks offline saves into queued `POST /schools/{school}/marks-entries` requests.
- added manual "Sync now" replay and automatic replay when the browser returns online.
- retained failed/conflicted records for review instead of silently discarding them.
- updated Phase 7E docs to distinguish implemented queue behavior from remaining conflict-review hardening.

Verification: `npm run build` passed from `apps/web`. Browser smoke used Nuxt at `http://127.0.0.1:3000` and API at `http://127.0.0.1:8030/api`; agent-browser verified an Attendance queued record while offline, saved `docs/browser-checks/offline-attendance-queue.png`, returned online, synced successfully, and confirmed IndexedDB queue records were empty afterward. Marks route loaded with queue integration present, but full marks sync was not smoke-tested because the current seeded browser school has no exam/class-subject options.

Phase 7E status: PWA/offline foundation plus first queued write replay foundation are complete. Remaining work: conflict resolution UI, `401` login-expiry stop flow, service worker update documentation, and queue-focused automated tests.

### Phase 7E Queue Failure/Auth Hardening

Current page/module complete: Phase 7E queue failure/auth hardening.

Scope: improved queue replay safety and user feedback:
- added `auth_required` as an offline queue status.
- classify API `401` as `auth_required`, keep the affected record, and stop replay.
- return a sync summary from `useOfflineQueue().syncEntries()`.
- updated Attendance and Marks to show precise sync outcomes rather than always reporting success.
- updated `OfflineQueuePanel` to show per-status counts, friendly labels, attempt counts, and retained errors.

Verification: `npm run build` from `apps/web` passed with the known classified Nuxt/Nitro/Node warnings. Browser smoke used API `http://127.0.0.1:8030/api` and web `http://127.0.0.1:3000`; agent-browser queued a duplicate offline Attendance record for `Assignment Demo Student` on `2026-04-20`, synced online, confirmed the queue retained it as `conflict` with one attempt and the API error message, confirmed the page showed the needs-review summary without stale success copy, and saved `docs/browser-checks/offline-attendance-conflict.png`.

Phase 7E status: queue replay now handles success, failed, conflict, and auth-required outcomes with visible retained records. Remaining work: one-click sign-in-again path, richer conflict review UI, service worker update docs, and automated queue tests.

### Frontend Design Handoff Checkpoint

Current checkpoint: frontend dashboard/design handoff to Antigravity.

Status:
- Codex work is paused after Phase 7E queue failure/auth hardening.
- Latest pushed checkpoint before this handoff is `f95a4bd`.
- Antigravity will take over frontend visual design and dashboard refinement.

Handoff notes:
- Main dashboard file: `apps/web/app/pages/dashboard.vue`.
- Shared CSS/theme entry: `apps/web/app/assets/css/main.css`.
- API client and typed contracts: `apps/web/app/composables/useApi.ts`.
- Auth state and bearer token path: `apps/web/app/composables/useAuth.ts` and `apps/web/app/stores/auth.ts`.
- Offline queue surfaces to preserve during redesign:
  - `apps/web/app/composables/useOfflineQueue.ts`
  - `apps/web/app/components/OfflineQueuePanel.vue`
  - `apps/web/app/pages/schools/[schoolId]/attendance.vue`
  - `apps/web/app/pages/schools/[schoolId]/marks.vue`
- Theme reference: `D:\Development\tailwindui-radiant\radiant-ts`.

Guardrails:
- Keep `NUXT_PUBLIC_API_BASE` at the `/api` level; `useApi()` appends `/v1`.
- Do not hide or silently delete failed, conflicted, or auth-required offline queue records.
- Keep `Accept-Language` and bearer token headers in `useApi()`.
- Keep route names/paths stable unless the backend/API docs are updated with the change.

Latest verification inherited by this handoff:
- `npm run build` passed.
- `npm audit --audit-level=high` returned `found 0 vulnerabilities`.
- Browser queue conflict smoke passed on Attendance.

### Demo Data Verification Checkpoint

Scope: added `apps/api/database/seeders/DemoDataSeeder.php` so full local browser checks can run from repeatable data instead of hand-created fragments. The seeder creates or updates the demo school, owner membership, academic year/class/section/group/shift/subject/class-subject, employee/teacher, student/enrollment, attendance, timetable, assignment/submission, exam type/exam/schedule, verified marks, grade scale/result summary, fee category/structure, paid invoice/payment, payment gateway config, salary, employee attendance, leave setup/application, calendar event, and public demo document.

Verification: `php artisan db:seed --class=DemoDataSeeder --force` passed against local MySQL/Herd; agent-browser logged into Nuxt at `http://127.0.0.1:3000` using Herd API `https://school-api.test/api`, smoke-tested every school workspace route, fixed the demo data gaps that left sections/groups/class-subjects/exams/marks/finance/staff/reports/calendar/documents underpopulated, and saved `docs/browser-checks/demo-data-reports.png`. `vendor\bin\pint --test` passed; full `php artisan test` passed with 117 tests / 702 assertions; `npm run build` passed with the known classified Nuxt/Nitro/Node warnings.

Local note: PHP's built-in `artisan serve` could not bind to local ports from this shell, so browser/API verification used Laravel Herd at `https://school-api.test`.

### Five-Year Demo Data And Dashboard QA Checkpoint

Scope: expanded `DemoDataSeeder` into a deterministic five-year school simulation for 2022-2026 and rebuilt `apps/web/app/pages/dashboard.vue` into a grouped command-center layout. The dashboard now uses the live `/dashboard/summary` API for KPIs, collection trends, attention counts, and grouped module navigation instead of a long flat setup menu.

Demo data volume after seeding: 5 academic years, 5 classes, 50 students, 241 enrollments, 4,322 student attendance records, 8 employees, 961 staff attendance records, 51 assignments, 10 exams, 101 schedules, 4,801 marks, 481 result summaries, 1,561 invoices, 1,524 payments, 416 salary records, 40 leave applications, 96 promotion records, 21 calendar events, and 6 documents.

Verification: `php artisan db:seed --class=DemoDataSeeder --force` passed; agent-browser verified the loaded dashboard and saved `docs/browser-checks/dashboard-after-five-year-loaded.png`; agent-browser route smoke loaded every current school workspace route without visible error copy; `vendor\bin\pint --test` passed; `php artisan test` passed with 117 tests / 702 assertions; `npm run build` passed with the existing classified Nuxt/Nitro/Node warnings.
