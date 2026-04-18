<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class LeaveType extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['school_id', 'name', 'code', 'max_days_per_year', 'is_paid', 'requires_approval', 'status'];

    protected function casts(): array
    {
        return ['is_paid' => 'boolean', 'requires_approval' => 'boolean'];
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }
}
