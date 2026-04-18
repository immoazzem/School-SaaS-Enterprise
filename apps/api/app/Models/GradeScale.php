<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class GradeScale extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['school_id', 'name', 'code', 'min_percent', 'max_percent', 'grade_point', 'fail_below_percent', 'gpa_calculation_method', 'status'];

    protected function casts(): array
    {
        return [
            'min_percent' => 'decimal:2',
            'max_percent' => 'decimal:2',
            'grade_point' => 'decimal:2',
            'fail_below_percent' => 'decimal:2',
        ];
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }
}
