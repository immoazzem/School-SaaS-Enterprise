<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class LeaveBalance extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['school_id', 'employee_id', 'leave_type_id', 'academic_year_id', 'total_days', 'used_days', 'remaining_days'];

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function leaveType(): BelongsTo
    {
        return $this->belongsTo(LeaveType::class);
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }
}
