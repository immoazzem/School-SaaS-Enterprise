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
- Scaffolded Laravel API app in `apps/api`.
- Composer resolved Laravel framework to `v13.5.0` with PHP 8.5.5.
- Scaffolded Nuxt web app in `apps/web`.
- Nuxt scaffold targets `nuxt:^4.4.2`, Vue 3, and Vue Router 5.
- Installed Nuxt dependencies and generated `package-lock.json`.
- Installed Laravel Sanctum `v4.3.1`.
- Published Laravel 13 API routing and Sanctum config/migration.
- Added enterprise foundation schema for schools, memberships, roles, permissions, role assignments, audit logs, and academic classes.
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
- Added feature tests for login, profile lookup, school creation, Academic Classes CRUD, and cross-school access denial.
- Added enterprise role/permission seeders.
- Added audit-log writes for Academic Classes create/update/delete.
- Added reusable `school.member` middleware for active school membership checks.
- Added Academic Class policy checks for `academic_classes.manage`.
- School creation now assigns the seeded `school-owner` role to the creator when the RBAC seed exists.
- Replaced the Nuxt welcome screen with the first app UI slice:
  - login page
  - dashboard shell
  - typed API/auth composables
  - Academic Classes workspace
  - shared CSS foundation
- Added Nuxt client route protection with stale-token cleanup.
- Added dashboard school creation, tenant selection list, and first-school empty state.
- Added role and permission details to the `/api/me` user payload.
- Added role-aware dashboard navigation states based on selected-school permissions.
- Updated Laravel and Nuxt env examples for Herd/MySQL local development.
- Updated local development docs for the current bearer-token Sanctum flow.
- Linked the Laravel API folder in Herd as secured `https://school-api.test` on PHP 8.5.
- Verified Herd serves the API app:
  - `https://school-api.test` returns 200.
  - `https://school-api.test/api/me` returns the expected unauthenticated 401 JSON response without a bearer token.

## Not Started

- MySQL database creation.
- Live browser verification against a running Laravel API.

## Verification

- `php artisan test` from `apps/api`: passed after RBAC payload update, 9 tests / 42 assertions.
- `vendor\bin\pint --test` from `apps/api`: passed.
- `php artisan route:list` from `apps/api`: passed, 16 routes.
- `php artisan migrate:fresh --seed` from `apps/api`: passed outside sandbox after sandbox SQLite disk I/O failure.
- `npm run build` from `apps/web`: passed after Nuxt app UI slice and again after route protection/school creation.
- Env template/docs update does not require code execution.
- `herd link school-api --secure --isolate=8.5` from `apps/api`: passed.
- `Invoke-WebRequest https://school-api.test`: passed, 200 OK.
- `Invoke-WebRequest https://school-api.test/api/me`: passed, 401 Unauthorized JSON.
- Initial sandbox runs hit Windows permission/process limits, then passed outside the sandbox with approval.

## Next Step

Continue Phase 1 implementation:

1. Configure Laravel API for local MySQL once DB credentials are confirmed.
2. Run login/dashboard/school creation/classes against the local API.
3. Start Phase 2 academic setup modules after the vertical slice is verified live.

## New Session Startup Prompt

```text
Read D:\Development\School-SaaS-Enterprise-CONTEXT.md and D:\Development\School-SaaS-Enterprise\docs\current-status.md.
Continue from the current status.
Only read D:\Development\School-SaaS-Enterprise-PLAN.md or docs\enterprise-plan.md if more detail is needed.
Minimize token usage.
```
