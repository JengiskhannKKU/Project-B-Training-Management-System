<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'icon',
        'color',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Available icon options
     */
    public const ICONS = [
        'Tag',
        'Code',
        'Palette',
        'Briefcase',
        'TrendingUp',
        'Database',
        'BookOpen',
        'Laptop',
        'Lightbulb',
        'Camera',
    ];

    /**
     * Available color options
     */
    public const COLORS = [
        'blue',
        'purple',
        'green',
        'yellow',
        'red',
        'pink',
        'indigo',
        'teal',
        'orange',
        'cyan',
    ];

    /**
     * Get courses that belong to this category
     */
    public function courses()
    {
        return $this->hasMany(\App\Models\Course::class, 'category', 'name');
    }
}
