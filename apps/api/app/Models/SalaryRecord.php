<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class SalaryRecord extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['school_id', 'employee_id', 'academic_year_id', 'month', 'basic_amount', 'allowances', 'gross_amount', 'deductions', 'total_deductions', 'net_amount', 'paid_at', 'payment_method', 'transaction_ref', 'notes', 'status', 'voided_at', 'voided_by', 'void_reason'];

    protected function casts(): array
    {
        return ['basic_amount' => 'decimal:2', 'allowances' => 'array', 'gross_amount' => 'decimal:2', 'deductions' => 'array', 'total_deductions' => 'decimal:2', 'net_amount' => 'decimal:2', 'paid_at' => 'datetime', 'voided_at' => 'datetime'];
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }
}
