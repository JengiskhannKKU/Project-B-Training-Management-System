<?php

namespace App\Console\Commands;

use App\Models\TrainingSession;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class CloseExpiredSessions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sessions:close-expired';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Close training sessions that have passed their end date';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Checking for expired sessions...');

        $count = TrainingSession::where('status', 'open')
            ->where('end_date', '<', Carbon::today())
            ->update(['status' => 'closed']);

        if ($count > 0) {
            $this->info("Successfully closed {$count} expired session(s).");
        } else {
            $this->info('No expired sessions found.');
        }

        return Command::SUCCESS;
    }
}
