# Enterprise Latest-Stack School SaaS Rebuild Plan

## Summary

Rebuild the school management system in a **new folder** as an enterprise-grade multi-school SaaS using **Laravel 13.x, PHP 8.5, MySQL, Laravel Herd, Nuxt 4.4.x, TypeScript, Tailwind CSS 4, and Sanctum SPA auth**.

Use the GitHub repo `https://github.com/immoazzem/Laravel-School-Management.git` only as the legacy functional reference. Use the local Tailwind UI Radiant template at `D:\Development\tailwindui-radiant\radiant-ts` as the visual design reference, but port it into Nuxt/Vue instead of using Next.js/React.

Local development will be **Herd native**, not Docker-first.

## Workspace And Local Setup

- Create a new project folder:

```text
D:\Development\School-SaaS-Enterprise
```

- Inside it, create:

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

- Clone the existing repo into `legacy-reference`.
- Do not modify `legacy-reference`.
- Create a fresh Laravel 13 app in `apps/api`.
- Create a fresh Nuxt 4 app in `apps/web`.
- Use Laravel Herd for the API local domain:

```text
https://school-api.test
```

- Use Nuxt dev server for the web app:

```text
http://localhost:3000
```

- Use MySQL from the local machine, managed through MySQL Workbench.
- Use a new database:

```text
school_saas_enterprise
```

- Codex must create `docs/local-development.md` with exact Herd, `.env`, MySQL, Nuxt, and test commands.

## Enterprise Architecture

- Backend:
  - Laravel `^13.0`
  - PHP `^8.5`
  - MySQL 8+
  - Laravel Sanctum SPA auth
  - Laravel Policies and Gates
  - Form Requests
  - API Resources
  - Queues
  - Scheduler
  - Events and listeners
  - Audit logging
  - Rate limiting
  - Pest or PHPUnit
  - Laravel Pint

- Frontend:
  - Nuxt `^4.4`
  - Vue 3
  - TypeScript
  - Tailwind CSS 4
  - Pinia
  - VeeValidate + Zod
  - Radiant-inspired marketing, auth, pricing, and public pages
  - Custom enterprise admin dashboard for authenticated SaaS users

- Tenancy:
  - Multi-school from day one.
  - Production canonical tenant access uses subdomains:

```text
{school-slug}.yourdomain.com
```

- Local development fallback:
  - Use `localhost:3000` with active school selected after login.
  - API must still store and enforce tenant context exactly as production would.
  - Tenant resolution logic must support hostname-based subdomains for production and explicit active-school context for local development.

## Enterprise Security Model

- Implement security and audit as first-class Phase 1 requirements.
- Add tenant isolation at all data-access boundaries.
- Every school-owned model must include `school_id`.
- All school-owned queries must be scoped by active school.
- Direct cross-school access must return `403` or `404` consistently.
- Use role-based access control with permissions.
- Initial roles:
  - Super Admin
  - School Owner
  - School Admin
  - Principal
  - Teacher
  - Accountant
  - Student
  - Parent
  - Read-only Auditor
- Add audit logs for:
  - login/logout
  - failed login
  - password change
  - role changes
  - permission changes
  - student create/update/delete
  - employee create/update/delete
  - attendance changes
  - marks changes
  - fee/payment changes
  - report exports
  - tenant settings changes
- Audit logs must store:
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
- Never store plaintext passwords, secrets, payment card data, or full sensitive documents in audit logs.

## Build Phases

### Phase 0: Legacy Audit And Enterprise Docs

Codex must inspect the legacy app before building new code.

Deliver:

```text
docs/module-inventory.md
docs/database-model.md
docs/api-contract.md
docs/security-model.md
docs/audit-log-model.md
docs/enterprise-plan.md
docs/local-development.md
```

Inventory these legacy modules:

```text
users
roles and permissions
profiles
academic setup
classes
years
groups
sections
shifts
fee categories
fee amounts
exam types
subjects
assigned subjects
designations
student registration
student promotion
student attendance
employee registration
employee attendance
employee leave
employee salary
marks
grades
student fees
salary accounts
other accounts
profit reports
marksheets
attendance reports
result reports
student ID card PDFs
lessons
routines
calendar
```

Phase 0 is complete only when the docs explain how each legacy module maps to the new SaaS modules.

### Phase 1: Enterprise Foundation And First Vertical Slice

Build the foundation plus one complete working module.

Backend:

- Install Laravel 13 in `apps/api`.
- Configure PHP 8.5.
- Configure MySQL connection to `school_saas_enterprise`.
- Configure Sanctum SPA auth for Nuxt.
- Add models and migrations for:
  - schools
  - users
  - school memberships
  - roles
  - permissions
  - role permissions
  - user role assignments
  - audit logs
  - academic classes
- Add active school context middleware.
- Add tenant-scoped base model or reusable tenant-scope pattern.
- Add policies for school-scoped access.
- Add rate limits for auth and API endpoints.
- Add seeders for one super admin, one school, and default roles.
- Add tests for auth, tenant isolation, roles, permissions, and academic classes.

Frontend:

- Install Nuxt 4 in `apps/web`.
- Configure TypeScript, Tailwind CSS 4, Pinia, validation, and API client.
- Port Radiant visual language into Nuxt:
  - typography
  - buttons
  - navigation
  - landing page sections
  - login page styling
  - pricing page shell
