<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class EmployeeAttendanceRecord extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['school_id', 'employee_id', 'date', 'status', 'check_in_time', 'check_out_time', 'notes', 'recorded_by'];

    protected function casts(): array
    {
        return ['date' => 'date:Y-m-d'];
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}
