<?php

namespace App\Services;

use App\Models\FeeStructure;
use App\Models\School;
use App\Models\StudentInvoice;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class InvoiceService
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function create(School $school, array $payload): StudentInvoice
    {
        return DB::transaction(function () use ($school, $payload): StudentInvoice {
            $feeStructures = FeeStructure::query()
                ->where('school_id', $school->id)
                ->whereIn('id', $payload['fee_structure_ids'])
                ->with('feeCategory:id,name,code')
                ->get();

            $subtotal = (float) $feeStructures->sum('amount');
            $discount = $this->discountFor(
                $school,
                (int) $payload['student_enrollment_id'],
                (int) $payload['academic_year_id'],
                $feeStructures
            );

            $invoice = $school->studentInvoices()->create([
                'student_enrollment_id' => $payload['student_enrollment_id'],
                'academic_year_id' => $payload['academic_year_id'],
                'invoice_no' => $payload['invoice_no'] ?? $this->nextInvoiceNo($school, (int) $payload['academic_year_id']),
                'fee_month' => $payload['fee_month'] ?? null,
                'subtotal' => $subtotal,
                'discount' => $discount['total'],
                'discount_breakdown' => $discount['breakdown'],
                'total' => max(0, $subtotal - $discount['total']),
                'paid_amount' => 0,
                'due_date' => $payload['due_date'] ?? null,
                'status' => 'unpaid',
                'notes' => $payload['notes'] ?? null,
            ]);

            return $invoice->load($this->relations());
        });
    }

    /**
     * @param  Collection<int, FeeStructure>  $feeStructures
     * @return array{total: float, breakdown: list<array<string, mixed>>}
     */
    private function discountFor(School $school, int $studentEnrollmentId, int $academicYearId, $feeStructures): array
    {
        $subtotal = (float) $feeStructures->sum('amount');
        $categoryTotals = $feeStructures
            ->groupBy('fee_category_id')
            ->map(fn ($items): float => (float) $items->sum('amount'));

        $discounts = $school->studentDiscounts()
            ->where('student_enrollment_id', $studentEnrollmentId)
            ->where('academic_year_id', $academicYearId)
            ->with('discountPolicy')
            ->get()
            ->filter(fn ($discount): bool => $discount->discountPolicy?->status === 'active');

        $breakdown = [];
        $total = 0.0;

        foreach ($discounts as $discount) {
            $policy = $discount->discountPolicy;
            $appliesTo = $policy->applies_to_category_ids;
            $applicableSubtotal = $appliesTo
                ? (float) collect($appliesTo)->sum(fn ($categoryId): float => (float) ($categoryTotals[$categoryId] ?? 0))
                : $subtotal;

            if ($applicableSubtotal <= 0) {
                continue;
            }

            $amount = $policy->discount_type === 'percent'
                ? $applicableSubtotal * ((float) $policy->amount / 100)
                : min((float) $policy->amount, $applicableSubtotal);

            $total += $amount;
            $breakdown[] = [
                'policy_id' => $policy->id,
                'code' => $policy->code,
                'name' => $policy->name,
                'amount' => round($amount, 2),
            ];

            if (! $policy->is_stackable) {
                break;
            }
        }

        return ['total' => round(min($total, $subtotal), 2), 'breakdown' => $breakdown];
    }

    private function nextInvoiceNo(School $school, int $academicYearId): string
    {
        $academicYear = $school->academicYears()->findOrFail($academicYearId);
        $prefix = strtoupper(Str::substr(preg_replace('/[^A-Za-z0-9]/', '', $school->slug ?: $school->name), 0, 3) ?: 'SCH');
        $start = $academicYear->starts_on?->format('y') ?? now()->format('y');
        $end = $academicYear->ends_on?->format('y') ?? now()->addYear()->format('y');
        $sequence = $school->studentInvoices()->withTrashed()->count() + 1;

        return sprintf('%s/%s-%s/%06d', $prefix, $start, $end, $sequence);
    }

    /**
     * @return list<string>
     */
    public function relations(): array
    {
        return [
            'studentEnrollment:id,student_id,academic_class_id,roll_no',
            'studentEnrollment.student:id,full_name,admission_no',
            'studentEnrollment.academicClass:id,name,code',
            'academicYear:id,name,starts_on,ends_on',
            'payments:id,student_invoice_id,amount,paid_on,payment_method,transaction_ref',
        ];
    }
}
