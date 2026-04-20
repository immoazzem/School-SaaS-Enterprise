# Phase 8 — Codebase Hardening and Architecture Improvements

**Purpose:** This file is a Codex-executable instruction set. Hand this to Codex at the start of a new session alongside `docs/session-context.md` and `docs/current-status.md`.

**Context:** After Phase 7E, a full codebase review found accumulated technical debt from rapid breadth-first development. This phase addresses the highest-impact items without changing any existing API contracts or auth behavior.

**Planning rule:** `docs/enterprise-plan-v3.md` remains the active plan. This phase extends it.

**Execution rule:** Work checkpoint by checkpoint. Run verification at the end of each checkpoint. Do not proceed to the next if verification fails. Update `docs/engineering-log.md` after each checkpoint.

---

## Checkpoint 8A — Split Monolithic Test File

**Problem:** `apps/api/tests/Feature/EnterpriseFoundationApiTest.php` is 87KB. It contains tests for every module from Phase 1 through Phase 3, making it impossible to run module-specific tests in isolation.

**Deliverables:**

1. Read `apps/api/tests/Feature/EnterpriseFoundationApiTest.php` fully.
2. Split into separate test files by domain module. Each file should extend `TestCase` and use `RefreshDatabase`. Suggested split:
   - `AuthApiTest.php` — login, logout, profile
   - `SchoolApiTest.php` — school CRUD, membership
   - `AcademicClassApiTest.php` — class CRUD, cross-tenant
   - `AcademicSectionApiTest.php` — section CRUD, class validation
   - `AcademicYearApiTest.php` — year CRUD, current-year enforcement
   - `SubjectApiTest.php` — subject CRUD
   - `ClassSubjectApiTest.php` — class-subject assignment
   - `StudentGroupApiTest.php` — groups CRUD
   - `ShiftApiTest.php` — shifts CRUD
   - `DesignationApiTest.php` — designations CRUD
   - `EmployeeApiTest.php` — employee CRUD
   - `GuardianApiTest.php` — guardian CRUD
   - `StudentApiTest.php` — student CRUD
   - `StudentEnrollmentApiTest.php` — enrollment CRUD
   - `TeacherProfileApiTest.php` — teacher profile CRUD
   - `StudentAttendanceApiTest.php` — attendance CRUD, bulk
   - `MarksEntryApiTest.php` — marks entry, grade scales
   - `FeeCategoryApiTest.php` — fee categories/structures/discounts
   - `StudentInvoiceApiTest.php` — invoices, payments, bulk generation
   - `SalaryRecordApiTest.php` — salary records
   - `EmployeeAttendanceApiTest.php` — employee attendance
   - `LeaveApiTest.php` — leave types/applications/balances workflow
3. Preserve every existing test method and assertion. Do not delete or rename any test. Every assertion in the original file must exist in exactly one of the new files.
4. Delete the original `EnterpriseFoundationApiTest.php` only after all split files pass.
5. Share test helpers (seeding, login, school creation) through a `TestHelpers` trait in `tests/Feature/Concerns/TestHelpers.php` or inline per file — whichever keeps each file self-contained.

**Verification:**

```bash
cd D:\Development\School-SaaS-Enterprise\apps\api
php artisan test
# Must pass with the same total test count and assertion count as before the split.
# Previously: 117 tests / 702 assertions (approximately — verify the current number first).
vendor\bin\pint --test
```

---

## Checkpoint 8B — Add API Resources for Sensitive Field Control

**Problem:** Controllers return raw Eloquent models. Sensitive data (student DOBs, guardian phones, employee salaries, payment transaction refs) is exposed to all authenticated users regardless of role.

**Deliverables:**

1. Create Laravel API Resource classes in `apps/api/app/Http/Resources/`:
   - `StudentResource.php` — mask `date_of_birth` to just the year for non-admin roles, hide `medical_notes` from non-admin roles.
   - `GuardianResource.php` — mask `phone` and `email` for teacher role (show only to admin/accountant).
   - `EmployeeResource.php` — hide `salary` for teacher/student/parent roles.
   - `SalaryRecordResource.php` — restricted to accountant and school-admin only in the controller already, but the resource should omit `allowances`/`deductions` breakdown for non-accountant roles.
   - `StudentInvoiceResource.php` — basic resource with eager-loaded enrollment/student/class.
   - `InvoicePaymentResource.php` — mask `transaction_ref` to last 4 characters for non-accountant roles.
