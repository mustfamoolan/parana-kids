<?php

namespace App\Console\Commands;

use App\Models\Notification;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CleanupNotifications extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:cleanup-notifications';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete notifications older than 24 hours';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting notification cleanup...');

        $cutoff = now()->subHours(24);

        $count = Notification::where('created_at', '<', $cutoff)->delete();

        $this->info("Deleted {$count} old notifications.");
        Log::info("Notification cleanup: Deleted {$count} notifications older than 24 hours.");

        return Command::SUCCESS;
    }
}
