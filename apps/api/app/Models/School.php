<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class School extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'status',
        'locale',
        'timezone',
        'settings',
    ];

    protected function casts(): array
    {
        return [
            'settings' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (School $school): void {
            $school->public_id ??= (string) Str::ulid();
            $school->slug = Str::slug($school->slug ?: $school->name);
        });
    }

    /**
     * @return HasMany<SchoolMembership, $this>
     */
    public function memberships(): HasMany
    {
        return $this->hasMany(SchoolMembership::class);
    }

    /**
     * @return BelongsToMany<User, $this>
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'school_memberships')
            ->withPivot(['status', 'joined_at'])
            ->withTimestamps();
    }

    /**
     * @return HasMany<AcademicClass, $this>
     */
    public function academicClasses(): HasMany
    {
        return $this->hasMany(AcademicClass::class);
    }

    /**
     * @return HasMany<AcademicSection, $this>
     */
    public function academicSections(): HasMany
    {
        return $this->hasMany(AcademicSection::class);
    }
}
