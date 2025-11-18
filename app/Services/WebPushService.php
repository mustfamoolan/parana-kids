<?php

namespace App\Services;

use App\Models\PushSubscription;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Minishlink\WebPush\WebPush;
use Minishlink\WebPush\Subscription;

class WebPushService
{
    protected $webPush;
    protected $vapidPublicKey;
    protected $vapidPrivateKey;

    public function __construct()
    {
        $this->vapidPublicKey = env('FIREBASE_VAPID_KEY', 'BET5Odck6WkOyun9SwgVCQjxpVcCi7o0WMCyu1vJbsX9K8kdNV-DGM-THOdKWBcXIYvo5rTH4E3cKX2LNmLGYX0');
        // VAPID private key - يجب إضافتها في .env
        $this->vapidPrivateKey = env('VAPID_PRIVATE_KEY', '');

        // تهيئة WebPush مع VAPID keys
        if ($this->vapidPublicKey && $this->vapidPrivateKey) {
            $this->webPush = new WebPush([
                'VAPID' => [
                    'subject' => env('APP_URL', 'https://parana-kids.com'),
                    'publicKey' => $this->vapidPublicKey,
                    'privateKey' => $this->vapidPrivateKey,
                ],
            ]);
            Log::info('WebPush initialized with VAPID keys');
        } else {
            // بدون VAPID keys - قد لا يعمل على بعض المتصفحات
            Log::warning('WebPush initialized without VAPID_PRIVATE_KEY - Push notifications may not work. Please add VAPID_PRIVATE_KEY to .env file.');
            $this->webPush = new WebPush();
        }
    }

    /**
     * إرسال إشعار Web Push لمستخدم واحد
     */
    public function sendToUser($userId, $title = 'رسالة جديدة', $body = 'لديك رسالة جديدة', $data = [])
    {
        $subscriptions = PushSubscription::where('user_id', $userId)->get();

        if ($subscriptions->isEmpty()) {
            Log::warning('No push subscriptions found for user', ['user_id' => $userId]);
            return false;
        }

        $successCount = 0;
        $failCount = 0;

        foreach ($subscriptions as $subscription) {
            $result = $this->sendToSubscription($subscription, $title, $body, $data);
            if ($result) {
                $successCount++;
            } else {
                $failCount++;
            }
        }

        Log::info('Web Push notification sent', [
            'user_id' => $userId,
            'success' => $successCount,
            'failures' => $failCount,
        ]);

        return $successCount > 0;
    }

    /**
     * إرسال إشعار Web Push لعدة مستخدمين
     */
    public function sendToUsers(array $userIds, $title = 'رسالة جديدة', $body = 'لديك رسالة جديدة', $data = [])
    {
        $subscriptions = PushSubscription::whereIn('user_id', $userIds)->get();

        // Log تفصيلي للمستخدمين والـ subscriptions
        $usersWithSubscriptions = $subscriptions->pluck('user_id')->unique()->toArray();
        $usersWithoutSubscriptions = array_diff($userIds, $usersWithSubscriptions);

        if (!empty($usersWithoutSubscriptions)) {
            Log::info('Users without push subscriptions', [
                'user_ids' => $usersWithoutSubscriptions,
                'message' => 'These users need to open the chat page to register push subscription',
            ]);
        }

        if ($subscriptions->isEmpty()) {
            Log::warning('No push subscriptions found for users', [
                'user_ids' => $userIds,
                'message' => 'All users need to open the chat page and grant notification permission to receive push notifications',
            ]);
            return false;
        }

        Log::info('Found push subscriptions', [
            'total_subscriptions' => $subscriptions->count(),
            'users_with_subscriptions' => $usersWithSubscriptions,
            'users_without_subscriptions' => $usersWithoutSubscriptions,
        ]);

        $successCount = 0;
        $failCount = 0;

        foreach ($subscriptions as $subscription) {
            $result = $this->sendToSubscription($subscription, $title, $body, $data);
            if ($result) {
                $successCount++;
            } else {
                $failCount++;
            }
        }

        Log::info('Web Push notification sent', [
            'user_ids' => $userIds,
            'success' => $successCount,
            'failures' => $failCount,
        ]);

        return $successCount > 0;
    }

    /**
     * إرسال إشعار Web Push إلى subscription واحد
     */
    protected function sendToSubscription(PushSubscription $subscription, $title, $body, $data = [])
    {
        try {
            // إنشاء Subscription object من البيانات المحفوظة
            $pushSubscription = Subscription::create([
                'endpoint' => $subscription->endpoint,
                'keys' => [
                    'p256dh' => $subscription->public_key,
                    'auth' => $subscription->auth_token,
                ],
            ]);

            // إعداد payload للإشعار
            $payloadData = [
                'title' => $title,
                'body' => $body,
                'data' => array_merge([
                    'title' => $title,
                    'body' => $body,
                    'message_text' => $body,
                ], $data),
            ];
            
            $payload = json_encode($payloadData);
            
            Log::info('Web Push payload prepared', [
                'subscription_id' => $subscription->id,
                'payload' => $payloadData,
                'payload_length' => strlen($payload),
            ]);

            // إرسال الإشعار
            $result = $this->webPush->sendOneNotification(
                $pushSubscription,
                $payload
            );
            
            Log::info('Web Push send result', [
                'subscription_id' => $subscription->id,
                'success' => $result->isSuccess(),
            ]);

            // التحقق من النتيجة
            if ($result->isSuccess()) {
                return true;
            } else {
                $statusCode = method_exists($result, 'getStatusCode') ? $result->getStatusCode() : null;
                $reason = method_exists($result, 'getReason') ? $result->getReason() : 'Unknown error';

                Log::warning('Web Push notification failed', [
                    'subscription_id' => $subscription->id,
                    'endpoint' => substr($subscription->endpoint, 0, 50) . '...',
                    'status_code' => $statusCode,
                    'reason' => $reason,
                ]);

                // حذف subscription إذا كان غير صالح (410 Gone أو 404 Not Found)
                if ($statusCode && in_array($statusCode, [410, 404])) {
                    Log::info('Deleting invalid subscription', ['subscription_id' => $subscription->id]);
                    $subscription->delete();
                }

                return false;
            }
        } catch (\Exception $e) {
            Log::error('Web Push notification error: ' . $e->getMessage());
            Log::error('Web Push error stack: ' . $e->getTraceAsString());
            return false;
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
            Log::warning('Conversation not found for Web Push notification', ['conversation_id' => $conversationId]);
            return false;
        }

        $recipientIds = $conversation->participants()
            ->where('user_id', '!=', $senderId)
            ->pluck('user_id')
            ->toArray();

        if (empty($recipientIds)) {
            Log::warning('No recipients found for Web Push notification', ['conversation_id' => $conversationId, 'sender_id' => $senderId]);
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

        Log::info('Sending Web Push notification', [
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

        Log::info('Web Push notification result', ['result' => $result]);

        return $result;
    }
}

