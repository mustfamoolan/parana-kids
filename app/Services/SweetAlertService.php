<?php

namespace App\Services;

use App\Models\SweetAlert;
use App\Models\User;
use App\Models\Order;
use App\Models\Notification;
use Illuminate\Support\Facades\Log;

class SweetAlertService
{
    /**
     * Create a sweet alert for a single user
     */
    public function create($userId, $type, $title, $message, $icon = 'info', $data = [])
    {
        try {
            return SweetAlert::create([
                'user_id' => $userId,
                'type' => $type,
                'title' => $title,
                'message' => $message,
                'icon' => $icon,
                'data' => $data,
            ]);
        } catch (\Exception $e) {
            Log::error('SweetAlertService: Failed to create alert', [
                'user_id' => $userId,
                'type' => $type,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Create sweet alerts for multiple users
     */
    public function createForUsers($userIds, $type, $title, $message, $icon = 'info', $data = [])
    {
        if (empty($userIds)) {
            return [];
        }

        $alerts = [];
        foreach ($userIds as $userId) {
            $alert = $this->create($userId, $type, $title, $message, $icon, $data);
            if ($alert) {
                $alerts[] = $alert;
            }
        }

        return $alerts;
    }

    /**
     * Get unread alerts for a user
     */
    public function getUnreadForUser($userId)
    {
        $alerts = SweetAlert::where('user_id', $userId)
            ->unread()
            ->orderBy('created_at', 'desc')
            ->get();

        Log::info('SweetAlertService: getUnreadForUser', [
            'user_id' => $userId,
            'count' => $alerts->count(),
        ]);

        return $alerts;
    }

    /**
     * Mark alert as read and delete it
     */
    public function markAsRead($alertId)
    {
        $alert = SweetAlert::find($alertId);
        if ($alert) {
            $alert->delete();
            return true;
        }
        return false;
    }

    /**
     * Delete all alerts for a specific order
     */
    public function deleteOrderAlerts($orderId, $userId = null)
    {
        $query = SweetAlert::whereIn('type', ['order_created', 'order_confirmed', 'order_deleted'])
            ->where('data->order_id', $orderId);

        if ($userId) {
            $query->where('user_id', $userId);
        }

        return $query->delete();
    }

    /**
     * Delete all alerts for a specific conversation
     */
    public function deleteConversationAlerts($conversationId, $userId = null)
    {
        $query = SweetAlert::where('type', 'message')
            ->where('data->conversation_id', $conversationId);

        if ($userId) {
            $query->where('user_id', $userId);
        }

        return $query->delete();
    }

    /**
     * Check if user has unread alert for a specific order
     */
    public function hasUnreadAlertForOrder($orderId, $userId)
    {
        return SweetAlert::where('user_id', $userId)
            ->whereIn('type', ['order_created', 'order_confirmed', 'order_deleted'])
            ->where('data->order_id', $orderId)
            ->unread()
            ->exists();
    }

    /**
     * Check if user has unread alert for a specific conversation
     */
    public function hasUnreadAlertForConversation($conversationId, $userId)
    {
        return SweetAlert::where('user_id', $userId)
            ->where('type', 'message')
            ->where('data->conversation_id', $conversationId)
            ->unread()
            ->exists();
    }

    /**
     * Create alert for order created
     * إشعار للمجهز (نفس المخزن) أو المدير
     */
    public function notifyOrderCreated(Order $order)
    {
        $warehouseIds = $order->items()
            ->with('product')
            ->get()
            ->pluck('product.warehouse_id')
            ->filter()
            ->unique()
            ->toArray();

        if (empty($warehouseIds)) {
            return;
        }

        // جلب المجهزين (suppliers) الذين لديهم صلاحية على نفس المخزن
        $supplierIds = User::whereIn('role', ['admin', 'supplier'])
            ->whereHas('warehouses', function($q) use ($warehouseIds) {
                $q->whereIn('warehouses.id', $warehouseIds);
            })
            ->pluck('id')
            ->toArray();

        // إضافة المديرين دائماً
        $adminIds = User::where('role', 'admin')->pluck('id')->toArray();
        $recipientIds = array_unique(array_merge($supplierIds, $adminIds));

        if (empty($recipientIds)) {
            return;
        }

        $title = 'طلب جديد';
        $message = "تم إنشاء طلب جديد: {$order->order_number}";
        $data = [
            'order_id' => $order->id,
            'order_number' => $order->order_number,
        ];

        // إرسال SweetAlert
        $this->createForUsers($recipientIds, 'order_created', $title, $message, 'success', $data);

        // حفظ إشعار في جدول notifications
        foreach ($recipientIds as $recipientId) {
            try {
                Notification::create([
                    'user_id' => $recipientId,
                    'type' => 'order_created',
                    'title' => $title,
                    'message' => $message,
                    'data' => $data,
                ]);
            } catch (\Exception $e) {
                Log::error('SweetAlertService: Failed to create notification record', [
                    'user_id' => $recipientId,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // إرسال إشعارات تليجرام للمستخدمين المربوطين
        try {
            $telegramService = app(TelegramService::class);
            $recipients = User::whereIn('id', $recipientIds)
                ->whereHas('telegramChats')
                ->get();

            foreach ($recipients as $recipient) {
                $telegramService->sendToAllUserDevices($recipient, function($chatId) use ($telegramService, $order) {
                    $telegramService->sendOrderNotification($chatId, $order);
                });
            }
        } catch (\Exception $e) {
            Log::error('SweetAlertService: Failed to send Telegram notifications', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);
        }

        // إرسال إشعارات Firebase للمندوبين فقط
        try {
            $fcmService = app(FirebaseCloudMessagingService::class);
            $delegates = User::whereIn('id', $recipientIds)
                ->where('role', 'delegate')
                ->get();

            foreach ($delegates as $delegate) {
                $fcmService->sendOrderNotification($order, 'order_created');
            }
        } catch (\Exception $e) {
            Log::error('SweetAlertService: Failed to send FCM notifications', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Create alert for order confirmed
     * إشعار للمجهز (نفس المخزن) أو المدير
     */
    public function notifyOrderConfirmed(Order $order)
    {
        $warehouseIds = $order->items()
            ->with('product')
            ->get()
            ->pluck('product.warehouse_id')
            ->filter()
            ->unique()
            ->toArray();

        if (empty($warehouseIds)) {
            return;
        }

        // جلب المجهزين (suppliers) الذين لديهم صلاحية على نفس المخزن
        $supplierIds = User::whereIn('role', ['admin', 'supplier'])
            ->whereHas('warehouses', function($q) use ($warehouseIds) {
                $q->whereIn('warehouses.id', $warehouseIds);
            })
            ->pluck('id')
            ->toArray();

        // إضافة المديرين دائماً
        $adminIds = User::where('role', 'admin')->pluck('id')->toArray();
        $recipientIds = array_unique(array_merge($supplierIds, $adminIds));

        if (empty($recipientIds)) {
            return;
        }

        $title = 'تم تقييد الطلب';
        $message = "تم تقييد الطلب: {$order->order_number}";
        $data = [
            'order_id' => $order->id,
            'order_number' => $order->order_number,
        ];

        // إرسال SweetAlert
        $this->createForUsers($recipientIds, 'order_confirmed', $title, $message, 'success', $data);

        // حفظ إشعار في جدول notifications
        foreach ($recipientIds as $recipientId) {
            try {
                Notification::create([
                    'user_id' => $recipientId,
                    'type' => 'order_confirmed',
                    'title' => $title,
                    'message' => $message,
                    'data' => $data,
                ]);
            } catch (\Exception $e) {
                Log::error('SweetAlertService: Failed to create notification record', [
                    'user_id' => $recipientId,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // إرسال إشعارات تليجرام للمستخدمين المربوطين
        try {
            $telegramService = app(TelegramService::class);
            $recipients = User::whereIn('id', $recipientIds)
                ->whereHas('telegramChats')
                ->get();

            foreach ($recipients as $recipient) {
                $telegramService->sendToAllUserDevices($recipient, function($chatId) use ($telegramService, $order) {
                    $telegramService->sendOrderRestrictedNotification($chatId, $order);
                });
            }
        } catch (\Exception $e) {
            Log::error('SweetAlertService: Failed to send Telegram notifications for order confirmed', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);
        }

        // إرسال إشعارات Firebase للمندوبين فقط
        try {
            $fcmService = app(FirebaseCloudMessagingService::class);
            $delegates = User::whereIn('id', $recipientIds)
                ->where('role', 'delegate')
                ->get();

            foreach ($delegates as $delegate) {
                $fcmService->sendOrderNotification($order, 'order_confirmed');
            }
        } catch (\Exception $e) {
            Log::error('SweetAlertService: Failed to send FCM notifications for order confirmed', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Create alert for order deleted
     * إشعار للمجهز (نفس المخزن) أو المدير أو المندوب (نفس المخزن)
     */
    public function notifyOrderDeleted(Order $order)
    {
        $warehouseIds = $order->items()
            ->with('product')
            ->get()
            ->pluck('product.warehouse_id')
            ->filter()
            ->unique()
            ->toArray();

        $recipientIds = [];

        // إضافة المجهزين (نفس المخزن)
        if (!empty($warehouseIds)) {
            $supplierIds = User::whereIn('role', ['admin', 'supplier'])
                ->whereHas('warehouses', function($q) use ($warehouseIds) {
                    $q->whereIn('warehouses.id', $warehouseIds);
                })
                ->pluck('id')
                ->toArray();
            $recipientIds = array_merge($recipientIds, $supplierIds);
        }

        // إضافة المديرين دائماً
        $adminIds = User::where('role', 'admin')->pluck('id')->toArray();
        $recipientIds = array_merge($recipientIds, $adminIds);

        // إضافة المندوب (نفس المخزن)
        if ($order->delegate_id) {
            $delegate = User::find($order->delegate_id);
            if ($delegate && !empty($warehouseIds)) {
                // التحقق من أن المندوب لديه صلاحية على نفس المخزن
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
            return;
        }

        $title = 'تم حذف الطلب';
        $message = "تم حذف الطلب: {$order->order_number}";
        $data = [
            'order_id' => $order->id,
            'order_number' => $order->order_number,
        ];

        // إرسال SweetAlert
        $this->createForUsers($recipientIds, 'order_deleted', $title, $message, 'warning', $data);

        // حفظ إشعار في جدول notifications
        foreach ($recipientIds as $recipientId) {
            try {
                Notification::create([
                    'user_id' => $recipientId,
                    'type' => 'order_deleted',
                    'title' => $title,
                    'message' => $message,
                    'data' => $data,
                ]);
            } catch (\Exception $e) {
                Log::error('SweetAlertService: Failed to create notification record', [
                    'user_id' => $recipientId,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // إرسال إشعارات تليجرام للمستخدمين المربوطين
        try {
            $telegramService = app(TelegramService::class);
            $recipients = User::whereIn('id', $recipientIds)
                ->whereHas('telegramChats')
                ->get();

            foreach ($recipients as $recipient) {
                $telegramService->sendToAllUserDevices($recipient, function($chatId) use ($telegramService, $order) {
                    $telegramService->sendOrderDeletedNotification($chatId, $order);
                });
            }
        } catch (\Exception $e) {
            Log::error('SweetAlertService: Failed to send Telegram notifications for order deleted', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);
        }

        // إرسال إشعارات Firebase للمندوبين فقط
        try {
            $fcmService = app(FirebaseCloudMessagingService::class);
            $delegates = User::whereIn('id', $recipientIds)
                ->where('role', 'delegate')
                ->get();

            foreach ($delegates as $delegate) {
                $fcmService->sendOrderNotification($order, 'order_deleted');
            }
        } catch (\Exception $e) {
            Log::error('SweetAlertService: Failed to send FCM notifications for order deleted', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Create alert for new message
     * إشعار للمستلم فقط
     */
    public function notifyNewMessage($conversationId, $senderId, $recipientId, $messageText)
    {
        $sender = User::find($senderId);
        if (!$sender) {
            return;
        }

        $title = 'رسالة جديدة';
        $message = "رسالة من {$sender->name}: " . mb_substr($messageText, 0, 50);
        $data = [
            'conversation_id' => $conversationId,
            'sender_id' => $senderId,
        ];

        // إرسال SweetAlert
        $this->create($recipientId, 'message', $title, $message, 'info', $data);

        // حفظ إشعار في جدول notifications
        try {
            Notification::create([
                'user_id' => $recipientId,
                'type' => 'message',
                'title' => $title,
                'message' => $message,
                'data' => $data,
            ]);
        } catch (\Exception $e) {
            Log::error('SweetAlertService: Failed to create notification record for message', [
                'user_id' => $recipientId,
                'error' => $e->getMessage(),
            ]);
        }

        // إرسال إشعار Firebase للمستلم إذا كان مندوب
        try {
            $recipient = User::find($recipientId);
            if ($recipient && $recipient->isDelegate()) {
                $fcmService = app(FirebaseCloudMessagingService::class);
                $fcmService->sendMessageNotification($conversationId, $senderId, $recipientId, $messageText);
            }
        } catch (\Exception $e) {
            Log::error('SweetAlertService: Failed to send FCM notification for message', [
                'recipient_id' => $recipientId,
                'error' => $e->getMessage(),
            ]);
        }
    }
}

