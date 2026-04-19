<?php

namespace App\Jobs;

use App\Models\ReportExport;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;
use Throwable;

class GenerateReportJob implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly string $jobId) {}

    public function handle(): void
    {
        $export = ReportExport::query()->where('job_id', $this->jobId)->firstOrFail();
        $export->update(['status' => 'processing', 'error' => null]);

        try {
            $export->load(['school', 'requester']);
            $payload = $this->payload($export);
            $pdf = Pdf::loadView('reports.generic', $payload)->setPaper('a4');
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

        return [
            'school' => $export->school,
            'type' => str($export->type)->replace('-', ' ')->headline()->toString(),
            'jobId' => $export->job_id,
            'requestedBy' => $export->requester?->name,
            'generatedAt' => now()->toDayDateTimeString(),
            'parameters' => $parameters,
            'target' => $export->target,
        ];
    }
}