2. Update the corresponding controllers to use `return new StudentResource($student)` and `return StudentResource::collection($students)` instead of returning raw models.
3. Use `$this->when()` and Request-based role checking in resources. The authenticated user's permissions for the current school can be derived from the request's school membership.
4. Do not change the response structure shape (keep `data` wrapper for collections, keep the existing field names). Only mask/hide sensitive fields.

**Verification:**

```bash
cd D:\Development\School-SaaS-Enterprise\apps\api
php artisan test
# All existing tests must pass. No response shape changes for admin users.
vendor\bin\pint --test
```

---

## Checkpoint 8C — Database Index Audit

**Problem:** No composite indexes exist for high-traffic query patterns. This will cause performance issues at production scale.

**Deliverables:**

1. Create a new migration `2026_04_20_100000_add_performance_indexes.php`.
2. Add composite indexes for these high-frequency query patterns:

```php
// Student attendance — filtered by school+date constantly
Schema::table('student_attendance_records', function (Blueprint $table) {
    $table->index(['school_id', 'attendance_date']);
    $table->index(['student_enrollment_id', 'attendance_date']);
});

// Employee attendance — same pattern
Schema::table('employee_attendance_records', function (Blueprint $table) {
    $table->index(['school_id', 'date']);
    $table->index(['employee_id', 'date']);
});

// Marks entry — filtered by exam + class subject
Schema::table('marks_entries', function (Blueprint $table) {
    $table->index(['school_id', 'exam_id', 'class_subject_id']);
});

// Student enrollments — filtered by year/class/status
Schema::table('student_enrollments', function (Blueprint $table) {
    $table->index(['school_id', 'academic_year_id', 'academic_class_id', 'status']);
});

// Student invoices — filtered by enrollment and status
Schema::table('student_invoices', function (Blueprint $table) {
    $table->index(['school_id', 'academic_year_id', 'status']);
});

// Audit logs — filtered by school and time
Schema::table('audit_logs', function (Blueprint $table) {
    $table->index(['school_id', 'created_at']);
});

// Fee structures — filtered by year and class
Schema::table('fee_structures', function (Blueprint $table) {
    $table->index(['school_id', 'academic_year_id', 'academic_class_id']);
});

// Leave applications — filtered by status for pending approvals
Schema::table('leave_applications', function (Blueprint $table) {
    $table->index(['school_id', 'status']);
});
```

3. Check existing indexes first — do not duplicate any index that already exists from a `unique()` constraint or primary key.

**Verification:**

```bash
cd D:\Development\School-SaaS-Enterprise\apps\api
php artisan migrate --force
php artisan test
# All tests must pass. No migration errors.
```

---

## Checkpoint 8D — Teacher-to-ClassSubject Assignment Enforcement

**Problem:** `enterprise-plan-v3.md` §3B requires that teachers with `marks.enter.own` can only enter marks for subjects/classes they are assigned to. The teacher-to-class-subject link does not exist. `marks.enter.own` is seeded but unenforced.

**Deliverables:**

1. Add a nullable `teacher_user_id` column to `class_subjects` if it does not already exist. If it exists, verify it is used.
2. Update `ClassSubjectController` store/update to accept `teacher_user_id` and validate the referenced user has an active school membership.
3. Update `MarksEntryController` store method:
   - If the authenticated user has `marks.enter.any`: no restriction (current behavior).
   - If the authenticated user has only `marks.enter.own`: check that the `class_subject` record's `teacher_user_id` matches `auth()->id()`. Deny with 403 if not assigned.
4. Add tests in a new `TeacherMarksRestrictionTest.php`:
   - Teacher assigned to class-subject can enter marks.
   - Teacher NOT assigned to class-subject gets 403 with `marks.enter.own`.
   - Admin with `marks.enter.any` can enter marks for any class-subject.

**Verification:**

```bash
cd D:\Development\School-SaaS-Enterprise\apps\api
php artisan migrate --force
php artisan test --filter=TeacherMarksRestriction
php artisan test
vendor\bin\pint --test
```

---

## Checkpoint 8E — Frontend Layout Extraction

**Problem:** All 24 module pages in `apps/web/app/pages/schools/[schoolId]/` rebuild the sidebar+workspace shell from scratch. Each page has 80-150 lines of identical scoped CSS for `.shell`, `.sidebar`, `.brand`, `.workspace`, `nav`, buttons, tables, forms, alerts, and responsive breakpoints.

**Deliverables:**

