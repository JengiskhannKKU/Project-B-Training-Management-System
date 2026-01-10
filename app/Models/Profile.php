<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Profile extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'prefix',
        'first_name',
        'last_name',
        'phone',
        'date_of_birth',
        'gender',
        'sub_category',
        'faculty',
        'major',
        'student_id',
        'degree_level',
        'year_of_study',
        'personnel_id',
        'organization',
        'department',
        'job_position',
        'employment_status',
        'personnel_type',
        'category',
        'bio',
        'avatar_image',
    ];

    protected $hidden = [
        'avatar_image',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
