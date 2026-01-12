<?php

namespace Database\Seeders;

use App\Models\SessionDay;
use App\Models\TrainingSession;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class SessionDaySeeder extends Seeder
{
    /**
     * Seed session days for all training sessions.
     */
    public function run(): void
    {
        $sessions = TrainingSession::all();

        $totalDays = 0;

        foreach ($sessions as $session) {
            $startDate = Carbon::parse($session->start_at);
            $endDate = Carbon::parse($session->end_at);

            // Calculate number of days
            $numberOfDays = $startDate->diffInDays($endDate) + 1;

            // Create session days
            for ($dayNumber = 1; $dayNumber <= $numberOfDays; $dayNumber++) {
                $currentDate = $startDate->copy()->addDays($dayNumber - 1);

                // Skip weekends for onsite/hybrid sessions (make them more realistic)
                if (in_array($session->mode, ['onsite', 'hybrid']) && in_array($currentDate->dayOfWeek, [0, 6])) {
                    continue;
                }

                // Generate realistic time slots
                $timeSlot = $this->generateTimeSlot($session->mode);

                // Determine status - mostly active, some cancelled for cancelled sessions
                $status = 'active';
                if ($session->status === 'cancelled' || ($session->status === 'completed' && rand(0, 100) < 5)) {
                    $status = 'cancelled';
                }

                SessionDay::create([
                    'session_id' => $session->id,
                    'date' => $currentDate->format('Y-m-d'),
                    'start_time' => $timeSlot['start'],
                    'end_time' => $timeSlot['end'],
                    'day_number' => $dayNumber,
                    'status' => $status,
                ]);

                $totalDays++;
            }
        }

        $this->command->info('Session days seeded: ' . $totalDays);
    }

    /**
     * Generate realistic time slots based on session mode
     */
    private function generateTimeSlot(string $mode): array
    {
        $timeSlots = [
            'morning' => ['start' => '09:00:00', 'end' => '12:00:00'],
            'afternoon' => ['start' => '13:00:00', 'end' => '16:00:00'],
            'full_day' => ['start' => '09:00:00', 'end' => '16:00:00'],
            'evening' => ['start' => '18:00:00', 'end' => '21:00:00'],
        ];

        // Online sessions can be any time, others are typically full day
        if ($mode === 'online') {
            $slots = array_values($timeSlots);
            return $slots[array_rand($slots)];
        }

        // Onsite and hybrid sessions are typically full day
        return $timeSlots['full_day'];
    }
}
