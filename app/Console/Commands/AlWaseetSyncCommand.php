<?php

namespace App\Console\Commands;

use App\Jobs\SyncAlWaseetOrdersJob;
use App\Models\Setting;
use Illuminate\Console\Command;

class AlWaseetSyncCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'alwaseet:sync {--status-id= : Filter by status ID} {--date-from= : Start date (YYYY-MM-DD)} {--date-to= : End date (YYYY-MM-DD)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync orders from AlWaseet API';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $statusId = $this->option('status-id');
        $dateFrom = $this->option('date-from');
        $dateTo = $this->option('date-to');

        $this->info('Starting AlWaseet sync...');

        try {
            SyncAlWaseetOrdersJob::dispatch($statusId, $dateFrom, $dateTo);
            $this->info('Sync job dispatched successfully!');
        } catch (\Exception $e) {
            $this->error('Failed to dispatch sync job: ' . $e->getMessage());
            return 1;
        }

        return 0;
    }
}
