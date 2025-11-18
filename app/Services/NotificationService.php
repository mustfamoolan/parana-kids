<?php

namespace App\Services;

use App\Models\AppNotification;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class NotificationService
{
    /**
     * إرسال إشعار شامل (قابل للتوسع)
     */
    public function send($userId, $type = 'message', $title = 'إشعار جديد', $body = 'لديك إشعار جديد', $data = [])
    {
        try {
            $notification = AppNotification::create([
                'user_id' => $userId,
                'type' => $type,
                'title' => $title,
                'message' => $body, // استخدام message بدلاً من body
                'data' => array_merge([
                    'title' => $title,
                    'body' => $body,
                ], $data),
            ]);

            Log::info('Notification sent', [
                'user_id' => $userId,
                'type' => $type,
                'notification_id' => $notification->id,
                'title' => $title,
            ]);

            return $notification;
        } catch (\Exception $e) {
            Log::error('Notification error: ' . $e->getMessage());
            Log::error('Notification error stack: ' . $e->getTraceAsString());
            return null;
        }
    }

    /**
     * إرسال إشعار لمستخدم واحد
     */
    public function sendToUser($userId, $type = 'message', $title = 'إشعار جديد', $body = 'لديك إشعار جديد', $data = [])
    {
        return $this->send($userId, $type, $title, $body, $data);
    }

    /**
     * إرسال إشعار لعدة مستخدمين
     */
    public function sendToUsers(array $userIds, $type = 'message', $title = 'إشعار جديد', $body = 'لديك إشعار جديد', $data = [])
    {
        $successCount = 0;

        foreach ($userIds as $userId) {
            if ($this->send($userId, $type, $title, $body, $data)) {
                $successCount++;
            }
        }

        Log::info('Notifications sent to users', [
            'user_ids' => $userIds,
            'type' => $type,
            'success_count' => $successCount,
        ]);

        return $successCount;
    }

    /**
     * جلب عدد الإشعارات غير المقروءة
     */
    public function getUnreadCount($userId, $type = null)
    {
        $query = AppNotification::forUser($userId)->unread();

        if ($type) {
            $query->ofType($type);
        }

        return $query->count();
    }

    /**
     * جلب الإشعارات غير المقروءة
     */
    public function getUnreadNotifications($userId, $type = null, $limit = 10)
    {
        $query = AppNotification::forUser($userId)->unread();

        if ($type) {
            $query->ofType($type);
        }

        return $query->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * تحديد إشعار كمقروء
     */
    public function markAsRead($notificationId, $userId = null)
    {
        $query = AppNotification::where('id', $notificationId);

        if ($userId) {
            $query->where('user_id', $userId);
        }

        $notification = $query->first();

        if ($notification) {
            $notification->markAsRead();
            return true;
        }

        return false;
    }

    /**
     * تحديد جميع إشعارات مستخدم كمقروءة
     */
    public function markAllAsRead($userId, $type = null)
    {
        $query = AppNotification::forUser($userId)->unread();

        if ($type) {
            $query->ofType($type);
        }

        return $query->update(['read_at' => now()]);
    }

    /**
     * إرسال إشعار رسالة جديدة
     */
    public function sendNewMessageNotification($conversationId, $senderId, $messageText = null)
    {
        $conversation = \App\Models\Conversation::with('participants')->find($conversationId);

        if (!$conversation) {
            Log::warning('Conversation not found for notification', ['conversation_id' => $conversationId]);
            return false;
        }

        $recipientIds = $conversation->participants()
            ->where('user_id', '!=', $senderId)
            ->pluck('user_id')
            ->toArray();

        if (empty($recipientIds)) {
            Log::warning('No recipients found for notification', ['conversation_id' => $conversationId, 'sender_id' => $senderId]);
            return false;
        }

        if (!$messageText) {
            $lastMessage = \App\Models\Message::where('conversation_id', $conversationId)
                ->where('user_id', $senderId)
                ->latest()
                ->first();

            if ($lastMessage) {
                if ($lastMessage->type === 'image') {
                    $messageText = 'صورة';
                } elseif ($lastMessage->type === 'order') {
                    $messageText = 'طلب';
                } elseif ($lastMessage->type === 'product') {
                    $messageText = 'منتج';
                } else {
                    $messageText = $lastMessage->message ?: 'رسالة جديدة';
                }
            } else {
                $messageText = 'رسالة جديدة';
            }
        }

        $notificationTitle = 'رسالة جديدة';
        $notificationBody = $messageText;

        if (mb_strlen($notificationBody) > 100) {
            $notificationBody = mb_substr($notificationBody, 0, 100) . '...';
        }

        return $this->sendToUsers(
            $recipientIds,
            'message',
            $notificationTitle,
            $notificationBody,
            [
                'type' => 'new_message',
                'conversation_id' => (string)$conversationId,
                'sender_id' => (string)$senderId,
                'message_text' => $messageText,
            ]
        );
    }
}

