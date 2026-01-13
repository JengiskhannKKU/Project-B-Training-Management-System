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

    /**
     * The accessors to append to the model's array form.
     *
     * @var array<int, string>
     */
    protected $appends = ['type'];

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

    /**
     * Check if user has the given role or a superior role (admin = superuser)
     */
    public function hasRoleOrHigher(string $role): bool
    {
        if (!$this->role) {
            return false;
        }

        // Admin has all permissions
        if ($this->role->name === 'admin') {
            return true;
        }

        // Match exact role
        return $this->role->name === $role;
    }

    public function createdCourses(): HasMany
    {
        return $this->hasMany(Course::class, 'owner_id');
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

    /**
     * Get the user type (Internal or External) based on profile data.
     */
    public function getTypeAttribute(): string
    {
        $profile = $this->profile;

        if (!$profile) {
            return 'Internal';
        }

        // If user has sub_category (Student or Personnel), they are Internal
        if (!empty($profile->sub_category)) {
            return 'Internal';
        }

        // If user has category, they are External
        if (!empty($profile->category)) {
            return 'External';
        }

        // Default to Internal
        return 'Internal';
    }
}
