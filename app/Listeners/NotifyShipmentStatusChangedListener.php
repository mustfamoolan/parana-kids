<?php

namespace App\Listeners;

use App\Events\AlWaseetShipmentStatusChanged;
use App\Models\AlWaseetNotification;
use App\Models\Notification;
use App\Models\Setting;
use App\Models\User;
use App\Services\TelegramService;
use App\Services\FirebaseCloudMessagingService;
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
            // جلب أسماء الحالات من جدول alwaseet_order_statuses
            $oldStatusText = \App\Models\AlWaseetOrderStatus::where('status_id', $event->oldStatusId)
                ->value('status_text') ?? $event->oldStatusId;
            $newStatusText = \App\Models\AlWaseetOrderStatus::where('status_id', $event->newStatusId)
                ->value('status_text') ?? $event->newStatusId;

            // التحقق من إعدادات الإشعارات
            $notifyStatuses = Setting::getValue('alwaseet_notify_statuses', '');
            $notifyStatusesArray = !empty($notifyStatuses) ? explode(',', $notifyStatuses) : [];

            // إذا كانت القائمة فارغة أو الحالة الجديدة في القائمة
            // 1. إنشاء إشعار خاص بتتبع الشحنة (AlWaseetNotification)
            // إذا كانت القائمة فارغة أو الحالة الجديدة في القائمة
            if (empty($notifyStatusesArray) || in_array($event->newStatusId, $notifyStatusesArray)) {
                \App\Models\AlWaseetNotification::create([
                    'alwaseet_shipment_id' => $event->shipment->id,
                    'type' => 'status_changed',
                    'title' => $event->shipment->order->customer_name ?? 'تغيير حالة الشحنة',
                    'message' => "تم '{$newStatusText}'",
                    'old_status' => $event->oldStatusId,
                    'new_status' => $event->newStatusId,
                ]);
            }

            // 2. إرسال جميع الإشعارات (DB, FCM, Telegram) عبر النظام الموحد
            app(\App\Services\AdminNotificationService::class)->notifyAlWaseetStatusChanged($event->shipment, $oldStatusText, $newStatusText);

        } catch (\Exception $e) {
            Log::error('AlWaseet: Failed to handle shipment status change', [
                'shipment_id' => $event->shipment->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

}
