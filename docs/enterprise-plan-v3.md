# School SaaS Enterprise — Enhanced Plan v3
> Based on Phase 0–2 codebase audit + real-world school operations review · April 2026
> Hand this to Codex before starting Phase 3. Supersedes v2.

---

## What Changed from v2 to v3

v2 was technically sound. v3 adds what actually happens in real schools:

- Fee management rewritten to match how Bangladeshi schools actually bill
- Student attendance extended to support late arrivals and half-days
- Employee leave gets a proper approval workflow
- Marks entry gets a teacher-facing workflow, not just raw data entry
- Notifications and communication layer added (missing entirely from v2)
- Result publication workflow added — results don't just get computed, they get published
- Document management added — schools store files constantly
- Promotion workflow made explicit and safe
- Parent portal endpoints added as a first-class concern
- Multi-academic-year data integrity rules tightened
- Deployment and backup concerns addressed for self-hosted schools
- Data export and GDPR/PDPA-equivalent rights added

---

## Mandatory Fixes Before Phase 3 (Unchanged from v2)

All four fixes from v2 remain mandatory:
- Fix 1: Pagination on all index endpoints
- Fix 2: Extract AuditLogger service
- Fix 3: Add School Management endpoints
- Fix 4: Add Rate Limiting

One addition:

### Fix 5 — Switch to MySQL Locally

The codebase is still on SQLite locally. This causes real bugs in Phase 3+ because:
- SQLite does not enforce foreign key constraints by default
- `decimal(12,2)` behaves differently in SQLite vs MySQL
- JSON column queries differ between engines

Before Phase 3, switch `DB_CONNECTION=mysql` and create `school_saas_enterprise` in local MySQL.
Run `php artisan migrate:fresh --seed` against MySQL and confirm all 43 tests still pass.

### Phase 3.0 Stabilization Status — 2026-04-18

Completed:
- Fix 1: Pagination on all index endpoints with a consistent top-level `data`, `meta`, and `links` response envelope.
- Fix 2: Shared `AuditLogger` service and centralized controller audit helper.
- Fix 3: School show/update endpoints with `schools.manage` enforcement and audit logging.
- Fix 4: Named auth/API rate limiters.

Blocked:
- Fix 5: Local MySQL switch. MySQL is listening on `3306`, and the client is available at `C:\Program Files\MySQL\MySQL Server 8.0\bin\mysql.exe`, but `root` without a password returns `ERROR 1045`. The API local `.env` remains SQLite until usable credentials are available or a dev MySQL user is created.

Latest verification:
- `php artisan test`: 47 tests / 293 assertions.
- `vendor\bin\pint --test`: passed.
- `php artisan route:list --path=api/schools`: passed.
- `npm run build`: passed with existing Nuxt/Nitro warnings.
- Agent-browser dashboard and Academic Classes smoke check passed.

---

## Phase 3 — Exams, Marks, Finance, Attendance, Leave

### 3A — Exam Types and Exam Schedules

No changes from v2. Tables, routes, and rules remain the same.

Additional real-world rule:
- Add `weightage_percent` to `exam_types` — many Bangladeshi schools weight term exams differently (e.g., midterm 40%, final 60%). This feeds into result calculation in Phase 4.
- Add `is_published` boolean to `exams` — results should not be visible to students/parents until the exam is published.

```
exam_types    +  weightage_percent decimal(5,2) nullable
exams         +  is_published boolean default false, published_at timestamp nullable, published_by FK users
```

### 3B — Marks Entry

No changes from v2. Keep voided boolean, verification workflow, and bulk endpoint.

Real-world additions:

**Teacher Assignment Enforcement**
- Only teachers assigned to a subject+class via `class_subjects` should be able to enter marks for that combination.
- Add a `marks.enter.own` permission vs `marks.enter.any` — teachers use `own`, principals/admins use `any`.

**Absent vs Zero**
- `is_absent` is already in the schema. Make it explicit in the API that absent and zero-marks are different states. A student who scored zero is different from one who did not appear.
- Add `absent_reason` varchar nullable.

