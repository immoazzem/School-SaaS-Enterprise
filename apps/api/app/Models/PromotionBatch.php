<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PromotionBatch extends Model
{
    use HasFactory;

    protected $fillable = [
        'school_id',
        'from_academic_year_id',
        'to_academic_year_id',
        'from_academic_class_id',
        'to_academic_class_id',
        'status',
        'processed_count',
        'created_by',
        'processed_at',
    ];

    protected function casts(): array
    {
        return [
            'processed_at' => 'datetime',
        ];
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function records(): HasMany
    {
        return $this->hasMany(PromotionRecord::class);
    }
}
