<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DataExportJob;
use App\Models\School;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class DataExportController extends Controller
{
    public function request(Request $request, School $school): JsonResponse
    {
        abort_unless($request->user()->hasSchoolPermission($school, 'schools.manage'), 403);

        $validated = $request->validate([
            'include_audit_logs' => ['nullable', 'boolean'],
        ]);

        $job = $school->dataExportJobs()->create([
            'requested_by' => $request->user()->id,
            'job_id' => (string) Str::uuid(),
            'status' => 'pending',
            'parameters' => [
                'include_audit_logs' => (bool) ($validated['include_audit_logs'] ?? false),
            ],
        ]);

        $payload = $this->buildPayload($school, (bool) ($validated['include_audit_logs'] ?? false));
        $fileName = "school-{$school->id}-data-export-{$job->job_id}.json";
        $filePath = "data-exports/{$school->id}/{$fileName}";

        Storage::disk('local')->put($filePath, json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        $job->update([
            'status' => 'completed',
            'file_path' => $filePath,
            'file_name' => $fileName,
            'completed_at' => now(),
        ]);

        $this->recordAudit($request, $school, 'data_export.requested', $job, [
            'job_id' => $job->job_id,
            'include_audit_logs' => (bool) ($validated['include_audit_logs'] ?? false),
        ]);

        return response()->json(['data' => $job->fresh()], 202);
    }

    public function download(Request $request, School $school, string $jobId)
    {
        abort_unless($request->user()->hasSchoolPermission($school, 'schools.manage'), 403);

        $job = DataExportJob::query()
            ->where('school_id', $school->id)
            ->where('job_id', $jobId)
            ->firstOrFail();

        abort_unless($job->status === 'completed' && $job->file_path !== null, 404);
        abort_unless(Storage::disk('local')->exists($job->file_path), 404);

        return response()->download(
            Storage::disk('local')->path($job->file_path),
            $job->file_name ?? 'school-data-export.json',
            ['Content-Type' => 'application/json'],
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function buildPayload(School $school, bool $includeAuditLogs): array
    {
        $payload = [
            'exported_at' => now()->toISOString(),
            'school' => $school->only([
                'id',
                'public_id',
                'name',
                'slug',
                'status',
                'plan',
                'subscription_status',
                'timezone',
                'locale',
                'settings',
                'plan_limits',
            ]),
            'guardians' => $school->guardians()->get(),
            'students' => $school->students()->with('enrollments')->get(),
            'employees' => $school->employees()->get(),
            'student_invoices' => $school->studentInvoices()->with('payments')->get(),
            'documents' => $school->documents()->get(['id', 'school_id', 'category', 'title', 'file_name', 'mime_type', 'file_size_bytes', 'is_public', 'created_at', 'updated_at']),
        ];

        if ($includeAuditLogs) {
            $payload['audit_logs'] = $school->auditLogs()->latest()->limit(500)->get();
        }

        return $payload;
    }
}
