# School SaaS Enterprise — Enhanced Plan v2
> Based on actual Phase 0–2 codebase audit · April 2026
> Hand this to Codex before starting Phase 3.

---

## Current State Summary (What Codex Actually Built)

**Backend (Laravel 13.5 / PHP 8.5.5 / Sanctum token auth)**
- 43 passing tests / 274 assertions
- 41 routes, all tenant-scoped via `school.member` middleware
- Models: School, User, SchoolMembership, Role, Permission, UserRoleAssignment, AuditLog, AcademicClass, AcademicYear, AcademicSection, Subject, ClassSubject, StudentGroup, Shift, Designation, Employee, Guardian, Student, StudentEnrollment, TeacherProfile, StudentAttendanceRecord
- Tenant isolation: enforced at relationship level (`$school->students()`) AND at validation (`Rule::exists(...)->where('school_id', $school->id)`)
- Audit logs: written manually in each controller's `recordAudit()` private method
- Auth: Sanctum API token (Bearer), NOT SPA cookie — this is the actual implemented pattern

**Frontend (Nuxt 4.4.2 / Vue 3 / TypeScript)**
- 15 page workspaces, all functional and live-tested via agent-browser
- Dashboard with school switcher, role-aware navigation
- Bearer token auth flow confirmed working against Herd

**What is NOT done yet**
- Pagination (all index methods return `->get()` — unbounded)
- MySQL setup (still using SQLite locally)
- Shared AuditLogger service (copy-pasted `recordAudit()` in every controller)
- No `SchoolController` show/update/destroy
- No API versioning
- No rate limiting implemented yet (planned but absent)
- Phase 3 onward: Exams, Marks, Fees, Finance, Reports, PDFs, SaaS Admin

---

## Mandatory Fixes Before Phase 3 Begins

These are not optional. Codex must complete all four before writing any Phase 3 code.

### Fix 1 — Pagination on All Index Endpoints

Every `index()` method currently returns `->get()`. Replace with `->paginate(15)` and wrap in the standard meta envelope:

```json
{
  "data": [],
  "meta": {
    "current_page": 1,
    "last_page": 4,
    "per_page": 15,
    "total": 58
  }
}
```

Apply to: AcademicClass, AcademicYear, AcademicSection, Subject, ClassSubject, StudentGroup, Shift, Designation, Employee, Guardian, Student, StudentEnrollment, TeacherProfile, StudentAttendanceRecord.

Update all affected Nuxt pages to handle paginated responses and render a simple prev/next control.

Update tests to use `assertJsonStructure(['data', 'meta'])` where relevant.

### Fix 2 — Extract AuditLogger Service

Every controller has an identical `recordAudit()` private method. Extract to a singleton service:

```php
// app/Services/AuditLogger.php
class AuditLogger
{
    public function log(
        Request $request,
        School $school,
        string $event,
        Model $target,
        array $metadata = []
    ): AuditLog;
}
```

Bind in `AppServiceProvider`. Inject via constructor in controllers. This is the last time Codex should touch audit plumbing — from Phase 3 onward every controller just calls `$this->auditLogger->log(...)`.

### Fix 3 — Add School Management Endpoints

Currently `SchoolController` only has `index` and `store`. Add:

```
GET    /api/schools/{school}          show
PATCH  /api/schools/{school}          update (name, locale, timezone, settings)
DELETE /api/schools/{school}          soft delete (super admin only)
GET    /api/schools/{school}/members  list members
POST   /api/schools/{school}/members  invite member
PATCH  /api/schools/{school}/members/{user}  update role/status
```

These are needed for Phase 5 SaaS admin and for school settings pages.

### Fix 4 — Add Rate Limiting

The original plan specified rate limiting but it was never implemented. Add in `bootstrap/app.php`:

```php
RateLimiter::for('login', function (Request $request) {
    return Limit::perMinute(10)->by($request->ip());
});
RateLimiter::for('api', function (Request $request) {
    return Limit::perMinute(120)->by($request->user()?->id ?: $request->ip());
});
```

Apply `throttle:login` to `POST /api/auth/login`. Apply `throttle:api` to all authenticated routes.

---

