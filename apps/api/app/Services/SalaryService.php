<?php

namespace App\Services;

use App\Models\SalaryRecord;
use App\Models\School;
use Illuminate\Support\Facades\DB;

class SalaryService
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function create(School $school, array $payload): SalaryRecord
    {
        return DB::transaction(function () use ($school, $payload): SalaryRecord {
            return $school->salaryRecords()->create($this->withTotals($payload));
        });
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function update(SalaryRecord $salaryRecord, array $payload): SalaryRecord
    {
        return DB::transaction(function () use ($salaryRecord, $payload): SalaryRecord {
            $salaryRecord->update($this->withTotals([
                ...$salaryRecord->only(['basic_amount', 'allowances', 'deductions']),
                ...$payload,
            ]));

            return $salaryRecord->fresh();
        });
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function withTotals(array $payload): array
    {
        $allowances = $payload['allowances'] ?? [];
        $deductions = $payload['deductions'] ?? [];
        $basicAmount = (float) ($payload['basic_amount'] ?? 0);
        $grossAmount = $basicAmount + $this->sumAmounts($allowances);
        $totalDeductions = $this->sumAmounts($deductions);

        return [
            ...$payload,
            'allowances' => $allowances,
            'gross_amount' => $grossAmount,
            'deductions' => $deductions,
            'total_deductions' => $totalDeductions,
            'net_amount' => $grossAmount - $totalDeductions,
        ];
    }

    /**
     * @param  mixed  $amounts
     */
    private function sumAmounts($amounts): float
    {
        if (! is_array($amounts)) {
            return 0.0;
        }

        return (float) collect($amounts)
            ->map(fn ($amount): float => is_numeric($amount) ? (float) $amount : 0.0)
            ->sum();
    }
}
