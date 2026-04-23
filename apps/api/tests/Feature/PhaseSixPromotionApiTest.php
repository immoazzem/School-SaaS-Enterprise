<?php

namespace Tests\Feature;

use App\Models\Permission;
use App\Models\PromotionBatch;
use App\Models\Role;
use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PhaseSixPromotionApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_promotion_preview_suggests_retained_for_failed_students(): void
    {
        [$user, $school, $fromYear, $toYear, $fromClass, $toClass, $passedEnrollment, $failedEnrollment] = $this->promotionFixture();

        Sanctum::actingAs($user);

        $this->postJson("/api/v1/schools/{$school->id}/promotions/preview", [
            'from_academic_year_id' => $fromYear->id,
            'to_academic_year_id' => $toYear->id,
            'from_academic_class_id' => $fromClass->id,
            'to_academic_class_id' => $toClass->id,
        ])
            ->assertOk()
            ->assertJsonPath('data.0.student_enrollment_id', $passedEnrollment->id)
            ->assertJsonPath('data.0.suggested_action', 'promoted')
            ->assertJsonPath('data.1.student_enrollment_id', $failedEnrollment->id)
            ->assertJsonPath('data.1.suggested_action', 'retained');
    }

    public function test_promotion_batch_can_execute_and_rollback_within_window(): void
    {
        [$user, $school, $fromYear, $toYear, $fromClass, $toClass, $passedEnrollment, $failedEnrollment] = $this->promotionFixture();

        Sanctum::actingAs($user);

        $batchId = $this->postJson("/api/v1/schools/{$school->id}/promotions", [
            'from_academic_year_id' => $fromYear->id,
            'to_academic_year_id' => $toYear->id,
            'from_academic_class_id' => $fromClass->id,
            'to_academic_class_id' => $toClass->id,
        ])
            ->assertCreated()
            ->assertJsonPath('data.status', 'draft')
            ->assertJsonCount(2, 'data.records')
            ->json('data.id');

        $recordId = PromotionBatch::query()->findOrFail($batchId)->records()->where('student_enrollment_id', $failedEnrollment->id)->firstOrFail()->id;

        $this->patchJson("/api/v1/schools/{$school->id}/promotions/{$batchId}/records/{$recordId}", [
            'action' => 'promoted',
            'notes' => 'Manual promotion after review.',
        ])
            ->assertOk()
            ->assertJsonPath('data.action', 'promoted');

        $this->postJson("/api/v1/schools/{$school->id}/promotions/{$batchId}/execute")
            ->assertOk()
            ->assertJsonPath('data.status', 'completed')
            ->assertJsonPath('data.processed_count', 2);

        $this->assertDatabaseHas('student_enrollments', [
            'id' => $passedEnrollment->id,
            'status' => 'completed',
        ]);
        $this->assertDatabaseHas('student_enrollments', [
            'student_id' => $passedEnrollment->student_id,
            'academic_year_id' => $toYear->id,
            'academic_class_id' => $toClass->id,
            'status' => 'active',
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'school_id' => $school->id,
            'actor_id' => $user->id,
            'event' => 'promotion.executed',
        ]);

        $this->postJson("/api/v1/schools/{$school->id}/promotions/{$batchId}/rollback")
            ->assertOk()
            ->assertJsonPath('data.status', 'rolled_back');

        $this->assertDatabaseHas('student_enrollments', [
            'id' => $passedEnrollment->id,
            'status' => 'active',
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'school_id' => $school->id,
            'actor_id' => $user->id,
            'event' => 'promotion.rolled_back',
        ]);
    }

    public function test_completed_promotion_batch_cannot_execute_twice(): void
    {
        [$user, $school, $fromYear, $toYear, $fromClass, $toClass] = $this->promotionFixture();

        Sanctum::actingAs($user);

        $batchId = $this->postJson("/api/v1/schools/{$school->id}/promotions", [
            'from_academic_year_id' => $fromYear->id,
            'to_academic_year_id' => $toYear->id,
            'from_academic_class_id' => $fromClass->id,
            'to_academic_class_id' => $toClass->id,
        ])->assertCreated()->json('data.id');

        $this->postJson("/api/v1/schools/{$school->id}/promotions/{$batchId}/execute")
            ->assertOk();

        $this->postJson("/api/v1/schools/{$school->id}/promotions/{$batchId}/execute")
            ->assertUnprocessable()
            ->assertJsonValidationErrors('batch');
    }

    public function test_promotion_execute_reuses_existing_target_year_enrollment_without_duplicate_failure(): void
    {
        [$user, $school, $fromYear, $toYear, $fromClass, $toClass, $passedEnrollment] = $this->promotionFixture();

        Sanctum::actingAs($user);

        $existingTargetEnrollment = $school->studentEnrollments()->create([
            'student_id' => $passedEnrollment->student_id,
            'academic_year_id' => $toYear->id,
            'academic_class_id' => $toClass->id,
            'roll_no' => $passedEnrollment->roll_no,
            'enrolled_on' => '2027-01-10',
            'status' => 'active',
            'notes' => 'Pre-existing target enrollment.',
        ]);

        $batchId = $this->postJson("/api/v1/schools/{$school->id}/promotions", [
            'from_academic_year_id' => $fromYear->id,
            'to_academic_year_id' => $toYear->id,
            'from_academic_class_id' => $fromClass->id,
            'to_academic_class_id' => $toClass->id,
        ])->assertCreated()->json('data.id');

        $this->postJson("/api/v1/schools/{$school->id}/promotions/{$batchId}/execute")
            ->assertOk()
            ->assertJsonPath('data.status', 'completed');

        $this->assertDatabaseCount('student_enrollments', 4);
        $this->assertDatabaseHas('student_enrollments', [
            'id' => $existingTargetEnrollment->id,
            'student_id' => $passedEnrollment->student_id,
            'academic_year_id' => $toYear->id,
        ]);

        $this->postJson("/api/v1/schools/{$school->id}/promotions/{$batchId}/rollback")
            ->assertOk()
            ->assertJsonPath('data.status', 'rolled_back');

        $this->assertDatabaseHas('student_enrollments', [
            'id' => $existingTargetEnrollment->id,
            'student_id' => $passedEnrollment->student_id,
            'academic_year_id' => $toYear->id,
        ]);
    }

    private function promotionFixture(): array
    {
        $user = User::factory()->create();
        $school = School::query()->create(['name' => 'Promotion School', 'slug' => str()->uuid()->toString()]);
        $school->memberships()->create(['user_id' => $user->id, 'status' => 'active', 'joined_at' => now()]);
        $this->grantSchoolPermission($user, $school, 'promotions.manage', 'academics');

        $fromYear = $school->academicYears()->create(['name' => 'Academic Year 2026', 'code' => 'AY-2026', 'starts_on' => '2026-01-01', 'ends_on' => '2026-12-31']);
        $toYear = $school->academicYears()->create(['name' => 'Academic Year 2027', 'code' => 'AY-2027', 'starts_on' => '2027-01-01', 'ends_on' => '2027-12-31']);
        $fromClass = $school->academicClasses()->create(['name' => 'Class Four', 'code' => 'C4']);
        $toClass = $school->academicClasses()->create(['name' => 'Class Five', 'code' => 'C5']);
        $examType = $school->examTypes()->create(['name' => 'Final', 'code' => 'FIN']);
        $exam = $school->exams()->create(['exam_type_id' => $examType->id, 'academic_year_id' => $fromYear->id, 'name' => 'Final 2026', 'code' => 'FIN-2026', 'starts_on' => '2026-11-01', 'ends_on' => '2026-11-15']);

        $passedStudent = $school->students()->create(['admission_no' => 'ADM-PROMO-01', 'full_name' => 'Passed Student', 'admitted_on' => '2026-01-01']);
        $failedStudent = $school->students()->create(['admission_no' => 'ADM-PROMO-02', 'full_name' => 'Failed Student', 'admitted_on' => '2026-01-01']);
        $passedEnrollment = $school->studentEnrollments()->create(['student_id' => $passedStudent->id, 'academic_year_id' => $fromYear->id, 'academic_class_id' => $fromClass->id, 'roll_no' => '1', 'enrolled_on' => '2026-01-10', 'status' => 'active']);
        $failedEnrollment = $school->studentEnrollments()->create(['student_id' => $failedStudent->id, 'academic_year_id' => $fromYear->id, 'academic_class_id' => $fromClass->id, 'roll_no' => '2', 'enrolled_on' => '2026-01-10', 'status' => 'active']);

        $school->resultSummaries()->create(['exam_id' => $exam->id, 'student_enrollment_id' => $passedEnrollment->id, 'total_marks_obtained' => 80, 'total_full_marks' => 100, 'percentage' => 80, 'gpa' => 5, 'grade' => 'A+', 'position_in_class' => 1, 'is_pass' => true, 'computed_at' => now()]);
        $school->resultSummaries()->create(['exam_id' => $exam->id, 'student_enrollment_id' => $failedEnrollment->id, 'total_marks_obtained' => 20, 'total_full_marks' => 100, 'percentage' => 20, 'gpa' => 0, 'grade' => 'F', 'position_in_class' => 2, 'is_pass' => false, 'computed_at' => now()]);

        return [$user, $school, $fromYear, $toYear, $fromClass, $toClass, $passedEnrollment, $failedEnrollment];
    }

    private function grantSchoolPermission(User $user, School $school, string $permissionKey, string $module): void
    {
        $permission = Permission::query()->firstOrCreate(
            ['key' => $permissionKey],
            [
                'module' => $module,
                'description' => str($permissionKey)->replace('.', ' ')->headline()->toString(),
            ]
        );

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
