<?php

namespace App\Jobs;

use App\Models\AlWaseetOrderStatus;
use App\Services\AlWaseetService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class SyncAlWaseetOrderStatusesJob implements ShouldQueue
{
    use Queueable;

    public $tries = 2;
    public $timeout = 60;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(AlWaseetService $alWaseetService): void
    {
        try {
            // جلب الحالات من API
            $statuses = $alWaseetService->getOrderStatuses();
            
            if (is_array($statuses) && !empty($statuses)) {
                // تحديث قاعدة البيانات بالحالات الجديدة
                AlWaseetOrderStatus::syncFromApi($statuses);
                
                Log::info('SyncAlWaseetOrderStatusesJob: Successfully synced statuses', [
                    'count' => count($statuses),
                ]);
            } else {
                Log::warning('SyncAlWaseetOrderStatusesJob: No statuses returned from API');
            }
        } catch (\Exception $e) {
            Log::error('SyncAlWaseetOrderStatusesJob: Failed to sync order statuses', [
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}
