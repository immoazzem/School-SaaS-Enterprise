<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PromotionRecord extends Model
{
    use HasFactory;

    protected $fillable = [
        'school_id',
        'promotion_batch_id',
        'student_enrollment_id',
        'action',
        'new_enrollment_id',
        'notes',
        'processed_by',
    ];

    public function batch(): BelongsTo
    {
        return $this->belongsTo(PromotionBatch::class, 'promotion_batch_id');
    }

    public function studentEnrollment(): BelongsTo
    {
        return $this->belongsTo(StudentEnrollment::class);
    }

    public function newEnrollment(): BelongsTo
    {
        return $this->belongsTo(StudentEnrollment::class, 'new_enrollment_id');
    }
}