**Marks Range Validation Source**
- `full_marks` and `pass_marks` must come from `class_subjects`, not be passed by the client.
- The server must look up the allowed range — never trust client-submitted max marks.

### 3C — Grade Configuration

No changes from v2.

Real-world addition:
- Add `fail_below_percent` to `grade_scales` — some schools have subject-wise pass thresholds separate from overall GPA. If a student scores below this in any subject they fail overall regardless of total.
- Add `gpa_calculation_method` enum: `weighted` (uses exam weightage) vs `simple_average`.

### 3D — Fee Structure (Significantly Enhanced)

**Real-world context:** Bangladeshi schools have several billing patterns that v2 does not fully model:
1. Monthly tuition billed per session month
2. One-time admission/registration fees
3. Exam fees billed per exam period
4. Optional fees (transport, hostel, lab)
5. Sibling discounts
6. Scholarship discounts per student

**Enhanced tables:**

```
fee_categories
  school_id, name, code, description, sort_order,
  billing_type  enum(monthly, one_time, per_exam, optional)
  status

fee_structures
  school_id, fee_category_id, academic_year_id,
  academic_class_id (nullable — null means all classes),
  student_group_id (nullable),
  amount decimal(12,2),
  due_day_of_month tinyint nullable,   -- for monthly fees
  months_applicable json nullable,     -- e.g. ["2025-01","2025-02"] for selective months
  is_recurring boolean default false,
  status

discount_policies
  school_id, name, code,
  discount_type  enum(flat, percent),
  amount decimal(12,2),
  applies_to_category_ids json nullable,  -- null = applies to all
  is_stackable boolean default false,     -- can this combine with other discounts?
  status

student_discounts
  school_id, student_enrollment_id, discount_policy_id,
  academic_year_id, approved_by, notes
```

**Invoice generation can be:**
- Manual (accountant creates one invoice)
- Bulk (generate for all students in a class for a month)

Add bulk invoice generation endpoint:
```
POST /api/schools/{school}/student-invoices/bulk-generate
  body: { academic_class_id, academic_year_id, month, fee_structure_ids[] }
  dispatches a background job, returns job_id
```

This is critical — schools cannot manually create 500 invoices one by one.

**Discount application at invoice creation:**
- When creating an invoice, the system must automatically apply all active `student_discounts` for that enrollment.
- The `discount` column on the invoice stores the computed total discount.
- Store `discount_breakdown` as JSON on the invoice for auditability.

### 3E — Student Invoices and Payments

All v2 rules remain. Additions:

**Invoice number format for Bangladesh:**
- Format: `{SCHOOL_CODE}/{YY-YY}/{NNNNNN}` e.g. `DPS/25-26/000142`
- This matches the format most Bangladesh schools use on printed receipts.

**Payment methods relevant to Bangladesh:**
```
payment_method  enum(cash, bkash, nagad, rocket, bank_transfer, cheque, card, other)
```

**Mobile money reference validation:**
- If `payment_method` is bkash/nagad/rocket, `transaction_ref` is required.
- Store `payment_channel_metadata` JSON for any provider-specific data.

**Receipt printing:**
- The `invoice_payments` record must have enough data to print a payment receipt without any additional queries.
- Add `student_name`, `class_name`, `fee_month` as denormalized columns or include via API resource.
- Actually, use API Resources properly — do not denormalize. The resource must eager-load what the receipt needs.

### 3F — Employee Salary Records

All v2 rules remain. Additions:

**Real Bangladesh payroll context:**
- Salary has components: basic, house rent allowance (HRA), medical allowance, transport allowance.
- Deductions: provident fund, income tax, advance recovery.

**Enhanced table:**
```
salary_records
  school_id, employee_id, academic_year_id, month (YYYY-MM),
  basic_amount decimal(12,2),
  allowances json,       -- { hra: 3000, medical: 500, transport: 800 }
  gross_amount decimal(12,2) generated,
  deductions json,       -- { provident_fund: 500, income_tax: 200, advance: 0 }
  total_deductions decimal(12,2) generated,
  net_amount decimal(12,2) generated,
  paid_at timestamp nullable,
  payment_method enum(cash, bank_transfer, bkash, nagad, cheque),
  transaction_ref varchar nullable,
  notes text nullable,
  status enum(pending, paid, voided),
  voided_at, voided_by, void_reason
```

