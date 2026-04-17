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

- `GET /api/schools/{school}/academic-classes`
- `POST /api/schools/{school}/academic-classes`
- `GET /api/schools/{school}/academic-classes/{academic_class}`
- `PUT /api/schools/{school}/academic-classes/{academic_class}`
- `DELETE /api/schools/{school}/academic-classes/{academic_class}`

Academic Classes must support pagination, search, and active/inactive filtering.

Academic Sections:

- `GET /api/schools/{school}/academic-sections`
- `POST /api/schools/{school}/academic-sections`
- `GET /api/schools/{school}/academic-sections/{academic_section}`
- `PUT /api/schools/{school}/academic-sections/{academic_section}`
- `DELETE /api/schools/{school}/academic-sections/{academic_section}`

Academic Sections require `sections.manage`, include `school_id`, and must reference an Academic Class owned by the same school. Lists accept optional `academic_class_id` filtering.

Academic Years:

- `GET /api/schools/{school}/academic-years`
- `POST /api/schools/{school}/academic-years`
- `GET /api/schools/{school}/academic-years/{academic_year}`
- `PUT /api/schools/{school}/academic-years/{academic_year}`
- `DELETE /api/schools/{school}/academic-years/{academic_year}`

Academic Years require `academic_years.manage`, include date bounds, and enforce one current year per school. Lists accept optional `status` and `is_current` filtering.

## Future API Groups

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
