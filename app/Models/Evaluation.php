<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Evaluation extends Model
{
    protected $fillable = [
        'session_id',
        'user_id',
        'overall_rating',
        'content_quality',
        'trainer_quality',
        'material_quality',
        'organization',
        'would_recommend',
        'difficulty_level',
        'strengths',
        'improvements',
        'comments',
        'submitted_at',
    ];

    protected $casts = [
        'would_recommend' => 'boolean',
        'submitted_at' => 'datetime',
        'overall_rating' => 'integer',
        'content_quality' => 'integer',
        'trainer_quality' => 'integer',
        'material_quality' => 'integer',
        'organization' => 'integer',
    ];

    /**
     * Get the session that owns the evaluation
     */
    public function session(): BelongsTo
    {
        return $this->belongsTo(Session::class);
    }

    /**
     * Get the user (trainee) who submitted the evaluation
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
