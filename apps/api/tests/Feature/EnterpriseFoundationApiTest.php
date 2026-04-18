<?php

namespace Tests\Feature;

use App\Models\AcademicClass;
use App\Models\AcademicSection;
use App\Models\AcademicYear;
use App\Models\AuditLog;
use App\Models\ClassSubject;
use App\Models\Designation;
use App\Models\Employee;
use App\Models\ExamSchedule;
use App\Models\Guardian;
use App\Models\Permission;
use App\Models\Role;
use App\Models\School;
use App\Models\Shift;
use App\Models\Student;
use App\Models\StudentAttendanceRecord;
use App\Models\StudentEnrollment;
use App\Models\StudentGroup;
use App\Models\Subject;
use App\Models\TeacherProfile;
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

    public function test_school_index_returns_paginated_school_memberships(): void
    {
        $user = User::factory()->create();
        $alphaSchool = School::query()->create(['name' => 'Alpha Academy', 'slug' => 'alpha-academy']);
        $betaSchool = School::query()->create(['name' => 'Beta Academy', 'slug' => 'beta-academy']);

        foreach ([$alphaSchool, $betaSchool] as $school) {
            $school->memberships()->create([
                'user_id' => $user->id,
                'status' => 'active',
                'joined_at' => now(),
            ]);
        }

        Sanctum::actingAs($user);

        $this->getJson('/api/schools?per_page=1')
            ->assertOk()
            ->assertJsonPath('data.0.id', $alphaSchool->id)
            ->assertJsonPath('meta.per_page', 1)
            ->assertJsonPath('meta.total', 2)
            ->assertJsonStructure(['data', 'meta', 'links']);
    }

    public function test_school_manager_can_show_and_update_school_settings(): void
    {
        $user = User::factory()->create();
        $school = School::query()->create(['name' => 'Enterprise School', 'slug' => 'enterprise-school']);
        $school->memberships()->create([
            'user_id' => $user->id,
            'status' => 'active',
            'joined_at' => now(),
        ]);
        $this->grantSchoolPermission($user, $school, 'schools.manage', 'schools');

        Sanctum::actingAs($user);

        $this->getJson("/api/schools/{$school->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $school->id);

        $this->patchJson("/api/schools/{$school->id}", [
            'name' => 'Enterprise School Prime',
            'timezone' => 'Asia/Dhaka',
            'settings' => ['academic_week_start' => 'sunday'],
        ])
            ->assertOk()
            ->assertJsonPath('data.name', 'Enterprise School Prime')
            ->assertJsonPath('data.timezone', 'Asia/Dhaka')
            ->assertJsonPath('data.settings.academic_week_start', 'sunday');

        $this->assertDatabaseHas('schools', [
            'id' => $school->id,
            'name' => 'Enterprise School Prime',
            'timezone' => 'Asia/Dhaka',
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'school_id' => $school->id,
            'actor_id' => $user->id,
            'event' => 'school.updated',
            'auditable_type' => (new School)->getMorphClass(),
            'auditable_id' => $school->id,
        ]);

        $auditLog = AuditLog::query()->where('event', 'school.updated')->firstOrFail();
        $this->assertSame('Enterprise School', $auditLog->metadata['old']['name']);
        $this->assertSame('Enterprise School Prime', $auditLog->metadata['new']['name']);
    }

    public function test_active_school_member_without_permission_cannot_update_school_settings(): void
    {
        $user = User::factory()->create();
        $school = School::query()->create(['name' => 'Limited School', 'slug' => 'limited-school']);
        $school->memberships()->create([
            'user_id' => $user->id,
            'status' => 'active',
            'joined_at' => now(),
        ]);

        Sanctum::actingAs($user);

        $this->patchJson("/api/schools/{$school->id}", [
            'name' => 'Blocked Update',
        ])->assertForbidden();
    }

    public function test_non_member_cannot_show_school_settings(): void
    {
        $user = User::factory()->create();
        $school = School::query()->create(['name' => 'Private School', 'slug' => 'private-school']);

        Sanctum::actingAs($user);

        $this->getJson("/api/schools/{$school->id}")
            ->assertForbidden();
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

    public function test_school_member_can_manage_employees(): void
    {
        $user = User::factory()->create();
        $school = School::query()->create(['name' => 'Employee School', 'slug' => 'employee-school']);
        $school->memberships()->create([
            'user_id' => $user->id,
            'status' => 'active',
            'joined_at' => now(),
        ]);
        $this->grantSchoolPermission($user, $school, 'employees.manage', 'people');

        $designation = $school->designations()->create([
            'name' => 'Senior Teacher',
            'code' => 'SNR-TCHR',
        ]);

        Sanctum::actingAs($user);

        $created = $this->postJson("/api/schools/{$school->id}/employees", [
            'designation_id' => $designation->id,
            'employee_no' => 'EMP-2026-0001',
            'full_name' => 'Amina Rahman',
            'father_name' => 'Karim Rahman',
            'mother_name' => 'Nadia Rahman',
            'email' => 'amina.rahman@example.test',
            'phone' => '+8801700000001',
            'gender' => 'Female',
            'religion' => 'Islam',
            'date_of_birth' => '1992-04-12',
            'joined_on' => '2026-01-15',
            'salary' => 55000,
            'employee_type' => 'teacher',
            'address' => 'Dhaka',
            'notes' => 'Primary mathematics faculty.',
        ]);

        $created
            ->assertCreated()
            ->assertJsonPath('data.full_name', 'Amina Rahman')
            ->assertJsonPath('data.employee_no', 'EMP-2026-0001')
            ->assertJsonPath('data.designation.id', $designation->id);

        $employeeId = $created->json('data.id');

        $this->assertDatabaseHas('audit_logs', [
            'school_id' => $school->id,
            'actor_id' => $user->id,
            'event' => 'employee.created',
            'auditable_id' => $employeeId,
        ]);

        $this->getJson("/api/schools/{$school->id}/employees?employee_type=teacher&search=amina")
            ->assertOk()
            ->assertJsonPath('data.0.id', $employeeId);

        $this->patchJson("/api/schools/{$school->id}/employees/{$employeeId}", [
            'full_name' => 'Amina Karim Rahman',
            'salary' => 60000,
        ])->assertOk()
            ->assertJsonPath('data.full_name', 'Amina Karim Rahman')
            ->assertJsonPath('data.salary', '60000.00');

        $this->assertDatabaseHas('audit_logs', [
            'school_id' => $school->id,
            'actor_id' => $user->id,
            'event' => 'employee.updated',
            'auditable_id' => $employeeId,
        ]);

        $this->deleteJson("/api/schools/{$school->id}/employees/{$employeeId}")
            ->assertNoContent();

        $this->assertSoftDeleted(Employee::class, ['id' => $employeeId]);
        $this->assertDatabaseHas('audit_logs', [
            'school_id' => $school->id,
            'actor_id' => $user->id,
            'event' => 'employee.deleted',
            'auditable_id' => $employeeId,
        ]);
    }

    public function test_employee_creation_rejects_designations_from_another_school(): void
    {
        $user = User::factory()->create();
        $school = School::query()->create(['name' => 'Employee Tenant', 'slug' => 'employee-tenant']);
        $otherSchool = School::query()->create(['name' => 'Foreign Employee Tenant', 'slug' => 'foreign-employee-tenant']);
        $school->memberships()->create([
            'user_id' => $user->id,
            'status' => 'active',
            'joined_at' => now(),
        ]);
        $this->grantSchoolPermission($user, $school, 'employees.manage', 'people');

        $foreignDesignation = $otherSchool->designations()->create([
            'name' => 'Foreign Teacher',
            'code' => 'FRN-TCHR',
        ]);

        Sanctum::actingAs($user);

        $this->postJson("/api/schools/{$school->id}/employees", [
            'designation_id' => $foreignDesignation->id,
            'employee_no' => 'EMP-2026-0002',
            'full_name' => 'Foreign Designation User',
            'joined_on' => '2026-01-15',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('designation_id');
    }

    public function test_school_member_can_manage_guardians(): void
    {
        $user = User::factory()->create();
        $school = School::query()->create(['name' => 'Guardian School', 'slug' => 'guardian-school']);
        $school->memberships()->create([
            'user_id' => $user->id,
            'status' => 'active',
            'joined_at' => now(),
        ]);
        $this->grantSchoolPermission($user, $school, 'guardians.manage', 'people');

        Sanctum::actingAs($user);

        $created = $this->postJson("/api/schools/{$school->id}/guardians", [
            'full_name' => 'Karim Rahman',
            'relationship' => 'Father',
            'phone' => '+8801700001000',
            'email' => 'karim.rahman@example.test',
            'occupation' => 'Engineer',
            'address' => 'Dhaka',
        ]);

        $created
            ->assertCreated()
            ->assertJsonPath('data.full_name', 'Karim Rahman')
            ->assertJsonPath('data.relationship', 'Father');

        $guardianId = $created->json('data.id');

        $this->assertDatabaseHas('audit_logs', [
            'school_id' => $school->id,
            'actor_id' => $user->id,
            'event' => 'guardian.created',
            'auditable_id' => $guardianId,
        ]);

        $this->getJson("/api/schools/{$school->id}/guardians?search=karim")
            ->assertOk()
            ->assertJsonPath('data.0.id', $guardianId);

        $this->patchJson("/api/schools/{$school->id}/guardians/{$guardianId}", [
            'occupation' => 'Architect',
        ])->assertOk()
            ->assertJsonPath('data.occupation', 'Architect');

        $this->assertDatabaseHas('audit_logs', [
            'school_id' => $school->id,
            'actor_id' => $user->id,
            'event' => 'guardian.updated',
            'auditable_id' => $guardianId,
        ]);

        $this->deleteJson("/api/schools/{$school->id}/guardians/{$guardianId}")
            ->assertNoContent();

        $this->assertSoftDeleted(Guardian::class, ['id' => $guardianId]);
        $this->assertDatabaseHas('audit_logs', [
            'school_id' => $school->id,
            'actor_id' => $user->id,
            'event' => 'guardian.deleted',
            'auditable_id' => $guardianId,
        ]);
    }

    public function test_school_member_can_manage_students(): void
    {
        $user = User::factory()->create();
        $school = School::query()->create(['name' => 'Student School', 'slug' => 'student-school']);
        $school->memberships()->create([
            'user_id' => $user->id,
            'status' => 'active',
            'joined_at' => now(),
        ]);
        $this->grantSchoolPermission($user, $school, 'students.manage', 'people');

        $guardian = $school->guardians()->create([
            'full_name' => 'Karim Rahman',
            'relationship' => 'Father',
            'phone' => '+8801700001000',
        ]);

        Sanctum::actingAs($user);

        $created = $this->postJson("/api/schools/{$school->id}/students", [
            'guardian_id' => $guardian->id,
            'admission_no' => 'ADM-2026-0001',
            'full_name' => 'Nadia Rahman',
            'father_name' => 'Karim Rahman',
            'mother_name' => 'Amina Rahman',
            'email' => 'nadia.rahman@example.test',
            'phone' => '+8801700002000',
            'gender' => 'Female',
            'religion' => 'Islam',
            'date_of_birth' => '2018-08-20',
            'admitted_on' => '2026-01-10',
            'address' => 'Dhaka',
            'medical_notes' => 'No known allergies.',
        ]);

        $created
            ->assertCreated()
            ->assertJsonPath('data.full_name', 'Nadia Rahman')
            ->assertJsonPath('data.admission_no', 'ADM-2026-0001')
            ->assertJsonPath('data.guardian.id', $guardian->id);

        $studentId = $created->json('data.id');

        $this->assertDatabaseHas('audit_logs', [
            'school_id' => $school->id,
            'actor_id' => $user->id,
            'event' => 'student.created',
            'auditable_id' => $studentId,
        ]);

        $this->getJson("/api/schools/{$school->id}/students?search=nadia")
            ->assertOk()
            ->assertJsonPath('data.0.id', $studentId);

        $this->patchJson("/api/schools/{$school->id}/students/{$studentId}", [
            'full_name' => 'Nadia Karim Rahman',
        ])->assertOk()
            ->assertJsonPath('data.full_name', 'Nadia Karim Rahman');

        $this->assertDatabaseHas('audit_logs', [
            'school_id' => $school->id,
            'actor_id' => $user->id,
            'event' => 'student.updated',
            'auditable_id' => $studentId,
        ]);

        $this->deleteJson("/api/schools/{$school->id}/students/{$studentId}")
            ->assertNoContent();

        $this->assertSoftDeleted(Student::class, ['id' => $studentId]);
        $this->assertDatabaseHas('audit_logs', [
            'school_id' => $school->id,
            'actor_id' => $user->id,
            'event' => 'student.deleted',
            'auditable_id' => $studentId,
        ]);
    }

    public function test_student_creation_rejects_guardians_from_another_school(): void
    {
        $user = User::factory()->create();
        $school = School::query()->create(['name' => 'Student Tenant', 'slug' => 'student-tenant']);
        $otherSchool = School::query()->create(['name' => 'Foreign Student Tenant', 'slug' => 'foreign-student-tenant']);
        $school->memberships()->create([
            'user_id' => $user->id,
            'status' => 'active',
            'joined_at' => now(),
        ]);
        $this->grantSchoolPermission($user, $school, 'students.manage', 'people');

        $foreignGuardian = $otherSchool->guardians()->create([
            'full_name' => 'Foreign Guardian',
            'relationship' => 'Father',
        ]);

        Sanctum::actingAs($user);

        $this->postJson("/api/schools/{$school->id}/students", [
            'guardian_id' => $foreignGuardian->id,
            'admission_no' => 'ADM-2026-0002',
            'full_name' => 'Foreign Guardian Student',
            'admitted_on' => '2026-01-10',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('guardian_id');
    }

    public function test_school_member_can_manage_student_enrollments(): void
    {
        $user = User::factory()->create();
        $school = School::query()->create(['name' => 'Enrollment School', 'slug' => 'enrollment-school']);
        $school->memberships()->create([
            'user_id' => $user->id,
            'status' => 'active',
            'joined_at' => now(),
        ]);
        $this->grantSchoolPermission($user, $school, 'enrollments.manage', 'people');

        $student = $school->students()->create([
            'admission_no' => 'ADM-2026-0100',
            'full_name' => 'Nadia Rahman',
            'admitted_on' => '2026-01-10',
        ]);
        $academicYear = $school->academicYears()->create([
            'name' => 'Academic Year 2026',
            'code' => 'AY-2026',
            'starts_on' => '2026-01-01',
            'ends_on' => '2026-12-31',
        ]);
        $academicClass = $school->academicClasses()->create(['name' => 'Class One', 'code' => 'C1']);
        $section = $school->academicSections()->create([
            'academic_class_id' => $academicClass->id,
            'name' => 'Section A',
            'code' => 'A',
        ]);
        $group = $school->studentGroups()->create(['name' => 'Science', 'code' => 'SCI']);
        $shift = $school->shifts()->create(['name' => 'Morning', 'code' => 'MOR']);

        Sanctum::actingAs($user);

        $created = $this->postJson("/api/schools/{$school->id}/student-enrollments", [
            'student_id' => $student->id,
            'academic_year_id' => $academicYear->id,
            'academic_class_id' => $academicClass->id,
            'academic_section_id' => $section->id,
            'student_group_id' => $group->id,
            'shift_id' => $shift->id,
            'roll_no' => '12',
            'enrolled_on' => '2026-01-15',
            'notes' => 'Initial admission.',
        ]);

        $created
            ->assertCreated()
            ->assertJsonPath('data.student.id', $student->id)
            ->assertJsonPath('data.academic_class.id', $academicClass->id)
            ->assertJsonPath('data.roll_no', '12');

        $enrollmentId = $created->json('data.id');

        $this->assertDatabaseHas('audit_logs', [
            'school_id' => $school->id,
            'actor_id' => $user->id,
            'event' => 'student_enrollment.created',
            'auditable_id' => $enrollmentId,
        ]);

        $this->getJson("/api/schools/{$school->id}/student-enrollments?search=nadia")
            ->assertOk()
            ->assertJsonPath('data.0.id', $enrollmentId);

        $this->patchJson("/api/schools/{$school->id}/student-enrollments/{$enrollmentId}", [
            'roll_no' => '15',
            'status' => 'active',
        ])->assertOk()
            ->assertJsonPath('data.roll_no', '15');

        $this->assertDatabaseHas('audit_logs', [
            'school_id' => $school->id,
            'actor_id' => $user->id,
            'event' => 'student_enrollment.updated',
            'auditable_id' => $enrollmentId,
        ]);

        $this->deleteJson("/api/schools/{$school->id}/student-enrollments/{$enrollmentId}")
            ->assertNoContent();

        $this->assertSoftDeleted(StudentEnrollment::class, ['id' => $enrollmentId]);
        $this->assertDatabaseHas('audit_logs', [
            'school_id' => $school->id,
            'actor_id' => $user->id,
            'event' => 'student_enrollment.deleted',
            'auditable_id' => $enrollmentId,
        ]);
    }

    public function test_student_enrollment_rejects_records_from_another_school(): void
    {
        $user = User::factory()->create();
        $school = School::query()->create(['name' => 'Enrollment Tenant', 'slug' => 'enrollment-tenant']);
        $otherSchool = School::query()->create(['name' => 'Foreign Enrollment Tenant', 'slug' => 'foreign-enrollment-tenant']);
        $school->memberships()->create([
            'user_id' => $user->id,
            'status' => 'active',
            'joined_at' => now(),
        ]);
        $this->grantSchoolPermission($user, $school, 'enrollments.manage', 'people');

        $foreignStudent = $otherSchool->students()->create([
            'admission_no' => 'ADM-FOREIGN',
            'full_name' => 'Foreign Student',
            'admitted_on' => '2026-01-10',
        ]);
        $academicYear = $school->academicYears()->create([
            'name' => 'Academic Year 2026',
            'code' => 'AY-2026',
            'starts_on' => '2026-01-01',
            'ends_on' => '2026-12-31',
        ]);
        $academicClass = $school->academicClasses()->create(['name' => 'Class One', 'code' => 'C1']);

        Sanctum::actingAs($user);

        $this->postJson("/api/schools/{$school->id}/student-enrollments", [
            'student_id' => $foreignStudent->id,
            'academic_year_id' => $academicYear->id,
            'academic_class_id' => $academicClass->id,
            'enrolled_on' => '2026-01-15',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('student_id');
    }

    public function test_school_member_can_manage_student_attendance_records(): void
    {
        $user = User::factory()->create();
        $school = School::query()->create(['name' => 'Attendance School', 'slug' => 'attendance-school']);
        $school->memberships()->create([
            'user_id' => $user->id,
            'status' => 'active',
            'joined_at' => now(),
        ]);
        $this->grantSchoolPermission($user, $school, 'attendance.manage', 'attendance');

        $student = $school->students()->create([
            'admission_no' => 'ADM-2026-0200',
            'full_name' => 'Attendance Student',
            'admitted_on' => '2026-01-10',
        ]);
        $academicYear = $school->academicYears()->create([
            'name' => 'Academic Year 2026',
            'code' => 'AY-2026',
            'starts_on' => '2026-01-01',
            'ends_on' => '2026-12-31',
        ]);
        $academicClass = $school->academicClasses()->create(['name' => 'Class One', 'code' => 'C1']);
        $enrollment = $school->studentEnrollments()->create([
            'student_id' => $student->id,
            'academic_year_id' => $academicYear->id,
            'academic_class_id' => $academicClass->id,
            'roll_no' => '12',
            'enrolled_on' => '2026-01-15',
        ]);

        Sanctum::actingAs($user);

        $created = $this->postJson("/api/schools/{$school->id}/student-attendance-records", [
            'student_enrollment_id' => $enrollment->id,
            'attendance_date' => '2026-04-18',
            'status' => 'present',
            'remarks' => 'Morning homeroom.',
        ]);

        $created
            ->assertCreated()
            ->assertJsonPath('data.student_enrollment.id', $enrollment->id)
            ->assertJsonPath('data.student_enrollment.student.full_name', 'Attendance Student')
            ->assertJsonPath('data.status', 'present');

        $recordId = $created->json('data.id');

        $this->assertDatabaseHas('audit_logs', [
            'school_id' => $school->id,
            'actor_id' => $user->id,
            'event' => 'student_attendance.created',
            'auditable_id' => $recordId,
        ]);

        $this->getJson("/api/schools/{$school->id}/student-attendance-records?attendance_date=2026-04-18&search=attendance")
            ->assertOk()
            ->assertJsonPath('data.0.id', $recordId);

        $this->postJson("/api/schools/{$school->id}/student-attendance-records", [
            'student_enrollment_id' => $enrollment->id,
            'attendance_date' => '2026-04-18',
            'status' => 'late',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('attendance_date');

        $this->patchJson("/api/schools/{$school->id}/student-attendance-records/{$recordId}", [
            'status' => 'late',
            'remarks' => 'Arrived after assembly.',
        ])->assertOk()
            ->assertJsonPath('data.status', 'late')
            ->assertJsonPath('data.remarks', 'Arrived after assembly.');

        $this->assertDatabaseHas('audit_logs', [
            'school_id' => $school->id,
            'actor_id' => $user->id,
            'event' => 'student_attendance.updated',
            'auditable_id' => $recordId,
        ]);

        $this->deleteJson("/api/schools/{$school->id}/student-attendance-records/{$recordId}")
            ->assertNoContent();

        $this->assertSoftDeleted(StudentAttendanceRecord::class, ['id' => $recordId]);
        $this->assertDatabaseHas('audit_logs', [
            'school_id' => $school->id,
            'actor_id' => $user->id,
            'event' => 'student_attendance.deleted',
            'auditable_id' => $recordId,
        ]);
    }

    public function test_student_attendance_rejects_enrollments_from_another_school(): void
    {
        $user = User::factory()->create();
        $school = School::query()->create(['name' => 'Attendance Tenant', 'slug' => 'attendance-tenant']);
        $otherSchool = School::query()->create(['name' => 'Foreign Attendance Tenant', 'slug' => 'foreign-attendance-tenant']);
        $school->memberships()->create([
            'user_id' => $user->id,
            'status' => 'active',
            'joined_at' => now(),
        ]);
        $this->grantSchoolPermission($user, $school, 'attendance.manage', 'attendance');

        $foreignStudent = $otherSchool->students()->create([
            'admission_no' => 'ADM-ATT-FOREIGN',
            'full_name' => 'Foreign Attendance Student',
            'admitted_on' => '2026-01-10',
        ]);
        $foreignYear = $otherSchool->academicYears()->create([
            'name' => 'Academic Year 2026',
            'code' => 'AY-2026',
            'starts_on' => '2026-01-01',
            'ends_on' => '2026-12-31',
        ]);
        $foreignClass = $otherSchool->academicClasses()->create(['name' => 'Class One', 'code' => 'C1']);
        $foreignEnrollment = $otherSchool->studentEnrollments()->create([
            'student_id' => $foreignStudent->id,
            'academic_year_id' => $foreignYear->id,
            'academic_class_id' => $foreignClass->id,
            'enrolled_on' => '2026-01-15',
        ]);

        Sanctum::actingAs($user);

        $this->postJson("/api/schools/{$school->id}/student-attendance-records", [
            'student_enrollment_id' => $foreignEnrollment->id,
            'attendance_date' => '2026-04-18',
            'status' => 'present',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('student_enrollment_id');
    }

    public function test_school_member_can_manage_teacher_profiles(): void
    {
        $user = User::factory()->create();
        $school = School::query()->create(['name' => 'Teacher School', 'slug' => 'teacher-school']);
        $school->memberships()->create([
            'user_id' => $user->id,
            'status' => 'active',
            'joined_at' => now(),
        ]);
        $this->grantSchoolPermission($user, $school, 'teachers.manage', 'people');

        $employee = $school->employees()->create([
            'employee_no' => 'EMP-2026-0100',
            'full_name' => 'Amina Rahman',
            'joined_on' => '2026-01-15',
        ]);

        Sanctum::actingAs($user);

        $created = $this->postJson("/api/schools/{$school->id}/teacher-profiles", [
            'employee_id' => $employee->id,
            'teacher_no' => 'TCHR-2026-0001',
            'specialization' => 'Mathematics',
            'qualification' => 'M.Ed',
            'experience_years' => 8,
            'joined_teaching_on' => '2026-02-01',
            'bio' => 'Senior mathematics teacher.',
        ]);

        $created
            ->assertCreated()
            ->assertJsonPath('data.teacher_no', 'TCHR-2026-0001')
            ->assertJsonPath('data.employee.id', $employee->id);

        $profileId = $created->json('data.id');

        $this->assertDatabaseHas('audit_logs', [
            'school_id' => $school->id,
            'actor_id' => $user->id,
            'event' => 'teacher_profile.created',
            'auditable_id' => $profileId,
        ]);

        $this->getJson("/api/schools/{$school->id}/teacher-profiles?search=amina")
            ->assertOk()
            ->assertJsonPath('data.0.id', $profileId);

        $this->patchJson("/api/schools/{$school->id}/teacher-profiles/{$profileId}", [
            'specialization' => 'Advanced Mathematics',
        ])->assertOk()
            ->assertJsonPath('data.specialization', 'Advanced Mathematics');

        $this->assertDatabaseHas('audit_logs', [
            'school_id' => $school->id,
            'actor_id' => $user->id,
            'event' => 'teacher_profile.updated',
            'auditable_id' => $profileId,
        ]);

        $this->deleteJson("/api/schools/{$school->id}/teacher-profiles/{$profileId}")
            ->assertNoContent();

        $this->assertSoftDeleted(TeacherProfile::class, ['id' => $profileId]);
        $this->assertDatabaseHas('audit_logs', [
            'school_id' => $school->id,
            'actor_id' => $user->id,
            'event' => 'teacher_profile.deleted',
            'auditable_id' => $profileId,
        ]);
    }

    public function test_teacher_profile_rejects_employees_from_another_school(): void
    {
        $user = User::factory()->create();
        $school = School::query()->create(['name' => 'Teacher Tenant', 'slug' => 'teacher-tenant']);
        $otherSchool = School::query()->create(['name' => 'Foreign Teacher Tenant', 'slug' => 'foreign-teacher-tenant']);
        $school->memberships()->create([
            'user_id' => $user->id,
            'status' => 'active',
            'joined_at' => now(),
        ]);
        $this->grantSchoolPermission($user, $school, 'teachers.manage', 'people');

        $foreignEmployee = $otherSchool->employees()->create([
            'employee_no' => 'EMP-FOREIGN',
            'full_name' => 'Foreign Teacher',
            'joined_on' => '2026-01-15',
        ]);

        Sanctum::actingAs($user);

        $this->postJson("/api/schools/{$school->id}/teacher-profiles", [
            'employee_id' => $foreignEmployee->id,
            'teacher_no' => 'TCHR-FOREIGN',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('employee_id');
    }

    public function test_school_member_can_manage_exam_types_exams_and_schedules(): void
    {
        $user = User::factory()->create();
        $school = School::query()->create(['name' => 'Exam School', 'slug' => 'exam-school']);
        $school->memberships()->create([
            'user_id' => $user->id,
            'status' => 'active',
            'joined_at' => now(),
        ]);
        $this->grantSchoolPermission($user, $school, 'exams.manage', 'exams');

        $academicYear = $school->academicYears()->create([
            'name' => 'Academic Year 2026',
            'code' => 'AY-2026',
            'starts_on' => '2026-01-01',
            'ends_on' => '2026-12-31',
        ]);
        $academicClass = $school->academicClasses()->create(['name' => 'Class One', 'code' => 'C1']);
        $subject = $school->subjects()->create(['name' => 'Mathematics', 'code' => 'MATH']);
        $classSubject = $school->classSubjects()->create([
            'academic_class_id' => $academicClass->id,
            'subject_id' => $subject->id,
            'full_marks' => 100,
            'pass_marks' => 33,
        ]);

        Sanctum::actingAs($user);

        $type = $this->postJson("/api/schools/{$school->id}/exam-types", [
            'name' => 'Midterm',
            'code' => 'MID',
            'weightage_percent' => 40,
            'description' => 'First term weighted exam.',
        ]);

        $type
            ->assertCreated()
            ->assertJsonPath('data.code', 'MID')
            ->assertJsonPath('data.weightage_percent', '40.00');

        $examTypeId = $type->json('data.id');

        $this->assertDatabaseHas('audit_logs', [
            'school_id' => $school->id,
            'actor_id' => $user->id,
            'event' => 'exam_type.created',
            'auditable_id' => $examTypeId,
        ]);

        $exam = $this->postJson("/api/schools/{$school->id}/exams", [
            'exam_type_id' => $examTypeId,
            'academic_year_id' => $academicYear->id,
            'name' => 'Midterm 2026',
            'code' => 'MID-2026',
            'starts_on' => '2026-06-01',
            'ends_on' => '2026-06-15',
            'status' => 'scheduled',
        ]);

        $exam
            ->assertCreated()
            ->assertJsonPath('data.code', 'MID-2026')
            ->assertJsonPath('data.is_published', false)
            ->assertJsonPath('data.exam_type.id', $examTypeId)
            ->assertJsonPath('data.academic_year.id', $academicYear->id);

        $examId = $exam->json('data.id');

        $this->assertDatabaseHas('exams', [
            'id' => $examId,
            'is_published' => false,
            'published_at' => null,
            'published_by' => null,
        ]);

        $schedule = $this->postJson("/api/schools/{$school->id}/exam-schedules", [
            'exam_id' => $examId,
            'class_subject_id' => $classSubject->id,
            'exam_date' => '2026-06-03',
            'starts_at' => '10:00',
            'ends_at' => '12:00',
            'room' => 'Room 101',
        ]);

        $schedule
            ->assertCreated()
            ->assertJsonPath('data.class_subject.academic_class.name', 'Class One')
            ->assertJsonPath('data.class_subject.subject.code', 'MATH')
            ->assertJsonPath('data.class_subject.full_marks', 100);

        $scheduleId = $schedule->json('data.id');

        $this->getJson("/api/schools/{$school->id}/exam-schedules?exam_id={$examId}")
            ->assertOk()
            ->assertJsonPath('data.0.id', $scheduleId)
            ->assertJsonPath('meta.total', 1);

        $this->patchJson("/api/schools/{$school->id}/exam-schedules/{$scheduleId}", [
            'room' => 'Room 202',
        ])->assertOk()
            ->assertJsonPath('data.room', 'Room 202');

        $this->deleteJson("/api/schools/{$school->id}/exam-schedules/{$scheduleId}")
            ->assertNoContent();

        $this->assertSoftDeleted(ExamSchedule::class, ['id' => $scheduleId]);
    }

    public function test_exam_foundation_rejects_cross_school_records(): void
    {
        $user = User::factory()->create();
        $school = School::query()->create(['name' => 'Exam Tenant', 'slug' => 'exam-tenant']);
        $otherSchool = School::query()->create(['name' => 'Foreign Exam Tenant', 'slug' => 'foreign-exam-tenant']);
        $school->memberships()->create([
            'user_id' => $user->id,
            'status' => 'active',
            'joined_at' => now(),
        ]);
        $this->grantSchoolPermission($user, $school, 'exams.manage', 'exams');

        $foreignExamType = $otherSchool->examTypes()->create(['name' => 'Foreign Type', 'code' => 'FOREIGN']);
        $academicYear = $school->academicYears()->create([
            'name' => 'Academic Year 2026',
            'code' => 'AY-2026',
            'starts_on' => '2026-01-01',
            'ends_on' => '2026-12-31',
        ]);

        Sanctum::actingAs($user);

        $this->postJson("/api/schools/{$school->id}/exams", [
            'exam_type_id' => $foreignExamType->id,
            'academic_year_id' => $academicYear->id,
            'name' => 'Blocked Exam',
            'code' => 'BLOCKED',
            'starts_on' => '2026-06-01',
            'ends_on' => '2026-06-10',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('exam_type_id');
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

    public function test_active_school_member_without_permission_cannot_manage_employees(): void
    {
        $user = User::factory()->create();
        $school = School::query()->create(['name' => 'Employee Limited', 'slug' => 'employee-limited']);
        $school->memberships()->create([
            'user_id' => $user->id,
            'status' => 'active',
            'joined_at' => now(),
        ]);

        Sanctum::actingAs($user);

        $this->postJson("/api/schools/{$school->id}/employees", [
            'employee_no' => 'EMP-2026-0003',
            'full_name' => 'Limited Employee',
            'joined_on' => '2026-01-15',
        ])->assertForbidden();
    }

    public function test_active_school_member_without_permission_cannot_manage_guardians(): void
    {
        $user = User::factory()->create();
        $school = School::query()->create(['name' => 'Guardian Limited', 'slug' => 'guardian-limited']);
        $school->memberships()->create([
            'user_id' => $user->id,
            'status' => 'active',
            'joined_at' => now(),
        ]);

        Sanctum::actingAs($user);

        $this->postJson("/api/schools/{$school->id}/guardians", [
            'full_name' => 'Limited Guardian',
        ])->assertForbidden();
    }

    public function test_active_school_member_without_permission_cannot_manage_students(): void
    {
        $user = User::factory()->create();
        $school = School::query()->create(['name' => 'Student Limited', 'slug' => 'student-limited']);
        $school->memberships()->create([
            'user_id' => $user->id,
            'status' => 'active',
            'joined_at' => now(),
        ]);

        Sanctum::actingAs($user);

        $this->postJson("/api/schools/{$school->id}/students", [
            'admission_no' => 'ADM-2026-0003',
            'full_name' => 'Limited Student',
            'admitted_on' => '2026-01-10',
        ])->assertForbidden();
    }

    public function test_active_school_member_without_permission_cannot_manage_student_enrollments(): void
    {
        $user = User::factory()->create();
        $school = School::query()->create(['name' => 'Enrollment Limited', 'slug' => 'enrollment-limited']);
        $student = $school->students()->create([
            'admission_no' => 'ADM-2026-0101',
            'full_name' => 'Limited Enrollment Student',
            'admitted_on' => '2026-01-10',
        ]);
        $academicYear = $school->academicYears()->create([
            'name' => 'Academic Year 2026',
            'code' => 'AY-2026',
            'starts_on' => '2026-01-01',
            'ends_on' => '2026-12-31',
        ]);
        $academicClass = $school->academicClasses()->create(['name' => 'Class One', 'code' => 'C1']);
        $school->memberships()->create([
            'user_id' => $user->id,
            'status' => 'active',
            'joined_at' => now(),
        ]);

        Sanctum::actingAs($user);

        $this->postJson("/api/schools/{$school->id}/student-enrollments", [
            'student_id' => $student->id,
            'academic_year_id' => $academicYear->id,
            'academic_class_id' => $academicClass->id,
            'enrolled_on' => '2026-01-15',
        ])->assertForbidden();
    }

    public function test_active_school_member_without_permission_cannot_manage_student_attendance(): void
    {
        $user = User::factory()->create();
        $school = School::query()->create(['name' => 'Attendance Limited', 'slug' => 'attendance-limited']);
        $student = $school->students()->create([
            'admission_no' => 'ADM-2026-0301',
            'full_name' => 'Limited Attendance Student',
            'admitted_on' => '2026-01-10',
        ]);
        $academicYear = $school->academicYears()->create([
            'name' => 'Academic Year 2026',
            'code' => 'AY-2026',
            'starts_on' => '2026-01-01',
            'ends_on' => '2026-12-31',
        ]);
        $academicClass = $school->academicClasses()->create(['name' => 'Class One', 'code' => 'C1']);
        $enrollment = $school->studentEnrollments()->create([
            'student_id' => $student->id,
            'academic_year_id' => $academicYear->id,
            'academic_class_id' => $academicClass->id,
            'enrolled_on' => '2026-01-15',
        ]);
        $school->memberships()->create([
            'user_id' => $user->id,
            'status' => 'active',
            'joined_at' => now(),
        ]);

        Sanctum::actingAs($user);

        $this->postJson("/api/schools/{$school->id}/student-attendance-records", [
            'student_enrollment_id' => $enrollment->id,
            'attendance_date' => '2026-04-18',
            'status' => 'present',
        ])->assertForbidden();
    }

    public function test_active_school_member_without_permission_cannot_manage_teacher_profiles(): void
    {
        $user = User::factory()->create();
        $school = School::query()->create(['name' => 'Teacher Limited', 'slug' => 'teacher-limited']);
        $employee = $school->employees()->create([
            'employee_no' => 'EMP-2026-0101',
            'full_name' => 'Limited Teacher',
            'joined_on' => '2026-01-15',
        ]);
        $school->memberships()->create([
            'user_id' => $user->id,
            'status' => 'active',
            'joined_at' => now(),
        ]);

        Sanctum::actingAs($user);

        $this->postJson("/api/schools/{$school->id}/teacher-profiles", [
            'employee_id' => $employee->id,
            'teacher_no' => 'TCHR-2026-0101',
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
        $this->assertDatabaseHas('permissions', ['key' => 'employees.manage']);
        $this->assertDatabaseHas('permissions', ['key' => 'guardians.manage']);
        $this->assertDatabaseHas('permissions', ['key' => 'students.manage']);
        $this->assertDatabaseHas('permissions', ['key' => 'enrollments.manage']);
        $this->assertDatabaseHas('permissions', ['key' => 'teachers.manage']);
        $this->assertDatabaseHas('permissions', ['key' => 'attendance.manage']);
        $this->assertDatabaseHas('permissions', ['key' => 'exams.manage']);
        $this->assertDatabaseHas('permissions', ['key' => 'exams.publish']);
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
