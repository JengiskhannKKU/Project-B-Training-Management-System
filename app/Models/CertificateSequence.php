<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CertificateSequence extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'year',
        'last_sequence',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'year' => 'integer',
        'last_sequence' => 'integer',
    ];

    /**
     * Get the next sequence number for a given year.
     *
     * @param int $year
     * @return int
     */
    public static function getNextSequence(int $year): int
    {
        return \DB::transaction(function () use ($year) {
            $sequence = self::lockForUpdate()
                ->firstOrCreate(['year' => $year], ['last_sequence' => 0]);

            $sequence->increment('last_sequence');

            return $sequence->last_sequence;
        });
    }

    /**
     * Get the current sequence number for a given year.
     *
     * @param int $year
     * @return int
     */
    public static function getCurrentSequence(int $year): int
    {
        $sequence = self::where('year', $year)->first();

        return $sequence ? $sequence->last_sequence : 0;
    }

    /**
     * Reset sequence for a year (use with caution).
     *
     * @param int $year
     * @return bool
     */
    public static function resetSequence(int $year): bool
    {
        return self::where('year', $year)->update(['last_sequence' => 0]) > 0;
    }
}
