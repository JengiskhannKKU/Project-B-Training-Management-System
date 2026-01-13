<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Get all existing training sessions
        $sessions = DB::table('training_sessions')->get();

        foreach ($sessions as $session) {
            // Skip if session doesn't have proper dates
            if (!$session->start_at || !$session->end_at) {
                continue;
            }

            $startDateTime = Carbon::parse($session->start_at);
            $endDateTime = Carbon::parse($session->end_at);

            $start = $startDateTime->startOfDay();
            $end = $endDateTime->startOfDay();

            // If start and end are on the same day, create single session day
            if ($start->isSameDay($end)) {
                DB::table('session_days')->insert([
                    'session_id' => $session->id,
                    'date' => $start->toDateString(),
                    'start_time' => $startDateTime->format('H:i:s'),
                    'end_time' => $endDateTime->format('H:i:s'),
                    'day_number' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            } else {
                // Multi-day session: create a session_day for each day
                // Include all days (weekends included per user decision)
                $dayNumber = 1;
                $currentDate = $start->copy();

                while ($currentDate->lte($end)) {
                    DB::table('session_days')->insert([
                        'session_id' => $session->id,
                        'date' => $currentDate->toDateString(),
                        'start_time' => $startDateTime->format('H:i:s'),
                        'end_time' => $endDateTime->format('H:i:s'),
                        'day_number' => $dayNumber,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);

                    $currentDate->addDay();
                    $dayNumber++;
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Clear all session_days (they will be recreated from training_sessions)
        DB::table('session_days')->truncate();
    }
};
