<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Notification;
use Illuminate\Support\Facades\Log;

class CustomerNotificationService
{
    protected $fcmService;

    public function __construct(FirebaseCloudMessagingService $fcmService)
    {
        $this->fcmService = $fcmService;
    }

    /**
     * Notify the customer about an order status change
     */
    public function notifyStatusChanged(Order $order, $newStatus)
    {
        if (!$order->customer_id) {
            return;
        }

        $translatedStatus = $this->translateStatusForCustomer($newStatus);
        if (!$translatedStatus) {
            return; // Don't notify for internal statuses like 'pending' usually
        }

        $title = "تحديث لحالة طلبك";
        $body = "طلبك رقم {$order->order_number} أصبح الآن: {$translatedStatus}";

        $data = [
            'type' => 'order_status_changed',
            'order_id' => (string) $order->id,
            'order_number' => $order->order_number,
            'new_status' => $newStatus,
            'new_status_text' => $translatedStatus,
            'screen' => 'order_details',
        ];

        // 1. Save in-app notification (if we use a shared notifications table)
        try {
            Notification::create([
                'user_id' => $order->customer_id,
                'type' => 'order_status',
                'title' => $title,
                'message' => $body,
                'data' => $data,
            ]);
        } catch (\Exception $e) {
            Log::error("CustomerNotificationService: Failed to save in-app notification: " . $e->getMessage());
        }

        // 2. Send Push Notification
        $this->fcmService->sendToUser($order->customer_id, $title, $body, $data, 'customer_mobile');
    }

    /**
     * Translate order status to reader-friendly Arabic for the customer
     */
    protected function translateStatusForCustomer($status)
    {
        $map = [
            'confirmed' => 'مجهز (بانتظار الشحن)',
            'shipped' => 'تم الشحن (قيد التوصيل)',
            'delivered' => 'تم التسليم بنجاح',
            'cancelled' => 'تم إلغاء الطلب',
            'returned' => 'تم إرجاع الطلب',
        ];
        
        return $map[$status] ?? null;
    }
}
