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

    public function activeEnrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class, 'session_id')
            ->whereIn('status', ['pending', 'confirmed', 'completed']);
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
        return $this->sessionDays()->count() > 1;
    }

    /**
     * Get the total number of session days
     */
    public function getTotalDaysAttribute(): int
    {
        return $this->sessionDays()->count();
    }

    /**
     * Get the earliest session date (for display purposes)
     */
    public function getStartDateAttribute(): ?Carbon
    {
        $firstDay = $this->sessionDays()->orderBy('date')->first();
        return $firstDay ? $firstDay->date : null;
    }

    /**
     * Get the latest session date (for display purposes)
     */
    public function getEndDateAttribute(): ?Carbon
    {
        $lastDay = $this->sessionDays()->orderBy('date', 'desc')->first();
        return $lastDay ? $lastDay->date : null;
    }

    /**
     * Check if the trainer is already booked at a specific date and time
     */
    public static function trainerHasConflict($trainerId, $date, $startTime, $endTime, $excludeSessionId = null): bool
    {
        $query = static::where('trainer_id', $trainerId)
            ->whereHas('sessionDays', function ($q) use ($date, $startTime, $endTime) {
                $q->where('date', $date)
                    ->where(function ($query) use ($startTime, $endTime) {
                        // Check for time overlap
                        $query->where(function ($q) use ($startTime, $endTime) {
                            // New session starts during existing session
                            $q->where('start_time', '<=', $startTime)
                              ->where('end_time', '>', $startTime);
                        })->orWhere(function ($q) use ($startTime, $endTime) {
                            // New session ends during existing session
                            $q->where('start_time', '<', $endTime)
                              ->where('end_time', '>=', $endTime);
                        })->orWhere(function ($q) use ($startTime, $endTime) {
                            // New session completely covers existing session
                            $q->where('start_time', '>=', $startTime)
                              ->where('end_time', '<=', $endTime);
                        });
                    });
            });

        if ($excludeSessionId) {
            $query->where('id', '!=', $excludeSessionId);
        }

        return $query->exists();
    }

    /**
     * Check if the location is already booked at a specific date and time
     */
    public static function locationHasConflict($location, $date, $startTime, $endTime, $excludeSessionId = null): bool
    {
        if (empty($location)) {
            return false;
        }

        $query = static::where('location', $location)
            ->whereHas('sessionDays', function ($q) use ($date, $startTime, $endTime) {
                $q->where('date', $date)
                    ->where(function ($query) use ($startTime, $endTime) {
                        $query->where(function ($q) use ($startTime, $endTime) {
                            $q->where('start_time', '<=', $startTime)
                              ->where('end_time', '>', $startTime);
                        })->orWhere(function ($q) use ($startTime, $endTime) {
                            $q->where('start_time', '<', $endTime)
                              ->where('end_time', '>=', $endTime);
                        })->orWhere(function ($q) use ($startTime, $endTime) {
                            $q->where('start_time', '>=', $startTime)
                              ->where('end_time', '<=', $endTime);
                        });
                    });
            });

        if ($excludeSessionId) {
            $query->where('id', '!=', $excludeSessionId);
        }

        return $query->exists();
    }
}
