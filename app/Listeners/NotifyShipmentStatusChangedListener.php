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

            // إرسال إشعارات Firebase للمندوبين
            $this->sendFirebaseNotifications($event);
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
                ->whereHas('telegramChats')
                ->get();

            if ($recipients->isEmpty()) {
                Log::info('AlWaseet: No Telegram-linked users found', [
                    'order_id' => $order->id,
                ]);
                return;
            }

            // إرسال إشعارات التليجرام لجميع أجهزة كل مستخدم
            $telegramService = app(TelegramService::class);
            foreach ($recipients as $recipient) {
                $telegramService->sendToAllUserDevices($recipient, function($chatId) use ($telegramService, $shipment, $order) {
                    $telegramService->sendOrderStatusNotification($chatId, $shipment, $order);
                });
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

    /**
     * Send Firebase notifications to delegates
     */
    protected function sendFirebaseNotifications(AlWaseetShipmentStatusChanged $event): void
    {
        try {
            $shipment = $event->shipment;
            $order = $shipment->order;

            if (!$order || !$order->delegate_id) {
                return;
            }

            $delegate = User::find($order->delegate_id);
            if (!$delegate || !$delegate->isDelegate()) {
                return;
            }

            // التحقق من أن المندوب لديه صلاحية على نفس المخزن
            $warehouseIds = $order->items()
                ->with('product')
                ->get()
                ->pluck('product.warehouse_id')
                ->filter()
                ->unique()
                ->toArray();

            if (!empty($warehouseIds)) {
                $hasAccess = $delegate->warehouses()
                    ->whereIn('warehouses.id', $warehouseIds)
                    ->exists();
                if (!$hasAccess) {
                    return;
                }
            }

            // حفظ إشعار في جدول notifications
            try {
                Notification::create([
                    'user_id' => $order->delegate_id,
                    'type' => 'shipment_status_changed',
                    'title' => 'تغيير حالة الشحنة',
                    'message' => "تم تغيير حالة شحنة الطلب {$order->order_number} من '{$event->oldStatusId}' إلى '{$event->newStatusId}'",
                    'data' => [
                        'order_id' => $order->id,
                        'order_number' => $order->order_number,
                        'shipment_id' => $shipment->id,
                        'old_status' => $event->oldStatusId,
                        'new_status' => $event->newStatusId,
                    ],
                ]);
            } catch (\Exception $e) {
                Log::error('AlWaseet: Failed to create notification record', [
                    'delegate_id' => $order->delegate_id,
                    'error' => $e->getMessage(),
                ]);
            }

            // إرسال إشعار Firebase
            $fcmService = app(FirebaseCloudMessagingService::class);
            $fcmService->sendShipmentNotification($shipment, $order, $event->oldStatusId, $event->newStatusId);

            Log::info('AlWaseet: Firebase notification sent', [
                'shipment_id' => $shipment->id,
                'delegate_id' => $order->delegate_id,
            ]);
        } catch (\Exception $e) {
            Log::error('AlWaseet: Failed to send Firebase notification', [
                'shipment_id' => $event->shipment->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
