<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class MarksEntry extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['school_id', 'exam_id', 'class_subject_id', 'student_enrollment_id', 'marks_obtained', 'full_marks', 'pass_marks', 'is_absent', 'absent_reason', 'verification_status', 'entered_by', 'verified_by', 'verified_at', 'voided', 'voided_at', 'voided_by', 'void_reason', 'remarks'];

    protected function casts(): array
    {
        return [
            'marks_obtained' => 'decimal:2',
            'is_absent' => 'boolean',
            'verified_at' => 'datetime',
            'voided' => 'boolean',
            'voided_at' => 'datetime',
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

    public function classSubject(): BelongsTo
    {
        return $this->belongsTo(ClassSubject::class);
    }

    public function studentEnrollment(): BelongsTo
    {
        return $this->belongsTo(StudentEnrollment::class);
    }

    public function enteredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'entered_by');
    }

    public function verifiedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }
}