For MySQL, `generated` columns can compute `gross_amount = basic + sum(allowances)`. Alternatively, compute in the service and store explicitly. Use explicit storage for portability.

Add a `SalaryService` that:
- Computes gross, deductions, net from inputs
- Prevents duplicate month per employee
- Wraps everything in `DB::transaction()`

### 3G — Employee Attendance (Enhanced from v2 placeholder)

v2 had `StudentAttendanceRecord` already. Employee attendance needs its own model.

```
employee_attendance_records
  school_id, employee_id, date, status enum(present, absent, late, half_day, on_leave),
  check_in_time time nullable,
  check_out_time time nullable,
  notes varchar nullable,
  recorded_by FK users
  unique(school_id, employee_id, date)
```

### 3H — Employee Leave (New — missing from v2)

This is a real-world gap. Schools have leave approval workflows.

```
leave_types
  school_id, name, code,
  max_days_per_year tinyint,
  is_paid boolean,
  requires_approval boolean,
  status

leave_applications
  school_id, employee_id, leave_type_id,
  from_date, to_date, total_days tinyint,
  reason text,
  status enum(pending, approved, rejected, cancelled),
  applied_at, reviewed_by nullable, reviewed_at nullable,
  review_note text nullable

leave_balances
  school_id, employee_id, leave_type_id, academic_year_id,
  total_days tinyint, used_days tinyint, remaining_days tinyint generated
  unique(school_id, employee_id, leave_type_id, academic_year_id)
```

**Workflow:**
```
POST /api/schools/{school}/leave-applications          employee submits
PATCH /api/schools/{school}/leave-applications/{id}/approve    principal/admin approves
PATCH /api/schools/{school}/leave-applications/{id}/reject
DELETE /api/schools/{school}/leave-applications/{id}  employee cancels (if pending)
```

- On approval: decrement `leave_balances.remaining_days`, create attendance records for approved leave days with `status = on_leave`
- On rejection/cancellation: reverse leave balance if already decremented
- All transitions wrapped in `DB::transaction()`
- Audit events: `leave.applied`, `leave.approved`, `leave.rejected`, `leave.cancelled`

### 3I — Student Attendance (Enhanced)

v2 had `StudentAttendanceRecord` already. Ensure it supports:

```
student_attendance_records
  + late_arrival_time time nullable
  + half_day boolean default false
  + leave_reference varchar nullable   -- reference to approved leave if applicable
```

**Bulk attendance entry:**
Already implicit in v2 but must be explicit:
```
POST /api/schools/{school}/student-attendance/bulk
  body: { academic_class_id, date, records: [{enrollment_id, status, late_arrival_time}] }
  wrapped in DB::transaction()
  one record per enrollment per date — upsert behavior (re-taking attendance replaces)
```

### Phase 3 Test Requirements (Enhanced from v2)

All v2 tests remain plus:
- Bulk invoice generation dispatches a job
- Student discounts are automatically applied to invoice amount
- Salary net amount = gross - deductions (computed correctly)
- Leave approval decrements leave balance
- Leave cancellation after approval restores leave balance
- Employee attendance upsert works correctly
- Bulk student attendance entry is atomic
- Marks entry by teacher blocked for subject not assigned to them (with `marks.enter.own`)
- `full_marks` sourced from `class_subjects`, not client input

---

## Phase 4 — Reports, PDFs, Calendar, Notifications

### 4A — Result Calculation Engine

All v2 rules remain. Additions:

**Exam weightage in calculation:**
- When `grade_scales.gpa_calculation_method = weighted`, use `exam_types.weightage_percent` to weight marks from different exams.
- Total must be computed from weighted average: `(midterm_marks * 0.40) + (final_marks * 0.60)`.

**Result Publication Workflow:**
```
POST /api/schools/{school}/exams/{exam}/publish
  - Sets exam.is_published = true, published_at = now(), published_by = actor
  - Clears result cache for this exam
  - Dispatches ResultPublishedNotification to enrolled students/parents (see 4F)
  - Audit event: result.published
  - Requires exams.publish permission (principal/admin only)
```

