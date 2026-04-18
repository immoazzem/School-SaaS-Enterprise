<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ResultSummary extends Model
{
    use HasFactory;

    protected $fillable = [
        'school_id',
        'exam_id',
        'student_enrollment_id',
        'total_marks_obtained',
        'total_full_marks',
        'percentage',
        'gpa',
        'grade',
        'position_in_class',
        'is_pass',
        'computed_at',
    ];

    protected function casts(): array
    {
        return [
            'total_marks_obtained' => 'decimal:2',
            'total_full_marks' => 'decimal:2',
            'percentage' => 'decimal:2',
            'gpa' => 'decimal:2',
            'is_pass' => 'boolean',
            'computed_at' => 'datetime',
        ];
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function exam(): BelongsTo
    {
        return $this->belongsTo(Exam::class);
    }

    public function studentEnrollment(): BelongsTo
    {
        return $this->belongsTo(StudentEnrollment::class);
    }
}
