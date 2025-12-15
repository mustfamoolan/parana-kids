<?php

namespace App\Observers;

use App\Models\AlWaseetShipment;
use App\Models\AlWaseetOrderStatusHistory;
use Illuminate\Support\Facades\Log;

class AlWaseetShipmentObserver
{
    /**
     * Handle the AlWaseetShipment "created" event.
     * إنشاء سجل statusHistory تلقائياً عند إنشاء shipment جديد
     */
    public function created(AlWaseetShipment $shipment): void
    {
        try {
            // إنشاء سجل statusHistory للحالة الأولى
            AlWaseetOrderStatusHistory::create([
                'order_id' => $shipment->order_id,
                'shipment_id' => $shipment->id,
                'status_id' => $shipment->status_id,
                'status_text' => $shipment->status ?? 'جديد',
                'changed_at' => $shipment->alwaseet_created_at ?? now(),
                'changed_by' => 'system_auto',
                'metadata' => json_encode(['auto_created' => true, 'event' => 'shipment_created']),
            ]);

            Log::info('AlWaseetShipmentObserver: Created statusHistory for new shipment', [
                'shipment_id' => $shipment->id,
                'status_id' => $shipment->status_id,
            ]);
        } catch (\Exception $e) {
            Log::error('AlWaseetShipmentObserver: Failed to create statusHistory', [
                'shipment_id' => $shipment->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Handle the AlWaseetShipment "updated" event.
     * إنشاء سجل statusHistory جديد عند تغيير status_id
     */
    public function updated(AlWaseetShipment $shipment): void
    {
        // التحقق من تغيير status_id
        if ($shipment->isDirty('status_id')) {
            try {
                AlWaseetOrderStatusHistory::create([
                    'order_id' => $shipment->order_id,
                    'shipment_id' => $shipment->id,
                    'status_id' => $shipment->status_id,
                    'status_text' => $shipment->status ?? 'محدث',
                    'changed_at' => now(),
                    'changed_by' => 'system_auto',
                    'metadata' => json_encode([
                        'auto_created' => true,
                        'event' => 'status_changed',
                        'old_status' => $shipment->getOriginal('status_id'),
                        'new_status' => $shipment->status_id,
                    ]),
                ]);

                Log::info('AlWaseetShipmentObserver: Created statusHistory for status change', [
                    'shipment_id' => $shipment->id,
                    'old_status' => $shipment->getOriginal('status_id'),
                    'new_status' => $shipment->status_id,
                ]);
            } catch (\Exception $e) {
                Log::error('AlWaseetShipmentObserver: Failed to create statusHistory on update', [
                    'shipment_id' => $shipment->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }
}
