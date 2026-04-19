<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class SchoolDocument extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'school_id',
        'uploader_id',
        'category',
        'title',
        'file_path',
        'file_name',
        'file_size_bytes',
        'mime_type',
        'is_public',
        'related_model_type',
        'related_model_id',
        'uploaded_at',
    ];

    protected $hidden = ['file_path'];

    protected function casts(): array
    {
        return [
            'is_public' => 'boolean',
            'uploaded_at' => 'datetime',
        ];
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploader_id');
    }

    public function relatedModel(): MorphTo
    {
        return $this->morphTo();
    }
}
