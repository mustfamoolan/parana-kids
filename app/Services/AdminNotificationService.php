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
     * Send notification to all admins and relevant suppliers
     */
    protected function notifyAdminsAndSuppliers(Order $order, string $type, string $title, string $message, array $data = [], string $icon = 'success')
    {
        try {
            // Get Warehouse IDs for the order
            $warehouseIds = $order->items()
                ->with('product')
                ->get()
                ->pluck('product.warehouse_id')
                ->filter()
                ->unique()
                ->toArray();

            // Get Suppliers for these warehouses
            $supplierIds = [];
            if (!empty($warehouseIds)) {
                $supplierIds = User::where('role', 'supplier')
                    ->whereHas('warehouses', function ($q) use ($warehouseIds) {
                        $q->whereIn('warehouses.id', $warehouseIds);
                    })
                    ->pluck('id')
                    ->toArray();
            }

            // Get all Admins
            $adminIds = User::where('role', 'admin')->pluck('id')->toArray();

            // Combine recipients
            $recipientIds = array_unique(array_merge($adminIds, $supplierIds));

            if (empty($recipientIds)) {
                return;
            }

            // 1. Save to database notifications
            foreach ($recipientIds as $recipientId) {
                Notification::create([
                    'user_id' => $recipientId,
                    'type' => $type,
                    'title' => $title,
                    'message' => $message,
                    'data' => $data,
                ]);
            }

            // 2. Send Push Notifications (Admin App type)
            $this->fcmService->sendToUsers($recipientIds, $title, $message, $data, 'admin_mobile');

            // 3. Send Telegram Alerts
            try {
                $telegramService = app(TelegramService::class);
                $recipientsWithTelegram = User::whereIn('id', $recipientIds)
                    ->whereHas('telegramChats')
                    ->get();

                foreach ($recipientsWithTelegram as $recipient) {
                    $telegramService->sendToAllUserDevices($recipient, function ($chatId) use ($telegramService, $order) {
                        $telegramService->sendOrderNotification($chatId, $order);
                    });
                }
            } catch (\Exception $e) {
                Log::error("AdminNotificationService: Telegram failed: " . $e->getMessage());
            }

            // 4. Create SweetAlerts (for web/app popups)
            try {
                $sweetAlertService = app(SweetAlertService::class);
                $sweetAlertService->createForUsers($recipientIds, $type, $title, $message, $icon, $data);
            } catch (\Exception $e) {
                Log::error("AdminNotificationService: SweetAlert failed: " . $e->getMessage());
            }

            Log::info("AdminNotificationService: Notified " . count($recipientIds) . " users about {$type}");
        } catch (\Exception $e) {
            Log::error("AdminNotificationService: Global notify failed: " . $e->getMessage());
        }
    }

    /**
     * Notify about a new order
     */
    public function notifyNewOrder(Order $order)
    {
        $customerName = $order->customer_name ?? "طلب #{$order->order_number}";
        $title = $customerName;
        $body = 'طلب جديد';

        $data = [
            'type' => 'order_created',
            'order_id' => (string) $order->id,
            'order_number' => $order->order_number,
            'customer_name' => $order->customer_name,
            'screen' => 'order_details',
        ];

        $this->notifyAdminsAndSuppliers($order, 'order_created', $title, $body, $data);
        
        // Also notify the delegate (confirmation)
        if ($order->delegate_id) {
            $this->fcmService->sendOrderNotification($order, 'order_created');
        }
    }

    /**
     * Notify about an order update
     */
    public function notifyOrderUpdated(Order $order, $updatedBy = null)
    {
        $customerName = $order->customer_name ?? "طلب #{$order->order_number}";
        $title = $customerName;
        $body = 'تعديل على الطلب';

        $data = [
            'type' => 'order_updated',
            'order_id' => (string) $order->id,
            'order_number' => $order->order_number,
            'customer_name' => $order->customer_name,
            'screen' => 'order_details',
        ];

        $this->notifyAdminsAndSuppliers($order, 'order_updated', $title, $body, $data);
        
        // Notify delegate if updated by admin/supplier
        if ($updatedBy && !$updatedBy->isDelegate() && $order->delegate_id) {
            $this->fcmService->sendOrderNotification($order, 'order_updated');
        }
    }

    /**
     * Notify about an order status change
     */
    public function notifyOrderStatusChanged(Order $order, $oldStatus, $newStatus, $updatedBy = null)
    {
        $translatedStatus = $this->translateStatus($newStatus);
        $body = "تم {$translatedStatus}";

        $data = [
            'type' => 'order_status_changed',
            'order_id' => (string) $order->id,
            'order_number' => $order->order_number,
            'customer_name' => $order->customer_name,
            'old_status' => $oldStatus,
            'new_status' => $newStatus,
            'new_status_text' => $translatedStatus,
            'screen' => 'order_details',
        ];

        $customerName = $order->customer_name ?? "طلب #{$order->order_number}";
        $title = $customerName;
        
        // Enhance body for FCM if it's for admins
        $fcmBody = "📦 {$order->order_number} | {$body}";
        if ($updatedBy) {
            $fcmBody .= " (بواسطة {$updatedBy->name})";
        }

        $this->notifyAdminsAndSuppliers($order, 'order_status_changed', $title, $fcmBody, $data);
        
        // Notify delegate
        if ($order->delegate_id) {
            $this->fcmService->sendOrderNotification($order, 'order_confirmed'); // Using general confirmed/status change
        }
    }

    /**
     * Notify about an order deletion
     */
    public function notifyOrderDeleted(Order $order, $deletedBy = null)
    {
        $customerName = $order->customer_name ?? "طلب #{$order->order_number}";
        $title = $customerName;
        $body = 'تم حذف الطلب';

        $data = [
            'type' => 'order_deleted',
            'order_id' => (string) $order->id,
            'order_number' => $order->order_number,
            'customer_name' => $order->customer_name,
            'screen' => 'orders_list',
        ];

        $this->notifyAdminsAndSuppliers($order, 'order_deleted', $title, $body, $data, 'warning');
        
        // Notify delegate
        if ($order->delegate_id) {
            $this->fcmService->sendOrderNotification($order, 'order_deleted');
        }
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

        $adminIds = User::where('role', 'admin')->pluck('id')->toArray();
        foreach ($adminIds as $adminId) {
            Notification::create([
                'user_id' => $adminId,
                'type' => 'warehouse_' . $action,
                'title' => $title,
                'message' => $body,
                'data' => $data,
            ]);
        }
        $this->fcmService->sendToUsers($adminIds, $title, $body, $data, 'admin_mobile');
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

        $adminIds = User::where('role', 'admin')->pluck('id')->toArray();
        foreach ($adminIds as $adminId) {
            Notification::create([
                'user_id' => $adminId,
                'type' => 'product_' . $action,
                'title' => $title,
                'message' => $body,
                'data' => $data,
            ]);
        }
        $this->fcmService->sendToUsers($adminIds, $title, $body, $data, 'admin_mobile');
    }

    /**
     * Notify all admins about a product size action
     */
    public function notifyProductSizeAction($productSize, $action, $user = null)
    {
        $userName = $user ? $user->name : 'المستخدم';
        $productName = $productSize->product ? $productSize->product->name : 'منتج غير معروف';
        
        $actions = [
            'created' => 'إضافة قياس جديد',
            'updated' => 'تعديل كمية/قياس',
            'deleted' => 'حذف قياس',
        ];
        
        $actionText = $actions[$action] ?? 'عملية على القياس';
        $title = "{$actionText}: {$productName}";
        $body = "قام {$userName} بـ " . mb_strtolower($actionText) . " ({$productSize->size_name}) - الكمية: {$productSize->quantity}";

        $data = [
            'type' => 'product_size_action',
            'product_id' => (string) $productSize->product_id,
            'size_id' => (string) $productSize->id,
            'size_name' => $productSize->size_name,
            'quantity' => (string) $productSize->quantity,
            'action' => $action,
            'screen' => 'product_details',
        ];

        $adminIds = User::where('role', 'admin')->pluck('id')->toArray();
        foreach ($adminIds as $adminId) {
            Notification::create([
                'user_id' => $adminId,
                'type' => 'product_size_' . $action,
                'title' => $title,
                'message' => $body,
                'data' => $data,
            ]);
        }
        $this->fcmService->sendToUsers($adminIds, $title, $body, $data, 'admin_mobile');
    }

    /**
     * Notify all admins about AlWaseet status change
     */
    public function notifyAlWaseetStatusChanged($shipment, $oldStatusText, $newStatusText)
    {
        $order = $shipment->order;
        if (!$order) return;

        $customerName = $order->customer_name ?? 'غير معروف';
        $title = "الواسط: {$customerName}";
        
        // Enhance body for FCM/Telegram
        $alwaseetId = $shipment->alwaseet_order_id ?? '---';
        $body = "📊 {$newStatusText}\n📦 {$order->order_number} | 🔢 {$alwaseetId}";

        $data = [
            'type' => 'alwaseet_status_changed',
            'order_id' => (string) $shipment->order_id,
            'shipment_id' => (string) $shipment->id,
            'old_status' => $oldStatusText,
            'new_status' => $newStatusText,
            'screen' => 'order_details',
        ];

        // Notify Admins and Suppliers
        $this->notifyAdminsAndSuppliers($order, 'alwaseet_status_changed', $title, $body, $data, 'info');

        // Notify Delegate separately via FCM if they are assigned
        if ($order->delegate_id) {
            $this->fcmService->sendShipmentNotification($shipment, $order, $oldStatusText, $newStatusText);
        }
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

        $adminIds = User::where('role', 'admin')->pluck('id')->toArray();
        foreach ($adminIds as $adminId) {
            Notification::create([
                'user_id' => $adminId,
                'type' => 'message_received',
                'title' => $title,
                'message' => $body,
                'data' => $data,
            ]);
        }
        $this->fcmService->sendToUsers($adminIds, $title, $body, $data, 'admin_mobile');
    }

    /**
     * Translate order status to concise Arabic
     */
    protected function translateStatus($status)
    {
        $map = [
            'pending' => 'بانتظار التجهيز',
            'confirmed' => 'التقييد/التجهيز',
            'cancelled' => 'الإلغاء',
            'returned' => 'الإرجاع',
            'exchanged' => 'الاستبدال',
            'shipped' => 'الشحن',
            'delivered' => 'التسليم',
        ];
        
        return $map[$status] ?? $status;
    }
}