Students and parents can only see results for published exams.

**Merit Position:**
- Compute and store position within class when results are published.
- Add `result_summaries` table to cache computed results at publication time:

```
result_summaries
  school_id, exam_id, student_enrollment_id,
  total_marks_obtained decimal(8,2),
  total_full_marks decimal(8,2),
  percentage decimal(5,2),
  gpa decimal(4,2),
  grade varchar,
  position_in_class smallint nullable,
  is_pass boolean,
  computed_at timestamp,
  unique(exam_id, student_enrollment_id)
```

This avoids recomputing on every marksheet request.

### 4B — Attendance Summary

No changes from v2. Add:

**Employee attendance summary** (was missing):
```
GET /api/schools/{school}/attendance/employee-summary
    ?employee_id=&academic_year_id=&month=
    Returns: present/absent/late/on_leave counts per employee
```

### 4C — PDF Generation

All v2 rules remain. 

**Bangladesh-specific PDF requirements:**

**Marksheet must include:**
- School name, logo, address
- Student name, roll number, class, section, academic year
- Exam name, date of publication
- Subject-wise: full marks, obtained marks, grade, GPA
- Total, percentage, overall grade, position in class
- Pass/fail stamp
- Principal signature line, class teacher signature line
- Watermark "CONFIDENTIAL" if exam is not published

**Student ID Card must include:**
- Photo (from student profile — use placeholder if missing)
- Name, class, roll, session
- Blood group
- Emergency contact
- School logo and address
- QR code encoding student enrollment ID (generate using `chillerlan/php-qrcode`)

**Fee Invoice Receipt:**
- Invoice number, date
- Student name, class
- Fee head breakdown (what was billed)
- Discount/waiver breakdown
- Payment history (all non-voided payments)
- Balance due
- "PAID" stamp if fully paid

### 4D — School Calendar

No changes from v2. Real-world addition:

```
calendar_events  + academic_class_id nullable  -- class-specific events (e.g., parent-teacher meeting for class 5 only)
                 + is_holiday boolean default false
                 + recurring_rule varchar nullable  -- iCal RRULE format for weekly assemblies etc.
```

**Academic year holidays** should auto-populate calendar when set up. Add:
```
POST /api/schools/{school}/calendar-events/bulk-import-holidays
  body: { academic_year_id, holidays: [{title, date}] }
```

### 4E — Dashboard Analytics API

All v2 roles remain. Real-world additions:

**Add to School Admin / Principal view:**
- Fee collection this month vs last month
- Leave applications pending approval count
- Students with attendance below 75% this month (attendance concern list)

**Add to Accountant view:**
- Monthly fee collection trend (last 6 months, one number per month)
- Outstanding balance by class

**Add to Teacher view:**
- Pending marks entry count (exams where entry is incomplete for their assigned subjects)

All dashboard data must use aggregation queries. No full collection loads.

### 4F — Notifications and Communication (New — Missing from v2)

This is a significant real-world gap. Schools communicate constantly. Without notifications, the system feels dead to users.

**New tables:**

```
notification_templates
  school_id nullable,   -- null = system template, non-null = school-customized
  slug varchar unique,
  channel enum(in_app, sms, email),
  subject varchar nullable,   -- for email
  body_template text,          -- Blade/plain template with {{variables}}
  is_active boolean

notifications
  school_id, recipient_user_id,
  type varchar,        -- e.g. result.published, payment.received, leave.approved
  title varchar,
  body text,
  data json nullable,  -- structured payload for deep linking
  read_at timestamp nullable,
  created_at

sms_logs
  school_id, recipient_phone, message, status enum(sent, failed, pending),
  provider varchar, provider_message_id varchar nullable,
  sent_at timestamp nullable, error text nullable
```

**Notification service:**

```php
// app/Services/NotificationService.php
class NotificationService
{
    public function send(
        User $recipient,
        School $school,
        string $type,
        array $data,
        array $channels = ['in_app']
    ): void;
}
```

