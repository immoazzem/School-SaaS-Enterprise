<?php

namespace Tests\Feature;

use App\Jobs\GenerateReportJob;
use App\Models\Permission;
use App\Models\Role;
use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PhaseFourReportingApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_phase_four_result_publication_creates_summaries_and_notifications(): void
    {
        $user = User::factory()->create();
        $school = School::query()->create(['name' => 'Result School', 'slug' => 'result-school']);
        $this->addActiveMember($user, $school);
        $this->grantSchoolPermission($user, $school, 'exams.publish', 'exams');

        $academicYear = $school->academicYears()->create(['name' => 'Academic Year 2026', 'code' => 'AY-2026', 'starts_on' => '2026-01-01', 'ends_on' => '2026-12-31']);
        $academicClass = $school->academicClasses()->create(['name' => 'Class Six', 'code' => 'C6']);
        $subject = $school->subjects()->create(['name' => 'Mathematics', 'code' => 'MATH', 'type' => 'core']);
        $classSubject = $school->classSubjects()->create(['academic_class_id' => $academicClass->id, 'subject_id' => $subject->id, 'full_marks' => 100, 'pass_marks' => 33]);
        $examType = $school->examTypes()->create(['name' => 'Midterm', 'code' => 'MID', 'weightage_percent' => 50]);
        $exam = $school->exams()->create([
            'exam_type_id' => $examType->id,
            'academic_year_id' => $academicYear->id,
            'name' => 'Midterm 2026',
            'code' => 'MID-2026',
            'starts_on' => '2026-04-01',
            'ends_on' => '2026-04-10',
        ]);

        $school->gradeScales()->create(['name' => 'A Plus', 'code' => 'A+', 'min_percent' => 80, 'max_percent' => 100, 'grade_point' => 5, 'fail_below_percent' => 33, 'gpa_calculation_method' => 'weighted']);
        $school->gradeScales()->create(['name' => 'Pass', 'code' => 'P', 'min_percent' => 33, 'max_percent' => 79.99, 'grade_point' => 3, 'fail_below_percent' => 33, 'gpa_calculation_method' => 'weighted']);

        $firstStudent = $school->students()->create(['admission_no' => 'ADM-RESULT-01', 'full_name' => 'First Student', 'admitted_on' => '2026-01-05']);
        $secondStudent = $school->students()->create(['admission_no' => 'ADM-RESULT-02', 'full_name' => 'Second Student', 'admitted_on' => '2026-01-05']);
        $firstEnrollment = $school->studentEnrollments()->create(['student_id' => $firstStudent->id, 'academic_year_id' => $academicYear->id, 'academic_class_id' => $academicClass->id, 'roll_no' => '1', 'enrolled_on' => '2026-01-10']);
        $secondEnrollment = $school->studentEnrollments()->create(['student_id' => $secondStudent->id, 'academic_year_id' => $academicYear->id, 'academic_class_id' => $academicClass->id, 'roll_no' => '2', 'enrolled_on' => '2026-01-10']);

        $school->marksEntries()->create(['exam_id' => $exam->id, 'class_subject_id' => $classSubject->id, 'student_enrollment_id' => $firstEnrollment->id, 'marks_obtained' => 80, 'full_marks' => 100, 'pass_marks' => 33, 'verification_status' => 'verified', 'entered_by' => $user->id, 'verified_by' => $user->id, 'verified_at' => now()]);
        $school->marksEntries()->create(['exam_id' => $exam->id, 'class_subject_id' => $classSubject->id, 'student_enrollment_id' => $secondEnrollment->id, 'marks_obtained' => 70, 'full_marks' => 100, 'pass_marks' => 33, 'verification_status' => 'verified', 'entered_by' => $user->id, 'verified_by' => $user->id, 'verified_at' => now()]);

        Sanctum::actingAs($user);

        $this->postJson("/api/v1/schools/{$school->id}/exams/{$exam->id}/publish")
            ->assertOk()
            ->assertJsonPath('data.is_published', true)
            ->assertJsonPath('data.status', 'completed')
            ->assertJsonPath('data.published_by', $user->id);

        $this->assertDatabaseHas('result_summaries', [
            'school_id' => $school->id,
            'exam_id' => $exam->id,
            'student_enrollment_id' => $firstEnrollment->id,
            'total_marks_obtained' => 40,
            'total_full_marks' => 50,
            'percentage' => 80,
            'grade' => 'A+',
            'position_in_class' => 1,
            'is_pass' => true,
        ]);
        $this->assertDatabaseHas('result_summaries', [
            'school_id' => $school->id,
            'exam_id' => $exam->id,
            'student_enrollment_id' => $secondEnrollment->id,
            'position_in_class' => 2,
        ]);
        $this->assertDatabaseHas('notifications', [
            'school_id' => $school->id,
            'recipient_user_id' => $user->id,
            'type' => 'result.published',
            'title' => 'Result published',
        ]);

        $this->getJson("/api/v1/schools/{$school->id}/exams/{$exam->id}/result-summaries")
            ->assertOk()
            ->assertJsonPath('data.0.student_enrollment.student.full_name', 'First Student')
            ->assertJsonPath('data.0.position_in_class', 1);
    }

    public function test_phase_four_unpublished_results_are_forbidden_without_exam_management(): void
    {
        $user = User::factory()->create();
        $school = School::query()->create(['name' => 'Private Results School', 'slug' => 'private-results-school']);
        $this->addActiveMember($user, $school);
        $academicYear = $school->academicYears()->create(['name' => 'Academic Year 2026', 'code' => 'AY-2026', 'starts_on' => '2026-01-01', 'ends_on' => '2026-12-31']);
        $examType = $school->examTypes()->create(['name' => 'Final', 'code' => 'FIN']);
        $exam = $school->exams()->create(['exam_type_id' => $examType->id, 'academic_year_id' => $academicYear->id, 'name' => 'Final 2026', 'code' => 'FIN-2026', 'starts_on' => '2026-11-01', 'ends_on' => '2026-11-15']);

        Sanctum::actingAs($user);

        $this->getJson("/api/v1/schools/{$school->id}/exams/{$exam->id}/result-summaries")
            ->assertForbidden();
    }

    public function test_phase_four_employee_attendance_summary_counts_monthly_statuses(): void
    {
        $user = User::factory()->create();
        $school = School::query()->create(['name' => 'Staff Summary School', 'slug' => 'staff-summary-school']);
        $this->addActiveMember($user, $school);
        $this->grantSchoolPermission($user, $school, 'employee_attendance.manage', 'attendance');
        $employee = $school->employees()->create(['employee_no' => 'EMP-SUM-01', 'full_name' => 'Amina Rahman', 'joined_on' => '2026-01-10']);

        $school->employeeAttendanceRecords()->create(['employee_id' => $employee->id, 'date' => '2026-04-01', 'status' => 'present', 'recorded_by' => $user->id]);
        $school->employeeAttendanceRecords()->create(['employee_id' => $employee->id, 'date' => '2026-04-02', 'status' => 'late', 'recorded_by' => $user->id]);
        $school->employeeAttendanceRecords()->create(['employee_id' => $employee->id, 'date' => '2026-04-03', 'status' => 'on_leave', 'recorded_by' => $user->id]);
        $school->employeeAttendanceRecords()->create(['employee_id' => $employee->id, 'date' => '2026-05-01', 'status' => 'absent', 'recorded_by' => $user->id]);

        Sanctum::actingAs($user);

        $this->getJson("/api/v1/schools/{$school->id}/attendance/employee-summary?month=2026-04")
            ->assertOk()
            ->assertJsonPath('data.0.employee.full_name', 'Amina Rahman')
            ->assertJsonPath('data.0.counts.present', 1)
            ->assertJsonPath('data.0.counts.late', 1)
            ->assertJsonPath('data.0.counts.on_leave', 1)
            ->assertJsonPath('data.0.counts.absent', 0);
    }

    public function test_phase_four_notifications_can_be_listed_counted_and_marked_read(): void
    {
        $user = User::factory()->create();
        $school = School::query()->create(['name' => 'Notification School', 'slug' => 'notification-school']);
        $this->addActiveMember($user, $school);
        $notification = $school->notifications()->create([
            'recipient_user_id' => $user->id,
            'type' => 'payment.received',
            'title' => 'Payment received',
            'body' => 'A payment has been posted.',
            'data' => ['amount' => 500],
        ]);

        Sanctum::actingAs($user);

        $this->getJson("/api/v1/schools/{$school->id}/notifications")
            ->assertOk()
            ->assertJsonPath('data.0.id', $notification->id)
            ->assertJsonPath('data.0.type', 'payment.received');

        $this->getJson("/api/v1/schools/{$school->id}/notifications/unread-count")
            ->assertOk()
            ->assertJsonPath('data.unread_count', 1);

        $this->postJson("/api/v1/schools/{$school->id}/notifications/mark-read", ['ids' => [$notification->id]])
            ->assertOk()
            ->assertJsonPath('data.updated', 1);

        $this->getJson("/api/v1/schools/{$school->id}/notifications/unread-count")
            ->assertOk()
            ->assertJsonPath('data.unread_count', 0);
    }

    public function test_phase_four_calendar_events_and_holiday_import_follow_v3_rules(): void
    {
        $manager = User::factory()->create();
        $viewer = User::factory()->create();
        $school = School::query()->create(['name' => 'Calendar School', 'slug' => 'calendar-school']);
        $this->addActiveMember($manager, $school);
        $this->addActiveMember($viewer, $school);
        $this->grantSchoolPermission($manager, $school, 'calendar.manage', 'calendar');
        $this->grantSchoolPermission($viewer, $school, 'reports.view', 'reports');
        $academicYear = $school->academicYears()->create(['name' => 'Academic Year 2026', 'code' => 'AY-2026', 'starts_on' => '2026-01-01', 'ends_on' => '2026-12-31']);
        $academicClass = $school->academicClasses()->create(['name' => 'Class Five', 'code' => 'C5']);

        Sanctum::actingAs($manager);

        $eventId = $this->postJson("/api/v1/schools/{$school->id}/calendar-events", [
            'academic_year_id' => $academicYear->id,
            'academic_class_id' => $academicClass->id,
            'title' => 'Class Five parent meeting',
            'description' => 'Meet the class teacher.',
            'starts_on' => '2026-04-25',
            'starts_at' => '10:00',
            'ends_at' => '11:00',
            'location' => 'Room 105',
            'recurring_rule' => 'FREQ=WEEKLY;COUNT=2',
        ])->assertCreated()
            ->assertJsonPath('data.academic_class.id', $academicClass->id)
            ->json('data.id');

        $this->postJson("/api/v1/schools/{$school->id}/calendar-events/bulk-import-holidays", [
            'academic_year_id' => $academicYear->id,
            'holidays' => [
                ['title' => 'Victory Day', 'date' => '2026-12-16'],
                ['title' => 'Language Martyrs Day', 'date' => '2026-02-21'],
            ],
        ])->assertCreated()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.is_holiday', true);

        $this->assertDatabaseHas('calendar_events', [
            'id' => $eventId,
            'school_id' => $school->id,
            'academic_class_id' => $academicClass->id,
            'recurring_rule' => 'FREQ=WEEKLY;COUNT=2',
        ]);
        $this->assertDatabaseHas('calendar_events', [
            'school_id' => $school->id,
            'title' => 'Victory Day',
            'starts_on' => '2026-12-16',
            'is_holiday' => true,
        ]);

        Sanctum::actingAs($viewer);

        $this->getJson("/api/v1/schools/{$school->id}/calendar-events?academic_class_id={$academicClass->id}&from=2026-04-01&to=2026-12-31")
            ->assertOk()
            ->assertJsonPath('data.0.title', 'Class Five parent meeting');
    }

    public function test_phase_four_payment_received_creates_in_app_notification_for_matched_guardian_user(): void
    {
        $accountant = User::factory()->create();
        $guardianUser = User::factory()->create(['email' => 'guardian-pay@example.test']);
        $school = School::query()->create(['name' => 'Payment Notify School', 'slug' => 'payment-notify-school']);
        $this->addActiveMember($accountant, $school);
        $this->addActiveMember($guardianUser, $school);
        $this->grantSchoolPermission($accountant, $school, 'finance.manage', 'finance');
        $academicYear = $school->academicYears()->create(['name' => 'Academic Year 2026', 'code' => 'AY-2026', 'starts_on' => '2026-01-01', 'ends_on' => '2026-12-31']);
        $academicClass = $school->academicClasses()->create(['name' => 'Class Two', 'code' => 'C2']);
        $guardian = $school->guardians()->create(['full_name' => 'Guardian Pay', 'email' => $guardianUser->email]);
        $student = $school->students()->create(['guardian_id' => $guardian->id, 'admission_no' => 'ADM-PAY-01', 'full_name' => 'Payment Student', 'admitted_on' => '2026-01-05']);
        $enrollment = $school->studentEnrollments()->create(['student_id' => $student->id, 'academic_year_id' => $academicYear->id, 'academic_class_id' => $academicClass->id, 'enrolled_on' => '2026-01-10']);
        $invoice = $school->studentInvoices()->create([
            'student_enrollment_id' => $enrollment->id,
            'academic_year_id' => $academicYear->id,
            'invoice_no' => 'INV-PAY-01',
            'subtotal' => 1000,
            'discount' => 0,
            'total' => 1000,
            'paid_amount' => 0,
            'status' => 'unpaid',
        ]);

        Sanctum::actingAs($accountant);

        $this->postJson("/api/v1/schools/{$school->id}/invoice-payments", [
            'student_invoice_id' => $invoice->id,
            'amount' => 500,
            'paid_on' => '2026-04-18',
            'payment_method' => 'cash',
        ])->assertCreated();

        $this->assertDatabaseHas('notifications', [
            'school_id' => $school->id,
            'recipient_user_id' => $guardianUser->id,
            'type' => 'payment.received',
            'title' => 'Payment received',
        ]);
    }

    public function test_phase_four_leave_approval_creates_in_app_notification_for_matched_employee_user(): void
    {
        $reviewer = User::factory()->create();
        $employeeUser = User::factory()->create(['email' => 'employee-leave@example.test']);
        $school = School::query()->create(['name' => 'Leave Notify School', 'slug' => 'leave-notify-school']);
        $this->addActiveMember($reviewer, $school);
        $this->addActiveMember($employeeUser, $school);
        $this->grantSchoolPermission($reviewer, $school, 'leave.manage', 'people');
        $academicYear = $school->academicYears()->create(['name' => 'Academic Year 2026', 'code' => 'AY-2026', 'starts_on' => '2026-01-01', 'ends_on' => '2026-12-31']);
        $employee = $school->employees()->create(['employee_no' => 'EMP-LEAVE-01', 'full_name' => 'Leave Employee', 'email' => $employeeUser->email, 'joined_on' => '2026-01-10']);
        $leaveType = $school->leaveTypes()->create(['name' => 'Casual Leave', 'code' => 'CL', 'max_days_per_year' => 10]);
        $school->leaveBalances()->create(['employee_id' => $employee->id, 'leave_type_id' => $leaveType->id, 'academic_year_id' => $academicYear->id, 'total_days' => 10, 'used_days' => 0, 'remaining_days' => 10]);
        $application = $school->leaveApplications()->create([
            'employee_id' => $employee->id,
            'leave_type_id' => $leaveType->id,
            'from_date' => '2026-04-20',
            'to_date' => '2026-04-21',
            'total_days' => 2,
            'reason' => 'Family work',
            'status' => 'pending',
            'applied_at' => now(),
        ]);

        Sanctum::actingAs($reviewer);

        $this->patchJson("/api/v1/schools/{$school->id}/leave-applications/{$application->id}/approve")
            ->assertOk()
            ->assertJsonPath('data.status', 'approved');

        $this->assertDatabaseHas('notifications', [
            'school_id' => $school->id,
            'recipient_user_id' => $employeeUser->id,
            'type' => 'leave.approved',
            'title' => 'Leave approved',
        ]);
    }

    public function test_phase_four_document_upload_rejects_oversized_files_by_plan_limit(): void
    {
        Storage::fake('local');
        $user = User::factory()->create();
        $school = School::query()->create([
            'name' => 'Document Limit School',
            'slug' => 'document-limit-school',
            'settings' => ['plan_limits' => ['max_storage_mb' => 1]],
        ]);
        $this->addActiveMember($user, $school);
        $this->grantSchoolPermission($user, $school, 'documents.manage', 'documents');

        Sanctum::actingAs($user);

        $this->postJson("/api/v1/schools/{$school->id}/documents", [
            'category' => 'student_document',
            'title' => 'Admission Form',
            'is_public' => false,
            'file' => UploadedFile::fake()->create('admission.pdf', 2048, 'application/pdf'),
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['file', 'error']);

        $this->assertDatabaseMissing('school_documents', [
            'school_id' => $school->id,
            'title' => 'Admission Form',
        ]);
    }

    public function test_phase_four_document_download_returns_signed_url_without_exposing_storage_path(): void
    {
        Storage::fake('local');
        $manager = User::factory()->create();
        $viewer = User::factory()->create();
        $school = School::query()->create(['name' => 'Document School', 'slug' => 'document-school']);
        $this->addActiveMember($manager, $school);
        $this->addActiveMember($viewer, $school);
        $this->grantSchoolPermission($manager, $school, 'documents.manage', 'documents');

        Sanctum::actingAs($manager);

        $documentId = $this->postJson("/api/v1/schools/{$school->id}/documents", [
            'category' => 'circular',
            'title' => 'Annual Circular',
            'is_public' => true,
            'file' => UploadedFile::fake()->create('annual-circular.pdf', 32, 'application/pdf'),
        ])->assertCreated()
            ->assertJsonMissingPath('data.file_path')
            ->json('data.id');

        $document = $school->documents()->firstOrFail();
        Storage::disk('local')->assertExists($document->file_path);

        Sanctum::actingAs($viewer);

        $response = $this->getJson("/api/v1/schools/{$school->id}/documents/{$documentId}")
            ->assertOk()
            ->assertJsonMissingPath('data.file_path')
            ->assertJsonPath('data.title', 'Annual Circular');

        $downloadUrl = $response->json('data.download_url');
        $this->assertIsString($downloadUrl);
        $this->assertStringContainsString('signature=', $downloadUrl);
        $this->assertStringNotContainsString($document->file_path, $downloadUrl);
        $this->assertStringNotContainsString($document->file_path, $response->getContent());
    }

    public function test_phase_four_report_export_dispatches_pdf_job_and_writes_audit_log(): void
    {
        Bus::fake();
        $user = User::factory()->create();
        $school = School::query()->create(['name' => 'Report School', 'slug' => 'report-school']);
        $this->addActiveMember($user, $school);
        $this->grantSchoolPermission($user, $school, 'reports.view', 'reports');
        $academicYear = $school->academicYears()->create(['name' => 'Academic Year 2026', 'code' => 'AY-2026', 'starts_on' => '2026-01-01', 'ends_on' => '2026-12-31']);
        $academicClass = $school->academicClasses()->create(['name' => 'Class Seven', 'code' => 'C7']);
        $student = $school->students()->create(['admission_no' => 'ADM-RPT-01', 'full_name' => 'Report Student', 'admitted_on' => '2026-01-05']);
        $enrollment = $school->studentEnrollments()->create(['student_id' => $student->id, 'academic_year_id' => $academicYear->id, 'academic_class_id' => $academicClass->id, 'enrolled_on' => '2026-01-10']);
        $examType = $school->examTypes()->create(['name' => 'Final', 'code' => 'FIN']);
        $exam = $school->exams()->create(['exam_type_id' => $examType->id, 'academic_year_id' => $academicYear->id, 'name' => 'Final 2026', 'code' => 'FIN-RPT', 'starts_on' => '2026-11-01', 'ends_on' => '2026-11-10']);

        Sanctum::actingAs($user);

        $jobId = $this->postJson("/api/v1/schools/{$school->id}/reports/marksheet", [
            'exam_id' => $exam->id,
            'student_enrollment_id' => $enrollment->id,
        ])->assertAccepted()
            ->assertJsonPath('data.type', 'marksheet')
            ->assertJsonPath('data.status', 'pending')
            ->json('data.job_id');

        Bus::assertDispatched(GenerateReportJob::class, fn (GenerateReportJob $job): bool => $job->jobId === $jobId);
        $this->assertDatabaseHas('report_exports', [
            'school_id' => $school->id,
            'job_id' => $jobId,
            'type' => 'marksheet',
            'status' => 'pending',
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'school_id' => $school->id,
            'actor_id' => $user->id,
            'event' => 'report.exported',
        ]);
    }

    public function test_phase_four_report_download_returns_signed_url_when_completed(): void
    {
        Storage::fake('local');
        $user = User::factory()->create();
        $school = School::query()->create(['name' => 'Report Download School', 'slug' => 'report-download-school']);
        $this->addActiveMember($user, $school);
        $this->grantSchoolPermission($user, $school, 'reports.view', 'reports');
        Storage::disk('local')->put("reports/{$school->id}/ready.pdf", 'pdf-content');
        $export = $school->reportExports()->create([
            'job_id' => '11111111-1111-4111-8111-111111111111',
            'requested_by' => $user->id,
            'type' => 'result-sheet',
            'status' => 'completed',
            'parameters' => ['exam_id' => 1],
            'file_path' => "reports/{$school->id}/ready.pdf",
            'file_name' => 'ready.pdf',
            'completed_at' => now(),
        ]);

        Sanctum::actingAs($user);

        $response = $this->getJson("/api/v1/schools/{$school->id}/reports/{$export->job_id}/download")
            ->assertOk()
            ->assertJsonPath('data.status', 'completed')
            ->assertJsonPath('data.file_name', 'ready.pdf');

        $downloadUrl = $response->json('data.download_url');
        $this->assertIsString($downloadUrl);
        $this->assertStringContainsString('signature=', $downloadUrl);
        $this->assertStringNotContainsString($export->file_path, $downloadUrl);
    }

    public function test_phase_four_student_attendance_summary_and_dashboard_analytics_return_aggregates(): void
    {
        $user = User::factory()->create();
        $school = School::query()->create(['name' => 'Analytics School', 'slug' => 'analytics-school']);
        $this->addActiveMember($user, $school);
        $this->grantSchoolPermission($user, $school, 'reports.view', 'reports');
        $academicYear = $school->academicYears()->create(['name' => 'Academic Year 2026', 'code' => 'AY-2026', 'starts_on' => '2026-01-01', 'ends_on' => '2026-12-31']);
        $academicClass = $school->academicClasses()->create(['name' => 'Class Three', 'code' => 'C3']);
        $student = $school->students()->create(['admission_no' => 'ADM-ANA-01', 'full_name' => 'Analytics Student', 'admitted_on' => '2026-01-05']);
        $enrollment = $school->studentEnrollments()->create(['student_id' => $student->id, 'academic_year_id' => $academicYear->id, 'academic_class_id' => $academicClass->id, 'enrolled_on' => '2026-01-10']);
        $invoice = $school->studentInvoices()->create(['student_enrollment_id' => $enrollment->id, 'academic_year_id' => $academicYear->id, 'invoice_no' => 'INV-ANA-01', 'subtotal' => 1000, 'discount' => 0, 'total' => 1000, 'paid_amount' => 400, 'status' => 'partial']);
        $school->invoicePayments()->create(['student_invoice_id' => $invoice->id, 'amount' => 400, 'paid_on' => now()->toDateString(), 'payment_method' => 'cash']);
        $school->studentAttendanceRecords()->create(['student_enrollment_id' => $enrollment->id, 'attendance_date' => '2026-04-01', 'status' => 'present']);
        $school->studentAttendanceRecords()->create(['student_enrollment_id' => $enrollment->id, 'attendance_date' => '2026-04-02', 'status' => 'late']);
        $school->studentAttendanceRecords()->create(['student_enrollment_id' => $enrollment->id, 'attendance_date' => '2026-04-03', 'status' => 'absent']);
        $school->leaveApplications()->create(['employee_id' => $school->employees()->create(['employee_no' => 'EMP-ANA-01', 'full_name' => 'Analytics Employee', 'joined_on' => '2026-01-10'])->id, 'leave_type_id' => $school->leaveTypes()->create(['name' => 'Casual Leave', 'code' => 'ANA-CL', 'max_days_per_year' => 10])->id, 'from_date' => '2026-04-10', 'to_date' => '2026-04-10', 'total_days' => 1, 'reason' => 'Work', 'status' => 'pending', 'applied_at' => now()]);

        Sanctum::actingAs($user);

        $this->getJson("/api/v1/schools/{$school->id}/attendance/summary?month=2026-04")
            ->assertOk()
            ->assertJsonPath('data.0.counts.present', 1)
            ->assertJsonPath('data.0.counts.late', 1)
            ->assertJsonPath('data.0.counts.absent', 1);

        $this->getJson("/api/v1/schools/{$school->id}/dashboard/summary")
            ->assertOk()
            ->assertJsonPath('data.admin.student_count', 1)
            ->assertJsonPath('data.admin.pending_leave_applications', 1)
            ->assertJsonPath('data.accountant.unpaid_invoices', 1);
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
