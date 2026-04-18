<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class FeeStructure extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['school_id', 'fee_category_id', 'academic_year_id', 'academic_class_id', 'student_group_id', 'amount', 'due_day_of_month', 'months_applicable', 'is_recurring', 'status'];

    protected function casts(): array
    {
        return ['amount' => 'decimal:2', 'months_applicable' => 'array', 'is_recurring' => 'boolean'];
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function feeCategory(): BelongsTo
    {
        return $this->belongsTo(FeeCategory::class);
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function academicClass(): BelongsTo
    {
        return $this->belongsTo(AcademicClass::class);
    }

    public function studentGroup(): BelongsTo
    {
        return $this->belongsTo(StudentGroup::class);
    }
}
