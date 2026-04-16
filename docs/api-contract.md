# API Contract

## API Style

Use REST JSON APIs under `/api`. The Nuxt app is a first-party SPA using Laravel Sanctum cookie/session authentication.

## Authentication

Required endpoints:

- `POST /api/auth/login`
- `POST /api/auth/logout`
- `GET /api/me`
- `GET /api/schools`
- `POST /api/active-school`

Sanctum CSRF flow must be documented in the Nuxt API client once the app is scaffolded.

## Tenant Context

Production tenant access uses subdomains:

```text
{school-slug}.yourdomain.com
```

Local development uses active school selection after login. API requests must resolve an active school from session or explicit active school state and enforce it on every school-owned endpoint.

## Standard Responses

Single resource or mutation:

```json
{
  "data": {},
  "message": "Saved successfully"
}
```

Paginated list:

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

## Status Codes

- `200`: success
- `201`: created
- `204`: deleted or no content
- `401`: unauthenticated
- `403`: authenticated but not allowed
- `404`: missing record or cross-tenant record when existence should not be revealed
- `422`: validation error
- `429`: rate limited

## Phase 1 Endpoints

Foundation:

- `/api/auth/*`
- `/api/me`
- `/api/schools`
- `/api/active-school`
- `/api/school-members`
- `/api/roles`
- `/api/permissions`
- `/api/audit-logs`

First vertical slice:

- `GET /api/academic/classes`
- `POST /api/academic/classes`
- `GET /api/academic/classes/{class}`
- `PUT /api/academic/classes/{class}`
- `DELETE /api/academic/classes/{class}`

Academic Classes must support pagination, search, and active/inactive filtering.

## Future API Groups

- `/api/academic/years`
- `/api/academic/sections`
- `/api/academic/subjects`
- `/api/students`
- `/api/guardians`
- `/api/employees`
- `/api/attendance`
- `/api/exams`
- `/api/marks`
- `/api/fees`
- `/api/accounts`
- `/api/reports`
- `/api/calendar`
- `/api/system`

## Validation And Authorization

- Use Laravel Form Requests for validation.
- Use policies/gates for authorization.
- Never trust client-provided `school_id` for tenant ownership.
- Server must set `school_id` from active school context.
- All mutations that affect business records must write audit logs.