## Phase 3 — Exams, Marks, Finance

This is the most complex phase. Data integrity is critical. Every finance write must use `DB::transaction()`. Finance records must never be hard-deleted.

### 3A — Exam Types and Exam Schedules

**New tables:**

```
exam_types        school_id, name, code, description, sort_order, status
exams             school_id, exam_type_id, academic_year_id, name, starts_on, ends_on, status
exam_schedules    school_id, exam_id, academic_class_id, subject_id, exam_date, start_time, end_time, room
```

**Permissions:** `exam_types.manage`, `exams.manage`

**API routes:**
```
/api/schools/{school}/exam-types      full CRUD
/api/schools/{school}/exams           full CRUD
/api/schools/{school}/exam-schedules  full CRUD
```

**Rules:**
- `exam_type_id` must belong to the same school
- `academic_year_id` must belong to the same school
- `exam_date` must fall within `exam.starts_on` and `exam.ends_on`
- Soft deletes on all three tables

**Tests required:**
- CRUD + audit log for each
- Cross-tenant rejection for exam_type_id, academic_year_id, subject_id, academic_class_id

### 3B — Marks Entry

**New tables:**

```
mark_entries    school_id, exam_id, student_enrollment_id, subject_id,
                marks_obtained, is_absent, remarks, entered_by, verified_by, verified_at
```

**Permissions:** `marks.enter`, `marks.verify`

**Rules:**
- `marks_obtained` must be between 0 and `class_subjects.full_marks` for that subject
- Duplicate entry (same exam + enrollment + subject) must be rejected with a clear validation error
- Marks can be edited by users with `marks.enter` but verification clears on re-edit
- Verification requires `marks.verify` permission (separate from entry)
- Never hard-delete mark entries — use a `voided` boolean and `voided_reason`
- `DB::transaction()` on bulk mark entry (saving multiple subjects at once)
- Audit events: `mark.entered`, `mark.updated`, `mark.verified`, `mark.voided`

**API routes:**
```
GET    /api/schools/{school}/mark-entries
POST   /api/schools/{school}/mark-entries         single entry
POST   /api/schools/{school}/mark-entries/bulk    array of entries in one transaction
PATCH  /api/schools/{school}/mark-entries/{entry}
POST   /api/schools/{school}/mark-entries/{entry}/verify
POST   /api/schools/{school}/mark-entries/{entry}/void
```

### 3C — Grade Configuration

**New tables:**

```
grade_scales     school_id, name, is_default, status
grade_rules      school_id, grade_scale_id, grade, gpa, min_percent, max_percent, remarks
```

**Rules:**
- Grade rules must not overlap within the same scale
- Each school should have exactly one default scale

**API routes:**
```
/api/schools/{school}/grade-scales    full CRUD
/api/schools/{school}/grade-rules     full CRUD, scoped by grade_scale_id
```

### 3D — Fee Structure

**New tables:**

```
fee_categories    school_id, name, code, description, sort_order, status
fee_structures    school_id, fee_category_id, academic_year_id, academic_class_id,
                  student_group_id (nullable), amount, due_day_of_month, is_recurring, status
```

**Permissions:** `fees.manage`, `finance.view`

**Rules:**
- `fee_category_id`, `academic_year_id`, `academic_class_id` must belong to the same school
- Soft deletes only — never hard delete fee structures once any invoice references them

**API routes:**
```
/api/schools/{school}/fee-categories    full CRUD
/api/schools/{school}/fee-structures    full CRUD
```

### 3E — Student Invoices and Payments

**New tables:**

```
student_invoices    school_id, student_enrollment_id, fee_structure_id,
                    invoice_no, amount, discount, waiver, net_amount,
                    due_date, status (unpaid/partial/paid/waived), notes

invoice_payments    school_id, invoice_id, amount_paid, payment_method,
                    transaction_ref, paid_at, received_by, notes
```

