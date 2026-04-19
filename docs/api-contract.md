# API Contract

## API Style

Use REST JSON APIs under `/api/v1`. The Nuxt app is a first-party SPA using Laravel Sanctum bearer-token authentication during local development.

## API Versioning

Version 1 is the baseline production API and is exposed under `/api/v1`.

Compatibility rules:

- Additive changes stay in v1: new optional fields, new endpoints, new filters, and new enum values where old clients can safely ignore them.
- Breaking changes require v2: removed fields, renamed fields, changed response shapes, stricter required request fields, or changed authorization semantics.
- When v2 is introduced, v1 remains supported for at least six months after the v2 release date unless a security issue forces a shorter window.
- Frontend code must add the `/v1` prefix in `apps/web/app/composables/useApi.ts`, not at individual call sites.

## Authentication

Required endpoints:

- `POST /api/v1/auth/login`
- `POST /api/v1/auth/logout`
- `GET /api/v1/me`
- `GET /api/v1/schools`

The Nuxt API client sends a bearer token through the centralized `useApi()` composable.

## Tenant Context

Production tenant access uses subdomains:

```text
{school-slug}.yourdomain.com
```

Local development uses active school selection after login. Tenant-owned API requests include the school id in the route and every controller/middleware/policy must enforce active membership for that school.

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

- `/api/v1/auth/*`
- `/api/v1/me`
- `/api/v1/schools`
- `/api/v1/admin/*`

First vertical slice:

- `GET /api/v1/schools/{school}/academic-classes`
- `POST /api/v1/schools/{school}/academic-classes`
- `GET /api/v1/schools/{school}/academic-classes/{academic_class}`
- `PUT /api/v1/schools/{school}/academic-classes/{academic_class}`
- `DELETE /api/v1/schools/{school}/academic-classes/{academic_class}`

Academic Classes must support pagination, search, and active/inactive filtering.

Academic Sections:

- `GET /api/v1/schools/{school}/academic-sections`
- `POST /api/v1/schools/{school}/academic-sections`
- `GET /api/v1/schools/{school}/academic-sections/{academic_section}`
- `PUT /api/v1/schools/{school}/academic-sections/{academic_section}`
- `DELETE /api/v1/schools/{school}/academic-sections/{academic_section}`

Academic Sections require `sections.manage`, include `school_id`, and must reference an Academic Class owned by the same school. Lists accept optional `academic_class_id` filtering.

Academic Years:

- `GET /api/v1/schools/{school}/academic-years`
- `POST /api/v1/schools/{school}/academic-years`
- `GET /api/v1/schools/{school}/academic-years/{academic_year}`
- `PUT /api/v1/schools/{school}/academic-years/{academic_year}`
- `DELETE /api/v1/schools/{school}/academic-years/{academic_year}`

Academic Years require `academic_years.manage`, include date bounds, and enforce one current year per school. Lists accept optional `status` and `is_current` filtering.

## Phase 7A Endpoints

Timetable Periods:

- `GET /api/v1/schools/{school}/timetable-periods`
- `POST /api/v1/schools/{school}/timetable-periods`
- `GET /api/v1/schools/{school}/timetable-periods/{timetablePeriod}`
- `PUT /api/v1/schools/{school}/timetable-periods/{timetablePeriod}`
- `DELETE /api/v1/schools/{school}/timetable-periods/{timetablePeriod}`

Timetable Periods require `timetable.manage`. Records are tenant-owned and include `academic_year_id`, `academic_class_id`, optional `shift_id`, `day_of_week` (`0` to `6`), `period_number`, `start_time`, `end_time`, optional `subject_id`, optional `teacher_user_id`, optional `room`, and `status`.

Lists accept optional `academic_year_id`, `academic_class_id`, `shift_id`, `day_of_week`, and `status` filters. Mutations reject cross-school academic references, reject teacher assignments unless the teacher user is an active school member, block duplicate class slots, block overlapping class periods, and block overlapping teacher bookings.

## Future API Groups

- `/api/v1/academic/subjects`
- `/api/v1/students`
- `/api/v1/guardians`
- `/api/v1/employees`
- `/api/v1/attendance`
- `/api/v1/exams`
- `/api/v1/marks`
- `/api/v1/fees`
- `/api/v1/accounts`
- `/api/v1/reports`
- `/api/v1/calendar`
- `/api/v1/system`

## Validation And Authorization

- Use Laravel Form Requests for validation.
- Use policies/gates for authorization.
- Never trust client-provided `school_id` for tenant ownership.
- Server must set `school_id` from active school context.
- All mutations that affect business records must write audit logs.
