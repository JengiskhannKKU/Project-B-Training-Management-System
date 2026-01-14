<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Certificate extends Model
{
    use HasFactory;

    protected $fillable = [
        'enrollment_id',
        'user_id',
        'course_id',
        'session_id',
        'description',
        'total_hours',
        'trainer_ids',
        'trainer_signatures',
        'issued_by',
        'issued_at',
        'certificate_code',
        'authorized_signatory_name',
        'authorized_signature_url',
        'organization_name',
        'organization_logo_url',
        'language',
        'score',
        'skills',
        'qr_code_url',
        'file_url',
        'file_data',
        'file_mime_type',
        'file_size',
        'generated_at',
        'status',
        'revoked_by',
        'revoked_at',
        'revoked_note',
    ];

    /**
     * Attributes that should be hidden from JSON serialization.
     * Binary fields (file_data) cannot be JSON encoded
     * and should only be accessed through dedicated download/view endpoints.
     */
    protected $hidden = [
        'file_data',
    ];

    protected $casts = [
        'trainer_ids' => 'array',
        'trainer_signatures' => 'array',
        'issued_at' => 'datetime',
        'generated_at' => 'datetime',
        'revoked_at' => 'datetime',
        'total_hours' => 'integer',
        'score' => 'decimal:2',
    ];

    protected $appends = [
        'verification_url',
        'is_valid',
    ];

    public function enrollment(): BelongsTo
    {
        return $this->belongsTo(Enrollment::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(TrainingSession::class, 'session_id');
    }

    public function issuer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'issued_by');
    }

    public function revoker(): BelongsTo
    {
        return $this->belongsTo(User::class, 'revoked_by');
    }

    public function verificationLogs(): HasMany
    {
        return $this->hasMany(CertificateVerificationLog::class);
    }

    /**
     * Get trainers associated with this certificate.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function trainers()
    {
        if (!$this->trainer_ids || empty($this->trainer_ids)) {
            return collect();
        }

        return User::whereIn('id', $this->trainer_ids)->get();
    }

    /**
     * Get the verification URL attribute.
     *
     * @return string
     */
    public function getVerificationUrlAttribute(): string
    {
        return url("/verify/{$this->certificate_code}");
    }

    /**
     * Get the is valid attribute.
     *
     * @return bool
     */
    public function getIsValidAttribute(): bool
    {
        return $this->status === 'valid';
    }

    /**
     * Scope a query to only include valid certificates.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeValid($query)
    {
        return $query->where('status', 'valid');
    }

    /**
     * Scope a query to only include revoked certificates.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeRevoked($query)
    {
        return $query->where('status', 'revoked');
    }

    /**
     * Scope a query to filter by language.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $language
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByLanguage($query, string $language)
    {
        return $query->where('language', $language);
    }

    /**
     * Revoke this certificate.
     *
     * @param int $revokedBy
     * @param string|null $note
     * @return bool
     */
    public function revoke(int $revokedBy, ?string $note = null): bool
    {
        return $this->update([
            'status' => 'revoked',
            'revoked_by' => $revokedBy,
            'revoked_at' => now(),
            'revoked_note' => $note,
        ]);
    }

    /**
     * Get verification count for this certificate.
     *
     * @return int
     */
    public function getVerificationCount(): int
    {
        return $this->verificationLogs()->count();
    }
}
