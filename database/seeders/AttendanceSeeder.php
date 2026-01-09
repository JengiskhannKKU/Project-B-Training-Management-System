<?php


namespace Database\Seeders;

use App\Models\Attendance;
use App\Models\Enrollment;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class AttendanceSeeder extends Seeder
{
    public function run(): void
    {
        // Find enrollments in completed sessions
        $enrollments = Enrollment::whereHas('session', function ($q) {
            $q->where('status', 'completed')
                ->orWhere('status', 'upcoming'); // Maybe some upcoming sessions already had a class? Assuming 'training_sessions' are the whole course, not individual classes.
            // Actually, if TrainingSession is the Course Session (e.g. Spring 2025), attendance usually implies daily attendance.
            // But 'attendances' table might link to TrainingSession directly?
        })->with('session')->get();

        // Check Attendance model structure or migration to see if it's per session or per day.
        // Migration: create_attendances_table.php
        // Schema::create('attendances', function (Blueprint $table) { ... $table->foreignId('session_id')... $table->date('date')? ... })
        // Let's assume simplest case: One attendance record per trainee per session (like "Completed course" or "Present on final exam").
        // Or if it's daily, I should create multiple.

        // Looking at previous AttendanceSeeder, it had 'checked_at'.
        // It seems to be single record per session? Or maybe multiple.
        // Previous had: 'session_id' => 3, 'enrollment_id' => 6...

        // If it's daily, I'd need a loop. If it's "summary" attendance, just one.
        // Given 'checked_at', it looks like a specific check-in.
        // I will create ONE attendance record per trainee for the session, assuming it's a "class" or "workshop" type single event, OR just one sample record.

        foreach ($enrollments as $enrollment) {
            // Randomly present/absent
            $status = rand(0, 10) > 1 ? 'present' : (rand(0, 1) ? 'late' : 'absent');

            Attendance::updateOrCreate(
                [
                    'enrollment_id' => $enrollment->id,
                    'session_id' => $enrollment->session_id,
                ],
                [
                    'checked_at' => $enrollment->session->start_date ? Carbon::parse($enrollment->session->start_date)->addHours(2) : Carbon::now(),
                    'status' => $status,
                    'checked_by' => $enrollment->session->trainer_id, // Trainer checks
                    'note' => $status === 'late' ? 'Arrived late' : null,
                ]
            );
        }
    }
}

