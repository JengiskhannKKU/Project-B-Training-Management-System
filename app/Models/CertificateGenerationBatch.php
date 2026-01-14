<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CertificateGenerationBatch extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'initiated_by',
        'type',
        'course_id',
        'session_id',
        'total_count',
        'generated_count',
        'failed_count',
        'status',
        'errors',
        'started_at',
        'completed_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'total_count' => 'integer',
        'generated_count' => 'integer',
        'failed_count' => 'integer',
        'errors' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    /**
     * Get the user who initiated the batch.
     */
    public function initiator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'initiated_by');
    }

    /**
     * Get the course associated with the batch.
     */
    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    /**
     * Get the session associated with the batch.
     */
    public function session(): BelongsTo
    {
        return $this->belongsTo(TrainingSession::class, 'session_id');
    }

    /**
     * Check if the batch is complete.
     *
     * @return bool
     */
    public function isComplete(): bool
    {
        return in_array($this->status, ['completed', 'failed']);
    }

    /**
     * Check if the batch is still processing.
     *
     * @return bool
     */
    public function isProcessing(): bool
    {
        return $this->status === 'processing';
    }

    /**
     * Get the success rate as a percentage.
     *
     * @return float
     */
    public function getSuccessRateAttribute(): float
    {
        if ($this->total_count === 0) {
            return 0;
        }

        return ($this->generated_count / $this->total_count) * 100;
    }

    /**
     * Get the progress as a percentage.
     *
     * @return float
     */
    public function getProgressPercentageAttribute(): float
    {
        if ($this->total_count === 0) {
            return 0;
        }

        $processed = $this->generated_count + $this->failed_count;
        return ($processed / $this->total_count) * 100;
    }

    /**
     * Mark the batch as started.
     *
     * @return bool
     */
    public function markAsStarted(): bool
    {
        return $this->update([
            'status' => 'processing',
            'started_at' => now(),
        ]);
    }

    /**
     * Mark the batch as completed.
     *
     * @return bool
     */
    public function markAsCompleted(): bool
    {
        return $this->update([
            'status' => 'completed',
            'completed_at' => now(),
        ]);
    }

    /**
     * Mark the batch as failed.
     *
     * @param array $errors
     * @return bool
     */
    public function markAsFailed(array $errors = []): bool
    {
        return $this->update([
            'status' => 'failed',
            'errors' => $errors,
            'completed_at' => now(),
        ]);
    }

    /**
     * Increment the generated count.
     *
     * @return int
     */
    public function incrementGenerated(): int
    {
        $this->increment('generated_count');
        return $this->generated_count;
    }

    /**
     * Increment the failed count.
     *
     * @param string|null $error
     * @return int
     */
    public function incrementFailed(?string $error = null): int
    {
        $this->increment('failed_count');

        if ($error) {
            $errors = $this->errors ?? [];
            $errors[] = $error;
            $this->update(['errors' => $errors]);
        }

        return $this->failed_count;
    }

    /**
     * Scope query to batches by type.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $type
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope query to batches by status.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $status
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWithStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope query to recent batches.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $days
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeRecent($query, int $days = 7)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }
}