**Critical rules:**
- `invoice_no` must be unique per school, auto-generated as `{school_slug}-INV-{year}-{sequence}`
- `net_amount = amount - discount - waiver`, must be computed and stored at creation time
- `invoice_payments` must never be hard-deleted — add `voided_at`, `voided_by`, `void_reason`
- Every invoice and payment write must use `DB::transaction()`
- Invoice status must be automatically recalculated after each payment
- `amount_paid` across all non-voided payments must never exceed `net_amount` — validate this
- Audit events: `invoice.created`, `invoice.updated`, `payment.received`, `payment.voided`
- Permission `finance.payments.create` is accountant-only; enforce in policy

**API routes:**
```
GET    /api/schools/{school}/student-invoices
POST   /api/schools/{school}/student-invoices
GET    /api/schools/{school}/student-invoices/{invoice}
PATCH  /api/schools/{school}/student-invoices/{invoice}
POST   /api/schools/{school}/student-invoices/{invoice}/payments
POST   /api/schools/{school}/student-invoices/{invoice}/payments/{payment}/void
```

### 3F — Employee Salary Records

**New tables:**

```
salary_records    school_id, employee_id, academic_year_id, month (YYYY-MM),
                  gross_amount, deductions, net_amount, paid_at, payment_method,
                  transaction_ref, notes, status (pending/paid/voided)
```

**Rules:**
- One salary record per employee per month — unique constraint on `(school_id, employee_id, month)`
- Never hard-delete — use `status = voided` with `voided_at`, `voided_by`, `void_reason`
- `DB::transaction()` on all writes
- Audit events: `salary.paid`, `salary.voided`

**API routes:**
```
GET    /api/schools/{school}/salary-records
POST   /api/schools/{school}/salary-records
GET    /api/schools/{school}/salary-records/{record}
POST   /api/schools/{school}/salary-records/{record}/void
```

### 3G — Income and Expense Accounts

**New tables:**

```
income_records    school_id, category, amount, received_at, description, reference, received_by
expense_records   school_id, category, amount, spent_at, description, reference, approved_by
```

**Rules:**
- These are general ledger entries, not tied to fee invoices
- Never hard-delete
- Audit events: `income.recorded`, `expense.recorded`

### Phase 3 Test Requirements

Beyond CRUD + audit log tests per module, these must be explicit:

- Mark entry rejects `marks_obtained` above `full_marks`
- Duplicate mark entry (same exam + enrollment + subject) returns 422
- Bulk mark entry is atomic — if one fails, all fail
- Payment that would exceed `net_amount` returns 422
- Voided payment does not count toward invoice balance
- Invoice status transitions: `unpaid → partial → paid` as payments accumulate
- Salary record prevents duplicate month per employee per school
- All cross-tenant FK rejections for new models

---

## Phase 4 — Reports, PDFs, and Dashboard Analytics

### 4A — Result Calculation Engine

Add a `ResultService` that computes:

```
per-student:
  marks_obtained per subject
  percentage per subject
  grade and GPA per subject (using school's grade scale)
  total marks, total obtained, overall percentage, overall grade
  pass/fail determination (each subject + overall)
  position/rank in class
```

This is pure computation, not a new table. Result data is derived on demand or cached.

**Cacheable:** Cache result summaries per `(exam_id, academic_class_id)` using Laravel cache with a tag like `results:{exam_id}`. Invalidate on any mark entry change for that exam.

**API routes:**
```
GET /api/schools/{school}/exams/{exam}/results              class result summary
GET /api/schools/{school}/exams/{exam}/results/{enrollment} individual result
GET /api/schools/{school}/exams/{exam}/marksheets           paginated marksheet data
```

### 4B — Attendance Summary

**API routes:**
```
GET /api/schools/{school}/attendance/summary
    ?academic_class_id=&academic_year_id=&month=
    Returns: per-student present/absent/late counts and percentage
```

This is an aggregation query, not a new table.

### 4C — PDF Generation

Use `barryvdh/laravel-dompdf` (Laravel 13 compatible). Do not use `niklasravnsborg/laravel-pdf` from the legacy app.

PDF types to support:
- Student marksheet (per student per exam)
- Class result sheet (tabular, all students in a class)
- Student ID card (compact, photo-ready layout)
- Fee invoice receipt
- Salary slip

All PDF generation must:
- Be dispatched as a background job (`GenerateReportJob`) for multi-page documents
- Store output temporarily in `storage/app/reports/{school_id}/` with a signed URL
- Audit log every export: `report.exported` with `type`, `target_id`, and actor

