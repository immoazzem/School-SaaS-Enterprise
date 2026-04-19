<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DataExportJob extends Model
{
    use HasFactory;

    protected $fillable = [
        'school_id',
        'requested_by',
        'job_id',
        'status',
        'parameters',
        'file_path',
        'file_name',
        'completed_at',
        'failure_reason',
    ];

    protected function casts(): array
    {
        return [
            'completed_at' => 'datetime',
            'parameters' => 'array',
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
}
