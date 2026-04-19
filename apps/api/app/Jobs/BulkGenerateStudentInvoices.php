<?php

namespace App\Jobs;

use App\Models\School;
use App\Services\InvoiceService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class BulkGenerateStudentInvoices implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $backoff = 60;

    /**
     * @param  array<string, mixed>  $payload
     */
    public function __construct(
        public int $schoolId,
        public string $jobId,
        public array $payload
    ) {}

    public function handle(InvoiceService $invoiceService): void
    {
        $school = School::query()->findOrFail($this->schoolId);
        $enrollments = $school->studentEnrollments()
            ->where('academic_class_id', $this->payload['academic_class_id'])
            ->where('academic_year_id', $this->payload['academic_year_id'])
            ->where('status', 'active')
            ->get();

        foreach ($enrollments as $enrollment) {
            $invoiceService->create($school, [
                'student_enrollment_id' => $enrollment->id,
                'academic_year_id' => $this->payload['academic_year_id'],
                'fee_month' => $this->payload['month'],
                'fee_structure_ids' => $this->payload['fee_structure_ids'],
            ]);
        }
    }
}
