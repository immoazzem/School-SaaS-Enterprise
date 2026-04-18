<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class StudentInvoice extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['school_id', 'student_enrollment_id', 'academic_year_id', 'invoice_no', 'fee_month', 'subtotal', 'discount', 'discount_breakdown', 'total', 'paid_amount', 'due_date', 'status', 'notes'];

    protected function casts(): array
    {
        return ['subtotal' => 'decimal:2', 'discount' => 'decimal:2', 'discount_breakdown' => 'array', 'total' => 'decimal:2', 'paid_amount' => 'decimal:2', 'due_date' => 'date:Y-m-d'];
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function studentEnrollment(): BelongsTo
    {
        return $this->belongsTo(StudentEnrollment::class);
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(InvoicePayment::class);
    }
}
