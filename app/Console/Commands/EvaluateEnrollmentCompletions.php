<?php

namespace App\Console\Commands;

use App\Models\Enrollment;
use Illuminate\Console\Command;

class EvaluateEnrollmentCompletions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'enrollments:evaluate-completions';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Evaluate and mark enrollments as completed based on criteria';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Evaluating enrollments for completion...');

        // TODO: Add criteria logic for marking enrollments as 'completed'
        // Possible criteria:
        // - Session status = 'completed'
        // - Attendance percentage >= threshold
        // - All required assignments/evaluations completed
        // - etc.

        $count = 0;

        // Placeholder: Find enrollments that should be marked as completed
        // $enrollments = Enrollment::with('session')
        //     ->where('status', '!=', 'completed')
        //     ->where('status', '!=', 'cancelled')
        //     ->get();

        // foreach ($enrollments as $enrollment) {
        //     if ($this->shouldMarkAsCompleted($enrollment)) {
        //         $enrollment->update(['status' => 'completed']);
        //         $count++;
        //     }
        // }

        if ($count > 0) {
            $this->info("Successfully marked {$count} enrollment(s) as completed.");
        } else {
            $this->info('No enrollments to mark as completed.');
        }

        return Command::SUCCESS;
    }

    /**
     * Determine if an enrollment should be marked as completed.
     *
     * @param Enrollment $enrollment
     * @return bool
     */
    // private function shouldMarkAsCompleted(Enrollment $enrollment): bool
    // {
    //     // TODO: Implement completion criteria
    //     // Example:
    //     // - Check if session is completed
    //     // - Check if attendance meets minimum requirement
    //     // - Check if all evaluations are done
    //
    //     return false;
    // }
}
