<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Course extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'category',
        'level',
        'learning_outcomes',
        'target_audience',
        'prerequisites',
        'additional_info',
        'thumbnail_path',
        'status',
        'owner_id',
    ];

    protected $appends = ['is_complete', 'is_incomplete'];

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(TrainingSession::class);
    }

    public function getIsCompleteAttribute(): bool
    {
        return $this->status === 'published' && $this->sessions()->count() >= 1;
    }

    public function getIsIncompleteAttribute(): bool
    {
        return !$this->is_complete;
    }
}
