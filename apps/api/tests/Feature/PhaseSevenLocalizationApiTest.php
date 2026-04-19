<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\Role;
use App\Models\School;
use App\Models\Student;
use App\Models\User;
use Database\Seeders\EnterpriseRolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PhaseSevenLocalizationApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(EnterpriseRolePermissionSeeder::class);
    }

    public function test_student_api_uses_bengali_display_name_for_bengali_school_locale(): void
    {
        [$school, $owner] = $this->createSchoolWithMember('school-owner', 'bn');

        Sanctum::actingAs($owner);

        $created = $this->postJson("/api/v1/schools/{$school->id}/students", [
            'admission_no' => 'ADM-BN-001',
            'full_name' => 'Amina Rahman',
            'name_bn' => 'আমিনা রহমান',
            'admitted_on' => '2026-01-10',
        ]);

        $created
            ->assertCreated()
            ->assertJsonPath('data.full_name', 'Amina Rahman')
            ->assertJsonPath('data.name_bn', 'আমিনা রহমান')
            ->assertJsonPath('data.display_name', 'আমিনা রহমান');

        $this->getJson("/api/v1/schools/{$school->id}/students?search=আমিনা")
            ->assertOk()
            ->assertJsonPath('data.0.display_name', 'আমিনা রহমান');

        $this->getJson("/api/v1/schools/{$school->id}/students/{$created->json('data.id')}?locale=en")
            ->assertOk()
            ->assertJsonPath('data.display_name', 'Amina Rahman');
    }

    public function test_employee_api_uses_bengali_display_name_from_accept_language(): void
    {
        [$school, $owner] = $this->createSchoolWithMember('school-owner', 'en');

        Sanctum::actingAs($owner);

        $created = $this
            ->withHeader('Accept-Language', 'bn-BD,bn;q=0.9,en;q=0.8')
            ->postJson("/api/v1/schools/{$school->id}/employees", [
                'employee_no' => 'EMP-BN-001',
                'full_name' => 'Karim Uddin',
                'name_bn' => 'করিম উদ্দিন',
                'joined_on' => '2026-01-15',
                'employee_type' => 'teacher',
            ]);

        $created
            ->assertCreated()
            ->assertJsonPath('data.full_name', 'Karim Uddin')
            ->assertJsonPath('data.name_bn', 'করিম উদ্দিন')
            ->assertJsonPath('data.display_name', 'করিম উদ্দিন');

        $this
            ->withHeader('Accept-Language', 'bn-BD,bn;q=0.9,en;q=0.8')
            ->getJson("/api/v1/schools/{$school->id}/employees?search=করিম")
            ->assertOk()
            ->assertJsonPath('data.0.display_name', 'করিম উদ্দিন');

        $this->getJson("/api/v1/schools/{$school->id}/employees/{$created->json('data.id')}?locale=en")
            ->assertOk()
            ->assertJsonPath('data.display_name', 'Karim Uddin');
    }

    public function test_localized_display_name_falls_back_to_full_name_when_bengali_name_is_missing(): void
    {
        [$school, $owner] = $this->createSchoolWithMember('school-owner', 'bn');
        $student = Student::query()->create([
            'school_id' => $school->id,
            'admission_no' => 'ADM-FALLBACK-001',
            'full_name' => 'Fallback Student',
            'admitted_on' => '2026-01-10',
            'status' => 'active',
        ]);
        $employee = Employee::query()->create([
            'school_id' => $school->id,
            'employee_no' => 'EMP-FALLBACK-001',
            'full_name' => 'Fallback Employee',
            'joined_on' => '2026-01-15',
            'employee_type' => 'staff',
            'status' => 'active',
        ]);

        Sanctum::actingAs($owner);

        $this->getJson("/api/v1/schools/{$school->id}/students/{$student->id}")
            ->assertOk()
            ->assertJsonPath('data.display_name', 'Fallback Student');

        $this->getJson("/api/v1/schools/{$school->id}/employees/{$employee->id}")
            ->assertOk()
            ->assertJsonPath('data.display_name', 'Fallback Employee');
    }

    /**
     * @return array{0: School, 1: User}
     */
    private function createSchoolWithMember(string $roleKey, string $locale): array
    {
        $school = School::query()->create([
            'name' => 'Localization School '.Str::random(6),
            'slug' => 'localization-school-'.Str::lower(Str::random(6)),
            'timezone' => 'Asia/Dhaka',
            'locale' => $locale,
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
