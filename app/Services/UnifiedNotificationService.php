<?php

namespace App\Services;

use App\Models\AppNotification;
use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class UnifiedNotificationService
{
    protected $fcmService;
    protected $webPushService;
    protected $notificationService;

    public function __construct(
        FcmService $fcmService,
        WebPushService $webPushService,
        NotificationService $notificationService
    ) {
        $this->fcmService = $fcmService;
        $this->webPushService = $webPushService;
        $this->notificationService = $notificationService;
    }

    /**
     * إرسال إشعار شامل عبر جميع القنوات المتاحة
     *
     * @param int|array $userIds
     * @param string $type
     * @param string $title
     * @param string $body
     * @param array $data
     * @return bool
     */
    public function send($userIds, $type = 'message', $title = 'إشعار جديد', $body = 'لديك إشعار جديد', $data = [])
    {
        // تحويل userId واحد إلى array
        if (!is_array($userIds)) {
            $userIds = [$userIds];
        }

        if (empty($userIds)) {
            Log::warning('UnifiedNotificationService: No user IDs provided');
            return false;
        }

        // إعداد بيانات الإشعار
        $notificationData = array_merge([
            'type' => $type,
            'title' => $title,
            'body' => $body,
            'message_text' => $body,
            'timestamp' => now()->timestamp,
        ], $data);

        $successCount = 0;
        $totalUsers = count($userIds);

        // إرسال عبر جميع القنوات لكل مستخدم
        foreach ($userIds as $userId) {
            try {
                // 1. حفظ الإشعار في قاعدة البيانات (للتاريخ فقط)
                $notification = $this->notificationService->send(
                    $userId,
                    $type,
                    $title,
                    $body,
                    $notificationData
                );

                // 2. إرسال عبر FCM (للموبايل والديسكتوب) - Push فوري بدون polling
                try {
                    $this->fcmService->sendToUser($userId, $title, $body, $notificationData);
                } catch (\Exception $e) {
                    Log::warning('UnifiedNotificationService: FCM failed', [
                        'user_id' => $userId,
                        'error' => $e->getMessage(),
                    ]);
                }

                // 3. إرسال عبر Web Push (للديسكتوب - fallback)
                try {
                    $this->webPushService->sendToUser($userId, $title, $body, $notificationData);
                } catch (\Exception $e) {
                    Log::debug('UnifiedNotificationService: Web Push failed (non-critical)', [
                        'user_id' => $userId,
                        'error' => $e->getMessage(),
                    ]);
                }

                $successCount++;

            } catch (\Exception $e) {
                Log::error('UnifiedNotificationService: Error sending notification', [
                    'user_id' => $userId,
                    'type' => $type,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
            }
        }

        // تقليل الـ logs - فقط عند وجود أخطاء أو معلومات مهمة
        if ($successCount < $totalUsers) {
            Log::warning('UnifiedNotificationService: Some notifications failed', [
                'type' => $type,
                'total_users' => $totalUsers,
                'success_count' => $successCount,
            ]);
        } else {
            Log::debug('UnifiedNotificationService: Notifications sent', [
                'type' => $type,
                'total_users' => $totalUsers,
            ]);
        }

        return $successCount > 0;
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
        return $this->send($userIds, $type, $title, $body, $data);
    }

    /**
     * إرسال إشعار رسالة جديدة
     */
    public function sendMessageNotification($conversationId, $senderId, $messageText = null)
    {
        $conversation = \App\Models\Conversation::with('participants')->find($conversationId);

        if (!$conversation) {
            Log::warning('UnifiedNotificationService: Conversation not found', ['conversation_id' => $conversationId]);
            return false;
        }

        $recipientIds = $conversation->participants()
            ->where('user_id', '!=', $senderId)
            ->pluck('user_id')
            ->toArray();

        if (empty($recipientIds)) {
            Log::warning('UnifiedNotificationService: No recipients found', [
                'conversation_id' => $conversationId,
                'sender_id' => $senderId,
            ]);
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

        // جلب اسم المرسل
        $sender = User::find($senderId);
        $senderName = $sender ? $sender->name : 'مستخدم';

        // تحديد نص الإشعار
        $notificationTitle = 'رسالة جديدة';
        $notificationBody = $messageText;

        // إذا كان النص طويلاً، اختصره
        if (mb_strlen($notificationBody) > 100) {
            $notificationBody = mb_substr($notificationBody, 0, 100) . '...';
        }

        return $this->send(
            $recipientIds,
            'message',
            $notificationTitle,
            $notificationBody,
            [
                'type' => 'new_message',
                'conversation_id' => (string)$conversationId,
                'sender_id' => (string)$senderId,
                'sender_name' => $senderName,
                'message_text' => $messageText,
            ]
        );
    }

    /**
     * إرسال إشعار طلب
     *
     * @param Order $order
     * @param string $eventType - order_created, order_confirmed, order_cancelled, order_returned, order_exchanged
     * @param array|null $recipientIds - إذا كان null، سيتم تحديد المستلمين تلقائياً
     * @return bool
     */
    public function sendOrderNotification(Order $order, $eventType, $recipientIds = null)
    {
        // تحديد المستلمين
        if ($recipientIds === null) {
            $recipientIds = $this->getOrderNotificationRecipients($order, $eventType);
        }

        if (empty($recipientIds)) {
            Log::warning('UnifiedNotificationService: No recipients for order notification', [
                'order_id' => $order->id,
                'event_type' => $eventType,
            ]);
            return false;
        }

        // تحديد نص الإشعار حسب نوع الحدث
        $notificationConfig = $this->getOrderNotificationConfig($order, $eventType);

        return $this->send(
            $recipientIds,
            $eventType,
            $notificationConfig['title'],
            $notificationConfig['body'],
            array_merge([
                'order_id' => (string)$order->id,
                'order_number' => $order->order_number,
                'order_status' => $order->status,
            ], $notificationConfig['data'])
        );
    }

    /**
     * تحديد المستلمين لإشعارات الطلب
     */
    protected function getOrderNotificationRecipients(Order $order, $eventType)
    {
        $recipientIds = [];

        switch ($eventType) {
            case 'order_created':
                // إشعار للمدير والمجهز عند إنشاء طلب جديد
                $recipientIds = User::whereIn('role', ['admin', 'supplier'])
                    ->pluck('id')
                    ->toArray();
                break;

            case 'order_confirmed':
                // إشعار للمندوب عند تأكيد الطلب
                if ($order->delegate_id) {
                    $recipientIds = [$order->delegate_id];
                }
                // أيضاً للمدير
                $adminIds = User::where('role', 'admin')->pluck('id')->toArray();
                $recipientIds = array_merge($recipientIds, $adminIds);
                break;

            case 'order_cancelled':
            case 'order_returned':
            case 'order_exchanged':
                // إشعار للمندوب والمدير
                $recipientIds = [];
                if ($order->delegate_id) {
                    $recipientIds[] = $order->delegate_id;
                }
                $adminIds = User::where('role', 'admin')->pluck('id')->toArray();
                $recipientIds = array_merge($recipientIds, $adminIds);
                break;
        }

        return array_unique($recipientIds);
    }

    /**
     * الحصول على إعدادات الإشعار حسب نوع الحدث
     */
    protected function getOrderNotificationConfig(Order $order, $eventType)
    {
        $configs = [
            'order_created' => [
                'title' => 'طلب جديد',
                'body' => "تم إنشاء طلب جديد: {$order->order_number}",
                'data' => [
                    'action' => 'view_order',
                    'order_id' => (string)$order->id,
                ],
            ],
            'order_confirmed' => [
                'title' => 'تم تأكيد الطلب',
                'body' => "تم تأكيد الطلب: {$order->order_number}",
                'data' => [
                    'action' => 'view_order',
                    'order_id' => (string)$order->id,
                ],
            ],
            'order_cancelled' => [
                'title' => 'تم إلغاء الطلب',
                'body' => "تم إلغاء الطلب: {$order->order_number}",
                'data' => [
                    'action' => 'view_order',
                    'order_id' => (string)$order->id,
                    'cancellation_reason' => $order->cancellation_reason ?? '',
                ],
            ],
            'order_returned' => [
                'title' => 'تم إرجاع الطلب',
                'body' => "تم إرجاع الطلب: {$order->order_number}",
                'data' => [
                    'action' => 'view_order',
                    'order_id' => (string)$order->id,
                ],
            ],
            'order_exchanged' => [
                'title' => 'تم استبدال الطلب',
                'body' => "تم استبدال الطلب: {$order->order_number}",
                'data' => [
                    'action' => 'view_order',
                    'order_id' => (string)$order->id,
                ],
            ],
        ];

        return $configs[$eventType] ?? [
            'title' => 'تحديث على الطلب',
            'body' => "تم تحديث الطلب: {$order->order_number}",
            'data' => [
                'action' => 'view_order',
                'order_id' => (string)$order->id,
            ],
        ];
    }
}

