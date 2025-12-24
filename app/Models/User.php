<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role_id',
        'email_verified_at',
        'status',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    public function profile(): HasOne
    {
        return $this->hasOne(Profile::class);
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function isRole(string $role): bool
    {
        return $this->role && $this->role->name === $role;
    }

    public function createdPrograms(): HasMany
    {
        return $this->hasMany(Program::class, 'created_by');
    }

    public function approvedPrograms(): HasMany
    {
        return $this->hasMany(Program::class, 'approved_by');
    }

    public function trainingSessions(): HasMany
    {
        return $this->hasMany(TrainingSession::class, 'trainer_id');
    }

    public function approvedSessions(): HasMany
    {
        return $this->hasMany(TrainingSession::class, 'approved_by');
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class, 'user_id');
    }

    public function certificateRequests(): HasMany
    {
        return $this->hasMany(CertificateRequest::class, 'trainer_id');
    }

    public function approvedCertificateRequests(): HasMany
    {
        return $this->hasMany(CertificateRequest::class, 'approved_by');
    }

    public function certificates(): HasMany
    {
        return $this->hasMany(Certificate::class, 'user_id');
    }

    public function issuedCertificates(): HasMany
    {
        return $this->hasMany(Certificate::class, 'issued_by');
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class, 'checked_by');
    }
}
