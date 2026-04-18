<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class StudentAttendanceRecord extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'school_id',
        'student_enrollment_id',
        'attendance_date',
        'status',
        'late_arrival_time',
        'half_day',
        'leave_reference',
        'remarks',
    ];

    protected function casts(): array
    {
        return [
            'attendance_date' => 'date:Y-m-d',
            'half_day' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<School, $this>
     */
    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    /**
     * @return BelongsTo<StudentEnrollment, $this>
     */
    public function studentEnrollment(): BelongsTo
    {
        return $this->belongsTo(StudentEnrollment::class);
    }
}
