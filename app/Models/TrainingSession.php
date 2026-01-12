<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class TrainingSession extends Model
{
    use HasFactory;

    protected $table = 'training_sessions';

    protected $fillable = [
        'course_id',
        'title',
        'start_at',
        'end_at',
        'min_participants',
        'capacity',
        'registration_start',
        'registration_end',
        'mode',
        'online_link',
        'trainer_id',
        'location',
        'status',
    ];

    protected $casts = [
        'start_at' => 'datetime',
        'end_at' => 'datetime',
        'registration_start' => 'datetime',
        'registration_end' => 'datetime',
    ];

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function trainer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'trainer_id');
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class, 'session_id');
    }

    public function certificateRequests(): HasMany
    {
        return $this->hasMany(CertificateRequest::class, 'session_id');
    }

    public function certificates(): HasMany
    {
        return $this->hasMany(Certificate::class, 'session_id');
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class, 'session_id');
    }

    public function certificateTemplates(): HasMany
    {
        return $this->hasMany(CertificateTemplate::class, 'session_id');
    }

    public function activeCertificateTemplate(): HasOne
    {
        return $this->hasOne(CertificateTemplate::class, 'session_id')
            ->where('is_active', true)
            ->latestOfMany();
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class, 'session_id');
    }

    public function sessionDays(): HasMany
    {
        return $this->hasMany(SessionDay::class, 'session_id')->orderBy('day_number');
    }

    /**
     * Check if this session spans multiple days
     */
    public function isMultiDay(): bool
    {
        if (!$this->start_at || !$this->end_at) {
            return false;
        }

        return $this->start_at->diffInDays($this->end_at) > 0;
    }

    /**
     * Get the total number of session days
     */
    public function getTotalDaysAttribute(): int
    {
        $sessionDaysCount = $this->sessionDays()->count();

        // Return count from session_days if exists, otherwise calculate from dates
        if ($sessionDaysCount > 0) {
            return $sessionDaysCount;
        }

        // Fallback calculation if session_days not populated
        if (!$this->start_at || !$this->end_at) {
            return 1;
        }

        $start = $this->start_at->startOfDay();
        $end = $this->end_at->startOfDay();

        if ($end->lessThan($start)) {
            return 1;
        }

        return $start->diffInDays($end) + 1;
    }
}
