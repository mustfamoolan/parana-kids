<?php

namespace App\Listeners;

use App\Events\AlWaseetShipmentStatusChanged;
use App\Models\AlWaseetNotification;
use App\Models\Setting;
use App\Models\User;
use App\Services\TelegramService;
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

            // إرسال إشعارات التليجرام
            $this->sendTelegramNotifications($event);
        } catch (\Exception $e) {
            Log::error('AlWaseet: Failed to create notification', [
                'shipment_id' => $event->shipment->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Send Telegram notifications to users with warehouse permissions
     */
    protected function sendTelegramNotifications(AlWaseetShipmentStatusChanged $event): void
    {
        try {
            $shipment = $event->shipment;
            $order = $shipment->order;

            if (!$order) {
                Log::warning('AlWaseet: Order not found for shipment', [
                    'shipment_id' => $shipment->id,
                ]);
                return;
            }

            // جلب warehouseIds من منتجات الطلب
            $warehouseIds = $order->items()
                ->with('product')
                ->get()
                ->pluck('product.warehouse_id')
                ->filter()
                ->unique()
                ->toArray();

            if (empty($warehouseIds)) {
                Log::info('AlWaseet: No warehouses found for order', [
                    'order_id' => $order->id,
                ]);
                return;
            }

            $recipientIds = [];

            // جلب المجهزين (suppliers) الذين لديهم صلاحية على نفس المخزن
            $supplierIds = User::whereIn('role', ['admin', 'supplier'])
                ->whereHas('warehouses', function($q) use ($warehouseIds) {
                    $q->whereIn('warehouses.id', $warehouseIds);
                })
                ->pluck('id')
                ->toArray();
            $recipientIds = array_merge($recipientIds, $supplierIds);

            // إضافة المديرين دائماً
            $adminIds = User::where('role', 'admin')->pluck('id')->toArray();
            $recipientIds = array_merge($recipientIds, $adminIds);

            // إضافة المندوب (نفس المخزن)
            if ($order->delegate_id) {
                $delegate = User::find($order->delegate_id);
                if ($delegate && !empty($warehouseIds)) {
                    $hasAccess = $delegate->warehouses()
                        ->whereIn('warehouses.id', $warehouseIds)
                        ->exists();
                    if ($hasAccess) {
                        $recipientIds[] = $order->delegate_id;
                    }
                }
            }

            $recipientIds = array_unique($recipientIds);

            if (empty($recipientIds)) {
                Log::info('AlWaseet: No recipients found for Telegram notification', [
                    'order_id' => $order->id,
                ]);
                return;
            }

            // جلب المستخدمين المربوطين بالتليجرام
            $recipients = User::whereIn('id', $recipientIds)
                ->whereNotNull('telegram_chat_id')
                ->get();

            if ($recipients->isEmpty()) {
                Log::info('AlWaseet: No Telegram-linked users found', [
                    'order_id' => $order->id,
                ]);
                return;
            }

            // إرسال إشعارات التليجرام
            $telegramService = app(TelegramService::class);
            foreach ($recipients as $recipient) {
                $telegramService->sendOrderStatusNotification(
                    $recipient->telegram_chat_id,
                    $shipment,
                    $order
                );
            }

            Log::info('AlWaseet: Telegram notifications sent', [
                'shipment_id' => $shipment->id,
                'order_id' => $order->id,
                'recipients_count' => $recipients->count(),
            ]);
        } catch (\Exception $e) {
            Log::error('AlWaseet: Failed to send Telegram notifications', [
                'shipment_id' => $event->shipment->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }
}
