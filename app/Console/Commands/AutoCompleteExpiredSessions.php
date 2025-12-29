<?php

namespace App\Console\Commands;

use App\Models\TrainingSession;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class AutoCompleteExpiredSessions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sessions:auto-complete {--days=7 : Number of days after end_date to auto-complete}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Automatically mark sessions as completed after X days past their end date';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $days = (int) $this->option('days');
        $this->info("Checking for sessions to auto-complete (threshold: {$days} days)...");

        // Calculate the cutoff date: today - X days
        $cutoffDate = Carbon::today()->subDays($days);

        $count = TrainingSession::where('status', '!=', 'completed')
            ->where('status', '!=', 'cancelled')
            ->where('end_date', '<', $cutoffDate)
            ->update(['status' => 'completed']);

        if ($count > 0) {
            $this->info("Successfully auto-completed {$count} session(s).");
        } else {
            $this->info('No sessions to auto-complete.');
        }

        return Command::SUCCESS;
    }
}
