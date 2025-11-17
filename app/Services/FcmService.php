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
            return false;
        }

        $tokens = FcmToken::whereIn('user_id', $userIds)->pluck('token')->toArray();

        if (empty($tokens)) {
            return false;
        }

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
            $notification = Notification::create($title, $body);

            $message = CloudMessage::new()
                ->withNotification($notification)
                ->withData(array_merge([
                    'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
                ], $data))
                ->withAndroidConfig([
                    'priority' => 'high',
                    'notification' => [
                        'sound' => 'default',
                        'channel_id' => 'high_importance_channel',
                    ],
                ])
                ->withApnsConfig([
                    'headers' => [
                        'apns-priority' => '10',
                    ],
                    'payload' => [
                        'aps' => [
                            'sound' => 'default',
                            'badge' => 1,
                        ],
                    ],
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

            Log::info('FCM notification sent', [
                'success' => $report->successes()->count(),
                'failures' => $report->failures()->count(),
            ]);

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

        Log::info('Sending FCM notification', [
            'conversation_id' => $conversationId,
            'sender_id' => $senderId,
            'recipient_ids' => $recipientIds,
        ]);

        // إرسال الإشعار
        $result = $this->sendToUsers(
            $recipientIds,
            'رسالة جديدة',
            'لديك رسالة جديدة',
            [
                'type' => 'new_message',
                'conversation_id' => (string)$conversationId,
                'sender_id' => (string)$senderId,
            ]
        );

        Log::info('FCM notification result', ['result' => $result]);

        return $result;
    }
}

