<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\BulkGenerateStudentInvoices;
use App\Models\School;
use App\Models\StudentInvoice;
use App\Services\InvoiceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class StudentInvoiceController extends Controller
{
    public function __construct(private readonly InvoiceService $invoiceService) {}

    public function index(Request $request, School $school): JsonResponse
    {
        $this->authorizeFinance($request, $school);

        return response()->json($this->paginated($school->studentInvoices()->with($this->invoiceService->relations())->orderByDesc('id')->paginate($this->perPage($request))));
    }

    public function store(Request $request, School $school): JsonResponse
    {
        $this->authorizeFinance($request, $school);
        $invoice = $this->invoiceService->create($school, $this->validatedPayload($request, $school));
        $this->recordAudit($request, $school, 'student_invoice.created', $invoice, ['new' => $invoice->toArray()]);

        return response()->json(['data' => $invoice], 201);
    }

    public function show(Request $request, School $school, StudentInvoice $studentInvoice): JsonResponse
    {
        $this->authorizeFinance($request, $school);
        abort_unless($studentInvoice->school_id === $school->id, 404);

        return response()->json(['data' => $studentInvoice->load($this->invoiceService->relations())]);
    }

    public function update(Request $request, School $school, StudentInvoice $studentInvoice): JsonResponse
    {
        $this->authorizeFinance($request, $school);
        abort_unless($studentInvoice->school_id === $school->id, 404);
        $validated = $request->validate([
            'due_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'status' => ['nullable', Rule::in(['unpaid', 'partial', 'paid', 'voided'])],
        ]);
        $studentInvoice->update($validated);

        return response()->json(['data' => $studentInvoice->fresh()->load($this->invoiceService->relations())]);
    }

    public function destroy(Request $request, School $school, StudentInvoice $studentInvoice): JsonResponse
    {
        $this->authorizeFinance($request, $school);
        abort_unless($studentInvoice->school_id === $school->id, 404);
        $studentInvoice->update(['status' => 'voided']);
        $studentInvoice->delete();

        return response()->json(status: 204);
    }

    public function bulkGenerate(Request $request, School $school): JsonResponse
    {
        $this->authorizeFinance($request, $school);
        $validated = $request->validate([
            'academic_class_id' => ['required', 'integer', Rule::exists('academic_classes', 'id')->where('school_id', $school->id)],
            'academic_year_id' => ['required', 'integer', Rule::exists('academic_years', 'id')->where('school_id', $school->id)],
            'month' => ['required', 'date_format:Y-m'],
            'fee_structure_ids' => ['required', 'array', 'min:1'],
            'fee_structure_ids.*' => ['integer', Rule::exists('fee_structures', 'id')->where('school_id', $school->id)],
        ]);

        $jobId = (string) Str::ulid();
        BulkGenerateStudentInvoices::dispatch($school->id, $jobId, $validated);

        return response()->json(['data' => ['job_id' => $jobId, 'status' => 'queued']], 202);
    }

    private function authorizeFinance(Request $request, School $school): void
    {
        abort_unless($request->user()->hasSchoolPermission($school, 'finance.manage'), 403);
    }

    private function validatedPayload(Request $request, School $school): array
    {
        return $request->validate([
            'student_enrollment_id' => ['required', 'integer', Rule::exists('student_enrollments', 'id')->where('school_id', $school->id)],
            'academic_year_id' => ['required', 'integer', Rule::exists('academic_years', 'id')->where('school_id', $school->id)],
            'invoice_no' => ['nullable', 'string', 'max:80', Rule::unique('student_invoices')->where('school_id', $school->id)],
            'fee_month' => ['nullable', 'date_format:Y-m'],
            'fee_structure_ids' => ['required', 'array', 'min:1'],
            'fee_structure_ids.*' => ['integer', Rule::exists('fee_structures', 'id')->where('school_id', $school->id)],
            'due_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);
    }
}
