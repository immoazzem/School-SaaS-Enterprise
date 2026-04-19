<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class PaymentGatewayConfig extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'school_id',
        'gateway',
        'credentials_encrypted',
        'is_active',
        'test_mode',
    ];

    protected $hidden = ['credentials_encrypted'];

    protected $appends = ['credentials_configured', 'credential_keys'];

    protected function casts(): array
    {
        return [
            'credentials_encrypted' => 'encrypted:array',
            'is_active' => 'boolean',
            'test_mode' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<School, $this>
     */
    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    /**
     * @return Attribute<bool, never>
     */
    protected function credentialsConfigured(): Attribute
    {
        return Attribute::get(fn (): bool => ! empty($this->credentials_encrypted));
    }

    /**
     * @return Attribute<array<int, string>, never>
     */
    protected function credentialKeys(): Attribute
    {
        return Attribute::get(fn (): array => collect($this->credentials_encrypted ?? [])
            ->keys()
            ->sort()
            ->values()
            ->all());
    }
}
