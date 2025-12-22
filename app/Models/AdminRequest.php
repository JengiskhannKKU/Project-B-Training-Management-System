<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdminRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'requester_id',
        'target_type',
        'action',
        'target_id',
        'payload',
        'status',
        'admin_note',
        'resolved_by',
        'resolved_at',
    ];

    protected $casts = [
        'payload' => 'array',
        'resolved_at' => 'datetime',
    ];

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requester_id');
    }

    public function resolver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }
}
