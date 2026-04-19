<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ReportExport extends Model
{
    use HasFactory;

    protected $fillable = [
        'job_id',
        'school_id',
        'requested_by',
        'type',
        'status',
        'target_type',
        'target_id',
        'parameters',
        'file_path',
        'file_name',
        'completed_at',
        'error',
    ];

    protected $hidden = ['file_path'];

    protected function casts(): array
    {
        return [
            'parameters' => 'array',
            'completed_at' => 'datetime',
        ];
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function target(): MorphTo
    {
        return $this->morphTo();
    }
}
