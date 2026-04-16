<?php

namespace Tests\Feature;

use App\Models\AcademicClass;
use App\Models\AcademicSection;
use App\Models\Permission;
use App\Models\Role;
use App\Models\School;
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

    public function test_database_seeder_creates_enterprise_roles_and_permissions(): void
    {
        $this->seed();

        $this->assertDatabaseHas('permissions', ['key' => 'academic_classes.manage']);
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
