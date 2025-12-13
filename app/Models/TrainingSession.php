<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TrainingSession extends Model
{
    use HasFactory;

    protected $table = 'training_sessions';

    protected $fillable = [
        'program_id',
        'title',
        'start_date',
        'end_date',
        'start_time',
        'end_time',
        'capacity',
        'trainer_id',
        'location',
        'status',
        'approval_status',
        'approved_by',
        'approved_at',
        'approval_note',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'approved_at' => 'datetime',
    ];

    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }

    public function trainer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'trainer_id');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
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
}
