<?php

namespace App\Jobs;

use App\Events\AlWaseetShipmentStatusChanged;
use App\Models\AlWaseetShipment;
use App\Services\AlWaseetService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class UpdateAlWaseetShipmentsStatusJob implements ShouldQueue
{
    use Queueable;

    public $tries = 2;
    public $timeout = 300;

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
    public function handle(AlWaseetService $alWaseetService, \App\Services\AlWaseetSyncService $syncService): void
    {
        try {
            // جلب فقط الطلبات النشطة (التي ليست في حالة نهائية) والتي لم يتم تحديثها في آخر 2 دقيقة
            $finalStatusIds = [4, 17, 31, 32, 34, 35, 39];

            $shipments = AlWaseetShipment::whereNotNull('order_id')
                ->whereNotNull('alwaseet_order_id')
                ->whereNotIn('status_id', $finalStatusIds) // استبعاد الطلبات المنتهية
                ->where(function ($query) {
                    $query->whereNull('synced_at')
                        ->orWhere('synced_at', '<', now()->subMinutes(2)); // تحديث كل دقيقتين لضمان عدم تجاوز حدود API (30 طلب/30 ثانية)
                })
                ->select('id', 'alwaseet_order_id', 'status_id', 'status')
                ->get();

            if ($shipments->isEmpty()) {
                Log::info('UpdateAlWaseetShipmentsStatusJob: No shipments to update');
                return;
            }

            // تجميع alwaseet_order_ids
            $alwaseetOrderIds = $shipments->pluck('alwaseet_order_id')->unique()->values()->toArray();

            if (empty($alwaseetOrderIds)) {
                return;
            }

            // تقسيم إلى batches (10 طلب في كل batch)
            $batchSize = 10;
            $batches = array_chunk($alwaseetOrderIds, $batchSize);

            $updated = 0;
            $failed = 0;

            foreach ($batches as $batch) {
                try {
                    // جلب بيانات API
                    $apiOrders = $alWaseetService->getOrdersByIds($batch);

                    // إنشاء mapping
                    $apiOrdersMap = [];
                    foreach ($apiOrders as $apiOrder) {
                        if (isset($apiOrder['id'])) {
                            $apiOrdersMap[$apiOrder['id']] = $apiOrder;
                        }
                    }

                    // تحديث shipments باستخدام AlWaseetSyncService
                    foreach ($batch as $alwaseetOrderId) {
                        if (!isset($apiOrdersMap[$alwaseetOrderId])) {
                            continue;
                        }

                        $apiOrder = $apiOrdersMap[$alwaseetOrderId];

                        $shipment = AlWaseetShipment::where('alwaseet_order_id', $alwaseetOrderId)
                            ->whereNotNull('order_id')
                            ->first();

                        if ($shipment) {
                            $syncService->syncShipmentWithApiData($shipment, $apiOrder);
                            $updated++;
                        }
                    }
                } catch (\Exception $e) {
                    $failed += count($batch);
                    Log::warning('UpdateAlWaseetShipmentsStatusJob: Failed to update batch', [
                        'error' => $e->getMessage(),
                        'batch_size' => count($batch),
                    ]);
                }
            }

            Log::info('UpdateAlWaseetShipmentsStatusJob: Completed', [
                'updated' => $updated,
                'failed' => $failed,
                'total' => $shipments->count(),
            ]);
        } catch (\Exception $e) {
            Log::error('UpdateAlWaseetShipmentsStatusJob: Job failed', [
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}