**In-app notifications API:**
```
GET  /api/schools/{school}/notifications          paginated, unread first
POST /api/schools/{school}/notifications/mark-read  body: { ids[] } or { all: true }
GET  /api/schools/{school}/notifications/unread-count
```

**SMS integration (Bangladesh context):**
- Integrate with SSL Wireless or Twilio as optional provider.
- SMS should be configurable per school in settings (`school.settings.sms_enabled`, `school.settings.sms_provider`).
- All SMS sends must be queued jobs.
- SMS logs must be stored for audit.

**Events that trigger notifications:**

| Event | In-app | SMS |
|---|---|---|
| result.published | students + parents | optional |
| payment.received | student/parent | yes |
| invoice.created | student/parent | optional |
| leave.approved | employee | optional |
| leave.rejected | employee | optional |
| invitation.sent | invitee | — |
| exam.scheduled | students + teachers | optional |
| attendance.warning (below 75%) | parent | optional |

Phase 4 implementation: in-app only. SMS as optional add-on. Do not block Phase 4 on SMS.

### 4G — Document Management (New — Missing from v2)

Schools attach documents everywhere: admission forms, transfer certificates, employee contracts, circulars.

**New tables:**

```
school_documents
  school_id, uploader_id,
  category enum(circular, student_document, employee_document, financial_document, other),
  title varchar,
  file_path varchar,      -- storage path under storage/app/schools/{school_id}/docs/
  file_name varchar,
  file_size_bytes int,
  mime_type varchar,
  is_public boolean,      -- public = visible to all school members, private = uploader/admin only
  related_model_type varchar nullable,   -- e.g. App\Models\Student
  related_model_id bigint nullable,
  uploaded_at timestamp
```

**Limits enforced by PlanLimitService:**
- `plan_limits.max_storage_mb` must be checked before accepting an upload.

**API routes:**
```
POST   /api/schools/{school}/documents          upload (multipart)
GET    /api/schools/{school}/documents          list with filters
GET    /api/schools/{school}/documents/{id}     signed download URL
DELETE /api/schools/{school}/documents/{id}     soft delete, does not immediately remove file
```

**File storage:**
- Store under `storage/app/schools/{school_id}/docs/{uuid}.{ext}`
- Never expose storage path in API responses — always use signed temporary URLs
- Cleanup job: remove files for soft-deleted document records older than 30 days

### Phase 4 Test Requirements (Enhanced from v2)

All v2 tests remain plus:
- Result publication sets `is_published` and triggers notification job
- Weighted result calculation uses exam weightage correctly
- Students cannot see unpublished results (403)
- `result_summaries` row created on publish
- PDF generation job dispatched and `job_id` returned
- In-app notification created on `payment.received`
- Unread count decrements after `mark-read`
- Document upload rejects oversized files (plan limit)
- Document download returns signed URL, not direct path
- Leave approval triggers in-app notification to employee

---

## Phase 5 — SaaS Administration Console

All v2 content remains. Additions:

### 5F — School Settings (Granular)

`school.settings` is referenced throughout but never defined. Define the shape:

```json
{
  "timezone": "Asia/Dhaka",
  "locale": "bn",
  "currency": "BDT",
  "academic_year_start_month": 1,
  "date_format": "DD/MM/YYYY",
  "sms_enabled": false,
  "sms_provider": null,
  "sms_api_key": null,
  "attendance_warning_threshold_percent": 75,
  "fee_invoice_prefix": "DPS",
  "result_grade_scale_id": null,
  "allow_parent_portal": true,
  "allow_student_portal": true,
  "pdf_header_logo": null,
  "pdf_footer_text": null
}
```

Store as JSON in `schools.settings`. Create a typed `SchoolSettings` DTO/Value Object in PHP. Validate on update using a SchoolSettingsRequest.

### 5G — Parent and Student Portal Endpoints (New)

This was missing from v2 entirely. Parents and students are users in the system (they have accounts and memberships). They need read-only views into their own data.

These endpoints use the same auth but different permissions (`student.portal.view`, `parent.portal.view`):