- Do not port Sanity, React, Next.js, or Framer Motion directly.
- Build:
  - public landing page
  - login page
  - protected dashboard layout
  - sidebar
  - top bar
  - school switcher
  - role-aware menu shell
  - Academic Classes CRUD screens

Acceptance:

- User can log in.
- User can select or enter active school context.
- User can create, view, edit, and delete academic classes.
- Cross-school class access is blocked.
- Audit logs are written for class create/update/delete.
- Laravel tests pass.
- Nuxt build passes.

### Phase 2: Academic And People Modules

Implement:

```text
academic years
student groups
sections
shifts
subjects
assigned subjects
exam types
designations
students
guardians
teachers
employees
student enrollment
student promotion
profile management
```

Rules:

- All modules must be school-scoped.
- All mutations must create audit logs.
- All list APIs must support pagination, search, and filters.
- All Nuxt list pages must include loading, empty, error, create, edit, delete, and permission-aware states.

### Phase 3: Attendance, Exams, And Finance

Implement:

```text
student attendance
employee attendance
employee leave
marks entry
grades
result calculation
fee setup
student invoices
fee collection
salary records
expenses
income records
```

Enterprise requirements:

- Finance writes must use database transactions.
- Attendance and marks updates must be auditable.
- Payment and fee records must never be hard-deleted by default.
- Add export-ready report data APIs.
- Add permission checks for accountant-only and admin-only operations.

### Phase 4: Reports, PDFs, Calendar, And Operations

Implement:

```text
marksheet reports
result reports
attendance reports
profit reports
student ID card PDFs
routines
lessons
school calendar
dashboard analytics
```

Enterprise requirements:

- Use a Laravel 13-compatible PDF package.
- Add print-friendly Nuxt report pages.
- Add report export audit logs.
- Add background jobs for heavy report generation where needed.
- Add dashboard cards and charts scoped by role.

### Phase 5: SaaS Administration

Implement:

```text
super admin console
school onboarding
school settings
school status
plan limits
subscription placeholders
user invitations
audit log viewer
system health page
data export controls
```

Billing is not implemented in Phase 1. Add only the structure needed to support billing later:

```text
plans
school_plan
subscription_status
trial_ends_at
feature_limits
```

No payment provider integration yet.

## API Contract

Use REST JSON APIs under `/api`.

Primary groups:

```text
/api/auth
/api/me
/api/schools
/api/active-school
/api/school-members
/api/roles
/api/permissions
/api/audit-logs
/api/academic/classes
/api/academic/years
/api/academic/sections
/api/academic/subjects
/api/students
/api/guardians
/api/employees
/api/attendance
/api/exams
/api/marks
/api/fees
/api/accounts
/api/reports
/api/calendar
/api/system
```

Use standard response shapes:

```json
{
  "data": {},
  "message": "Saved successfully"
}
```

```json
{
  "data": [],
  "meta": {
    "current_page": 1,
    "per_page": 15,
    "total": 0
  }
}
```

Validation errors use Laravel's standard `422` JSON format.

Authorization failures use `403`.

Unauthenticated requests use `401`.

Tenant-not-found or cross-tenant records should use `404` where revealing existence would be unsafe.

## Testing And Quality Gates

Codex must run checks before completing each phase.

Backend checks:

```bash
php artisan test
vendor/bin/pint --test
php artisan route:list
php artisan migrate:fresh --seed
```

Frontend checks:

```bash
npm run build
npm run typecheck
npm run lint
```

Phase 1 required backend tests:

- login succeeds
- logout succeeds
- current user endpoint works
- user cannot access another school's data
- role permission allows academic class management
- missing permission blocks academic class management
- academic class create/update/delete writes audit logs

Phase 1 required frontend checks:

- landing page loads
- login page loads
- protected dashboard redirects unauthenticated users
- authenticated user can see dashboard shell
- academic classes CRUD works against API
- empty/loading/error states render

## Git And Codex Workflow

- Codex works in `D:\Development\School-SaaS-Enterprise`.
- Codex commits phase by phase.
- Commit style:

```text
docs: audit legacy school modules
chore: scaffold laravel api and nuxt web apps
feat: add enterprise auth and tenancy foundation
feat: add academic classes vertical slice
```

- Codex pauses after each phase with:
  - files changed summary
  - commands run
  - tests/build result
  - what to review in VS Code
  - next recommended phase

- Codex must not mutate:
  - `D:\Development\tailwindui-radiant`
  - `legacy-reference`
  - existing `D:\Development\Laravel-School-Management`

## Assumptions And Defaults

- New folder is `D:\Development\School-SaaS-Enterprise`.
- Local PHP/Laravel development uses Laravel Herd.
- Local database uses installed MySQL, managed with MySQL Workbench.
- Frontend remains Nuxt 4, not Next.js.
- Radiant is a design source only.
- Database is MySQL.
- Authentication is Laravel Sanctum SPA.
- Tenancy is multi-school from day one.
- Production tenant routing uses subdomains.
- Security and audit are the enterprise priority.
- Billing is deferred until after core modules stabilize.
- Docker is not required for Phase 1.
