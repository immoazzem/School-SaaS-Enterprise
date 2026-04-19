<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Invoice Receipt</title>
    <style>
        body { color: #111827; font-family: DejaVu Sans, sans-serif; font-size: 12px; line-height: 1.5; }
        h1 { font-size: 22px; margin: 0; text-align: center; }
        h2 { color: #374151; font-size: 15px; margin: 4px 0 18px; text-align: center; }
        h3 { font-size: 13px; margin: 18px 0 8px; }
        table { border-collapse: collapse; width: 100%; }
        th { background: #f3f4f6; font-weight: bold; }
        th, td { border: 1px solid #d1d5db; padding: 7px 8px; text-align: left; vertical-align: top; }
        .header { border-bottom: 2px solid #111827; margin-bottom: 16px; padding-bottom: 12px; }
        .muted { color: #6b7280; }
        .meta { margin-bottom: 14px; }
        .meta td { border: 0; padding: 2px 6px 2px 0; }
        .label { color: #374151; font-weight: bold; width: 120px; }
        .amount { text-align: right; }
        .total td { background: #f9fafb; font-weight: bold; }
        .footer { border-top: 1px solid #d1d5db; color: #6b7280; font-size: 10px; margin-top: 28px; padding-top: 8px; text-align: right; }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ $school->name }}</h1>
        <h2>Invoice Receipt</h2>
        <div class="muted">Generated {{ $generatedAt }}@if ($requestedBy) by {{ $requestedBy }}@endif / Job {{ $jobId }}</div>
    </div>

    <table class="meta">
        <tr>
            <td class="label">Invoice No</td>
            <td>{{ $invoice->invoice_no }}</td>
            <td class="label">Status</td>
            <td>{{ strtoupper($invoice->status) }}</td>
        </tr>
        <tr>
            <td class="label">Student</td>
            <td>{{ $invoice->studentEnrollment?->student?->full_name ?? 'N/A' }}</td>
            <td class="label">Admission No</td>
            <td>{{ $invoice->studentEnrollment?->student?->admission_no ?? 'N/A' }}</td>
        </tr>
        <tr>
            <td class="label">Class</td>
            <td>{{ $invoice->studentEnrollment?->academicClass?->name ?? 'N/A' }}</td>
            <td class="label">Academic Year</td>
            <td>{{ $invoice->academicYear?->name ?? 'N/A' }}</td>
        </tr>
        <tr>
            <td class="label">Fee Month</td>
            <td>{{ $invoice->fee_month ?? 'N/A' }}</td>
            <td class="label">Due Date</td>
            <td>{{ $invoice->due_date?->toDateString() ?? 'N/A' }}</td>
        </tr>
    </table>

    <h3>Invoice Summary</h3>
    <table>
        <tbody>
            <tr>
                <td>Subtotal</td>
                <td class="amount">{{ number_format((float) $invoice->subtotal, 2) }}</td>
            </tr>
            <tr>
                <td>Discount</td>
                <td class="amount">-{{ number_format((float) $invoice->discount, 2) }}</td>
            </tr>
            <tr class="total">
                <td>Total</td>
                <td class="amount">{{ number_format((float) $invoice->total, 2) }}</td>
            </tr>
            <tr>
                <td>Paid Amount</td>
                <td class="amount">{{ number_format((float) $invoice->paid_amount, 2) }}</td>
            </tr>
            <tr>
                <td>Balance</td>
                <td class="amount">{{ number_format(max(0, (float) $invoice->total - (float) $invoice->paid_amount), 2) }}</td>
            </tr>
        </tbody>
    </table>

    <h3>Payment History</h3>
    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Paid On</th>
                <th>Method</th>
                <th>Reference</th>
                <th class="amount">Amount</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($payments as $index => $payment)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $payment->paid_on?->toDateString() ?? 'N/A' }}</td>
                    <td>{{ str($payment->payment_method)->headline() }}</td>
                    <td>{{ $payment->transaction_ref ?? 'N/A' }}</td>
                    <td class="amount">{{ number_format((float) $payment->amount, 2) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" style="text-align: center;">No payments recorded.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">Official school copy / {{ $school->name }}</div>
</body>
</html>
