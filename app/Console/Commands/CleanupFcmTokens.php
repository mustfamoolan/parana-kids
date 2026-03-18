<?php

namespace App\Console\Commands;

use App\Models\FcmToken;
use Illuminate\Console\Command;
use Carbon\Carbon;

class CleanupFcmTokens extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fcm:cleanup {--days=60 : Number of days of inactivity to prune}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean up stale FCM tokens that haven\'t been updated for a long time';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $days = $this->option('days');
        $cutoff = Carbon::now()->subDays($days);

        $this->info("Cleaning up FCM tokens older than {$days} days (Last updated before {$cutoff})...");

        $count = FcmToken::where('updated_at', '<', $cutoff)->delete();

        $this->info("Successfully deleted {$count} stale FCM tokens.");
        
        return Command::SUCCESS;
    }
}