1. Create `apps/web/app/layouts/school.vue` with the shared shell:
   - Sidebar with brand link, school name, and a `<slot name="nav" />` for page-specific navigation links.
   - Workspace area with a `<slot />` for page content.
   - Dashboard link in sidebar.
   - All shared styling: `.shell`, `.sidebar`, `.brand`, `.workspace`, `.button`, `.button.secondary`, `.button.compact`, `table`, `th`, `td`, `input`, `select`, `textarea`, `label`, `.form-row`, `.form-actions`, `.status-pill`, `.link-button`, `.alert`, and responsive breakpoints.
2. Migrate **3 pages** as proof of concept (do not migrate all 24 yet):
   - `academic-classes.vue`
   - `attendance.vue`
   - `students.vue`
3. For each migrated page:
   - Add `definePageMeta({ layout: 'school' })`.
   - Remove the inline sidebar template.
   - Remove the duplicate scoped CSS for layout/button/table/form styles.
   - Keep only page-specific styles (like attendance status pills with color variants, or page-specific grid layouts).
   - Pass navigation items through the named slot or a prop.
4. Do not change any script logic, API calls, or form behavior.
5. The layout must receive the `schoolId` from the route params and make it available to the page.

**Verification:**

```bash
cd D:\Development\School-SaaS-Enterprise\apps\web
npm run build
# Must pass with exit code 0. No new warnings beyond the known classified set.
```

Then visually verify in the browser that the 3 migrated pages look and function identically to before migration.

---

## Checkpoint 8F — Tailwind Design Tokens

**Problem:** `tailwind.config.ts` has an empty `extend: {}`. All colors are hardcoded hex values across the codebase. The brand color `#be3455` appears 30+ times, the background `#f7f3ef` appears 20+ times. Any theme change requires 100+ manual edits.

**Deliverables:**

1. Update `apps/web/tailwind.config.ts` to define design tokens:

```typescript
export default {
  content: ['./app/**/*.{vue,ts,js}', './app.vue'],
  theme: {
    extend: {
      colors: {
        brand: {
          DEFAULT: '#be3455',
          50: '#fef2f4',
          100: '#fde6eb',
          200: '#fbd0db',
          300: '#f9a8bc',
          400: '#f37397',
          500: '#be3455',
          600: '#d1244c',
          700: '#b01a3e',
          800: '#93193a',
          900: '#7c1938',
        },
        cream: {
          DEFAULT: '#f7f3ef',
          50: '#fdfcfa',
          100: '#f7f3ef',
          200: '#ede5dc',
          300: '#ddd0c1',
        },
        ink: {
          DEFAULT: '#111827',
          50: '#f9fafb',
          100: '#f3f4f6',
          200: '#e5e7eb',
          300: '#d1d5db',
          400: '#9ca3af',
          500: '#6b7280',
          600: '#4b5563',
          700: '#374151',
          800: '#1f2937',
          900: '#111827',
        },
      },
      fontFamily: {
        sans: ['Switzer', 'Inter', 'ui-sans-serif', 'system-ui', '-apple-system', 'sans-serif'],
      },
      boxShadow: {
        surface: 'inset 0 1px 0 rgba(255,255,255,0.78), 0 24px 60px rgba(17,24,39,0.07)',
        button: '0 12px 28px rgba(17,24,39,0.14)',
      },
      borderRadius: {
        pill: '999px',
      },
    },
  },
  plugins: [],
} satisfies Config
```

2. Update `apps/web/app/assets/css/main.css` to use the tokens where possible. Replace the most common hardcoded values:
   - `#be3455` → `theme('colors.brand.DEFAULT')` or use `@apply text-brand-500` in the relevant class definitions
   - `#111827` → `theme('colors.ink.900')`
   - `#6b7280` → `theme('colors.ink.500')`
   - `#4b5563` → `theme('colors.ink.600')`
   - `#f7f3ef` → `theme('colors.cream.DEFAULT')`
3. Update `LocaleSwitcher.vue` to use brand colors instead of the legacy green `#26342f`/`#163f34`/`#cbd8d2`.
4. Do not rewrite all 24 module pages — only update `main.css`, the layout (from 8E), and `LocaleSwitcher.vue`.

**Verification:**

```bash
cd D:\Development\School-SaaS-Enterprise\apps\web
npm run build
# Must pass. The resulting UI must look visually identical.
```

---

## Checkpoint 8G — Add TypeScript Type Checking and Lint Script

