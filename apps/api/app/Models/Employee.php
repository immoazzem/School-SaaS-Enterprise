<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Employee extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'school_id',
        'designation_id',
        'employee_no',
        'full_name',
        'name_bn',
        'father_name',
        'mother_name',
        'email',
        'phone',
        'gender',
        'religion',
        'date_of_birth',
        'joined_on',
        'salary',
        'employee_type',
        'address',
        'notes',
        'status',
    ];

    /**
     * @var list<string>
     */
    protected $appends = [
        'display_name',
    ];

    protected function casts(): array
    {
        return [
            'date_of_birth' => 'date',
            'joined_on' => 'date',
            'salary' => 'decimal:2',
        ];
    }

    /**
     * @return Attribute<string, never>
     */
    protected function displayName(): Attribute
    {
        return Attribute::get(fn (): string => app()->getLocale() === 'bn' && filled($this->name_bn)
            ? $this->name_bn
            : $this->full_name);
    }

    /**
     * @return BelongsTo<School, $this>
     */
    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    /**
     * @return BelongsTo<Designation, $this>
     */
    public function designation(): BelongsTo
    {
        return $this->belongsTo(Designation::class);
    }

    /**
     * @return HasOne<TeacherProfile, $this>
     */
    public function teacherProfile(): HasOne
    {
        return $this->hasOne(TeacherProfile::class);
    }
}
