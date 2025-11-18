<?php

namespace App\Services;

use App\Models\User;
use App\Models\SseNotification;
use Illuminate\Support\Facades\Log;

class SseNotificationService
{
    /**
     * إرسال إشعار عبر SSE لمستخدم واحد
     */
    public function sendToUser($userId, $title = 'رسالة جديدة', $body = 'لديك رسالة جديدة', $data = [])
    {
        try {
            // حفظ الإشعار في Database
            $notificationData = array_merge([
                'title' => $title,
                'body' => $body,
                'message_text' => $body,
            ], $data);

            $notification = SseNotification::create([
                'user_id' => $userId,
                'title' => $title,
                'body' => $body,
                'data' => $notificationData,
            ]);

            Log::info('SSE notification saved to database', [
                'user_id' => $userId,
                'notification_id' => $notification->id,
                'title' => $title,
                'body' => $body,
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('SSE notification error: ' . $e->getMessage());
            Log::error('SSE notification error stack: ' . $e->getTraceAsString());
            return false;
        }
    }

    /**
     * إرسال إشعار عبر SSE لعدة مستخدمين
     */
    public function sendToUsers(array $userIds, $title = 'رسالة جديدة', $body = 'لديك رسالة جديدة', $data = [])
    {
        $successCount = 0;

        foreach ($userIds as $userId) {
            if ($this->sendToUser($userId, $title, $body, $data)) {
                $successCount++;
            }
        }

        Log::info('SSE notification sent to users', [
            'user_ids' => $userIds,
            'success_count' => $successCount,
        ]);

        return $successCount > 0;
    }

    /**
     * جلب الإشعارات غير المقروءة للمستخدم
     */
    public function getNotificationsForUser($userId)
    {
        try {
            $notifications = SseNotification::forUser($userId)
                ->unread()
                ->orderBy('created_at', 'desc')
                ->limit(10)
                ->get()
                ->map(function ($notification) {
                    return [
                        'id' => $notification->id,
                        'title' => $notification->title,
                        'body' => $notification->body,
                        'data' => $notification->data ?? [],
                        'timestamp' => $notification->created_at->timestamp,
                    ];
                })
                ->toArray();

            Log::info('SSE notifications retrieved from database', [
                'user_id' => $userId,
                'count' => count($notifications),
            ]);

            return $notifications;
        } catch (\Exception $e) {
            Log::error('Error getting SSE notifications: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * حذف الإشعارات بعد إرسالها (تحديدها كمقروءة)
     */
    public function clearNotificationsForUser($userId)
    {
        try {
            $deleted = SseNotification::forUser($userId)
                ->unread()
                ->update(['read_at' => now()]);

            Log::info('SSE notifications marked as read', [
                'user_id' => $userId,
                'count' => $deleted,
            ]);

            return $deleted;
        } catch (\Exception $e) {
            Log::error('Error clearing SSE notifications: ' . $e->getMessage());
            return 0;
        }
    }

    /**
     * إرسال إشعار عند وصول رسالة جديدة
     */
    public function sendNewMessageNotification($conversationId, $senderId, $messageText = null)
    {
        // جلب جميع المشاركين في المحادثة عدا المرسل
        $conversation = \App\Models\Conversation::with('participants')->find($conversationId);

        if (!$conversation) {
            Log::warning('Conversation not found for SSE notification', ['conversation_id' => $conversationId]);
            return false;
        }

        $recipientIds = $conversation->participants()
            ->where('user_id', '!=', $senderId)
            ->pluck('user_id')
            ->toArray();

        if (empty($recipientIds)) {
            Log::warning('No recipients found for SSE notification', ['conversation_id' => $conversationId, 'sender_id' => $senderId]);
            return false;
        }

        // جلب آخر رسالة إذا لم يتم تمرير نص الرسالة
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

        // تحديد نص الإشعار
        $notificationTitle = 'رسالة جديدة';
        $notificationBody = $messageText;

        // إذا كان النص طويلاً، اختصره
        if (mb_strlen($notificationBody) > 100) {
            $notificationBody = mb_substr($notificationBody, 0, 100) . '...';
        }

        Log::info('Sending SSE notification', [
            'conversation_id' => $conversationId,
            'sender_id' => $senderId,
            'recipient_ids' => $recipientIds,
            'message_text' => $messageText,
        ]);

        // إرسال الإشعار
        $result = $this->sendToUsers(
            $recipientIds,
            $notificationTitle,
            $notificationBody,
            [
                'type' => 'new_message',
                'conversation_id' => (string)$conversationId,
                'sender_id' => (string)$senderId,
                'message_text' => $messageText,
            ]
        );

        Log::info('SSE notification result', ['result' => $result]);

        return $result;
    }
}

