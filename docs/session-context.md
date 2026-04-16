# School SaaS Enterprise: Codex Session Context

## Purpose

Use this file at the start of every new Codex session to minimize token usage and continue the project without re-explaining the full conversation.

Master plan:

```text
D:\Development\School-SaaS-Enterprise-PLAN.md
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
- Backend foundation schema exists for schools, memberships, RBAC, audit logs, and academic classes.
- Token auth, school list/create, and tenant-scoped Academic Classes CRUD endpoints exist.
- Enterprise role/permission seeders and Academic Classes audit-log writes exist.
- Active school membership checks use reusable `school.member` route middleware.
- Academic Class policy checks enforce `academic_classes.manage`.
- School creation assigns the seeded `school-owner` role to the creator.
- Backend foundation tests pass: `php artisan test` reports 9 tests / 34 assertions.
- Pint, route list, and `php artisan migrate:fresh --seed` pass for the backend foundation.
- Nuxt has a first app UI slice: login page, dashboard shell, API/auth composables, and Academic Classes workspace.
- Nuxt has client route protection with stale-token cleanup.
- Dashboard can create a school through `POST /api/schools`, select the new tenant, and show active tenants.
- Nuxt build passes after the app UI slice and after route protection/school creation.
- MySQL/Herd site config is still pending; tests currently use SQLite in memory.

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
3. Phase 2: Academic setup and people modules.
4. Phase 3: Attendance, exams, and finance.
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