**API routes:**
```
POST /api/schools/{school}/reports/marksheet          dispatch job, return job_id
POST /api/schools/{school}/reports/result-sheet
POST /api/schools/{school}/reports/id-card
POST /api/schools/{school}/reports/invoice/{invoice}
POST /api/schools/{school}/reports/salary/{record}
GET  /api/schools/{school}/reports/{job_id}/download  return signed URL when ready
```

### 4D — School Calendar

**New table:**

```
calendar_events    school_id, title, description, starts_at, ends_at,
                   event_type (holiday/exam/meeting/other), is_public, created_by
```

**API routes:**
```
/api/schools/{school}/calendar-events    full CRUD
GET with ?from=&to= date range filter
```

### 4E — Dashboard Analytics API

Role-aware summary cards. Return different data per role:

```
GET /api/schools/{school}/dashboard/summary
```

Response shape varies by permission:
- School admin / principal: student count, employee count, today's attendance rate, pending fee collection total, upcoming exams
- Teacher: assigned subjects, today's class schedule, pending marks entry
- Accountant: today's collections, unpaid invoices count, pending salaries
- Auditor: recent audit log entries, summary counts

Keep this as a single endpoint that assembles data from multiple sources. Use `DB::select` aggregations where appropriate — avoid loading full collections just to count.

### Phase 4 Test Requirements

- Result calculation produces correct grades per grade scale
- Attendance summary returns correct counts
- PDF job is dispatched and job record created
- Report export writes audit log
- Dashboard summary returns 200 for each role type
- Calendar events are tenant-scoped

---

## Phase 5 — SaaS Administration Console

### 5A — Super Admin Panel

A separate route group `middleware(['auth:sanctum', 'super.admin'])` for all `/api/admin/*` routes.

Add `SuperAdminMiddleware` that checks:
```php
$request->user()->hasSystemRole('super-admin')
```

**Endpoints:**
```
GET    /api/admin/schools               paginated, search, status filter
GET    /api/admin/schools/{school}      full detail
PATCH  /api/admin/schools/{school}      status, plan, trial dates
DELETE /api/admin/schools/{school}      soft delete
GET    /api/admin/audit-logs            cross-school audit log viewer with school/actor/event filters
GET    /api/admin/users                 global user list
GET    /api/admin/system/health         DB ping, queue status, disk usage
GET    /api/admin/system/stats          total schools, total users, monthly active
```

### 5B — SaaS Plan Structure (Billing Placeholder)

Add these columns to `schools`:

```
plan              varchar default 'free'
subscription_status  enum(trialing, active, past_due, cancelled) default 'trialing'
trial_ends_at     timestamp nullable
plan_limits       json nullable
```

`plan_limits` JSON shape:
```json
{
  "max_students": 200,
  "max_employees": 30,
  "max_storage_mb": 512,
  "reports_enabled": true,
  "api_access": false
}
```

Add a `PlanLimitService` that checks limits before creating students, employees, etc. Return `422` with `"error": "plan_limit_reached"` when exceeded.

No payment provider in Phase 5. Wire only the structure.

### 5C — School Onboarding Flow

```
POST /api/admin/schools/{school}/onboard
```

This endpoint:
1. Sets `subscription_status = trialing` and `trial_ends_at = now() + 30 days`
2. Creates default roles for the school
3. Sends welcome email to school owner (queued)
4. Audit logs: `school.onboarded`

### 5D — User Invitation System

```
POST   /api/schools/{school}/invitations     send invite by email
GET    /api/schools/{school}/invitations     list pending
DELETE /api/schools/{school}/invitations/{id}  revoke

POST   /api/invitations/{token}/accept       public endpoint, creates membership
```

Use a signed URL with `Str::uuid()` token and `expires_at`. Store in `school_invitations` table.

### 5E — Audit Log Viewer

```
GET /api/schools/{school}/audit-logs
    ?event=&actor_id=&from=&to=&per_page=25
    Returns paginated audit log with actor name, school name, event, target, metadata summary
```

Read-only. Requires `audit.view` permission. Auditors and school admins can access. Super admin can see all schools.

