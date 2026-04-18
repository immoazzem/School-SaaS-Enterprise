<?php

namespace Tests\Feature;

use App\Models\Permission;
use App\Models\Role;
use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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

        $this->postJson("/api/schools/{$school->id}/exams/{$exam->id}/publish")
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

        $this->getJson("/api/schools/{$school->id}/exams/{$exam->id}/result-summaries")
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

        $this->getJson("/api/schools/{$school->id}/exams/{$exam->id}/result-summaries")
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

        $this->getJson("/api/schools/{$school->id}/attendance/employee-summary?month=2026-04")
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

        $this->getJson("/api/schools/{$school->id}/notifications")
            ->assertOk()
            ->assertJsonPath('data.0.id', $notification->id)
            ->assertJsonPath('data.0.type', 'payment.received');

        $this->getJson("/api/schools/{$school->id}/notifications/unread-count")
            ->assertOk()
            ->assertJsonPath('data.unread_count', 1);

        $this->postJson("/api/schools/{$school->id}/notifications/mark-read", ['ids' => [$notification->id]])
            ->assertOk()
            ->assertJsonPath('data.updated', 1);

        $this->getJson("/api/schools/{$school->id}/notifications/unread-count")
            ->assertOk()
            ->assertJsonPath('data.unread_count', 0);
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
