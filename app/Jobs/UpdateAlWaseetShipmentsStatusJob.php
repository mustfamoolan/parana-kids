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
            // جلب فقط الطلبات المرتبطة (التي لها order_id) والتي لم يتم تحديثها في آخر دقيقة
            $shipments = AlWaseetShipment::whereNotNull('order_id')
                ->whereNotNull('alwaseet_order_id')
                ->where(function($query) {
                    $query->whereNull('synced_at')
                        ->orWhere('synced_at', '<', now()->subMinute());
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
                        
                        $shipment = AlWaseetShipment::where('alwaseet_order_id', $alwaseetOrderId)
                            ->whereNotNull('order_id')
                            ->first();

                        if ($shipment) {
                            $oldStatusId = $shipment->status_id;
                            
                            // حفظ جميع البيانات من API
                            $updateData = [
                                'status_id' => $apiOrder['status_id'] ?? $shipment->status_id,
                                'status' => $apiOrder['status'] ?? $shipment->status,
                                'pickup_id' => $apiOrder['pickup_id'] ?? $shipment->pickup_id,
                                'merchant_invoice_id' => $apiOrder['merchant_invoice_id'] ?? $shipment->merchant_invoice_id,
                                'qr_id' => $apiOrder['qr_id'] ?? $shipment->qr_id,
                                'api_data' => $apiOrder, // حفظ جميع بيانات API كاملة
                                'synced_at' => now(),
                            ];
                            
                            // تحديث الحقول الأخرى إذا كانت موجودة في API
                            if (isset($apiOrder['client_name'])) {
                                $updateData['client_name'] = $apiOrder['client_name'];
                            }
                            if (isset($apiOrder['client_mobile'])) {
                                $updateData['client_mobile'] = $apiOrder['client_mobile'];
                            }
                            if (isset($apiOrder['client_mobile2'])) {
                                $updateData['client_mobile2'] = $apiOrder['client_mobile2'];
                            }
                            if (isset($apiOrder['city_id'])) {
                                $updateData['city_id'] = $apiOrder['city_id'];
                            }
                            if (isset($apiOrder['city_name'])) {
                                $updateData['city_name'] = $apiOrder['city_name'];
                            }
                            if (isset($apiOrder['region_id'])) {
                                $updateData['region_id'] = $apiOrder['region_id'];
                            }
                            if (isset($apiOrder['region_name'])) {
                                $updateData['region_name'] = $apiOrder['region_name'];
                            }
                            if (isset($apiOrder['location'])) {
                                $updateData['location'] = $apiOrder['location'];
                            }
                            if (isset($apiOrder['price'])) {
                                $updateData['price'] = $apiOrder['price'];
                            }
                            if (isset($apiOrder['delivery_price'])) {
                                $updateData['delivery_price'] = $apiOrder['delivery_price'];
                            }
                            if (isset($apiOrder['package_size'])) {
                                $updateData['package_size'] = $apiOrder['package_size'];
                            }
                            if (isset($apiOrder['type_name'])) {
                                $updateData['type_name'] = $apiOrder['type_name'];
                            }
                            if (isset($apiOrder['items_number'])) {
                                $updateData['items_number'] = $apiOrder['items_number'];
                            }
                            if (isset($apiOrder['merchant_notes'])) {
                                $updateData['merchant_notes'] = $apiOrder['merchant_notes'];
                            }
                            if (isset($apiOrder['issue_notes'])) {
                                $updateData['issue_notes'] = $apiOrder['issue_notes'];
                            }
                            if (isset($apiOrder['replacement'])) {
                                $updateData['replacement'] = ($apiOrder['replacement'] === '1' || $apiOrder['replacement'] === true);
                            }
                            if (isset($apiOrder['qr_link'])) {
                                $updateData['qr_link'] = $apiOrder['qr_link'];
                            }
                            if (isset($apiOrder['created_at'])) {
                                $updateData['alwaseet_created_at'] = \Carbon\Carbon::parse($apiOrder['created_at']);
                            }
                            if (isset($apiOrder['updated_at'])) {
                                $updateData['alwaseet_updated_at'] = \Carbon\Carbon::parse($apiOrder['updated_at']);
                            }
                            
                            $shipment->update($updateData);

                            // إرسال event إذا تغيرت الحالة
                            if ($oldStatusId && $oldStatusId !== $updateData['status_id']) {
                                event(new AlWaseetShipmentStatusChanged($shipment, $oldStatusId, $updateData['status_id']));
                            }

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
