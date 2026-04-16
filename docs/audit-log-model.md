# Audit Log Model

## Purpose

Audit logs provide enterprise traceability for security-sensitive and business-critical changes.

## Required Fields

`audit_logs` should store:

- `id`
- `school_id` nullable for system-wide events
- `actor_id` nullable for unauthenticated events
- `action`
- `target_type`
- `target_id`
- `old_values` JSON nullable
- `new_values` JSON nullable
- `ip_address`
- `user_agent`
- `metadata` JSON nullable
- `created_at`

Audit logs should be append-only. Do not expose update/delete operations except through controlled retention tooling in a future compliance phase.

## Phase 1 Events

Record:

- login
- logout
- failed login
- active school changed
- role assigned
- role removed
- permission changed
- academic class created
- academic class updated
- academic class deleted

## Later Events

Record:

- student create/update/delete
- employee create/update/delete
- attendance changes
- marks changes
- fee/payment changes
- payroll changes
- report exports
- tenant settings changes
- data exports
- user invitations

## Sensitive Data Rules

Never store:

- plaintext passwords
- password reset tokens
- Sanctum tokens
- API secrets
- payment card data
- full uploaded documents

Mask or omit sensitive fields before writing `old_values` and `new_values`.

## Access Rules

- School Admin can view audit logs for their school if granted `audit_logs.view`.
- Read-only Auditor can view logs but not mutate business data.
- Super Admin can view system-wide logs.
- All audit log reads should themselves be rate limited and permission checked.