```
GET /api/schools/{school}/portal/student/profile
GET /api/schools/{school}/portal/student/attendance
GET /api/schools/{school}/portal/student/results
GET /api/schools/{school}/portal/student/invoices
GET /api/schools/{school}/portal/student/notifications

GET /api/schools/{school}/portal/parent/children          list linked students
GET /api/schools/{school}/portal/parent/children/{enrollment}/attendance
GET /api/schools/{school}/portal/parent/children/{enrollment}/results
GET /api/schools/{school}/portal/parent/children/{enrollment}/invoices
GET /api/schools/{school}/portal/parent/notifications
```

**Parent-to-child linking:**
Add `guardian_student_links` (may already exist via `Guardian` model — verify):
```
guardian_student_links
  school_id, guardian_user_id, student_enrollment_id, relationship varchar
```

If the `Guardian` model already stores this differently, adapt rather than duplicate.

### 5H — Data Export and Right to Erasure

Schools must be able to export their own data, and individuals must be able to request deletion. This is increasingly a legal requirement in Bangladesh's Digital Security Act context.

```
POST /api/schools/{school}/data-export/request
  Dispatches ExportSchoolDataJob
  Returns job_id
  Creates a ZIP of all school data as JSON/CSV
  Audit event: data.export.requested

GET  /api/schools/{school}/data-export/{job_id}/download
  Returns signed URL when ready, 202 if still processing

POST /api/schools/{school}/students/{student}/anonymize
  Super admin or school owner only
  Replaces PII (name, phone, address, photo) with anonymized placeholders
  Retains financial and academic records with anonymized identity
  Audit event: student.anonymized
  Cannot be undone
```

### 5I — System Health and Monitoring

Enhance v2's system health endpoint:

```
GET /api/admin/system/health
  Returns:
  {
    "database": { "status": "ok", "latency_ms": 4 },
    "queue": { "status": "ok", "pending_jobs": 12, "failed_jobs": 0 },
    "storage": { "status": "ok", "used_mb": 842, "free_mb": 15360 },
    "cache": { "status": "ok" },
    "scheduler": { "last_run_at": "2026-04-18T06:00:00Z", "status": "ok" }
  }
```

Add a scheduled health check command that runs every 5 minutes and writes to cache. The API endpoint reads from cache, not live checks — prevents the health endpoint from becoming a DDoS vector.

---

## Phase 6 — Student Promotion and Academic Year Transition (New Phase)

**This was missing from v2 entirely.** In reality, this is one of the most critical annual workflows for any school system.

At end of academic year:
1. Results are finalized
2. Students are promoted (or retained/held back) to next class
3. New academic year begins
4. New fee structures are created for the new year
5. Old year data is archived but remains queryable

**New tables:**

```
promotion_batches
  school_id, from_academic_year_id, to_academic_year_id,
  from_academic_class_id, to_academic_class_id,
  status enum(draft, in_progress, completed, rolled_back),
  processed_count int default 0,
  created_by, processed_at nullable

promotion_records
  school_id, promotion_batch_id, student_enrollment_id,
  action enum(promoted, retained, transferred_out, graduated, dropped),
  new_enrollment_id FK student_enrollments nullable,
  notes varchar nullable,
  processed_by
```

**Promotion workflow:**

```
POST /api/schools/{school}/promotions/preview
  body: { from_academic_year_id, from_academic_class_id, to_academic_year_id, to_academic_class_id }
  Returns: list of students with suggested action (promoted by default, retained if failed overall)
  Does not write anything — pure preview

POST /api/schools/{school}/promotions
  Creates promotion_batch in draft status
  Stores all records with actions

PATCH /api/schools/{school}/promotions/{batch}/records/{record}
  Override action for individual student (e.g. manually retain a student who passed)

POST /api/schools/{school}/promotions/{batch}/execute
  Runs in DB::transaction()
  Creates new StudentEnrollment records for promoted students in new class/year
  Sets old enrollment status to 'completed'
  Dispatches job for large batches (>100 students)
  Audit event: promotion.executed

POST /api/schools/{school}/promotions/{batch}/rollback
  Only available if batch.status = completed and within 48 hours
  Reverses all enrollment changes
  Audit event: promotion.rolled_back
```

