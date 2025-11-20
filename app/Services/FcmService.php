<?php

namespace App\Services;

use App\Models\User;
use App\Models\FcmToken;
use Kreait\Firebase\Factory;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;
use Kreait\Firebase\Exception\MessagingException;
use Illuminate\Support\Facades\Log;

class FcmService
{
    protected $messaging;

    public function __construct()
    {
        try {
            $credentialsPath = config('services.firebase.credentials');

            // إذا كان المسار نسبي، تحويله إلى مسار مطلق
            if (!str_starts_with($credentialsPath, '/') && !preg_match('/^[A-Za-z]:\\\\/', $credentialsPath)) {
                $credentialsPath = storage_path('app/' . basename($credentialsPath));
            }

            if (!file_exists($credentialsPath)) {
                Log::warning('Firebase credentials file not found: ' . $credentialsPath);
                Log::warning('Trying alternative path: ' . storage_path('app/parana-kids-firebase-adminsdk-fbsvc-aabd2ef994.json'));

                // محاولة المسار البديل
                $alternativePath = storage_path('app/parana-kids-firebase-adminsdk-fbsvc-aabd2ef994.json');
                if (file_exists($alternativePath)) {
                    $credentialsPath = $alternativePath;
                    Log::info('Using alternative path: ' . $credentialsPath);
                } else {
                    return;
                }
            }

            $factory = (new Factory)->withServiceAccount($credentialsPath);
            $this->messaging = $factory->createMessaging();
            Log::info('Firebase initialized successfully');
        } catch (\Exception $e) {
            Log::error('Failed to initialize Firebase: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
        }
    }

    /**
     * إرسال إشعار FCM لمستخدم واحد
     */
    public function sendToUser($userId, $title = 'رسالة جديدة', $body = 'لديك رسالة جديدة', $data = [])
    {
        if (!$this->messaging) {
            return false;
        }

        $tokens = FcmToken::where('user_id', $userId)->pluck('token')->toArray();

        if (empty($tokens)) {
            return false;
        }

        return $this->sendToTokens($tokens, $title, $body, $data);
    }

    /**
     * إرسال إشعار FCM لعدة مستخدمين
     */
    public function sendToUsers(array $userIds, $title = 'رسالة جديدة', $body = 'لديك رسالة جديدة', $data = [])
    {
        if (!$this->messaging) {
            Log::warning('FCM messaging not initialized');
            return false;
        }

        $tokens = FcmToken::whereIn('user_id', $userIds)->pluck('token')->toArray();

        if (empty($tokens)) {
            Log::warning('No FCM tokens found for users', [
                'user_ids' => $userIds,
                'tokens_count' => 0,
            ]);
            return false;
        }

        // تقليل الـ logs
        Log::debug('FCM tokens found', [
            'user_count' => count($userIds),
            'tokens_count' => count($tokens),
        ]);

        return $this->sendToTokens($tokens, $title, $body, $data);
    }

    /**
     * إرسال إشعار FCM إلى tokens محددة
     */
    protected function sendToTokens(array $tokens, $title, $body, $data = [])
    {
        if (!$this->messaging || empty($tokens)) {
            return false;
        }

        try {
            // الحل النهائي: إرسال data-only message وإظهار الإشعار يدوياً في Service Worker
            // هذا يضمن أن نص الرسالة يظهر دائماً
            $message = CloudMessage::new()
                ->withData(array_merge([
                    'title' => $title, // العنوان في data
                    'body' => $body, // نص الرسالة في data
                    'message_text' => $body, // backup
                    'notification_title' => $title, // backup
                    'notification_body' => $body, // backup
                    'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
                ], $data))
                ->withAndroidConfig([
                    'priority' => 'high',
                ])
                ->withApnsConfig([
                    'headers' => [
                        'apns-priority' => '10',
                    ],
                    'payload' => [
                        'aps' => [
                            'sound' => 'default',
                            'badge' => 1,
                            'content-available' => 1,
                        ],
                    ],
                ]);

            // تقليل الـ logs
            Log::debug('FCM message prepared', [
                'tokens_count' => count($tokens),
            ]);

            // إرسال لجميع الـ tokens
            $report = $this->messaging->sendMulticast($message, $tokens);

            // حذف الـ tokens الفاشلة
            if ($report->hasFailures()) {
                $invalidTokens = [];
                foreach ($report->failures() as $failure) {
                    $invalidTokens[] = $failure->target()->value();
                }

                if (!empty($invalidTokens)) {
                    FcmToken::whereIn('token', $invalidTokens)->delete();
                }
            }

            // تقليل الـ logs - فقط عند وجود أخطاء
            if ($report->hasFailures()) {
                Log::warning('FCM notification partially failed', [
                    'success' => $report->successes()->count(),
                    'failures' => $report->failures()->count(),
                ]);
            } else {
                Log::debug('FCM notification sent successfully', [
                    'success' => $report->successes()->count(),
                ]);
            }

            return true;
        } catch (MessagingException $e) {
            Log::error('FCM messaging error: ' . $e->getMessage());
            return false;
        } catch (\Exception $e) {
            Log::error('FCM error: ' . $e->getMessage());
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
            Log::warning('Conversation not found for FCM notification', ['conversation_id' => $conversationId]);
            return false;
        }

        $recipientIds = $conversation->participants()
            ->where('user_id', '!=', $senderId)
            ->pluck('user_id')
            ->toArray();

        if (empty($recipientIds)) {
            Log::warning('No recipients found for FCM notification', ['conversation_id' => $conversationId, 'sender_id' => $senderId]);
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
        $sender = \App\Models\User::find($senderId);
        $senderName = $sender ? $sender->name : 'مستخدم';

        // تحديد نص الإشعار
        $notificationTitle = 'رسالة جديدة';
        $notificationBody = $messageText;

        // إذا كان النص طويلاً، اختصره
        if (mb_strlen($notificationBody) > 100) {
            $notificationBody = mb_substr($notificationBody, 0, 100) . '...';
        }

        Log::info('Sending FCM notification', [
            'conversation_id' => $conversationId,
            'sender_id' => $senderId,
            'sender_name' => $senderName,
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
                'sender_name' => $senderName,
                'message_text' => $messageText,
            ]
        );

        Log::info('FCM notification result', ['result' => $result]);

        return $result;
    }
}

