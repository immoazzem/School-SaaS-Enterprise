# Improvement Roadmap

**Last updated:** April 19, 2026  
**Active plan:** `docs/enterprise-plan-v3.md`  
**V2 baseline:** `docs/enterprise-plan-v2.md` when referenced by v3  
**Branch:** `master`  
**Stack target:** Laravel 13.5 / PHP 8.5 API, Nuxt 4.4.2 / Vue 3 / TypeScript web app

## Production Stabilization Before Phase 7

Phase 6 backend and Nuxt promotion workflow UI are complete. Before planning Phase 7, the project needs a stabilization pass focused on dependency clarity, API compatibility, security test coverage, PDF reliability, queue visibility, and durable local setup.

This roadmap replaces generic Copilot/Claude output with repo-specific decisions. Do not execute pasted third-party snippets blindly; inspect the current code first and adapt to existing models, policies, seeders, controllers, and tests.

## Current Decisions

- Keep Nuxt's `$fetch`-based `useApi()` pattern. Do not add Axios unless a concrete gap appears.
- Use Tailwind CSS v3 with `@nuxtjs/tailwindcss` for Nuxt module compatibility. Do not jump to Tailwind v4 in this stabilization pass.
- Add Pinia, but migrate safely. Stores should become the source of truth through `useAuth()` rather than forcing a broad page rewrite in one checkpoint.
- Version the API at the route level with `/api/v1`, not by subdomain.
- Use PHPUnit-style feature tests to match the current test suite.
- Standardize unauthorized tenant/resource access around the existing middleware/policy behavior, expected to be `403` unless a controller intentionally hides resources.
- Treat Nuxt/Nitro build warnings as technical debt only after classification. The goal is zero unknown warnings, not dishonest suppression of upstream package warnings.
- Use Laravel's queue retry mechanisms for failed jobs. Do not manually copy failed job payloads back into the `jobs` table unless there is no framework-safe option.

## Checkpoint A: Roadmap

**Status:** Complete

Deliverables:

- Create this roadmap.
- Commit and push it before code changes.

Acceptance:

- `docs/IMPROVEMENT-ROADMAP.md` exists.
- The roadmap references the real branch, stack, active plan, and safer execution order.

## Checkpoint B: Frontend Foundation And Build Warnings

**Status:** Complete

Deliverables:

- Update `apps/web/package.json` dependencies:
  - `nuxt`
  - `vue`
  - `vue-router`
  - `@nuxtjs/tailwindcss`
  - `tailwindcss`
  - `postcss`
  - `autoprefixer`
  - `pinia`
  - `@pinia/nuxt`
  - `@vueuse/core`
  - `zod`
- Add Node engine metadata and `apps/web/.nvmrc` with `20.11.0`.
- Update `apps/web/nuxt.config.ts` for SPA mode, Tailwind module, Pinia module, CSS entry, and existing runtime API base.
- Add `apps/web/tailwind.config.ts`.
- Run `npm install`.
- Run `npm run build`.
- Capture build warnings in `docs/KNOWN-BUILD-WARNINGS.md`.

Acceptance:

- `npm run build` exits `0`.
- No missing module errors.
- Tailwind module and Pinia module are loaded by Nuxt.
- Every build warning is either fixed or documented with severity, source package, and planned action.
- No app-caused warning remains undocumented.

## Checkpoint C: API Versioning

**Status:** Complete

Deliverables:

- Wrap all current API routes in `apps/api/routes/api.php` under `Route::prefix('v1')`.
- Keep auth/login throttling and authenticated route middleware behavior intact.
- Remove or version legacy unversioned stubs.
- Update `apps/web/app/composables/useApi.ts` to request `${apiBase}/v1${path}`.
- Update API tests from `/api/...` to `/api/v1/...`.
- Update `docs/api-contract.md` and `docs/ARCHITECTURE.md` with API versioning and deprecation rules.

Acceptance:

- `php artisan route:list` shows current application routes under `/api/v1`.
- Frontend uses the `/v1` prefix through the API composable, not scattered call-site edits.
- `php artisan test` passes.
- Nuxt build passes.

## Checkpoint D: Environment And Local Development

**Status:** Complete

Deliverables:

