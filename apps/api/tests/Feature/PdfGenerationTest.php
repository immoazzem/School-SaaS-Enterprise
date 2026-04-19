<?php

namespace Tests\Feature;

use App\Jobs\GenerateReportJob;
use App\Models\Exam;
use App\Models\Permission;
use App\Models\ReportExport;
use App\Models\Role;
use App\Models\School;
use App\Models\StudentEnrollment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;
use Throwable;

class PdfGenerationTest extends TestCase
{
    use RefreshDatabase;

    public function test_marksheet_pdf_renders_and_persists_completed_export(): void
    {
        Storage::fake('local');

        [$school, $user, $exam, $enrollment] = $this->createMarksheetFixture();
        $this->grantSchoolPermission($user, $school, 'reports.view', 'reports');

        $export = $school->reportExports()->create([
            'job_id' => '22222222-2222-4222-8222-222222222222',
            'requested_by' => $user->id,
            'type' => 'marksheet',
            'status' => 'pending',
            'target_type' => $enrollment->getMorphClass(),
            'target_id' => $enrollment->id,
            'parameters' => [
                'exam_id' => $exam->id,
                'student_enrollment_id' => $enrollment->id,
            ],
        ]);

        (new GenerateReportJob($export->job_id))->handle();

        $export->refresh();
        $this->assertSame('completed', $export->status);
        $this->assertNotNull($export->file_path);
        Storage::disk('local')->assertExists($export->file_path);

        $content = Storage::disk('local')->get($export->file_path);
        $this->assertStringStartsWith('%PDF', $content);
        $this->assertGreaterThan(1000, strlen($content));
    }

    public function test_invoice_pdf_renders_with_payment_history(): void
    {
        Storage::fake('local');

        [$school, $user, $exam, $enrollment] = $this->createMarksheetFixture();
        $invoice = $school->studentInvoices()->create([
            'student_enrollment_id' => $enrollment->id,
            'academic_year_id' => $enrollment->academic_year_id,
            'invoice_no' => 'INV-PDF-001',
            'fee_month' => '2026-04',
            'subtotal' => 1000,
            'discount' => 100,
            'total' => 900,
            'paid_amount' => 500,
            'due_date' => '2026-04-30',
            'status' => 'partial',
        ]);
        $school->invoicePayments()->create([
            'student_invoice_id' => $invoice->id,
            'amount' => 300,
            'paid_on' => '2026-04-10',
            'payment_method' => 'cash',
            'transaction_ref' => 'CASH-001',
        ]);
        $school->invoicePayments()->create([
            'student_invoice_id' => $invoice->id,
            'amount' => 200,
            'paid_on' => '2026-04-12',
            'payment_method' => 'bank',
            'transaction_ref' => 'BANK-002',
        ]);

        $export = $school->reportExports()->create([
            'job_id' => '33333333-3333-4333-8333-333333333333',
            'requested_by' => $user->id,
            'type' => 'invoice-receipt',
            'status' => 'pending',
            'target_type' => $invoice->getMorphClass(),
            'target_id' => $invoice->id,
            'parameters' => ['invoice_id' => $invoice->id],
        ]);

        (new GenerateReportJob($export->job_id))->handle();

        $export->refresh();
        $this->assertSame('completed', $export->status);
        $this->assertNotNull($export->file_path);
        Storage::disk('local')->assertExists($export->file_path);

        $content = Storage::disk('local')->get($export->file_path);
        $this->assertStringStartsWith('%PDF', $content);
        $this->assertGreaterThan(1000, strlen($content));
    }

    public function test_report_job_marks_export_failed_when_required_pdf_data_is_missing(): void
    {
        Storage::fake('local');

        $user = User::factory()->create();
        $school = School::query()->create(['name' => 'Missing PDF School', 'slug' => 'missing-pdf-school']);
        $this->addActiveMember($user, $school);

        $export = ReportExport::query()->create([
            'job_id' => '44444444-4444-4444-8444-444444444444',
            'school_id' => $school->id,
            'requested_by' => $user->id,
            'type' => 'marksheet',
            'status' => 'pending',
            'target_type' => (new StudentEnrollment)->getMorphClass(),
            'target_id' => 999999,
            'parameters' => ['exam_id' => 999999, 'student_enrollment_id' => 999999],
        ]);

        try {
            (new GenerateReportJob($export->job_id))->handle();
            $this->fail('The report job should fail when the marksheet target is missing.');
        } catch (Throwable) {
            $this->assertDatabaseHas('report_exports', [
                'job_id' => $export->job_id,
                'status' => 'failed',
            ]);
        }
    }

