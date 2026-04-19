<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\GenerateReportJob;
use App\Models\SalaryRecord;
use App\Models\School;
use App\Models\StudentInvoice;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ReportExportController extends Controller
{
    public function marksheet(Request $request, School $school): JsonResponse
    {
        $validated = $request->validate([
            'exam_id' => ['required', 'integer', Rule::exists('exams', 'id')->where('school_id', $school->id)],
            'student_enrollment_id' => ['required', 'integer', Rule::exists('student_enrollments', 'id')->where('school_id', $school->id)],
        ]);

        $enrollment = $school->studentEnrollments()->findOrFail($validated['student_enrollment_id']);

        return $this->dispatchReport($request, $school, 'marksheet', $enrollment, $validated);
    }

    public function resultSheet(Request $request, School $school): JsonResponse
    {
        $validated = $request->validate([
            'exam_id' => ['required', 'integer', Rule::exists('exams', 'id')->where('school_id', $school->id)],
            'academic_class_id' => ['nullable', 'integer', Rule::exists('academic_classes', 'id')->where('school_id', $school->id)],
        ]);

        $exam = $school->exams()->findOrFail($validated['exam_id']);

        return $this->dispatchReport($request, $school, 'result-sheet', $exam, $validated);
    }

    public function idCard(Request $request, School $school): JsonResponse
    {
        $validated = $request->validate([
            'student_enrollment_id' => ['required', 'integer', Rule::exists('student_enrollments', 'id')->where('school_id', $school->id)],
        ]);

        $enrollment = $school->studentEnrollments()->findOrFail($validated['student_enrollment_id']);

        return $this->dispatchReport($request, $school, 'id-card', $enrollment, $validated);
    }

    public function invoice(Request $request, School $school, StudentInvoice $invoice): JsonResponse
    {
        abort_unless($invoice->school_id === $school->id, 404);

        return $this->dispatchReport($request, $school, 'invoice-receipt', $invoice, ['invoice_id' => $invoice->id]);
    }

    public function salary(Request $request, School $school, SalaryRecord $record): JsonResponse
    {
        abort_unless($record->school_id === $school->id, 404);

        return $this->dispatchReport($request, $school, 'salary-slip', $record, ['salary_record_id' => $record->id]);
    }

    public function download(Request $request, School $school, string $jobId): JsonResponse
    {
        $this->authorizeReports($request, $school);

        $export = $school->reportExports()->where('job_id', $jobId)->firstOrFail();
        $data = [
            'job_id' => $export->job_id,
            'type' => $export->type,
            'status' => $export->status,
            'file_name' => $export->file_name,
            'download_url' => null,
        ];

        if ($export->status === 'completed' && $export->file_path && Storage::disk('local')->exists($export->file_path)) {
            $data['download_url'] = URL::temporarySignedRoute(
                'schools.reports.file',
                now()->addMinutes(15),
                ['school' => $school->id, 'export' => $export->job_id]
            );
        }

        return response()->json(['data' => $data]);
    }

    public function file(Request $request, School $school, string $export)
    {
        $this->authorizeReports($request, $school);

        $report = $school->reportExports()
            ->where('job_id', $export)
            ->where('status', 'completed')
            ->firstOrFail();

        abort_unless($report->file_path && Storage::disk('local')->exists($report->file_path), 404);

        return Storage::disk('local')->download($report->file_path, $report->file_name);
    }

    private function dispatchReport(Request $request, School $school, string $type, mixed $target, array $parameters): JsonResponse
    {
        $this->authorizeReports($request, $school);

        $export = $school->reportExports()->create([
            'job_id' => (string) Str::uuid(),
            'requested_by' => $request->user()->id,
            'type' => $type,
            'status' => 'pending',
            'target_type' => $target->getMorphClass(),
            'target_id' => $target->getKey(),
            'parameters' => $parameters,
        ]);

        $this->recordAudit($request, $school, 'report.exported', $export, [
            'new' => [
                'type' => $type,
                'target_id' => $target->getKey(),
                'job_id' => $export->job_id,
            ],
        ]);

        GenerateReportJob::dispatch($export->job_id);

        return response()->json(['data' => $export->makeHidden('file_path')], 202);
    }

    private function authorizeReports(Request $request, School $school): void
    {
        abort_unless($request->user()->hasSchoolPermission($school, 'reports.view'), 403);
    }
}
