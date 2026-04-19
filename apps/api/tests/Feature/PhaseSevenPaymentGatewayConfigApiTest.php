<?php

namespace Tests\Feature;

use App\Models\PaymentGatewayConfig;
use App\Models\Role;
use App\Models\School;
use App\Models\User;
use Database\Seeders\EnterpriseRolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PhaseSevenPaymentGatewayConfigApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(EnterpriseRolePermissionSeeder::class);
    }

    public function test_payment_gateway_manager_can_create_list_update_and_delete_config_without_exposing_credentials(): void
    {
        [$school, $accountant] = $this->createSchoolWithMember('accountant');

        Sanctum::actingAs($accountant);

        $created = $this->postJson("/api/v1/schools/{$school->id}/payment-gateway-configs", [
            'gateway' => 'bkash',
            'credentials' => [
                'merchant_id' => 'merchant-100',
                'app_key' => 'bkash-app-key',
                'app_secret' => 'bkash-app-secret',
            ],
            'is_active' => true,
            'test_mode' => true,
        ]);

        $created
            ->assertCreated()
            ->assertJsonPath('data.gateway', 'bkash')
            ->assertJsonPath('data.is_active', true)
            ->assertJsonPath('data.credentials_configured', true)
            ->assertJsonPath('data.credential_keys', ['app_key', 'app_secret', 'merchant_id'])
            ->assertJsonMissingPath('data.credentials')
            ->assertJsonMissingPath('data.credentials_encrypted');

        $configId = $created->json('data.id');
        $rawCredentials = PaymentGatewayConfig::query()->findOrFail($configId)->getRawOriginal('credentials_encrypted');

        $this->assertIsString($rawCredentials);
        $this->assertStringNotContainsString('bkash-app-secret', $rawCredentials);

        $this->assertDatabaseHas('audit_logs', [
            'school_id' => $school->id,
            'event' => 'payment_gateway_config.created',
            'auditable_type' => PaymentGatewayConfig::class,
            'auditable_id' => $configId,
        ]);

        $this->getJson("/api/v1/schools/{$school->id}/payment-gateway-configs")
            ->assertOk()
            ->assertJsonPath('data.0.id', $configId)
            ->assertJsonMissingPath('data.0.credentials_encrypted');

        $this->patchJson("/api/v1/schools/{$school->id}/payment-gateway-configs/{$configId}", [
            'credentials' => [
                'merchant_id' => 'merchant-100',
                'app_key' => 'rotated-app-key',
            ],
            'test_mode' => false,
        ])
            ->assertOk()
            ->assertJsonPath('data.test_mode', false)
            ->assertJsonPath('data.credential_keys', ['app_key', 'merchant_id'])
            ->assertJsonMissingPath('data.credentials_encrypted');

        $this->deleteJson("/api/v1/schools/{$school->id}/payment-gateway-configs/{$configId}")
            ->assertNoContent();

        $this->assertSoftDeleted('payment_gateway_configs', ['id' => $configId]);
    }

    public function test_duplicate_live_gateway_config_is_rejected_per_school(): void
    {
        [$school, $accountant] = $this->createSchoolWithMember('accountant');

        PaymentGatewayConfig::query()->create([
            'school_id' => $school->id,
            'gateway' => 'sslcommerz',
            'credentials_encrypted' => ['store_id' => 'school-store', 'store_password' => 'secret'],
            'is_active' => true,
            'test_mode' => true,
        ]);

        Sanctum::actingAs($accountant);

        $this->postJson("/api/v1/schools/{$school->id}/payment-gateway-configs", [
            'gateway' => 'sslcommerz',
            'credentials' => ['store_id' => 'other-store'],
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['gateway']);
    }

    public function test_member_without_payment_gateway_permission_cannot_manage_configs(): void
    {
        [$school] = $this->createSchoolWithMember('school-owner');
        $teacher = $this->createMemberWithRole($school, 'teacher');

        Sanctum::actingAs($teacher);

        $this->postJson("/api/v1/schools/{$school->id}/payment-gateway-configs", [
            'gateway' => 'nagad',
            'credentials' => ['merchant_id' => 'nagad-merchant'],
        ])->assertForbidden();
    }

    public function test_payment_gateway_config_is_tenant_scoped(): void
    {
        [$schoolA, $accountantA] = $this->createSchoolWithMember('accountant');
        [$schoolB] = $this->createSchoolWithMember('accountant');

        $config = PaymentGatewayConfig::query()->create([
            'school_id' => $schoolB->id,
            'gateway' => 'stripe',
            'credentials_encrypted' => ['secret_key' => 'sk_test_fake'],
            'is_active' => true,
            'test_mode' => true,
        ]);

        Sanctum::actingAs($accountantA);

        $this->getJson("/api/v1/schools/{$schoolB->id}/payment-gateway-configs/{$config->id}")
            ->assertForbidden();

        $this->getJson("/api/v1/schools/{$schoolA->id}/payment-gateway-configs/{$config->id}")
            ->assertNotFound();
    }

    /**
     * @return array{0: School, 1: User}
     */
    private function createSchoolWithMember(string $roleKey, string $name = 'Gateway School'): array
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
}
