<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CertificateTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'scope',
        'course_id',
        'session_id',
        'background_image',
        'background_mime_type',
        'layout_config',
        'font_family',
        'font_size',
        'text_color',
        'is_active',
    ];

    /**
     * Attributes that should be hidden from JSON serialization.
     * Binary background_image field cannot be JSON encoded.
     */
    protected $hidden = [
        'background_image',
    ];

    protected $casts = [
        'layout_config' => 'array',
        'is_active' => 'boolean',
    ];

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(TrainingSession::class, 'session_id');
    }

    public function certificates(): HasMany
    {
        return $this->hasMany(Certificate::class, 'template_id');
    }
}
