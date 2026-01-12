<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Course extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'title',
        'description',
        'category_id',
        'level',
        'learning_outcomes',
        'target_audience',
        'prerequisites',
        'additional_info',
        'thumbnail_path',
        'status',
        'owner_id',
    ];

    protected $appends = ['is_complete', 'is_incomplete', 'sessions_count', 'enrolled_count'];

    /**
     * Get the route key for the model.
     */
    public function getRouteKeyName()
    {
        return 'code';
    }

    /**
     * Boot method to auto-generate course code
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($course) {
            if (empty($course->code)) {
                $course->code = static::generateCourseCode();
            }
        });
    }

    /**
     * Generate a unique course code
     */
    public static function generateCourseCode(): string
    {
        do {
            $code = 'CRS-' . Str::upper(Str::random(8));
        } while (static::where('code', $code)->exists());

        return $code;
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(TrainingSession::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function getIsCompleteAttribute(): bool
    {
        return $this->status === 'published' && $this->sessions()->count() >= 1;
    }

    public function getIsIncompleteAttribute(): bool
    {
        return !$this->is_complete;
    }

    public function getSessionsCountAttribute(): int
    {
        return $this->sessions()->count();
    }

    public function getEnrolledCountAttribute(): int
    {
        return $this->sessions()
            ->withCount([
                'enrollments' => function ($query) {
                    $query->whereIn('status', ['pending', 'confirmed', 'completed']);
                }
            ])
            ->get()
            ->sum('enrollments_count');
    }
}