**Problem:** `package.json` has no `typecheck` or `lint` scripts. The v3 quality gates mandate both. No ESLint is configured. Zod is installed but unused.

**Deliverables:**

1. Add a typecheck script to `apps/web/package.json`:
   ```json
   "typecheck": "nuxi typecheck"
   ```
2. Install `@nuxt/eslint` (Nuxt's flat config ESLint module):
   ```bash
   cd apps/web
   npm install -D @nuxt/eslint eslint
   ```
3. Add `@nuxt/eslint` to the Nuxt modules array in `nuxt.config.ts`.
4. Add a lint script:
   ```json
   "lint": "eslint .",
   "lint:fix": "eslint . --fix"
   ```
5. Run `npm run typecheck`. Fix any type errors that appear. Document any that cannot be fixed without API contract changes.
6. Run `npm run lint`. Fix auto-fixable issues. Document any that require manual intervention.

**Verification:**

```bash
cd D:\Development\School-SaaS-Enterprise\apps\web
npm run typecheck
# Must exit 0 or document remaining type issues.
npm run lint
# Must exit 0 or document remaining lint issues.
npm run build
# Must still pass.
```

---

## Checkpoint 8H — Frontend Error Handling System

**Problem:** Every page repeats the same error handling pattern 2-5 times: `catch (e) { error.value = e instanceof Error ? e.message : 'Unable to load X.' }`. There is no typed API error, no toast notification, no retry mechanism.

**Deliverables:**

1. Create `apps/web/app/composables/useApiError.ts`:
   ```typescript
   export interface ApiError {
     message: string
     status?: number
     errors?: Record<string, string[]>  // Laravel validation errors
     error?: string  // Machine-readable error code from v3 plan
   }

   export function useApiError() {
     function parse(error: unknown): ApiError {
       // Handle $fetch errors (which include statusCode, data, etc.)
       // Handle standard Error objects
       // Handle string errors
       // Return a structured ApiError
     }

     function formatMessage(error: unknown, fallback: string): string {
       const parsed = parse(error)
       return parsed.message || fallback
     }

     return { parse, formatMessage }
   }
   ```
2. Update 3 representative pages (same as 8E: `academic-classes.vue`, `attendance.vue`, `students.vue`) to use `useApiError()` instead of inline error parsing.
3. Do not change all 24 pages yet — this is a foundation checkpoint.

**Verification:**

```bash
cd D:\Development\School-SaaS-Enterprise\apps\web
npm run build
```

---

## Checkpoint 8I — Dashboard Cleanup

**Problem:** `dashboard.vue` is 924 lines with 22 identical navigation functions and massive scoped CSS.

**Deliverables:**

1. Replace the 22 individual `openX()` functions with a single generic navigation function:
   ```typescript
   async function navigateToModule(path: string) {
     if (!auth.selectedSchoolId.value) {
       error.value = 'Create or select a school first.'
       return
     }
     await router.push(`/schools/${auth.selectedSchoolId.value}/${path}`)
   }
   ```
2. Update `navActions` to use the path directly:
   ```typescript
   const navActions: Record<string, string> = {
     'Academic Classes': 'academic-classes',
     Sections: 'academic-sections',
     // ... etc
   }
   ```
3. Update `navItems` to carry the route path instead of just a label.
4. If the school layout from 8E is done, consider migrating the dashboard to use it. If not, at minimum deduplicate the scoped CSS by extracting shared button/surface/form styles to `main.css`.

**Verification:**

```bash
cd D:\Development\School-SaaS-Enterprise\apps\web
npm run build
```

Then verify in the browser that all navigation items still work correctly.

---

## Post-Checkpoint: Documentation Update

After completing any or all checkpoints above, update:

- `docs/engineering-log.md` — add Phase 8 entries.
- `docs/current-status.md` — update current checkpoint status.
- `docs/session-context.md` — update latest checkpoint and verification results.

Commit message format: `phase-8X: brief description` (e.g., `phase-8a: split monolithic test file into per-module tests`).

---

## Codex Session Startup Prompt

```text
Read D:\Development\School-SaaS-Enterprise\docs\session-context.md and D:\Development\School-SaaS-Enterprise\docs\current-status.md.
Read D:\Development\School-SaaS-Enterprise\docs\codex-phase-8-improvements.md.
Execute checkpoint 8A first. Run verification. Proceed to 8B only after 8A passes.
Use D:\Development\School-SaaS-Enterprise\docs\enterprise-plan-v3.md as the active plan.
Minimize token usage.
```