### Phase 5 Test Requirements

- Super admin middleware blocks non-super-admin users
- Plan limit service returns 422 when student limit reached
- Invitation token creates membership on accept
- Invitation rejects expired tokens
- Audit log viewer is paginated and school-scoped
- Onboarding sets correct trial dates and default roles

---

## Standing Rules for Codex — All Phases

These apply to every file written from Phase 3 onward.

### Tenant Isolation (Never Relax)
- Every controller must query through `$school->relationship()` — never `Model::where('school_id', $school->id)`
- Every FK validated with `Rule::exists('table', 'id')->where('school_id', $school->id)`
- Every policy must check `$model->school_id === $school->id` before checking permissions

### Finance Integrity
- Every invoice, payment, salary, income, and expense write must be inside `DB::transaction()`
- No hard deletes on any financial record — ever
- Voided records must store `voided_at`, `voided_by`, `void_reason`

### Audit Logging (Use the Service)
- Inject `AuditLogger` — never copy-paste `recordAudit()` again
- Every Phase 3+ mutation must have a test asserting `audit_logs` contains the event

### Response Shape Consistency
Single record:
```json
{ "data": {} }
```
Collection (always paginated from Phase 3):
```json
{ "data": [], "meta": { "current_page": 1, "last_page": 4, "per_page": 15, "total": 58 } }
```
Created: HTTP 201
Updated/action: HTTP 200
Deleted: HTTP 204
Unprocessable: HTTP 422 with `{ "message": "", "errors": {} }`
Forbidden: HTTP 403
Unauthorized: HTTP 401
Cross-tenant or not found: HTTP 404

### Test Requirements Every Phase
For every new module:
- CRUD happy path with audit log assertion
- Permission denied (403) for member without permission
- Non-member gets 403 from middleware
- At least one cross-tenant FK rejection test
- Finance modules additionally: transaction atomicity and financial constraint tests

### Queue and Jobs
- Any operation taking >200ms (PDF generation, bulk imports, email sending) must be dispatched as a job
- Use `database` queue driver locally (already in Laravel default)
- Job failures must be logged to `failed_jobs` and visible to super admin

### Bangladesh-Specific Requirements
- Default `locale` on school creation: `bn` for Bengali schools, `en` default
- Currency: `BDT` — store all monetary values as `decimal(12,2)` in BDT
- Phone validation: accept `+880xxxxxxxxxx` and `01xxxxxxxxx` formats
- Date display: support both Gregorian and Bengali calendar in API responses via `?calendar=bn` query param (Phase 4+)
- Timezone default: `Asia/Dhaka`

---

## Phase Completion Checklist

Codex must run these checks before declaring any phase complete:

```bash
# Backend
php artisan test
vendor/bin/pint --test
php artisan route:list
php artisan migrate:fresh --seed

# Frontend
npm run build
npm run typecheck

# Smoke check
# agent-browser must open each new page and verify no error overlay
# agent-browser must complete at least one create flow per new module
```

No phase is complete if:
- Any test is failing
- Pint has violations
- `npm run build` has errors (warnings from Nuxt/Nitro are acceptable)
- Any new page has a visible JS error in agent-browser

---

## Context Files for New Codex Sessions

Start every new session with:
```
Read D:\Development\School-SaaS-Enterprise-CONTEXT.md
Read D:\Development\School-SaaS-Enterprise\docs\current-status.md
Read D:\Development\School-SaaS-Enterprise\docs\enterprise-plan-v2.md
Continue from current status. Minimize token usage.
```

Update `docs/current-status.md` and `docs/engineering-log.md` at the end of every session.

---

## What This Plan Does Not Change

- Stack: Laravel 13.5 / PHP 8.5.5 / Nuxt 4.4.2 / Tailwind v4 / TypeScript — no changes
- Auth: Bearer token (Sanctum API tokens) — do not switch to SPA cookies mid-project
- Tenancy model: `school.member` middleware + policy checks + relationship-scoped queries — keep exactly as built
- Test framework: PHPUnit via `php artisan test` — keep as is
- Herd local dev setup — keep as is
- No Docker — keep as is
