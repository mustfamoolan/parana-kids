<?php

namespace App\Jobs;

use App\Events\AlWaseetShipmentStatusChanged;
use App\Models\AlWaseetShipment;
use App\Services\AlWaseetService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Contracts\Queue\ShouldBeUnique;

class UpdateAlWaseetShipmentsStatusJob implements ShouldQueue, ShouldBeUnique
{
    use Queueable;

    public $tries = 1;
    public $timeout = 120; // تقليل الوقت لضمان عدم التداخل

    /**
     * The number of seconds after which the job's unique lock will be released.
     */
    public $uniqueFor = 60;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    public function handle(AlWaseetService $alWaseetService, \App\Services\AlWaseetSyncService $syncService): void
    {
        // تشغيل مرتين في الدقيقة (كل 30 ثانية) لتحقيق سرعة "شبه فورية"
        for ($i = 0; $i < 2; $i++) {
            $this->performSync($alWaseetService, $syncService);
            
            if ($i === 0) {
                sleep(30);
            }
        }
    }

    /**
     * تنفيذ المزامنة
     */
    protected function performSync(AlWaseetService $alWaseetService, \App\Services\AlWaseetSyncService $syncService): void
    {
        try {
            // جلب فقط الطلبات النشطة (التي ليست في حالة نهائية) والتي لم يتم تحديثها في آخر 2 دقيقة
            $finalStatusIds = [4, 17, 31, 32, 34, 35, 39];

            $shipments = AlWaseetShipment::whereNotNull('order_id')
                ->whereNotNull('alwaseet_order_id')
                ->whereNotIn('status_id', $finalStatusIds) // استبعاد الطلبات المنتهية
                ->where(function ($query) {
                    $query->whereNull('synced_at')
                        ->orWhere('synced_at', '<', now()->subSeconds(30)); // تحديث كل 30 ثانية لضمان سرعة الوصول واستقرار النظام
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

            // تقسيم إلى batches (30 طلب في كل batch لتقليل عدد الطلبات لـ API)
            $batchSize = 30; 
            $batches = array_chunk($alwaseetOrderIds, $batchSize);

            $updated = 0;
            $failed = 0;
            $requestCount = 0;

            foreach ($batches as $batch) {
                // التوقف إذا وصلنا للحد الأقصى (25 طلب في الـ 30 ثانية لترك هامش أمان)
                if ($requestCount >= 25) {
                    Log::warning('UpdateAlWaseetShipmentsStatusJob: Reached safety limit of 25 requests per window');
                    break;
                }

                try {
                    $requestCount++;
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
