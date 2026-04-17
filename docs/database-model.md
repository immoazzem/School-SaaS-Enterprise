# Database Model

## Legacy Schema Summary

The legacy app stores multiple concepts in the `users` table:

- login identity
- student profile fields
- parent names
- employee fields
- salary
- role/usertype
- designation

The rebuild must normalize these concepts for enterprise tenancy, security, reporting, and future billing.

## Core SaaS Tables

Create these tables first:

- `schools`: tenant record, slug, name, status, contact metadata, subscription placeholders.
- `users`: login identity only; no student/employee-specific fields.
- `school_memberships`: connects users to schools and stores membership status.
- `roles`: school-aware or system roles.
- `permissions`: normalized permission catalog.
- `role_permissions`: role-to-permission assignments.
- `user_role_assignments`: assigns roles to users within a school.
- `audit_logs`: immutable activity records.

## Academic Tables

Map legacy setup tables to school-scoped tables:

- `academic_years`
- `academic_classes`
- `student_groups`
- `academic_sections`
- `shifts`
- `subjects`
- `class_subjects`
- `exam_types`
- `grading_scales`
- `designations`

Every table must include `school_id`, timestamps, and soft deletes where business-safe.

## People Tables

Replace the legacy overloaded `users` table with:

- `students`: student profile, school id, user id nullable for portal access.
- `guardians`: parent/guardian profile.
- `guardian_student`: many-to-many relationship.
- `employees`: staff profile, user id nullable for portal access.
- `teacher_profiles`: teacher-specific profile, linked to employees or users.
- `enrollments`: student class/year/section/group/shift/roll history.
- `student_documents`: optional student files/records.

## Attendance And Exams

Use normalized operational records:

- `student_attendance_records`
- `employee_attendance_records`
- `leave_purposes`
- `employee_leaves`
- `exam_terms` or `exam_types`
- `marks_entries`
- `result_publications`

Attendance statuses should be constrained to known values: present, absent, leave, half_day, late, excused.

## Finance Tables

Replace legacy account tables with auditable finance records:

- `fee_categories`
- `fee_structures`
- `student_invoices`
- `student_invoice_items`
- `payments`
- `payment_allocations`
- `salary_profiles`
- `salary_adjustments`
- `payroll_runs`
- `payroll_items`
- `expense_categories`
- `expenses`

Finance records should not be hard-deleted by default. Use status fields, reversals, voids, and audit logs.

## Calendar And Schedule

Use:

- `routines`
- `lessons`
- `calendar_events`

Lessons should reference school, class, subject, teacher, weekday, start time, and end time.

## Legacy Import Mapping

| Legacy table | New target |
| --- | --- |
| `users` | `users`, `students`, `employees`, `teacher_profiles`, `school_memberships` |
| `user_permissions` | `roles`, `permissions`, `role_permissions` |
| `student_classes` | `academic_classes` |
| `student_years` | `academic_years` |
| `student_groups` | `student_groups` |
| `student_sections` | `academic_sections` |
| `student_shifts` | `shifts` |
| `school_subjects` | `subjects` |
| `assign_subjects` | `class_subjects` |
| `assign_students` | `enrollments` |
| `discount_students` | fee concessions/discounts |
| `student_attendances` | `student_attendance_records` |
| `student_marks` | `marks_entries` |
| `marks_grades` | `grading_scales` |
| `employee_salary_logs` | `salary_adjustments` |
| `employee_leaves` | `employee_leaves` |
| `employee_attendances` | `employee_attendance_records` |
| `account_student_fees` | `payments` and `payment_allocations` |
| `account_employee_salaries` | `payroll_items` |
| `other_account_costs` | `expenses` |
| `routines` | `routines` |
| `lessons` | `lessons` |

## Enterprise Defaults

- Add `school_id` to every tenant-owned table.
- Use foreign keys for core relationships.
- Add unique indexes scoped by `school_id`, not global unique names.
- Prefer decimal columns for money instead of double.
- Prefer date columns for dates instead of strings.
- Use soft deletes for master data; avoid hard deletes for finance and audit data.
