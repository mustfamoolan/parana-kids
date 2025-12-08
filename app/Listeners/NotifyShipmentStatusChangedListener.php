<?php

namespace App\Listeners;

use App\Events\AlWaseetShipmentStatusChanged;
use App\Models\AlWaseetNotification;
use App\Models\Setting;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class NotifyShipmentStatusChangedListener implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(AlWaseetShipmentStatusChanged $event): void
    {
        try {
            // التحقق من إعدادات الإشعارات
            $notifyStatuses = Setting::getValue('alwaseet_notify_statuses', '');
            $notifyStatusesArray = !empty($notifyStatuses) ? explode(',', $notifyStatuses) : [];

            // إذا كانت القائمة فارغة أو الحالة الجديدة في القائمة
            if (empty($notifyStatusesArray) || in_array($event->newStatusId, $notifyStatusesArray)) {
                // إنشاء إشعار
                AlWaseetNotification::create([
                    'alwaseet_shipment_id' => $event->shipment->id,
                    'type' => 'status_changed',
                    'title' => 'تغيير حالة الشحنة',
                    'message' => "تم تغيير حالة الشحنة من '{$event->oldStatusId}' إلى '{$event->newStatusId}'",
                    'old_status' => $event->oldStatusId,
                    'new_status' => $event->newStatusId,
                ]);

                Log::info('AlWaseet: Status change notification created', [
                    'shipment_id' => $event->shipment->id,
                    'old_status' => $event->oldStatusId,
                    'new_status' => $event->newStatusId,
                ]);
            }
        } catch (\Exception $e) {
            Log::error('AlWaseet: Failed to create notification', [
                'shipment_id' => $event->shipment->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
