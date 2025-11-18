<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class SseNotificationService
{
    /**
     * إرسال إشعار عبر SSE لمستخدم واحد
     */
    public function sendToUser($userId, $title = 'رسالة جديدة', $body = 'لديك رسالة جديدة', $data = [])
    {
        try {
            // حفظ الإشعار في cache للمستخدم
            $notification = [
                'title' => $title,
                'body' => $body,
                'data' => array_merge([
                    'title' => $title,
                    'body' => $body,
                    'message_text' => $body,
                ], $data),
                'timestamp' => time(),
            ];

            // حفظ الإشعار في قائمة إشعارات المستخدم
            $this->sendSseEvent($userId, $notification);

            Log::info('SSE notification sent', [
                'user_id' => $userId,
                'title' => $title,
                'body' => $body,
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('SSE notification error: ' . $e->getMessage());
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
     * إرسال SSE event للمستخدم
     */
    protected function sendSseEvent($userId, $notification)
    {
        // حفظ الإشعار في cache للمستخدم
        $cacheKey = "sse_notification_{$userId}";
        $notifications = Cache::get($cacheKey, []);
        
        // إضافة الإشعار الجديد في البداية
        array_unshift($notifications, $notification);
        
        // الاحتفاظ بآخر 10 إشعارات فقط
        if (count($notifications) > 10) {
            $notifications = array_slice($notifications, 0, 10);
        }
        
        Cache::put($cacheKey, $notifications, 300); // 5 دقائق
    }

    /**
     * جلب الإشعارات للمستخدم
     */
    public function getNotificationsForUser($userId)
    {
        $cacheKey = "sse_notification_{$userId}";
        return Cache::get($cacheKey, []);
    }

    /**
     * حذف الإشعارات للمستخدم
     */
    public function clearNotificationsForUser($userId)
    {
        $cacheKey = "sse_notification_{$userId}";
        Cache::forget($cacheKey);
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

