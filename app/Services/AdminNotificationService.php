<?php

namespace App\Services;

use App\Models\User;
use App\Models\Order;
use App\Models\Notification;
use Illuminate\Support\Facades\Log;

class AdminNotificationService
{
    protected $fcmService;

    public function __construct(FirebaseCloudMessagingService $fcmService)
    {
        $this->fcmService = $fcmService;
    }

    /**
     * Notify all admins about a new order
     */
    public function notifyNewOrder(Order $order)
    {
        try {
            $adminIds = User::where('role', 'admin')->pluck('id')->toArray();

            if (empty($adminIds)) {
                return;
            }

            $title = 'طلب جديد: ' . ($order->customer_name ?? $order->order_number);
            $body = 'تم إنشاء طلب جديد برقم ' . $order->order_number;

            $data = [
                'type' => 'new_order',
                'order_id' => (string) $order->id,
                'order_number' => $order->order_number,
                'screen' => 'order_details',
            ];

            // Save to database
            foreach ($adminIds as $adminId) {
                Notification::create([
                    'user_id' => $adminId,
                    'type' => 'order_created',
                    'title' => $title,
                    'message' => $body,
                    'data' => $data,
                ]);
            }

            $this->fcmService->sendToUsers($adminIds, $title, $body, $data, 'admin_mobile');

            Log::info('AdminNotificationService: Notified admins about new order', [
                'order_id' => $order->id,
                'admins_count' => count($adminIds)
            ]);
        } catch (\Exception $e) {
            Log::error('AdminNotificationService: Failed to notify about new order: ' . $e->getMessage());
        }
    }

    /**
     * Notify all admins about an order update (by delegate)
     */
    public function notifyOrderUpdated(Order $order, $updatedBy = null)
    {
        try {
            $adminIds = User::where('role', 'admin')->pluck('id')->toArray();

            if (empty($adminIds)) {
                return;
            }

            $updaterName = $updatedBy ? $updatedBy->name : 'المندوب';
            $title = 'تعديل طلب: ' . $order->order_number;
            $body = "قام {$updaterName} بتعديل الطلب الخاص بـ " . ($order->customer_name ?? '');

            $data = [
                'type' => 'order_updated',
                'order_id' => (string) $order->id,
                'order_number' => $order->order_number,
                'screen' => 'order_details',
            ];

            // Save to database
            foreach ($adminIds as $adminId) {
                Notification::create([
                    'user_id' => $adminId,
                    'type' => 'order_updated',
                    'title' => $title,
                    'message' => $body,
                    'data' => $data,
                ]);
            }

            $this->fcmService->sendToUsers($adminIds, $title, $body, $data, 'admin_mobile');
        } catch (\Exception $e) {
            Log::error('AdminNotificationService: Failed to notify about order update: ' . $e->getMessage());
        }
    }
}
