<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Carbon\Carbon;

class SessionDay extends Model
{
    use HasFactory;

    protected $fillable = [
        'session_id',
        'date',
        'start_time',
        'end_time',
        'day_number',
    ];

    protected $casts = [
        'date' => 'date',
    ];

    /**
     * Get the session that owns this day
     */
    public function session(): BelongsTo
    {
        return $this->belongsTo(TrainingSession::class, 'session_id');
    }

    /**
     * Get all attendance records for this session day
     */
    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class, 'session_day_id');
    }

    /**
     * Check if this session day has already occurred
     */
    public function hasOccurred(): bool
    {
        return $this->date->isPast();
    }

    /**
     * Check if this session day is today
     */
    public function isToday(): bool
    {
        return $this->date->isToday();
    }

    /**
     * Get the count of attendance records marked for this day
     */
    public function getAttendanceCountAttribute(): int
    {
        return $this->attendances()->count();
    }

    /**
     * Check if this day has any attendance marked
     */
    public function hasAttendanceMarked(): bool
    {
        return $this->attendances()->exists();
    }
}