**Rules:**
- Cannot promote to a class in a non-existent academic year — year must exist first
- Cannot execute promotion twice for the same batch
- Promoted students retain all historical records (attendance, marks, fees) under old enrollment
- Rollback window is 48 hours. After that, manual correction only.

---

## Phase 7 — Advanced Features (Post-Core)

These are deferred but must be in the plan so schema decisions in Phases 3–6 don't block them.

### 7A — Timetable / Routine

```
timetable_periods
  school_id, academic_class_id, shift_id, day_of_week tinyint,
  period_number tinyint, start_time, end_time,
  subject_id nullable, teacher_user_id nullable, room varchar nullable,
  academic_year_id
```

No changes to existing tables needed — plan this now, build later.

### 7B — Homework and Assignments

```
assignments
  school_id, academic_class_id, subject_id, assigned_by,
  title, description, due_date, attachment_path nullable,
  is_published boolean

assignment_submissions
  school_id, assignment_id, student_enrollment_id,
  submitted_at, attachment_path nullable, marks_awarded nullable, feedback nullable
```

### 7C — Payment Gateway Integration (Billing)

When implementing payment for subscriptions or fee collection online:
- bKash Merchant API
- Nagad Merchant API
- SSLCommerz (card payments)

Schema placeholder already in v2 (`subscription_status`, `plan_limits`). Add:
```
payment_gateway_configs
  school_id, gateway enum(bkash, nagad, sslcommerz, stripe),
  credentials_encrypted text,   -- AES-256 encrypted, never store plaintext
  is_active boolean,
  test_mode boolean
```

### 7D — Multi-Language Support

Currently, `school.settings.locale` exists but no i18n is wired.

When implementing:
- Backend: Laravel's `Lang` with JSON translation files per locale (`bn`, `en`)
- Frontend: Nuxt i18n module
- API responses: include locale-appropriate field names where needed (e.g., student name in Bengali)
- Add `name_bn` varchar nullable to `students`, `employees` for Bengali name

### 7E — Offline Support / PWA

For self-hosted schools with unreliable internet:
- Nuxt PWA module for service worker
- Cache key attendance and marks entry pages
- Queue writes locally when offline, sync when connection restored
- This is a significant frontend undertaking — plan only, do not implement before Phase 5

---

## Self-Hosted Deployment Guide (New — Missing from v2)

The plan must address deployment since this is a dual-use system (cloud SaaS + self-hosted).

### Minimum Server Requirements (Self-Hosted)

```
CPU: 2 vCPU
RAM: 4 GB
Storage: 20 GB SSD (expandable)
OS: Ubuntu 22.04 LTS or 24.04 LTS
PHP: 8.3+
MySQL: 8.0+
Redis: 7.x (for queues and cache)
Node: 20+ (for Nuxt build)
```

### Deployment Stack

```
Web server: Nginx
PHP: PHP-FPM
Process manager: Supervisor (for queue workers)
SSL: Let's Encrypt via Certbot
Scheduler: Cron (calls php artisan schedule:run every minute)
```

### Required for Phase 5 Completion

Add `docs/self-hosted-deployment.md` covering:
- Environment variables reference
- Nginx config for API (school-api.yourdomain.com) and Nuxt (app.yourdomain.com)
- PHP-FPM pool config
- Supervisor config for queue:work
- Cron entry for scheduler
- Storage symlink: `php artisan storage:link`
- First-run commands: `migrate --force`, `db:seed --class=SuperAdminSeeder`
- Backup strategy: mysqldump + storage folder, daily

### Backup and Restore

Add artisan commands:

```
php artisan school:backup               full DB dump + storage to dated archive
php artisan school:backup --school={id} single school export
php artisan school:restore {archive}    restore from archive (with confirmation prompt)
```

These are critical for self-hosted users who have no managed backup.

---

## Standing Rules for Codex — All Phases (Enhanced from v2)

All v2 rules remain. Additions:

### Mobile-First API Design

The frontend is currently desktop-focused but parents and teachers will use phones.

- All list endpoints must support `?per_page=` (default 15, max 100)
- All timestamp fields must return ISO 8601 with timezone: `2026-04-18T10:30:00+06:00`
- Images and file URLs must always be absolute signed URLs, never relative paths
- Phone number fields must return in consistent E.164 format: `+8801XXXXXXXXX`

