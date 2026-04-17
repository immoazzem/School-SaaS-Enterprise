<?php

namespace Tests\Feature;

use App\Models\AcademicClass;
use App\Models\AcademicSection;
use App\Models\AcademicYear;
use App\Models\ClassSubject;
use App\Models\Designation;
use App\Models\Permission;
use App\Models\Role;
use App\Models\School;
use App\Models\Shift;
use App\Models\StudentGroup;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\Fluent\AssertableJson;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class EnterpriseFoundationApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_login_and_read_profile(): void
    {
        $user = User::factory()->create([
            'email' => 'principal@example.test',
            'password' => 'secret-password',
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'principal@example.test',
            'password' => 'secret-password',
            'device_name' => 'feature-test',
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('token_type', 'Bearer')
            ->assertJsonPath('user.email', $user->email)
            ->assertJsonStructure(['token']);

        $token = $response->json('token');

        $this->withToken($token)
            ->getJson('/api/me')
            ->assertOk()
            ->assertJsonPath('user.email', $user->email);
    }

    public function test_authenticated_user_can_create_a_school_membership(): void
    {
        $this->seed();
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/schools', [
            'name' => 'North Star Academy',
            'timezone' => 'Asia/Dhaka',
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('data.name', 'North Star Academy')
            ->assertJsonPath('data.slug', 'north-star-academy');

        $this->assertDatabaseHas('school_memberships', [
            'user_id' => $user->id,
            'status' => 'active',
        ]);

        $schoolId = $response->json('data.id');
        $ownerRole = Role::query()->where('key', 'school-owner')->firstOrFail();

        $this->assertDatabaseHas('user_role_assignments', [
            'school_id' => $schoolId,
            'user_id' => $user->id,
            'role_id' => $ownerRole->id,
            'assigned_by' => $user->id,
        ]);

        $this->getJson('/api/me')
            ->assertOk()
            ->assertJson(fn (AssertableJson $json) => $json
                ->where('user.schools.0.id', $schoolId)
                ->where('user.schools.0.roles.0.key', 'school-owner')
                ->where('user.schools.0.permissions', fn ($permissions): bool => $permissions->contains('academic_classes.manage'))
                ->etc()
            );
    }

    public function test_school_member_can_manage_academic_classes(): void
    {
        $user = User::factory()->create();
        $school = School::query()->create(['name' => 'Enterprise School', 'slug' => 'enterprise-school']);
        $school->memberships()->create([
            'user_id' => $user->id,
            'status' => 'active',
            'joined_at' => now(),
        ]);
        $this->grantAcademicClassManagement($user, $school);

        Sanctum::actingAs($user);

        $created = $this->postJson("/api/schools/{$school->id}/academic-classes", [
            'name' => 'Class One',
            'code' => 'C1',
            'sort_order' => 10,
        ]);

        $created
            ->assertCreated()
            ->assertJsonPath('data.name', 'Class One')
            ->assertJsonPath('data.code', 'C1');

        $classId = $created->json('data.id');

        $this->assertDatabaseHas('audit_logs', [
            'school_id' => $school->id,
            'actor_id' => $user->id,
            'event' => 'academic_class.created',
            'auditable_id' => $classId,
        ]);

        $this->getJson("/api/schools/{$school->id}/academic-classes")
            ->assertOk()
            ->assertJsonPath('data.0.id', $classId);

        $this->patchJson("/api/schools/{$school->id}/academic-classes/{$classId}", [
            'name' => 'Class 1',
        ])->assertOk()->assertJsonPath('data.name', 'Class 1');

        $this->assertDatabaseHas('audit_logs', [
            'school_id' => $school->id,
            'actor_id' => $user->id,
            'event' => 'academic_class.updated',
            'auditable_id' => $classId,
        ]);

        $this->deleteJson("/api/schools/{$school->id}/academic-classes/{$classId}")
            ->assertNoContent();

        $this->assertSoftDeleted(AcademicClass::class, ['id' => $classId]);
        $this->assertDatabaseHas('audit_logs', [
            'school_id' => $school->id,
            'actor_id' => $user->id,
            'event' => 'academic_class.deleted',
            'auditable_id' => $classId,
        ]);
    }

    public function test_school_member_can_manage_academic_sections(): void
    {
        $user = User::factory()->create();
        $school = School::query()->create(['name' => 'Section School', 'slug' => 'section-school']);
        $school->memberships()->create([
            'user_id' => $user->id,
            'status' => 'active',
            'joined_at' => now(),
        ]);
        $this->grantSchoolPermission($user, $school, 'sections.manage', 'academics');

        $academicClass = $school->academicClasses()->create([
            'name' => 'Class One',
            'code' => 'C1',
        ]);

        Sanctum::actingAs($user);

        $created = $this->postJson("/api/schools/{$school->id}/academic-sections", [
            'academic_class_id' => $academicClass->id,
            'name' => 'Section A',
            'code' => 'A',
            'capacity' => 35,
            'room' => '101',
            'sort_order' => 10,
        ]);

        $created
            ->assertCreated()
            ->assertJsonPath('data.name', 'Section A')
            ->assertJsonPath('data.academic_class.id', $academicClass->id);

        $sectionId = $created->json('data.id');

        $this->assertDatabaseHas('audit_logs', [
            'school_id' => $school->id,
            'actor_id' => $user->id,
            'event' => 'academic_section.created',
            'auditable_id' => $sectionId,
        ]);

        $this->getJson("/api/schools/{$school->id}/academic-sections?academic_class_id={$academicClass->id}")
            ->assertOk()
            ->assertJsonPath('data.0.id', $sectionId);

        $this->patchJson("/api/schools/{$school->id}/academic-sections/{$sectionId}", [
            'name' => 'Section Alpha',
            'capacity' => 40,
        ])->assertOk()
            ->assertJsonPath('data.name', 'Section Alpha')
            ->assertJsonPath('data.capacity', 40);

        $this->assertDatabaseHas('audit_logs', [
            'school_id' => $school->id,
            'actor_id' => $user->id,
            'event' => 'academic_section.updated',
            'auditable_id' => $sectionId,
        ]);

        $this->deleteJson("/api/schools/{$school->id}/academic-sections/{$sectionId}")
            ->assertNoContent();

        $this->assertSoftDeleted(AcademicSection::class, ['id' => $sectionId]);
        $this->assertDatabaseHas('audit_logs', [
            'school_id' => $school->id,
            'actor_id' => $user->id,
            'event' => 'academic_section.deleted',
            'auditable_id' => $sectionId,
        ]);
    }

    public function test_school_member_can_manage_academic_years(): void
    {
        $user = User::factory()->create();
        $school = School::query()->create(['name' => 'Year School', 'slug' => 'year-school']);
        $school->memberships()->create([
            'user_id' => $user->id,
            'status' => 'active',
            'joined_at' => now(),
        ]);
        $this->grantSchoolPermission($user, $school, 'academic_years.manage', 'academics');

        Sanctum::actingAs($user);

        $created = $this->postJson("/api/schools/{$school->id}/academic-years", [
            'name' => 'Academic Year 2026',
            'code' => 'AY-2026',
            'starts_on' => '2026-01-01',
            'ends_on' => '2026-12-31',
            'is_current' => true,
        ]);

        $created
            ->assertCreated()
            ->assertJsonPath('data.name', 'Academic Year 2026')
            ->assertJsonPath('data.is_current', true);

        $yearId = $created->json('data.id');

        $this->assertDatabaseHas('audit_logs', [
            'school_id' => $school->id,
            'actor_id' => $user->id,
            'event' => 'academic_year.created',
            'auditable_id' => $yearId,
        ]);

        $this->getJson("/api/schools/{$school->id}/academic-years?is_current=1")
            ->assertOk()
            ->assertJsonPath('data.0.id', $yearId);

        $this->patchJson("/api/schools/{$school->id}/academic-years/{$yearId}", [
            'name' => 'Academic Year 2026-27',
            'ends_on' => '2027-03-31',
        ])->assertOk()
            ->assertJsonPath('data.name', 'Academic Year 2026-27');

        $this->assertDatabaseHas('audit_logs', [
            'school_id' => $school->id,
            'actor_id' => $user->id,
            'event' => 'academic_year.updated',
            'auditable_id' => $yearId,
        ]);

        $this->deleteJson("/api/schools/{$school->id}/academic-years/{$yearId}")
            ->assertNoContent();

        $this->assertSoftDeleted(AcademicYear::class, ['id' => $yearId]);
        $this->assertDatabaseHas('audit_logs', [
            'school_id' => $school->id,
            'actor_id' => $user->id,
            'event' => 'academic_year.deleted',
            'auditable_id' => $yearId,
        ]);
    }

    public function test_current_academic_year_is_unique_per_school(): void
    {
        $user = User::factory()->create();
        $school = School::query()->create(['name' => 'Current Year School', 'slug' => 'current-year-school']);
        $school->memberships()->create([
            'user_id' => $user->id,
            'status' => 'active',
            'joined_at' => now(),
        ]);
        $this->grantSchoolPermission($user, $school, 'academic_years.manage', 'academics');

        $firstYear = $school->academicYears()->create([
            'name' => 'Academic Year 2025',
            'code' => 'AY-2025',
            'starts_on' => '2025-01-01',
            'ends_on' => '2025-12-31',
            'is_current' => true,
        ]);

        Sanctum::actingAs($user);

        $created = $this->postJson("/api/schools/{$school->id}/academic-years", [
            'name' => 'Academic Year 2026',
            'code' => 'AY-2026',
            'starts_on' => '2026-01-01',
            'ends_on' => '2026-12-31',
            'is_current' => true,
        ]);

        $created->assertCreated();

        $this->assertFalse($firstYear->fresh()->is_current);
        $this->assertTrue(AcademicYear::query()->findOrFail($created->json('data.id'))->is_current);
    }

    public function test_school_member_can_manage_subjects(): void
    {
        $user = User::factory()->create();
        $school = School::query()->create(['name' => 'Subject School', 'slug' => 'subject-school']);
        $school->memberships()->create([
            'user_id' => $user->id,
            'status' => 'active',
            'joined_at' => now(),
        ]);
        $this->grantSchoolPermission($user, $school, 'subjects.manage', 'academics');

        Sanctum::actingAs($user);

        $created = $this->postJson("/api/schools/{$school->id}/subjects", [
            'name' => 'Mathematics',
            'code' => 'MATH',
            'type' => 'core',
            'description' => 'Primary mathematics curriculum.',
            'credit_hours' => 4,
            'sort_order' => 10,
        ]);

        $created
            ->assertCreated()
            ->assertJsonPath('data.name', 'Mathematics')
            ->assertJsonPath('data.code', 'MATH')
            ->assertJsonPath('data.type', 'core');

        $subjectId = $created->json('data.id');

        $this->assertDatabaseHas('audit_logs', [
            'school_id' => $school->id,
            'actor_id' => $user->id,
            'event' => 'subject.created',
            'auditable_id' => $subjectId,
        ]);

        $this->getJson("/api/schools/{$school->id}/subjects?type=core&search=math")
            ->assertOk()
            ->assertJsonPath('data.0.id', $subjectId);

        $this->patchJson("/api/schools/{$school->id}/subjects/{$subjectId}", [
            'name' => 'Advanced Mathematics',
            'credit_hours' => 5,
        ])->assertOk()
            ->assertJsonPath('data.name', 'Advanced Mathematics')
            ->assertJsonPath('data.credit_hours', 5);

        $this->assertDatabaseHas('audit_logs', [
            'school_id' => $school->id,
            'actor_id' => $user->id,
            'event' => 'subject.updated',
            'auditable_id' => $subjectId,
        ]);

        $this->deleteJson("/api/schools/{$school->id}/subjects/{$subjectId}")
            ->assertNoContent();

        $this->assertSoftDeleted(Subject::class, ['id' => $subjectId]);
        $this->assertDatabaseHas('audit_logs', [
            'school_id' => $school->id,
            'actor_id' => $user->id,
            'event' => 'subject.deleted',
            'auditable_id' => $subjectId,
        ]);
    }

    public function test_school_member_can_manage_class_subject_assignments(): void
    {
        $user = User::factory()->create();
        $school = School::query()->create(['name' => 'Assignment School', 'slug' => 'assignment-school']);
        $school->memberships()->create([
            'user_id' => $user->id,
            'status' => 'active',
            'joined_at' => now(),
        ]);
        $this->grantSchoolPermission($user, $school, 'class_subjects.manage', 'academics');

        $academicClass = $school->academicClasses()->create(['name' => 'Class Nine', 'code' => 'C9']);
        $subject = $school->subjects()->create(['name' => 'Physics', 'code' => 'PHY', 'type' => 'core']);

        Sanctum::actingAs($user);

        $created = $this->postJson("/api/schools/{$school->id}/class-subjects", [
            'academic_class_id' => $academicClass->id,
            'subject_id' => $subject->id,
            'full_marks' => 100,
            'pass_marks' => 40,
            'subjective_marks' => 60,
            'sort_order' => 10,
        ]);

        $created
            ->assertCreated()
            ->assertJsonPath('data.academic_class.id', $academicClass->id)
            ->assertJsonPath('data.subject.id', $subject->id)
            ->assertJsonPath('data.pass_marks', 40);

        $assignmentId = $created->json('data.id');

        $this->assertDatabaseHas('audit_logs', [
            'school_id' => $school->id,
            'actor_id' => $user->id,
            'event' => 'class_subject.created',
            'auditable_id' => $assignmentId,
        ]);

        $this->getJson("/api/schools/{$school->id}/class-subjects?academic_class_id={$academicClass->id}&search=physics")
            ->assertOk()
            ->assertJsonPath('data.0.id', $assignmentId);

        $this->patchJson("/api/schools/{$school->id}/class-subjects/{$assignmentId}", [
            'full_marks' => 100,
            'pass_marks' => 45,
            'status' => 'active',
        ])->assertOk()
            ->assertJsonPath('data.pass_marks', 45);

        $this->assertDatabaseHas('audit_logs', [
            'school_id' => $school->id,
            'actor_id' => $user->id,
            'event' => 'class_subject.updated',
            'auditable_id' => $assignmentId,
        ]);

        $this->deleteJson("/api/schools/{$school->id}/class-subjects/{$assignmentId}")
            ->assertNoContent();

        $this->assertSoftDeleted(ClassSubject::class, ['id' => $assignmentId]);
        $this->assertDatabaseHas('audit_logs', [
            'school_id' => $school->id,
            'actor_id' => $user->id,
            'event' => 'class_subject.deleted',
            'auditable_id' => $assignmentId,
        ]);
    }

    public function test_class_subject_assignment_rejects_records_from_another_school(): void
    {
        $user = User::factory()->create();
        $school = School::query()->create(['name' => 'Assignment Tenant', 'slug' => 'assignment-tenant']);
        $otherSchool = School::query()->create(['name' => 'Foreign Assignment Tenant', 'slug' => 'foreign-assignment-tenant']);
        $school->memberships()->create([
            'user_id' => $user->id,
            'status' => 'active',
            'joined_at' => now(),
        ]);
        $this->grantSchoolPermission($user, $school, 'class_subjects.manage', 'academics');

        $academicClass = $school->academicClasses()->create(['name' => 'Class Nine', 'code' => 'C9']);
        $foreignSubject = $otherSchool->subjects()->create(['name' => 'Foreign Physics', 'code' => 'FPHY', 'type' => 'core']);

        Sanctum::actingAs($user);

        $this->postJson("/api/schools/{$school->id}/class-subjects", [
            'academic_class_id' => $academicClass->id,
            'subject_id' => $foreignSubject->id,
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('subject_id');
    }

    public function test_school_member_can_manage_student_groups(): void
    {
        $user = User::factory()->create();
        $school = School::query()->create(['name' => 'Group School', 'slug' => 'group-school']);
        $school->memberships()->create([
            'user_id' => $user->id,
            'status' => 'active',
            'joined_at' => now(),
        ]);
        $this->grantSchoolPermission($user, $school, 'student_groups.manage', 'academics');

        Sanctum::actingAs($user);

        $created = $this->postJson("/api/schools/{$school->id}/student-groups", [
            'name' => 'Science Group',
            'code' => 'SCI',
            'description' => 'Science-focused student cohort.',
            'sort_order' => 10,
        ]);

        $created
            ->assertCreated()
            ->assertJsonPath('data.name', 'Science Group')
            ->assertJsonPath('data.code', 'SCI');

        $groupId = $created->json('data.id');

        $this->assertDatabaseHas('audit_logs', [
            'school_id' => $school->id,
            'actor_id' => $user->id,
            'event' => 'student_group.created',
            'auditable_id' => $groupId,
        ]);

        $this->getJson("/api/schools/{$school->id}/student-groups?search=science")
            ->assertOk()
            ->assertJsonPath('data.0.id', $groupId);

        $this->patchJson("/api/schools/{$school->id}/student-groups/{$groupId}", [
            'name' => 'Science Cohort',
        ])->assertOk()
            ->assertJsonPath('data.name', 'Science Cohort');

        $this->assertDatabaseHas('audit_logs', [
            'school_id' => $school->id,
            'actor_id' => $user->id,
            'event' => 'student_group.updated',
            'auditable_id' => $groupId,
        ]);

        $this->deleteJson("/api/schools/{$school->id}/student-groups/{$groupId}")
            ->assertNoContent();

        $this->assertSoftDeleted(StudentGroup::class, ['id' => $groupId]);
        $this->assertDatabaseHas('audit_logs', [
            'school_id' => $school->id,
            'actor_id' => $user->id,
            'event' => 'student_group.deleted',
            'auditable_id' => $groupId,
        ]);
    }

    public function test_school_member_can_manage_shifts(): void
    {
        $user = User::factory()->create();
        $school = School::query()->create(['name' => 'Shift School', 'slug' => 'shift-school']);
        $school->memberships()->create([
            'user_id' => $user->id,
            'status' => 'active',
            'joined_at' => now(),
        ]);
        $this->grantSchoolPermission($user, $school, 'shifts.manage', 'academics');

        Sanctum::actingAs($user);

        $created = $this->postJson("/api/schools/{$school->id}/shifts", [
            'name' => 'Morning Shift',
            'code' => 'MOR',
            'starts_at' => '08:00',
            'ends_at' => '12:30',
            'description' => 'Morning academic schedule.',
            'sort_order' => 10,
        ]);

        $created
            ->assertCreated()
            ->assertJsonPath('data.name', 'Morning Shift')
            ->assertJsonPath('data.code', 'MOR');

        $shiftId = $created->json('data.id');

        $this->assertDatabaseHas('audit_logs', [
            'school_id' => $school->id,
            'actor_id' => $user->id,
            'event' => 'shift.created',
            'auditable_id' => $shiftId,
        ]);

        $this->getJson("/api/schools/{$school->id}/shifts?search=morning")
            ->assertOk()
            ->assertJsonPath('data.0.id', $shiftId);

        $this->patchJson("/api/schools/{$school->id}/shifts/{$shiftId}", [
            'name' => 'Morning Session',
            'ends_at' => '13:00',
        ])->assertOk()
            ->assertJsonPath('data.name', 'Morning Session')
            ->assertJsonPath('data.ends_at', '13:00');

        $this->assertDatabaseHas('audit_logs', [
            'school_id' => $school->id,
            'actor_id' => $user->id,
            'event' => 'shift.updated',
            'auditable_id' => $shiftId,
        ]);

        $this->deleteJson("/api/schools/{$school->id}/shifts/{$shiftId}")
            ->assertNoContent();

        $this->assertSoftDeleted(Shift::class, ['id' => $shiftId]);
        $this->assertDatabaseHas('audit_logs', [
            'school_id' => $school->id,
            'actor_id' => $user->id,
            'event' => 'shift.deleted',
            'auditable_id' => $shiftId,
        ]);
    }

    public function test_school_member_can_manage_designations(): void
    {
        $user = User::factory()->create();
        $school = School::query()->create(['name' => 'People School', 'slug' => 'people-school']);
        $school->memberships()->create([
            'user_id' => $user->id,
            'status' => 'active',
            'joined_at' => now(),
        ]);
        $this->grantSchoolPermission($user, $school, 'designations.manage', 'people');

        Sanctum::actingAs($user);

        $created = $this->postJson("/api/schools/{$school->id}/designations", [
            'name' => 'Senior Teacher',
            'code' => 'SNR-TCHR',
            'description' => 'Lead classroom teacher designation.',
            'sort_order' => 10,
        ]);

        $created
            ->assertCreated()
            ->assertJsonPath('data.name', 'Senior Teacher')
            ->assertJsonPath('data.code', 'SNR-TCHR');

        $designationId = $created->json('data.id');

        $this->assertDatabaseHas('audit_logs', [
            'school_id' => $school->id,
            'actor_id' => $user->id,
            'event' => 'designation.created',
            'auditable_id' => $designationId,
        ]);

        $this->getJson("/api/schools/{$school->id}/designations?search=senior")
            ->assertOk()
            ->assertJsonPath('data.0.id', $designationId);

        $this->patchJson("/api/schools/{$school->id}/designations/{$designationId}", [
            'name' => 'Senior Faculty',
        ])->assertOk()
            ->assertJsonPath('data.name', 'Senior Faculty');

        $this->assertDatabaseHas('audit_logs', [
            'school_id' => $school->id,
            'actor_id' => $user->id,
            'event' => 'designation.updated',
            'auditable_id' => $designationId,
        ]);

        $this->deleteJson("/api/schools/{$school->id}/designations/{$designationId}")
            ->assertNoContent();

        $this->assertSoftDeleted(Designation::class, ['id' => $designationId]);
        $this->assertDatabaseHas('audit_logs', [
            'school_id' => $school->id,
            'actor_id' => $user->id,
            'event' => 'designation.deleted',
            'auditable_id' => $designationId,
        ]);
    }

    public function test_section_creation_rejects_classes_from_another_school(): void
    {
        $user = User::factory()->create();
        $school = School::query()->create(['name' => 'Tenant School', 'slug' => 'tenant-school']);
        $otherSchool = School::query()->create(['name' => 'Other Tenant', 'slug' => 'other-tenant']);
        $school->memberships()->create([
            'user_id' => $user->id,
            'status' => 'active',
            'joined_at' => now(),
        ]);
        $this->grantSchoolPermission($user, $school, 'sections.manage', 'academics');

        $foreignClass = $otherSchool->academicClasses()->create([
            'name' => 'Foreign Class',
            'code' => 'FC',
        ]);

        Sanctum::actingAs($user);

        $this->postJson("/api/schools/{$school->id}/academic-sections", [
            'academic_class_id' => $foreignClass->id,
            'name' => 'Section A',
            'code' => 'A',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('academic_class_id');
    }

    public function test_user_cannot_access_another_schools_classes(): void
    {
        $user = User::factory()->create();
        $school = School::query()->create(['name' => 'Other School', 'slug' => 'other-school']);

        Sanctum::actingAs($user);

        $this->getJson("/api/schools/{$school->id}/academic-classes")
            ->assertForbidden();
    }

    public function test_inactive_school_member_cannot_access_school_classes(): void
    {
        $user = User::factory()->create();
        $school = School::query()->create(['name' => 'Paused School', 'slug' => 'paused-school']);
        $school->memberships()->create([
            'user_id' => $user->id,
            'status' => 'inactive',
            'joined_at' => now(),
        ]);

        Sanctum::actingAs($user);

        $this->getJson("/api/schools/{$school->id}/academic-classes")
            ->assertForbidden();
    }

    public function test_active_school_member_without_permission_cannot_manage_academic_classes(): void
    {
        $user = User::factory()->create();
        $school = School::query()->create(['name' => 'Limited School', 'slug' => 'limited-school']);
        $school->memberships()->create([
            'user_id' => $user->id,
            'status' => 'active',
            'joined_at' => now(),
        ]);

        Sanctum::actingAs($user);

        $this->postJson("/api/schools/{$school->id}/academic-classes", [
            'name' => 'Class One',
            'code' => 'C1',
        ])->assertForbidden();
    }

    public function test_active_school_member_without_permission_cannot_manage_academic_sections(): void
    {
        $user = User::factory()->create();
        $school = School::query()->create(['name' => 'Section Limited', 'slug' => 'section-limited']);
        $academicClass = $school->academicClasses()->create([
            'name' => 'Class One',
            'code' => 'C1',
        ]);
        $school->memberships()->create([
            'user_id' => $user->id,
            'status' => 'active',
            'joined_at' => now(),
        ]);

        Sanctum::actingAs($user);

        $this->postJson("/api/schools/{$school->id}/academic-sections", [
            'academic_class_id' => $academicClass->id,
            'name' => 'Section A',
            'code' => 'A',
        ])->assertForbidden();
    }

    public function test_active_school_member_without_permission_cannot_manage_academic_years(): void
    {
        $user = User::factory()->create();
        $school = School::query()->create(['name' => 'Year Limited', 'slug' => 'year-limited']);
        $school->memberships()->create([
            'user_id' => $user->id,
            'status' => 'active',
            'joined_at' => now(),
        ]);

        Sanctum::actingAs($user);

        $this->postJson("/api/schools/{$school->id}/academic-years", [
            'name' => 'Academic Year 2026',
            'code' => 'AY-2026',
            'starts_on' => '2026-01-01',
            'ends_on' => '2026-12-31',
        ])->assertForbidden();
    }

    public function test_active_school_member_without_permission_cannot_manage_subjects(): void
    {
        $user = User::factory()->create();
        $school = School::query()->create(['name' => 'Subject Limited', 'slug' => 'subject-limited']);
        $school->memberships()->create([
            'user_id' => $user->id,
            'status' => 'active',
            'joined_at' => now(),
        ]);

        Sanctum::actingAs($user);

        $this->postJson("/api/schools/{$school->id}/subjects", [
            'name' => 'Science',
            'code' => 'SCI',
        ])->assertForbidden();
    }

    public function test_active_school_member_without_permission_cannot_manage_class_subjects(): void
    {
        $user = User::factory()->create();
        $school = School::query()->create(['name' => 'Assignment Limited', 'slug' => 'assignment-limited']);
        $academicClass = $school->academicClasses()->create(['name' => 'Class Ten', 'code' => 'C10']);
        $subject = $school->subjects()->create(['name' => 'Chemistry', 'code' => 'CHEM', 'type' => 'core']);
        $school->memberships()->create([
            'user_id' => $user->id,
            'status' => 'active',
            'joined_at' => now(),
        ]);

        Sanctum::actingAs($user);

        $this->postJson("/api/schools/{$school->id}/class-subjects", [
            'academic_class_id' => $academicClass->id,
            'subject_id' => $subject->id,
        ])->assertForbidden();
    }

    public function test_active_school_member_without_permission_cannot_manage_student_groups(): void
    {
        $user = User::factory()->create();
        $school = School::query()->create(['name' => 'Group Limited', 'slug' => 'group-limited']);
        $school->memberships()->create([
            'user_id' => $user->id,
            'status' => 'active',
            'joined_at' => now(),
        ]);

        Sanctum::actingAs($user);

        $this->postJson("/api/schools/{$school->id}/student-groups", [
            'name' => 'Commerce Group',
            'code' => 'COM',
        ])->assertForbidden();
    }

    public function test_active_school_member_without_permission_cannot_manage_shifts(): void
    {
        $user = User::factory()->create();
        $school = School::query()->create(['name' => 'Shift Limited', 'slug' => 'shift-limited']);
        $school->memberships()->create([
            'user_id' => $user->id,
            'status' => 'active',
            'joined_at' => now(),
        ]);

        Sanctum::actingAs($user);

        $this->postJson("/api/schools/{$school->id}/shifts", [
            'name' => 'Day Shift',
            'code' => 'DAY',
        ])->assertForbidden();
    }

    public function test_active_school_member_without_permission_cannot_manage_designations(): void
    {
        $user = User::factory()->create();
        $school = School::query()->create(['name' => 'People Limited', 'slug' => 'people-limited']);
        $school->memberships()->create([
            'user_id' => $user->id,
            'status' => 'active',
            'joined_at' => now(),
        ]);

        Sanctum::actingAs($user);

        $this->postJson("/api/schools/{$school->id}/designations", [
            'name' => 'Assistant Teacher',
            'code' => 'AST-TCHR',
        ])->assertForbidden();
    }

    public function test_database_seeder_creates_enterprise_roles_and_permissions(): void
    {
        $this->seed();

        $this->assertDatabaseHas('permissions', ['key' => 'academic_years.manage']);
        $this->assertDatabaseHas('permissions', ['key' => 'academic_classes.manage']);
        $this->assertDatabaseHas('permissions', ['key' => 'subjects.manage']);
        $this->assertDatabaseHas('permissions', ['key' => 'class_subjects.manage']);
        $this->assertDatabaseHas('permissions', ['key' => 'student_groups.manage']);
        $this->assertDatabaseHas('permissions', ['key' => 'shifts.manage']);
        $this->assertDatabaseHas('permissions', ['key' => 'designations.manage']);
        $this->assertDatabaseHas('permissions', ['key' => 'audit.view']);
        $this->assertDatabaseHas('roles', ['key' => 'super-admin', 'is_system' => true]);
        $this->assertDatabaseHas('roles', ['key' => 'read-only-auditor', 'is_system' => true]);
        $this->assertDatabaseCount('roles', 9);
    }

    private function grantAcademicClassManagement(User $user, School $school): void
    {
        $this->grantSchoolPermission($user, $school, 'academic_classes.manage', 'academics');
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
            'name' => str($permissionKey)->before('.')->headline()->append(' Manager')->toString(),
            'key' => str($permissionKey)->before('.')->slug()->append('-manager')->toString(),
        ]);

        $role->permissions()->attach($permission);

        $user->roleAssignments()->create([
            'school_id' => $school->id,
            'role_id' => $role->id,
        ]);
    }
}
