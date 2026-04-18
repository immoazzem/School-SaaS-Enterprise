<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class StudentDiscount extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['school_id', 'student_enrollment_id', 'discount_policy_id', 'academic_year_id', 'approved_by', 'notes'];

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function studentEnrollment(): BelongsTo
    {
        return $this->belongsTo(StudentEnrollment::class);
    }

    public function discountPolicy(): BelongsTo
    {
        return $this->belongsTo(DiscountPolicy::class);
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
