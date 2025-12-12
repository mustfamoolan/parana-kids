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
    public function handle(AlWaseetService $alWaseetService): void
    {
        try {
            // جلب فقط الطلبات المرتبطة (التي لها order_id) والتي لم يتم تحديثها في آخر دقيقتين
            $shipments = AlWaseetShipment::whereNotNull('order_id')
                ->whereNotNull('alwaseet_order_id')
                ->where(function($query) {
                    $query->whereNull('synced_at')
                        ->orWhere('synced_at', '<', now()->subMinutes(2));
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

                    // تحديث shipments
                    foreach ($batch as $alwaseetOrderId) {
                        if (!isset($apiOrdersMap[$alwaseetOrderId])) {
                            continue;
                        }

                        $apiOrder = $apiOrdersMap[$alwaseetOrderId];
                        $newStatusId = $apiOrder['status_id'] ?? null;
                        $newStatus = $apiOrder['status'] ?? null;

                        if ($newStatusId && $newStatus) {
                            $shipment = AlWaseetShipment::where('alwaseet_order_id', $alwaseetOrderId)
                                ->whereNotNull('order_id')
                                ->first();

                            if ($shipment) {
                                $oldStatusId = $shipment->status_id;
                                
                                $shipment->update([
                                    'status_id' => $newStatusId,
                                    'status' => $newStatus,
                                    'synced_at' => now(),
                                ]);

                                // إرسال event إذا تغيرت الحالة
                                if ($oldStatusId && $oldStatusId !== $newStatusId) {
                                    event(new AlWaseetShipmentStatusChanged($shipment, $oldStatusId, $newStatusId));
                                }

                                $updated++;
                            }
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
