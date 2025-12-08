<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class ProcessAlWaseetQueueJob implements ShouldQueue
{
    use Queueable;

    public $tries = 3;
    public $timeout = 60;

    protected $jobData;
    protected $jobType;

    /**
     * Create a new job instance.
     */
    public function __construct($jobType, $jobData)
    {
        $this->jobType = $jobType;
        $this->jobData = $jobData;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // التحقق من Rate Limiting
        $rateLimitKey = 'alwaseet_rate_limit';
        $rateLimitCount = Cache::get($rateLimitKey, 0);
        $rateLimitWindow = 30; // 30 seconds
        $rateLimitMax = 30; // 30 requests

        if ($rateLimitCount >= $rateLimitMax) {
            // تجاوز الحد، إعادة المحاولة لاحقاً
            $this->release(30); // إعادة المحاولة بعد 30 ثانية
            Log::warning('AlWaseet: Rate limit exceeded, job released', [
                'job_type' => $this->jobType,
                'rate_limit_count' => $rateLimitCount,
            ]);
            return;
        }

        // زيادة العداد
        Cache::put($rateLimitKey, $rateLimitCount + 1, now()->addSeconds($rateLimitWindow));

        try {
            // تنفيذ المهمة حسب النوع
            match($this->jobType) {
                'create_shipment' => $this->handleCreateShipment(),
                'sync_orders' => $this->handleSyncOrders(),
                default => throw new \Exception("Unknown job type: {$this->jobType}"),
            };
        } catch (\Exception $e) {
            Log::error('AlWaseet: Queue job failed', [
                'job_type' => $this->jobType,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Handle create shipment job
     */
    protected function handleCreateShipment(): void
    {
        $orderId = $this->jobData['order_id'] ?? null;
        if (!$orderId) {
            throw new \Exception('Order ID is required');
        }

        $order = \App\Models\Order::findOrFail($orderId);
        \App\Jobs\CreateAlWaseetShipmentJob::dispatch($order);
    }

    /**
     * Handle sync orders job
     */
    protected function handleSyncOrders(): void
    {
        $statusId = $this->jobData['status_id'] ?? null;
        $dateFrom = $this->jobData['date_from'] ?? null;
        $dateTo = $this->jobData['date_to'] ?? null;

        \App\Jobs\SyncAlWaseetOrdersJob::dispatch($statusId, $dateFrom, $dateTo);
    }
}
