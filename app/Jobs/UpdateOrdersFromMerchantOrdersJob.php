<?php

namespace App\Jobs;

use App\Models\AlWaseetShipment;
use App\Services\AlWaseetService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class UpdateOrdersFromMerchantOrdersJob implements ShouldQueue
{
    use Queueable;

    public $tries = 2;
    public $timeout = 300;

    /**
     * Execute the job.
     */
    public function handle(AlWaseetService $alWaseetService): void
    {
        try {
            // جلب جميع الطلبات من API
            $apiOrders = $alWaseetService->getOrders();
            
            if (!is_array($apiOrders) || empty($apiOrders)) {
                Log::info('UpdateOrdersFromMerchantOrdersJob: No orders returned from API');
                return;
            }

            $updated = 0;
            $notFound = 0;

            foreach ($apiOrders as $apiOrder) {
                if (!isset($apiOrder['id'])) {
                    continue;
                }

                // البحث عن shipment بالـ alwaseet_order_id
                $shipment = AlWaseetShipment::where('alwaseet_order_id', $apiOrder['id'])
                    ->first();

                if ($shipment) {
                    $oldStatusId = $shipment->status_id;
                    
                    // حفظ جميع البيانات من API
                    $updateData = [
                        'status_id' => $apiOrder['status_id'] ?? $shipment->status_id,
                        'status' => $apiOrder['status'] ?? $shipment->status,
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
                    if (isset($apiOrder['merchant_invoice_id'])) {
                        $updateData['merchant_invoice_id'] = $apiOrder['merchant_invoice_id'];
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
                        event(new \App\Events\AlWaseetShipmentStatusChanged($shipment, $oldStatusId, $updateData['status_id']));
                    }

                    $updated++;
                } else {
                    $notFound++;
                }
            }

            Log::info('UpdateOrdersFromMerchantOrdersJob: Completed', [
                'total_orders' => count($apiOrders),
                'updated' => $updated,
                'not_found' => $notFound,
            ]);

            // بعد التحديث، تشغيل UpdateStatusCountsJob لتحديث statusCounts
            \App\Jobs\UpdateStatusCountsJob::dispatch();
            
        } catch (\Exception $e) {
            Log::error('UpdateOrdersFromMerchantOrdersJob: Failed', [
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}

