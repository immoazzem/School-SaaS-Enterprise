<?php

namespace Tests\Feature;

use App\Models\AcademicClass;
use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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

        $this->getJson("/api/schools/{$school->id}/academic-classes")
            ->assertOk()
            ->assertJsonPath('data.0.id', $classId);

        $this->patchJson("/api/schools/{$school->id}/academic-classes/{$classId}", [
            'name' => 'Class 1',
        ])->assertOk()->assertJsonPath('data.name', 'Class 1');

        $this->deleteJson("/api/schools/{$school->id}/academic-classes/{$classId}")
            ->assertNoContent();

        $this->assertSoftDeleted(AcademicClass::class, ['id' => $classId]);
    }

    public function test_user_cannot_access_another_schools_classes(): void
    {
        $user = User::factory()->create();
        $school = School::query()->create(['name' => 'Other School', 'slug' => 'other-school']);

        Sanctum::actingAs($user);

        $this->getJson("/api/schools/{$school->id}/academic-classes")
            ->assertForbidden();
    }
}
