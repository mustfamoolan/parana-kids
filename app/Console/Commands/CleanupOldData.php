<?php

namespace App\Console\Commands;

use App\Models\Notification;
use App\Models\Message;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CleanupOldData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:cleanup-old-data';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete notifications and messages older than 24 hours';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting data cleanup...');

        $cutoff = now()->subHours(24);

        // Cleanup Notifications
        $notifCount = Notification::where('created_at', '<', $cutoff)->delete();
        $this->info("Deleted {$notifCount} old notifications.");

        // Cleanup Messages
        $msgCount = Message::where('created_at', '<', $cutoff)->delete();
        $this->info("Deleted {$msgCount} old messages.");

        Log::info("Data cleanup: Deleted {$notifCount} notifications and {$msgCount} messages older than 24 hours.");

        return Command::SUCCESS;
    }
}
