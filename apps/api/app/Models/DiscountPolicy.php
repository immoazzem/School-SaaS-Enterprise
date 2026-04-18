<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class DiscountPolicy extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['school_id', 'name', 'code', 'discount_type', 'amount', 'applies_to_category_ids', 'is_stackable', 'status'];

    protected function casts(): array
    {
        return ['amount' => 'decimal:2', 'applies_to_category_ids' => 'array', 'is_stackable' => 'boolean'];
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }
}