- Audit existing `apps/api/.env.example` and `apps/web/.env.example`.
- Normalize frontend API env naming around `NUXT_PUBLIC_API_BASE`.
- Keep the base at `/api`; let `useApi()` append `/v1` after Checkpoint C.
- Update `docs/local-development.md` with copy-paste setup for:
  - PHP 8.5
  - Composer
  - MySQL 8+
  - Node 20.11.0
  - npm 10.2+
  - optional Redis/queue notes
  - Herd API setup
  - direct PHP built-in server fallback
  - Nuxt setup
  - migrations, seeders, tests, and build

Acceptance:

- A new clone can copy `.env.example` to `.env` and follow the docs without guessing env names.
- Local docs match the actual code paths and commands.

## Checkpoint E: Pinia State Migration

**Status:** Pending

Deliverables:

- Create `apps/web/app/stores/auth.ts`.
- Create `apps/web/app/stores/school.ts`.
- Create `apps/web/app/stores/index.ts`.
- Add `can(permission)` to the auth store.
- Migrate `useAuth()` to delegate to the store while keeping its current public API stable for existing pages.

Acceptance:

- Existing pages continue working without broad rewrites.
- `useAuth()` and stores agree on token, user, schools, selected school, and logout behavior.
- `npm run build` passes.

## Checkpoint F: Permission And Tenant Isolation Tests

**Status:** Pending

Deliverables:

- Create a dedicated PHPUnit-style `PermissionIsolationTest`.
- Use real relationship names from `School`, `Role`, membership, and role assignment models.
- Cover cross-tenant denial for:
  - academic sections
  - students
  - student enrollments
  - invoices/payments
  - employees
  - audit logs
- Cover same-school permission denial for:
  - sections
  - employees
  - finance/invoices
  - inactive membership
  - unauthenticated access
- Add a small permission matrix test for owner/admin/teacher/accountant where practical.

Acceptance:

- `php artisan test --filter=PermissionIsolation` passes.
- The test file provides at least 15 assertions.
- Denial behavior is consistent and documented where it intentionally differs.

## Checkpoint G: PDF Rendering Reliability

**Status:** Pending

Deliverables:

- Add dedicated report Blade views for marksheet and invoice receipt.
- Update `GenerateReportJob` to choose a view by report type and fall back to `reports.generic`.
- Improve report payload assembly so dedicated views receive real data, not only generic target metadata.
- Add retry/backoff properties to `GenerateReportJob`.
- Add PHPUnit tests for:
  - successful PDF generation
  - generated file exists and is larger than 1 KB
  - missing data marks the export failed
  - unauthorized report request is denied

Acceptance:

- `php artisan test --filter=PdfGeneration` passes.
- At least one PDF test exercises the job synchronously and verifies a completed export artifact.
- Generated PDFs are not blank.

## Checkpoint H: Background Job Observability

**Status:** Pending

Deliverables:

- Add `failed_jobs` migration if missing.
- Confirm `config/queue.php` persists failed jobs to the database.
- Add super-admin job status endpoint showing pending and failed job counts plus recent failures.
- Add a safe retry endpoint using Laravel's queue retry mechanism.
- Add retry/backoff to long-running jobs such as bulk invoice generation.
- Add tests for job status and dispatch behavior.

Acceptance:

- `failed_jobs` exists after migration.
- Super-admin can see job status.
- Non-super-admin cannot access job status.
- Retry uses Laravel's queue retry path, not manual payload reinsertion.
- Relevant job tests pass.

## Checkpoint I: Final Stabilization Review

**Status:** Pending

Deliverables:

- Run full backend test suite.
- Run frontend build.
- Update:
  - `docs/current-status.md`
  - `docs/session-context.md`
  - `docs/engineering-log.md`
  - root `D:\Development\School-SaaS-Enterprise-CONTEXT.md`
- Commit and push final stabilization checkpoint.

Acceptance:

- `php artisan test` passes.
- `npm run build` passes.
- `docs/KNOWN-BUILD-WARNINGS.md` has no unknown/unclassified warnings.
- GitHub remote is updated.

## Phase 7 Gate

Do not begin Phase 7 planning until all production stabilization checkpoints above are complete or explicitly deferred with a written reason in this file.

Phase 7 candidates after stabilization:

- Timetable and class scheduling
- Homework and assignment tracking
- Payment gateway integration
- SMS and push notification delivery
- Mobile-responsive UI pass