    public function test_user_without_reports_view_cannot_request_marksheet_pdf(): void
    {
        [$school, $user, $exam, $enrollment] = $this->createMarksheetFixture();

        Sanctum::actingAs($user);

        $this->postJson("/api/v1/schools/{$school->id}/reports/marksheet", [
            'exam_id' => $exam->id,
            'student_enrollment_id' => $enrollment->id,
        ])->assertForbidden();
    }

    /**
     * @return array{0: School, 1: User, 2: Exam, 3: StudentEnrollment}
     */
    private function createMarksheetFixture(): array
    {
        $user = User::factory()->create();
        $school = School::query()->create(['name' => 'PDF School', 'slug' => 'pdf-school-'.str()->random(6)]);
        $this->addActiveMember($user, $school);

        $academicYear = $school->academicYears()->create([
            'name' => 'Academic Year 2026',
            'code' => 'AY-2026-'.str()->upper(str()->random(4)),
            'starts_on' => '2026-01-01',
            'ends_on' => '2026-12-31',
        ]);
        $academicClass = $school->academicClasses()->create([
            'name' => 'Class PDF',
            'code' => 'PDF-'.str()->upper(str()->random(4)),
        ]);
        $academicSection = $school->academicSections()->create([
            'academic_class_id' => $academicClass->id,
            'name' => 'Section A',
            'code' => 'A-'.str()->upper(str()->random(4)),
        ]);
        $subject = $school->subjects()->create([
            'name' => 'Mathematics',
            'code' => 'MATH-'.str()->upper(str()->random(4)),
            'type' => 'core',
        ]);
        $classSubject = $school->classSubjects()->create([
            'academic_class_id' => $academicClass->id,
            'subject_id' => $subject->id,
            'full_marks' => 100,
            'pass_marks' => 33,
        ]);
        $examType = $school->examTypes()->create([
            'name' => 'Midterm',
            'code' => 'MID-'.str()->upper(str()->random(4)),
        ]);
        $exam = $school->exams()->create([
            'exam_type_id' => $examType->id,
            'academic_year_id' => $academicYear->id,
            'name' => 'Midterm 2026',
            'code' => 'MID-PDF-'.str()->upper(str()->random(4)),
            'starts_on' => '2026-04-01',
            'ends_on' => '2026-04-10',
            'is_published' => true,
            'status' => 'completed',
        ]);

        $student = $school->students()->create([
            'admission_no' => 'ADM-PDF-'.str()->upper(str()->random(4)),
            'full_name' => 'PDF Student',
            'admitted_on' => '2026-01-05',
        ]);
        $enrollment = $school->studentEnrollments()->create([
            'student_id' => $student->id,
            'academic_year_id' => $academicYear->id,
            'academic_class_id' => $academicClass->id,
            'academic_section_id' => $academicSection->id,
            'roll_no' => '7',
            'enrolled_on' => '2026-01-10',
        ]);

        $school->marksEntries()->create([
            'exam_id' => $exam->id,
            'class_subject_id' => $classSubject->id,
            'student_enrollment_id' => $enrollment->id,
            'marks_obtained' => 82,
            'full_marks' => 100,
            'pass_marks' => 33,
            'verification_status' => 'verified',
            'entered_by' => $user->id,
            'verified_by' => $user->id,
            'verified_at' => now(),
        ]);
        $school->resultSummaries()->create([
            'exam_id' => $exam->id,
            'student_enrollment_id' => $enrollment->id,
            'total_marks_obtained' => 82,
            'total_full_marks' => 100,
            'percentage' => 82,
            'gpa' => 5,
            'grade' => 'A+',
            'position_in_class' => 1,
            'is_pass' => true,
            'computed_at' => now(),
        ]);

        return [$school, $user, $exam, $enrollment];
    }

    private function addActiveMember(User $user, School $school): void
    {
        $school->memberships()->create([
            'user_id' => $user->id,
            'status' => 'active',
            'joined_at' => now(),
        ]);
    }

    private function grantSchoolPermission(User $user, School $school, string $permissionKey, string $module): void
    {
        $permission = Permission::query()->create([
            'module' => $module,
            'key' => $permissionKey,
            'description' => str($permissionKey)->replace('.', ' ')->headline()->toString(),
        ]);

        $role = Role::query()->create([
            'school_id' => $school->id,
            'name' => str($permissionKey)->replace('.', ' ')->headline()->append(' Manager')->toString(),
            'key' => str($permissionKey)->slug()->append('-manager')->toString(),
        ]);

        $role->permissions()->attach($permission);

        $user->roleAssignments()->create([
            'school_id' => $school->id,
            'role_id' => $role->id,
        ]);
    }
}
