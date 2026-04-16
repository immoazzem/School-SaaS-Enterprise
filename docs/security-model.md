# Security Model

## Goals

Security and audit are enterprise priorities. The rebuild must prevent cross-school data leakage, normalize permissions, and leave a reliable activity trail.

## Tenancy

- Every school-owned table includes `school_id`.
- All queries for school-owned records must scope by active school.
- Cross-school record access must fail.
- Avoid exposing record existence across tenants; use `404` where safer than `403`.
- Super Admin access must be explicit and auditable.

## RBAC

Initial roles:

- Super Admin
- School Owner
- School Admin
- Principal
- Teacher
- Accountant
- Student
- Parent
- Read-only Auditor

Permission naming should be granular and action-oriented:

- `academic.classes.view`
- `academic.classes.create`
- `academic.classes.update`
- `academic.classes.delete`
- `students.view`
- `students.create`
- `finance.payments.create`
- `reports.export`
- `audit_logs.view`

The old `user_permissions` broad flags are only a migration reference and must not be kept as the new permission model.

## Authentication

- Use Laravel Sanctum SPA authentication.
- Use secure, HTTP-only cookies.
- Configure stateful domains for the Nuxt origin.
- Rate limit login, password reset, and sensitive endpoints.
- Add audit logs for login, logout, failed login, password change, role change, and permission change.

## Data Protection

- Never store plaintext passwords.
- Never store secrets, API keys, payment card data, or full sensitive documents in audit logs.
- Avoid hard deletes for finance, audit, and reporting-critical records.
- Use soft deletes for master data where recovery is useful.

## Local Development Security

- Use `.env` files that are not committed.
- Use a local MySQL database named `school_saas_enterprise`.
- Do not commit local credentials.
- Document Herd and Nuxt local origins before enabling Sanctum stateful domains.

## Phase 1 Security Acceptance

- Authenticated user can only access schools they belong to.
- User cannot access another school's academic classes.
- Missing permission blocks Academic Classes CRUD.
- Class create/update/delete writes audit logs.
- Auth endpoints are rate limited.

