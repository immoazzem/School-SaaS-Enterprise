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
