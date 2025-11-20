<?php

namespace App\Services;

use App\Models\User;
use App\Models\AppNotification;
use Illuminate\Support\Facades\Log;

class SseNotificationService
{
    protected $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * إرسال إشعار عبر SSE لمستخدم واحد
     */
    public function sendToUser($userId, $title = 'رسالة جديدة', $body = 'لديك رسالة جديدة', $data = [])
    {
        try {
            // استخدام NotificationService لحفظ الإشعار
            $notification = $this->notificationService->send(
                $userId,
                'message',
                $title,
                $body,
                array_merge(['message_text' => $body], $data)
            );

            if ($notification) {
                Log::info('SSE notification saved', [
                    'user_id' => $userId,
                    'notification_id' => $notification->id,
                    'title' => $title,
                    'body' => $body,
                ]);
                return true;
            }

            return false;
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
            $notifications = AppNotification::forUser($userId)
                ->unread()
                // إزالة ->ofType('message') لجلب جميع أنواع الإشعارات (رسائل، طلبات، إلخ)
                ->orderBy('created_at', 'desc')
                ->limit(10)
                ->get()
                ->map(function ($notification) {
                    // استخدام message أو body حسب ما هو متاح
                    $body = $notification->message ?: ($notification->data['body'] ?? $notification->data['message_text'] ?? 'لديك إشعار جديد');

                    return [
                        'id' => $notification->id,
                        'type' => $notification->type, // إضافة type للإشعار
                        'title' => $notification->title,
                        'body' => $body,
                        'message' => $notification->message, // إضافة message أيضاً
                        'data' => $notification->data ?? [],
                        'timestamp' => $notification->created_at->timestamp,
                    ];
                })
                ->toArray();

            Log::info('SSE notifications retrieved', [
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
     * نحدد فقط الإشعارات التي تم إرسالها (حسب IDs)
     */
    public function clearNotificationsForUser($userId, $notificationIds = [])
    {
        try {
            $query = AppNotification::forUser($userId)
                ->unread();
            // إزالة ->ofType('message') لجلب جميع أنواع الإشعارات

            // إذا تم تمرير IDs، نحدد فقط هذه الإشعارات
            if (!empty($notificationIds)) {
                $query->whereIn('id', $notificationIds);
            }

            $deleted = $query->update(['read_at' => now()]);

            Log::info('SSE notifications marked as read', [
                'user_id' => $userId,
                'count' => $deleted,
                'notification_ids' => $notificationIds,
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
        // استخدام NotificationService
        return $this->notificationService->sendNewMessageNotification($conversationId, $senderId, $messageText);
    }
}

