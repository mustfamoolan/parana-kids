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
     * Send notification to all admins
     */
    protected function notifyAdmins(string $type, string $title, string $message, array $data = [])
    {
        try {
            $adminIds = User::where('role', 'admin')->pluck('id')->toArray();

            if (empty($adminIds)) {
                return;
            }

            // Save to database
            foreach ($adminIds as $adminId) {
                Notification::create([
                    'user_id' => $adminId,
                    'type' => $type,
                    'title' => $title,
                    'message' => $message,
                    'data' => $data,
                ]);
            }

            // Send Push Notification
            $this->fcmService->sendToUsers($adminIds, $title, $message, $data, 'admin_mobile');

            Log::info("AdminNotificationService: Notified admins about {$type}");
        } catch (\Exception $e) {
            Log::error("AdminNotificationService: Failed to notify about {$type}: " . $e->getMessage());
        }
    }

    /**
     * Notify all admins about a new order
     */
    public function notifyNewOrder(Order $order)
    {
        $title = 'طلب جديد: ' . ($order->customer_name ?? $order->order_number);
        $body = 'تم إنشاء طلب جديد برقم ' . $order->order_number;

        $data = [
            'type' => 'new_order',
            'order_id' => (string) $order->id,
            'order_number' => $order->order_number,
            'screen' => 'order_details',
        ];

        $this->notifyAdmins('order_created', $title, $body, $data);
    }

    /**
     * Notify all admins about an order update
     */
    public function notifyOrderUpdated(Order $order, $updatedBy = null)
    {
        $updaterName = $updatedBy ? $updatedBy->name : 'المستخدم';
        $title = 'تعديل طلب: ' . $order->order_number;
        $body = "قام {$updaterName} بتعديل الطلب الخاص بـ " . ($order->customer_name ?? '');

        $data = [
            'type' => 'order_updated',
            'order_id' => (string) $order->id,
            'order_number' => $order->order_number,
            'screen' => 'order_details',
        ];

        $this->notifyAdmins('order_updated', $title, $body, $data);
    }

    /**
     * Notify all admins about an order status change
     */
    public function notifyOrderStatusChanged(Order $order, $oldStatus, $newStatus, $updatedBy = null)
    {
        $updaterName = $updatedBy ? $updatedBy->name : 'المستخدم';
        $title = 'تغيير حالة الطلب: ' . $order->order_number;
        $body = "قام {$updaterName} بتغيير حالة الطلب إلى '{$newStatus}'";

        $data = [
            'type' => 'order_status_changed',
            'order_id' => (string) $order->id,
            'order_number' => $order->order_number,
            'old_status' => $oldStatus,
            'new_status' => $newStatus,
            'screen' => 'order_details',
        ];

        $this->notifyAdmins('order_status_changed', $title, $body, $data);
    }

    /**
     * Notify all admins about an order deletion
     */
    public function notifyOrderDeleted(Order $order, $deletedBy = null)
    {
        $deleterName = $deletedBy ? $deletedBy->name : 'المستخدم';
        $title = 'حذف طلب: ' . $order->order_number;
        $body = "قام {$deleterName} بحذف الطلب الخاص بـ " . ($order->customer_name ?? '');

        $data = [
            'type' => 'order_deleted',
            'order_id' => (string) $order->id,
            'order_number' => $order->order_number,
            'screen' => 'orders_list',
        ];

        $this->notifyAdmins('order_deleted', $title, $body, $data);
    }

    /**
     * Notify all admins about a warehouse action
     */
    public function notifyWarehouseAction($warehouse, $action, $user = null)
    {
        $userName = $user ? $user->name : 'المستخدم';
        $actions = [
            'created' => 'إنشاء مخزن جديد',
            'updated' => 'تعديل مخزن',
            'deleted' => 'حذف مخزن',
        ];
        
        $actionText = $actions[$action] ?? 'عملية على المخزن';
        $title = $actionText . ': ' . $warehouse->name;
        $body = "قام {$userName} بـ " . mb_strtolower($actionText);

        $data = [
            'type' => 'warehouse_action',
            'warehouse_id' => (string) $warehouse->id,
            'warehouse_name' => $warehouse->name,
            'action' => $action,
            'screen' => 'warehouses_list',
        ];

        $this->notifyAdmins('warehouse_' . $action, $title, $body, $data);
    }

    /**
     * Notify all admins about a product action
     */
    public function notifyProductAction($product, $action, $user = null)
    {
        $userName = $user ? $user->name : 'المستخدم';
        $actions = [
            'created' => 'إضافة منتج جديد',
            'updated' => 'تعديل منتج',
            'deleted' => 'حذف منتج',
        ];
        
        $actionText = $actions[$action] ?? 'عملية على المنتج';
        $title = $actionText . ': ' . $product->name;
        $body = "قام {$userName} بـ " . mb_strtolower($actionText);

        $data = [
            'type' => 'product_action',
            'product_id' => (string) $product->id,
            'product_name' => $product->name,
            'action' => $action,
            'screen' => 'product_details',
        ];

        $this->notifyAdmins('product_' . $action, $title, $body, $data);
    }

    /**
     * Notify all admins about a message
     */
    public function notifyAdminMessage($sender, $messageText, $conversationId)
    {
        $title = 'رسالة جديدة من ' . $sender->name;
        $body = mb_substr($messageText, 0, 100) . (mb_strlen($messageText) > 100 ? '...' : '');

        $data = [
            'type' => 'new_message',
            'sender_id' => (string) $sender->id,
            'conversation_id' => (string) $conversationId,
            'screen' => 'chat',
        ];

        $this->notifyAdmins('message_received', $title, $body, $data);
    }
}
