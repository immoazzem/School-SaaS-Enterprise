<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\InvoicePayment;
use App\Models\School;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class InvoicePaymentController extends Controller
{
    public function index(Request $request, School $school): JsonResponse
    {
        $this->authorizeFinance($request, $school);

        return response()->json($this->paginated($school->invoicePayments()->with($this->relations())->orderByDesc('paid_on')->paginate($this->perPage($request))));
    }

    public function store(Request $request, School $school): JsonResponse
    {
        $this->authorizeFinance($request, $school);
        $validated = $this->validatedPayload($request, $school);
        $payment = DB::transaction(function () use ($school, $validated): InvoicePayment {
            $payment = $school->invoicePayments()->create($validated);
            $invoice = $payment->studentInvoice;
            $paid = (float) $invoice->paid_amount + (float) $payment->amount;
            $invoice->update([
                'paid_amount' => $paid,
                'status' => $paid >= (float) $invoice->total ? 'paid' : 'partial',
            ]);

            return $payment->load($this->relations());
        });

        return response()->json(['data' => $payment], 201);
    }

    public function show(Request $request, School $school, InvoicePayment $invoicePayment): JsonResponse
    {
        $this->authorizeFinance($request, $school);
        abort_unless($invoicePayment->school_id === $school->id, 404);

        return response()->json(['data' => $invoicePayment->load($this->relations())]);
    }

    public function update(Request $request, School $school, InvoicePayment $invoicePayment): JsonResponse
    {
        $this->authorizeFinance($request, $school);
        abort_unless($invoicePayment->school_id === $school->id, 404);
        $invoicePayment->update($this->validatedPayload($request, $school, $invoicePayment));

        return response()->json(['data' => $invoicePayment->fresh()->load($this->relations())]);
    }

    public function destroy(Request $request, School $school, InvoicePayment $invoicePayment): JsonResponse
    {
        $this->authorizeFinance($request, $school);
        abort_unless($invoicePayment->school_id === $school->id, 404);
        $invoicePayment->delete();

        return response()->json(status: 204);
    }

    private function authorizeFinance(Request $request, School $school): void
    {
        abort_unless($request->user()->hasSchoolPermission($school, 'finance.manage'), 403);
    }

    private function validatedPayload(Request $request, School $school, ?InvoicePayment $payment = null): array
    {
        return $request->validate([
            'student_invoice_id' => [$payment ? 'sometimes' : 'required', 'integer', Rule::exists('student_invoices', 'id')->where('school_id', $school->id)],
            'amount' => [$payment ? 'sometimes' : 'required', 'numeric', 'min:0.01'],
            'paid_on' => [$payment ? 'sometimes' : 'required', 'date'],
            'payment_method' => [$payment ? 'sometimes' : 'required', Rule::in(['cash', 'bkash', 'nagad', 'rocket', 'bank_transfer', 'cheque', 'card', 'other'])],
            'transaction_ref' => ['required_if:payment_method,bkash,nagad,rocket', 'nullable', 'string', 'max:120'],
            'payment_channel_metadata' => ['nullable', 'array'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);
    }

    private function relations(): array
    {
        return ['studentInvoice:id,invoice_no,total,paid_amount,status,student_enrollment_id', 'studentInvoice.studentEnrollment.student:id,full_name,admission_no'];
    }
}
