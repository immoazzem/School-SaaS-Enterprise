<?php

namespace Tests\Feature;

use App\Models\AcademicClass;
use App\Models\AcademicYear;
use App\Models\Role;
use App\Models\School;
use App\Models\Shift;
use App\Models\Subject;
use App\Models\TimetablePeriod;
use App\Models\User;
use Database\Seeders\EnterpriseRolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PhaseSevenTimetableApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(EnterpriseRolePermissionSeeder::class);
    }

    public function test_timetable_manager_can_create_list_update_and_delete_periods(): void
    {
        [$school, $principal] = $this->createSchoolWithMember('principal');
        $fixture = $this->createTimetableFixture($school);

        Sanctum::actingAs($principal);

        $createResponse = $this->postJson("/api/v1/schools/{$school->id}/timetable-periods", [
            'academic_year_id' => $fixture['year']->id,
            'academic_class_id' => $fixture['class']->id,
            'shift_id' => $fixture['shift']->id,
            'day_of_week' => 1,
            'period_number' => 1,
            'start_time' => '08:00',
            'end_time' => '08:45',
            'subject_id' => $fixture['subject']->id,
            'teacher_user_id' => $fixture['teacher']->id,
            'room' => 'A-101',
        ]);

        $createResponse
            ->assertCreated()
            ->assertJsonPath('data.period_number', 1)
            ->assertJsonPath('data.room', 'A-101');

        $periodId = $createResponse->json('data.id');

        $this->assertDatabaseHas('audit_logs', [
            'school_id' => $school->id,
            'event' => 'timetable_period.created',
            'auditable_type' => TimetablePeriod::class,
            'auditable_id' => $periodId,
        ]);

        $this->getJson("/api/v1/schools/{$school->id}/timetable-periods?academic_class_id={$fixture['class']->id}")
            ->assertOk()
            ->assertJsonPath('data.0.id', $periodId);

        $this->patchJson("/api/v1/schools/{$school->id}/timetable-periods/{$periodId}", [
            'room' => 'Science Lab',
        ])
            ->assertOk()
            ->assertJsonPath('data.room', 'Science Lab');

        $this->deleteJson("/api/v1/schools/{$school->id}/timetable-periods/{$periodId}")
            ->assertNoContent();

        $this->assertSoftDeleted('timetable_periods', ['id' => $periodId]);
    }

    public function test_timetable_period_rejects_cross_school_references(): void
    {
        [$school, $principal] = $this->createSchoolWithMember('principal');
        [$otherSchool] = $this->createSchoolWithMember('principal');
        $fixture = $this->createTimetableFixture($otherSchool);

        Sanctum::actingAs($principal);

        $this->postJson("/api/v1/schools/{$school->id}/timetable-periods", [
            'academic_year_id' => $fixture['year']->id,
            'academic_class_id' => $fixture['class']->id,
            'shift_id' => $fixture['shift']->id,
            'day_of_week' => 1,
            'period_number' => 1,
            'start_time' => '08:00',
            'end_time' => '08:45',
            'subject_id' => $fixture['subject']->id,
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['academic_year_id', 'academic_class_id', 'shift_id', 'subject_id']);
    }

    public function test_timetable_period_prevents_duplicate_class_period_slots(): void
    {
        [$school, $principal] = $this->createSchoolWithMember('principal');
        $fixture = $this->createTimetableFixture($school);

        TimetablePeriod::query()->create([
            'school_id' => $school->id,
            'academic_year_id' => $fixture['year']->id,
            'academic_class_id' => $fixture['class']->id,
            'shift_id' => $fixture['shift']->id,
            'day_of_week' => 2,
            'period_number' => 3,
            'start_time' => '10:00',
            'end_time' => '10:45',
            'subject_id' => $fixture['subject']->id,
            'teacher_user_id' => $fixture['teacher']->id,
            'status' => 'active',
        ]);

        Sanctum::actingAs($principal);

        $this->postJson("/api/v1/schools/{$school->id}/timetable-periods", [
            'academic_year_id' => $fixture['year']->id,
            'academic_class_id' => $fixture['class']->id,
            'shift_id' => $fixture['shift']->id,
            'day_of_week' => 2,
            'period_number' => 3,
            'start_time' => '11:00',
            'end_time' => '11:45',
            'subject_id' => $fixture['subject']->id,
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['period_number']);
    }

    public function test_timetable_period_prevents_teacher_time_overlap(): void
    {
        [$school, $principal] = $this->createSchoolWithMember('principal');
        $fixture = $this->createTimetableFixture($school);
        $secondClass = $this->createAcademicClass($school);

        TimetablePeriod::query()->create([
            'school_id' => $school->id,
            'academic_year_id' => $fixture['year']->id,
            'academic_class_id' => $fixture['class']->id,
            'shift_id' => $fixture['shift']->id,
            'day_of_week' => 4,
            'period_number' => 1,
            'start_time' => '08:00',
            'end_time' => '08:45',
            'subject_id' => $fixture['subject']->id,
            'teacher_user_id' => $fixture['teacher']->id,
            'status' => 'active',
        ]);

        Sanctum::actingAs($principal);

        $this->postJson("/api/v1/schools/{$school->id}/timetable-periods", [
            'academic_year_id' => $fixture['year']->id,
            'academic_class_id' => $secondClass->id,
            'shift_id' => $fixture['shift']->id,
            'day_of_week' => 4,
            'period_number' => 2,
            'start_time' => '08:30',
            'end_time' => '09:15',
            'subject_id' => $fixture['subject']->id,
            'teacher_user_id' => $fixture['teacher']->id,
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['teacher_user_id']);
    }

    public function test_member_without_timetable_manage_cannot_create_period(): void
    {
        [$school] = $this->createSchoolWithMember('school-owner');
        $teacher = $this->createMemberWithRole($school, 'teacher');
        $fixture = $this->createTimetableFixture($school);

        Sanctum::actingAs($teacher);

        $this->postJson("/api/v1/schools/{$school->id}/timetable-periods", [
            'academic_year_id' => $fixture['year']->id,
            'academic_class_id' => $fixture['class']->id,
            'day_of_week' => 1,
            'period_number' => 1,
            'start_time' => '08:00',
            'end_time' => '08:45',
        ])->assertForbidden();
    }

    /**
     * @return array{0: School, 1: User}
     */
    private function createSchoolWithMember(string $roleKey, string $name = 'Timetable School'): array
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
     * @return array{year: AcademicYear, class: AcademicClass, shift: Shift, subject: Subject, teacher: User}
     */
    private function createTimetableFixture(School $school): array
    {
        $teacher = User::factory()->create();
        $school->memberships()->create([
            'user_id' => $teacher->id,
            'status' => 'active',
            'joined_at' => now(),
        ]);

        return [
            'year' => AcademicYear::query()->create([
                'school_id' => $school->id,
                'name' => 'Academic Year '.Str::random(5),
                'code' => 'AY-'.Str::upper(Str::random(5)),
                'starts_on' => '2026-01-01',
                'ends_on' => '2026-12-31',
                'is_current' => true,
                'status' => 'active',
            ]),
            'class' => $this->createAcademicClass($school),
            'shift' => Shift::query()->create([
                'school_id' => $school->id,
                'name' => 'Morning '.Str::random(5),
                'code' => 'M-'.Str::upper(Str::random(5)),
                'starts_at' => '08:00',
                'ends_at' => '12:00',
                'sort_order' => 1,
                'status' => 'active',
            ]),
            'subject' => Subject::query()->create([
                'school_id' => $school->id,
                'name' => 'Mathematics '.Str::random(5),
                'code' => 'MATH-'.Str::upper(Str::random(5)),
                'type' => 'core',
                'credit_hours' => 4,
                'sort_order' => 1,
                'status' => 'active',
            ]),
            'teacher' => $teacher,
        ];
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
}
