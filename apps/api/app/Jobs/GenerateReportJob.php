<?php

namespace App\Jobs;

use App\Models\Exam;
use App\Models\MarksEntry;
use App\Models\ReportExport;
use App\Models\StudentEnrollment;
use App\Models\StudentInvoice;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

class GenerateReportJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $backoff = 30;

    public function __construct(public readonly string $jobId) {}

    public function handle(): void
    {
        $export = ReportExport::query()->where('job_id', $this->jobId)->firstOrFail();
        $export->update(['status' => 'processing', 'error' => null]);

        try {
            $export->load(['school', 'requester']);
            $payload = $this->payload($export);
            $pdf = Pdf::loadView($this->viewFor($export), $payload)->setPaper('a4');
            $fileName = "{$export->type}-{$export->job_id}.pdf";
            $filePath = "reports/{$export->school_id}/{$fileName}";

            Storage::disk('local')->put($filePath, $pdf->output());

            $export->update([
                'status' => 'completed',
                'file_path' => $filePath,
                'file_name' => $fileName,
                'completed_at' => now(),
            ]);
        } catch (Throwable $exception) {
            $export->update([
                'status' => 'failed',
                'error' => $exception->getMessage(),
            ]);

            throw $exception;
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(ReportExport $export): array
    {
        $parameters = $export->parameters ?? [];

        $payload = [
            'school' => $export->school,
            'type' => str($export->type)->replace('-', ' ')->headline()->toString(),
            'jobId' => $export->job_id,
            'requestedBy' => $export->requester?->name,
            'generatedAt' => now()->toDayDateTimeString(),
            'parameters' => $parameters,
            'target' => $export->target,
        ];

        return match ($export->type) {
            'marksheet' => $this->marksheetPayload($export, $payload),
            'invoice-receipt' => $this->invoicePayload($export, $payload),
            default => $payload,
        };
    }

    private function viewFor(ReportExport $export): string
    {
        return match ($export->type) {
            'marksheet' => 'reports.marksheet',
            'invoice-receipt' => 'reports.invoice',
            default => 'reports.generic',
        };
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function marksheetPayload(ReportExport $export, array $payload): array
    {
        $enrollment = $export->target;

        if (! $enrollment instanceof StudentEnrollment) {
            throw new RuntimeException('Marksheet report target enrollment was not found.');
        }

        $examId = (int) data_get($export->parameters, 'exam_id');
        $exam = Exam::query()
            ->where('school_id', $export->school_id)
            ->with(['academicYear:id,name,code', 'examType:id,name,code'])
            ->find($examId);

        if (! $exam) {
            throw new RuntimeException('Marksheet report exam was not found.');
        }

        $enrollment->load([
            'student:id,full_name,admission_no',
            'academicYear:id,name,code',
            'academicClass:id,name,code',
            'academicSection:id,name,code',
        ]);

        $marksEntries = MarksEntry::query()
            ->where('school_id', $export->school_id)
            ->where('exam_id', $exam->id)
            ->where('student_enrollment_id', $enrollment->id)
            ->with('classSubject.subject:id,name,code')
            ->orderBy('id')
            ->get();

        return [
            ...$payload,
            'exam' => $exam,
            'target' => $enrollment,
            'marksEntries' => $marksEntries,
            'resultSummary' => $export->school
                ->resultSummaries()
                ->where('exam_id', $exam->id)
                ->where('student_enrollment_id', $enrollment->id)
                ->first(),
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function invoicePayload(ReportExport $export, array $payload): array
    {
        $invoice = $export->target;

        if (! $invoice instanceof StudentInvoice) {
            throw new RuntimeException('Invoice report target was not found.');
        }

        $invoice->load([
            'studentEnrollment.student:id,full_name,admission_no',
            'studentEnrollment.academicClass:id,name,code',
            'academicYear:id,name,starts_on,ends_on',
            'payments:id,student_invoice_id,amount,paid_on,payment_method,transaction_ref',
        ]);

        return [
            ...$payload,
            'target' => $invoice,
            'invoice' => $invoice,
            'payments' => $invoice->payments,
        ];
    }
}
