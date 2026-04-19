<?php

namespace Tests\Feature;

use App\Models\AcademicClass;
use App\Models\AcademicSection;
use App\Models\AcademicYear;
use App\Models\Exam;
use App\Models\ExamType;
use App\Models\Role;
use App\Models\School;
use App\Models\User;
use Database\Seeders\EnterpriseRolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PermissionIsolationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(EnterpriseRolePermissionSeeder::class);
    }

    public function test_user_from_school_a_cannot_read_school_b_academic_sections(): void
    {
        [$schoolA, $ownerA] = $this->createSchoolWithMember('school-owner', 'Tenant A');
        [$schoolB] = $this->createSchoolWithMember('school-owner', 'Tenant B');
        $class = $this->createAcademicClass($schoolB);

        AcademicSection::query()->create([
            'school_id' => $schoolB->id,
            'academic_class_id' => $class->id,
            'name' => 'Section Alpha',
            'code' => 'A',
            'status' => 'active',
            'sort_order' => 1,
        ]);

        Sanctum::actingAs($ownerA);

        $response = $this->getJson("/api/v1/schools/{$schoolB->id}/academic-sections");

        $response->assertForbidden();
        $this->assertTrue($ownerA->hasSchoolPermission($schoolA, 'sections.manage'));
    }

    public function test_user_from_school_a_cannot_create_section_in_school_b(): void
    {
        [$schoolA, $ownerA] = $this->createSchoolWithMember('school-owner', 'Tenant A');
        [$schoolB] = $this->createSchoolWithMember('school-owner', 'Tenant B');
        $class = $this->createAcademicClass($schoolB);

        Sanctum::actingAs($ownerA);

        $response = $this->postJson("/api/v1/schools/{$schoolB->id}/academic-sections", [
            'academic_class_id' => $class->id,
            'name' => 'Injected Section',
            'code' => 'INJ',
        ]);

        $response->assertForbidden();
        $this->assertFalse($ownerA->hasSchoolPermission($schoolB, 'sections.manage'));
        $this->assertTrue($ownerA->hasSchoolPermission($schoolA, 'sections.manage'));
    }

    public function test_user_from_school_a_cannot_view_school_b_students(): void
    {
        [, $ownerA] = $this->createSchoolWithMember('school-owner', 'Tenant A');
        [$schoolB] = $this->createSchoolWithMember('school-owner', 'Tenant B');

        Sanctum::actingAs($ownerA);

        $response = $this->getJson("/api/v1/schools/{$schoolB->id}/students");

        $response->assertForbidden();
    }

    public function test_user_from_school_a_cannot_view_school_b_invoices(): void
    {
        [, $ownerA] = $this->createSchoolWithMember('school-owner', 'Tenant A');
        [$schoolB] = $this->createSchoolWithMember('school-owner', 'Tenant B');

        Sanctum::actingAs($ownerA);

        $response = $this->getJson("/api/v1/schools/{$schoolB->id}/student-invoices");

        $response->assertForbidden();
    }

    public function test_user_from_school_a_cannot_view_school_b_audit_logs(): void
    {
        [, $ownerA] = $this->createSchoolWithMember('school-owner', 'Tenant A');
        [$schoolB] = $this->createSchoolWithMember('school-owner', 'Tenant B');

        Sanctum::actingAs($ownerA);

        $response = $this->getJson("/api/v1/schools/{$schoolB->id}/audit-logs");

        $response->assertForbidden();
    }

    public function test_user_from_school_a_cannot_view_school_b_employees(): void
    {
        [, $ownerA] = $this->createSchoolWithMember('school-owner', 'Tenant A');
        [$schoolB] = $this->createSchoolWithMember('school-owner', 'Tenant B');

        Sanctum::actingAs($ownerA);

        $response = $this->getJson("/api/v1/schools/{$schoolB->id}/employees");

        $response->assertForbidden();
    }

    public function test_member_without_sections_manage_cannot_create_section(): void
    {
        [$school] = $this->createSchoolWithMember('school-owner');
        $teacher = $this->createMemberWithRole($school, 'teacher');
        $class = $this->createAcademicClass($school);

        Sanctum::actingAs($teacher);

        $response = $this->postJson("/api/v1/schools/{$school->id}/academic-sections", [
            'academic_class_id' => $class->id,
            'name' => 'Unauthorized Section',
            'code' => 'UNAUTH',
        ]);

        $response->assertForbidden();
        $this->assertFalse($teacher->hasSchoolPermission($school, 'sections.manage'));
    }

    public function test_member_without_finance_manage_cannot_create_invoice(): void
    {
        [$school] = $this->createSchoolWithMember('school-owner');
        $teacher = $this->createMemberWithRole($school, 'teacher');

        Sanctum::actingAs($teacher);

        $response = $this->postJson("/api/v1/schools/{$school->id}/student-invoices", [
            'student_enrollment_id' => 1,
            'academic_year_id' => 1,
            'fee_structure_ids' => [1],
        ]);

        $response->assertForbidden();
        $this->assertFalse($teacher->hasSchoolPermission($school, 'finance.manage'));
    }

    public function test_member_without_exams_publish_cannot_publish_exam_results(): void
    {
        [$school] = $this->createSchoolWithMember('school-owner');
        $accountant = $this->createMemberWithRole($school, 'accountant');
        $exam = $this->createExam($school);

        Sanctum::actingAs($accountant);

        $response = $this->postJson("/api/v1/schools/{$school->id}/exams/{$exam->id}/publish");

        $response->assertForbidden();
        $this->assertFalse($accountant->hasSchoolPermission($school, 'exams.publish'));
    }

    public function test_inactive_member_cannot_access_school_resources(): void
    {
        [$school] = $this->createSchoolWithMember('school-owner');
        $inactiveOwner = $this->createMemberWithRole($school, 'school-owner', 'inactive');

        Sanctum::actingAs($inactiveOwner);

        $response = $this->getJson("/api/v1/schools/{$school->id}/academic-sections");

        $response->assertForbidden();
    }

    public function test_unauthenticated_user_cannot_access_school_resources(): void
    {
        [$school] = $this->createSchoolWithMember('school-owner');

        $response = $this->getJson("/api/v1/schools/{$school->id}/students");

        $response->assertUnauthorized();
    }

    public function test_permission_matrix_for_core_school_roles(): void
    {
        [$school, $owner] = $this->createSchoolWithMember('school-owner');
        $teacher = $this->createMemberWithRole($school, 'teacher');
        $accountant = $this->createMemberWithRole($school, 'accountant');

        $this->assertTrue($owner->hasSchoolPermission($school, 'sections.manage'));
        $this->assertTrue($owner->hasSchoolPermission($school, 'finance.manage'));
        $this->assertTrue($owner->hasSchoolPermission($school, 'audit.view'));

        $this->assertFalse($teacher->hasSchoolPermission($school, 'sections.manage'));
        $this->assertTrue($teacher->hasSchoolPermission($school, 'students.manage'));
        $this->assertFalse($teacher->hasSchoolPermission($school, 'finance.manage'));

        $this->assertTrue($accountant->hasSchoolPermission($school, 'finance.manage'));
        $this->assertFalse($accountant->hasSchoolPermission($school, 'sections.manage'));
        $this->assertTrue($accountant->hasSchoolPermission($school, 'payroll.manage'));
    }

    /**
     * @return array{0: School, 1: User}
     */
    private function createSchoolWithMember(string $roleKey, string $name = 'Tenant School'): array
    {
        $school = School::query()->create([
            'name' => $name.' '.Str::random(6),
            'slug' => $name.' '.Str::random(6),
            'timezone' => 'Asia/Dhaka',
            'locale' => 'en',
        ]);

        return [$school, $this->createMemberWithRole($school, $roleKey)];
    }

    private function createMemberWithRole(School $school, string $roleKey, string $status = 'active'): User
    {
        $user = User::factory()->create();

        $school->memberships()->create([
            'user_id' => $user->id,
            'status' => $status,
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

    private function createAcademicClass(School $school): AcademicClass
    {
        return AcademicClass::query()->create([
            'school_id' => $school->id,
            'name' => 'Class '.Str::random(5),
            'code' => 'CLS-'.Str::upper(Str::random(5)),
            'status' => 'active',
            'sort_order' => 1,
        ]);
    }

    private function createExam(School $school): Exam
    {
        $academicYear = AcademicYear::query()->create([
            'school_id' => $school->id,
            'name' => 'Academic Year '.Str::random(5),
            'code' => 'AY-'.Str::upper(Str::random(5)),
            'starts_on' => '2026-01-01',
            'ends_on' => '2026-12-31',
            'is_current' => true,
            'status' => 'active',
        ]);

        $examType = ExamType::query()->create([
            'school_id' => $school->id,
            'name' => 'Term '.Str::random(5),
            'code' => 'TERM-'.Str::upper(Str::random(5)),
            'weightage_percent' => 50,
            'sort_order' => 1,
            'status' => 'active',
        ]);

        return Exam::query()->create([
            'school_id' => $school->id,
            'academic_year_id' => $academicYear->id,
            'exam_type_id' => $examType->id,
            'name' => 'Midterm '.Str::random(5),
            'code' => 'EXAM-'.Str::upper(Str::random(5)),
            'starts_on' => '2026-04-01',
            'ends_on' => '2026-04-10',
            'is_published' => false,
            'status' => 'scheduled',
        ]);
    }
}
