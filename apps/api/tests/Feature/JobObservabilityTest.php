<?php

namespace Tests\Feature;

use App\Jobs\BulkGenerateStudentInvoices;
use App\Models\Role;
use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class JobObservabilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_can_view_job_status_counts_and_recent_failures(): void
    {
        $this->seed();
        $admin = User::factory()->create();
        $school = School::query()->create(['name' => 'Queue Admin School', 'slug' => 'queue-admin-school']);
        $this->grantSystemRole($admin, $school, 'super-admin');
        $this->insertPendingJob();
        $this->insertFailedJob('11111111-1111-4111-8111-111111111111');

        Sanctum::actingAs($admin);

        $this->getJson('/api/v1/admin/jobs/status')
            ->assertOk()
            ->assertJsonPath('data.pending', 1)
            ->assertJsonPath('data.failed', 1)
            ->assertJsonPath('data.recent_failures.0.uuid', '11111111-1111-4111-8111-111111111111')
            ->assertJsonPath('data.recent_failures.0.queue', 'default');
    }

    public function test_non_super_admin_cannot_view_job_status(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->getJson('/api/v1/admin/jobs/status')->assertForbidden();
    }

    public function test_super_admin_can_retry_failed_job_through_laravel_queue_command(): void
    {
        $this->seed();
        $admin = User::factory()->create();
        $school = School::query()->create(['name' => 'Retry Admin School', 'slug' => 'retry-admin-school']);
        $this->grantSystemRole($admin, $school, 'super-admin');
        $uuid = '22222222-2222-4222-8222-222222222222';
        $this->insertFailedJob($uuid);

        Artisan::shouldReceive('call')
            ->once()
            ->with('queue:retry', ['id' => [$uuid]])
            ->andReturn(0);
        Artisan::shouldReceive('output')
            ->once()
            ->andReturn('The failed job has been pushed back onto the queue.');

        Sanctum::actingAs($admin);

        $this->postJson("/api/v1/admin/jobs/{$uuid}/retry")
            ->assertAccepted()
            ->assertJsonPath('data.uuid', $uuid)
            ->assertJsonPath('data.queued_for_retry', true);
    }

    public function test_bulk_invoice_job_has_retry_policy(): void
    {
        $job = new BulkGenerateStudentInvoices(1, 'bulk-job-id', [
            'academic_class_id' => 1,
            'academic_year_id' => 1,
            'month' => '2026-04',
            'fee_structure_ids' => [1],
        ]);

        $this->assertSame(3, $job->tries);
        $this->assertSame(60, $job->backoff);
        $this->assertTrue(config('queue.connections.database.after_commit'));
        $this->assertSame('failed_jobs', config('queue.failed.table'));
    }

    private function grantSystemRole(User $user, School $school, string $roleKey): void
    {
        $role = Role::query()
            ->whereNull('school_id')
            ->where('key', $roleKey)
            ->firstOrFail();

        $user->roleAssignments()->create([
            'school_id' => $school->id,
            'role_id' => $role->id,
        ]);
    }

    private function insertPendingJob(): void
    {
        DB::table('jobs')->insert([
            'queue' => 'default',
            'payload' => '{}',
            'attempts' => 0,
            'reserved_at' => null,
            'available_at' => now()->getTimestamp(),
            'created_at' => now()->getTimestamp(),
        ]);
    }

    private function insertFailedJob(string $uuid): void
    {
        DB::table('failed_jobs')->insert([
            'uuid' => $uuid,
            'connection' => 'database',
            'queue' => 'default',
            'payload' => '{}',
            'exception' => str_repeat('Synthetic queue failure. ', 40),
            'failed_at' => now(),
        ]);
    }
}
