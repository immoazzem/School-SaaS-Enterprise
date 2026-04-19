<?php

namespace Tests\Feature;

use App\Models\AcademicClass;
use App\Models\AcademicYear;
use App\Models\Assignment;
use App\Models\AssignmentSubmission;
use App\Models\Role;
use App\Models\School;
use App\Models\Student;
use App\Models\StudentEnrollment;
use App\Models\Subject;
use App\Models\User;
use Database\Seeders\EnterpriseRolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PhaseSevenAssignmentsApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(EnterpriseRolePermissionSeeder::class);
    }

    public function test_assignment_manager_can_create_list_update_and_archive_assignment(): void
    {
        [$school, $teacher] = $this->createSchoolWithMember('teacher');
        $fixture = $this->createAssignmentFixture($school);

        Sanctum::actingAs($teacher);

        $created = $this->postJson("/api/v1/schools/{$school->id}/assignments", [
            'academic_class_id' => $fixture['class']->id,
            'subject_id' => $fixture['subject']->id,
            'title' => 'Algebra practice',
            'description' => 'Complete the first two exercise sets.',
            'due_date' => '2026-05-10',
            'is_published' => true,
        ]);

        $created
            ->assertCreated()
            ->assertJsonPath('data.title', 'Algebra practice')
            ->assertJsonPath('data.assigned_by', $teacher->id);

        $assignmentId = $created->json('data.id');

        $this->assertDatabaseHas('audit_logs', [
            'school_id' => $school->id,
            'event' => 'assignment.created',
            'auditable_type' => Assignment::class,
            'auditable_id' => $assignmentId,
        ]);

        $this->getJson("/api/v1/schools/{$school->id}/assignments?academic_class_id={$fixture['class']->id}")
            ->assertOk()
            ->assertJsonPath('data.0.id', $assignmentId)
            ->assertJsonPath('data.0.submissions_count', 0);

        $this->patchJson("/api/v1/schools/{$school->id}/assignments/{$assignmentId}", [
            'title' => 'Updated algebra practice',
        ])
            ->assertOk()
            ->assertJsonPath('data.title', 'Updated algebra practice');

        $this->deleteJson("/api/v1/schools/{$school->id}/assignments/{$assignmentId}")
            ->assertNoContent();

        $this->assertSoftDeleted('assignments', ['id' => $assignmentId]);
    }

    public function test_assignment_submission_can_be_created_and_graded(): void
    {
        [$school, $teacher] = $this->createSchoolWithMember('teacher');
        $fixture = $this->createAssignmentFixture($school);
        $assignment = $this->createAssignment($school, $fixture['class'], $fixture['subject'], $teacher);

        Sanctum::actingAs($teacher);

        $created = $this->postJson("/api/v1/schools/{$school->id}/assignment-submissions", [
            'assignment_id' => $assignment->id,
            'student_enrollment_id' => $fixture['enrollment']->id,
            'submitted_at' => '2026-05-09 10:30:00',
            'attachment_path' => 'assignments/submissions/algebra.pdf',
        ]);

        $created
            ->assertCreated()
            ->assertJsonPath('data.assignment_id', $assignment->id)
            ->assertJsonPath('data.status', 'submitted');

        $submissionId = $created->json('data.id');

        $this->patchJson("/api/v1/schools/{$school->id}/assignment-submissions/{$submissionId}", [
            'marks_awarded' => 18.5,
            'feedback' => 'Good work.',
            'status' => 'graded',
        ])
            ->assertOk()
            ->assertJsonPath('data.marks_awarded', '18.50')
            ->assertJsonPath('data.status', 'graded');

        $this->getJson("/api/v1/schools/{$school->id}/assignment-submissions?assignment_id={$assignment->id}")
            ->assertOk()
            ->assertJsonPath('data.0.id', $submissionId);

        $this->assertDatabaseHas('audit_logs', [
            'school_id' => $school->id,
            'event' => 'assignment_submission.updated',
            'auditable_type' => AssignmentSubmission::class,
            'auditable_id' => $submissionId,
        ]);
    }

    public function test_assignment_rejects_cross_school_class_and_subject(): void
    {
        [$school, $teacher] = $this->createSchoolWithMember('teacher');
        [$otherSchool] = $this->createSchoolWithMember('teacher');
        $otherFixture = $this->createAssignmentFixture($otherSchool);

        Sanctum::actingAs($teacher);

        $this->postJson("/api/v1/schools/{$school->id}/assignments", [
            'academic_class_id' => $otherFixture['class']->id,
            'subject_id' => $otherFixture['subject']->id,
            'title' => 'Foreign assignment',
            'due_date' => '2026-05-10',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['academic_class_id', 'subject_id']);
    }

    public function test_submission_rejects_enrollment_outside_assignment_class(): void
    {
        [$school, $teacher] = $this->createSchoolWithMember('teacher');
        $fixture = $this->createAssignmentFixture($school);
        $otherClass = $this->createAcademicClass($school, 'Class Two');
        $otherEnrollment = $this->createEnrollment($school, $fixture['year'], $otherClass, 'ADM-200');
        $assignment = $this->createAssignment($school, $fixture['class'], $fixture['subject'], $teacher);

        Sanctum::actingAs($teacher);

        $this->postJson("/api/v1/schools/{$school->id}/assignment-submissions", [
            'assignment_id' => $assignment->id,
            'student_enrollment_id' => $otherEnrollment->id,
            'submitted_at' => '2026-05-09 10:30:00',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['student_enrollment_id']);
    }

    public function test_duplicate_assignment_submission_is_rejected(): void
    {
        [$school, $teacher] = $this->createSchoolWithMember('teacher');
        $fixture = $this->createAssignmentFixture($school);
        $assignment = $this->createAssignment($school, $fixture['class'], $fixture['subject'], $teacher);

        AssignmentSubmission::query()->create([
            'school_id' => $school->id,
            'assignment_id' => $assignment->id,
            'student_enrollment_id' => $fixture['enrollment']->id,
            'submitted_at' => now(),
            'status' => 'submitted',
        ]);

        Sanctum::actingAs($teacher);

        $this->postJson("/api/v1/schools/{$school->id}/assignment-submissions", [
            'assignment_id' => $assignment->id,
            'student_enrollment_id' => $fixture['enrollment']->id,
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['student_enrollment_id']);
    }

    public function test_member_without_assignments_manage_cannot_create_assignment(): void
    {
        [$school] = $this->createSchoolWithMember('school-owner');
        $accountant = $this->createMemberWithRole($school, 'accountant');
        $fixture = $this->createAssignmentFixture($school);

        Sanctum::actingAs($accountant);

        $this->postJson("/api/v1/schools/{$school->id}/assignments", [
            'academic_class_id' => $fixture['class']->id,
            'subject_id' => $fixture['subject']->id,
            'title' => 'Unauthorized homework',
            'due_date' => '2026-05-10',
        ])->assertForbidden();
    }

    /**
     * @return array{0: School, 1: User}
     */
    private function createSchoolWithMember(string $roleKey, string $name = 'Assignment School'): array
    {
        $school = School::query()->create([
            'name' => $name.' '.Str::random(6),
            'slug' => $name.' '.Str::random(6),
            'timezone' => 'Asia/Dhaka',
            'locale' => 'en',
        ]);

        return [$school, $this->createMemberWithRole($school, $roleKey)];
    }

    private function createMemberWithRole(School $school, string $roleKey): User
    {
        $user = User::factory()->create();

        $school->memberships()->create([
            'user_id' => $user->id,
            'status' => 'active',
            'joined_at' => now(),
        ]);

        $role = Role::query()
            ->whereNull('school_id')
            ->where('key', $roleKey)
            ->firstOrFail();

        $user->roleAssignments()->create([
            'school_id' => $school->id,
            'role_id' => $role->id,
            'assigned_by' => $user->id,
        ]);

        return $user;
    }

    /**
     * @return array{year: AcademicYear, class: AcademicClass, subject: Subject, enrollment: StudentEnrollment}
     */
    private function createAssignmentFixture(School $school): array
    {
        $year = AcademicYear::query()->create([
            'school_id' => $school->id,
            'name' => 'Academic Year '.Str::random(5),
            'code' => 'AY-'.Str::upper(Str::random(5)),
            'starts_on' => '2026-01-01',
            'ends_on' => '2026-12-31',
            'is_current' => true,
            'status' => 'active',
        ]);
        $class = $this->createAcademicClass($school, 'Class One');
        $subject = Subject::query()->create([
            'school_id' => $school->id,
            'name' => 'Mathematics '.Str::random(5),
            'code' => 'MATH-'.Str::upper(Str::random(5)),
            'type' => 'core',
            'credit_hours' => 4,
            'sort_order' => 1,
            'status' => 'active',
        ]);

        return [
            'year' => $year,
            'class' => $class,
            'subject' => $subject,
            'enrollment' => $this->createEnrollment($school, $year, $class, 'ADM-100'),
        ];
    }

    private function createAcademicClass(School $school, string $name): AcademicClass
    {
        return AcademicClass::query()->create([
            'school_id' => $school->id,
            'name' => $name.' '.Str::random(4),
            'code' => 'CLS-'.Str::upper(Str::random(5)),
            'status' => 'active',
            'sort_order' => 1,
        ]);
    }

    private function createEnrollment(School $school, AcademicYear $year, AcademicClass $class, string $admissionNo): StudentEnrollment
    {
        $student = Student::query()->create([
            'school_id' => $school->id,
            'admission_no' => $admissionNo.'-'.Str::upper(Str::random(4)),
            'full_name' => 'Student '.Str::random(5),
            'admitted_on' => '2026-01-01',
            'status' => 'active',
        ]);

        return StudentEnrollment::query()->create([
            'school_id' => $school->id,
            'student_id' => $student->id,
            'academic_year_id' => $year->id,
            'academic_class_id' => $class->id,
            'roll_no' => (string) random_int(1, 99),
            'enrolled_on' => '2026-01-01',
            'status' => 'active',
        ]);
    }

    private function createAssignment(School $school, AcademicClass $class, Subject $subject, User $teacher): Assignment
    {
        return Assignment::query()->create([
            'school_id' => $school->id,
            'academic_class_id' => $class->id,
            'subject_id' => $subject->id,
            'assigned_by' => $teacher->id,
            'title' => 'Practice Sheet',
            'description' => 'Complete the worksheet.',
            'due_date' => '2026-05-10',
            'is_published' => true,
            'status' => 'active',
        ]);
    }
}
