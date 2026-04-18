<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class LeaveApplication extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['school_id', 'employee_id', 'leave_type_id', 'from_date', 'to_date', 'total_days', 'reason', 'status', 'applied_at', 'reviewed_by', 'reviewed_at', 'review_note'];

    protected function casts(): array
    {
        return ['from_date' => 'date:Y-m-d', 'to_date' => 'date:Y-m-d', 'applied_at' => 'datetime', 'reviewed_at' => 'datetime'];
    }

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

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
