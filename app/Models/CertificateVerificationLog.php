<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CertificateVerificationLog extends Model
{
    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'certificate_id',
        'ip_address',
        'user_agent',
        'verified_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'verified_at' => 'datetime',
    ];

    /**
     * Get the certificate that was verified.
     */
    public function certificate(): BelongsTo
    {
        return $this->belongsTo(Certificate::class);
    }

    /**
     * Log a certificate verification.
     *
     * @param int $certificateId
     * @param string|null $ipAddress
     * @param string|null $userAgent
     * @return self
     */
    public static function logVerification(
        int $certificateId,
        ?string $ipAddress = null,
        ?string $userAgent = null
    ): self {
        return self::create([
            'certificate_id' => $certificateId,
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
            'verified_at' => now(),
        ]);
    }

    /**
     * Get verification count for a certificate.
     *
     * @param int $certificateId
     * @return int
     */
    public static function getVerificationCount(int $certificateId): int
    {
        return self::where('certificate_id', $certificateId)->count();
    }

    /**
     * Get recent verifications for a certificate.
     *
     * @param int $certificateId
     * @param int $limit
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function getRecentVerifications(int $certificateId, int $limit = 10)
    {
        return self::where('certificate_id', $certificateId)
            ->orderBy('verified_at', 'desc')
            ->limit($limit)
            ->get();
    }
}