### Sensitive Data Handling

- Student date of birth: mask in list responses, show only in individual detail for authorized roles
- Guardian phone numbers: visible to school admin, teacher, accountant — not to other students
- Employee salary details: visible to accountant and school admin only — never to teacher role
- Financial transaction refs (bKash numbers): visible to accountant only — mask in other responses

Implement a `SensitiveFieldPolicy` or use API Resource `when()` conditions consistently.

### Avoid N+1 Queries

All index responses must use eager loading. Codex must add:
```php
// In every index method that returns related data
->with(['relationship1', 'relationship2'])
```

Before any Phase 3+ index endpoint goes into a controller, check: does this response load a relationship inside a loop? If yes, add `->with()`.

### Error Response Consistency

Add these to the standard error shapes:

```json
// Plan limit reached
{
  "message": "Student limit reached for your current plan.",
  "error": "plan_limit_reached",
  "limit": 200,
  "current": 200
}

// Workflow state violation
{
  "message": "Cannot approve a leave application that is not pending.",
  "error": "invalid_state_transition",
  "current_state": "approved"
}
```

---

## Updated Phase Completion Checklist

Same as v2, with additions:

```bash
# Backend
php artisan test                          # all tests pass
vendor/bin/pint --test                    # no style violations
php artisan route:list                    # no missing routes
php artisan migrate:fresh --seed          # clean migration from scratch
php artisan queue:work --stop-when-empty  # queue processes without errors

# Frontend
npm run build
npm run typecheck

# Manual checks each phase
# - Open each new page in browser, no JS errors
# - Complete one create + edit + delete flow per new module
# - Verify audit log written for each mutation
# - Verify cross-tenant access returns 404
# - Verify permission-denied returns 403
```

---

## Context Files for New Codex Sessions (Unchanged from v2)

```
Read D:\Development\School-SaaS-Enterprise-CONTEXT.md
Read D:\Development\School-SaaS-Enterprise\docs\current-status.md
Read D:\Development\School-SaaS-Enterprise\docs\enterprise-plan-v3.md
Continue from current status. Minimize token usage.
```

Update `docs/current-status.md` and `docs/engineering-log.md` at the end of every session.

---

## What This Plan Does Not Change

- Stack: Laravel / PHP / Nuxt / Tailwind v4 / TypeScript — no changes
- Auth: Bearer token (Sanctum API tokens) — do not switch mid-project
- Tenancy model: `school.member` middleware + policy checks + relationship-scoped queries
- Test framework: PHPUnit via `php artisan test`
- Herd local dev setup
- No Docker required for local dev

---

## Summary of Net New Items in v3 vs v2

| Area | v2 | v3 |
|---|---|---|
| MySQL locally | SQLite | Required fix before Phase 3 |
| Exam weightage | Not in schema | Added to exam_types |
| Result publication workflow | Not present | Full publish/unpublish workflow |
| Result summaries (cached) | Not present | result_summaries table |
| Fee bulk generation | Not present | Bulk invoice generation endpoint + job |
| Discount policies | Not present | discount_policies + student_discounts |
| Salary components | Single amount | Basic + allowances + deductions breakdown |
| Employee leave | Not present | Full leave workflow with approval |
| Employee attendance | Not in schema | Separate model with check-in/out |
| Student attendance | Basic | Late arrival, half-day, leave reference |
| Notifications | Not present | In-app notification system, SMS placeholder |
| Document management | Not present | File upload/download with signed URLs |
| Parent/student portal | Not present | Dedicated portal endpoints |
| Student promotion | Not present | Full promotion workflow with rollback |
| Data export / anonymize | Not present | GDPR-equivalent rights |
| Self-hosted deployment | Not documented | Full deployment guide |
| Backup/restore commands | Not present | Artisan backup commands |
| Timetable (deferred) | In backlog | Schema planned, Phase 7 |
| SMS notifications | Not present | Planned, bKash/Nagad/SSL |
| Bengali name fields | Not present | name_bn on student/employee |
| School settings schema | Undefined | Fully typed JSON shape defined |
