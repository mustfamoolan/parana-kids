<?php

namespace App\Services;

use App\Models\SweetAlert;
use App\Models\User;
use App\Models\Order;
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
     * Mark alert as read
     */
    public function markAsRead($alertId)
    {
        $alert = SweetAlert::find($alertId);
        if ($alert) {
            $alert->markAsRead();
            return true;
        }
        return false;
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

        $this->createForUsers($recipientIds, 'order_created', $title, $message, 'success', $data);
    }

    /**
     * Create alert for order confirmed
     * إشعار للمندوب (نفس المخزن)
     */
    public function notifyOrderConfirmed(Order $order)
    {
        if (!$order->delegate_id) {
            return;
        }

        $title = 'تم تقييد الطلب';
        $message = "تم تقييد الطلب: {$order->order_number}";
        $data = [
            'order_id' => $order->id,
            'order_number' => $order->order_number,
        ];

        $this->create($order->delegate_id, 'order_confirmed', $title, $message, 'success', $data);
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

        $this->createForUsers($recipientIds, 'order_deleted', $title, $message, 'warning', $data);
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

        $this->create($recipientId, 'message', $title, $message, 'info', $data);
    }
}

