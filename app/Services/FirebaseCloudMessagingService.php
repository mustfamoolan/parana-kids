<?php

namespace App\Services;

use App\Models\FcmToken;
use App\Models\User;
use App\Models\Order;
use App\Models\AlWaseetShipment;
use Illuminate\Support\Facades\Log;
use Kreait\Firebase\Factory;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification as FirebaseNotification;
use Kreait\Firebase\Messaging\AndroidConfig;
use Kreait\Firebase\Messaging\ApnsConfig;
use Kreait\Firebase\Exception\Messaging\InvalidArgument;

class FirebaseCloudMessagingService
{
    protected $messaging;
    protected $delegateVapidKey;
    protected $delegateSenderId;

    public $initError = null;
    public $debugInfo = [];

    public function __construct()
    {
        try {
            $projectId = config('services.firebase.project_id');
            $factory = null;

            // محاولة استخدام Base64 أولاً (الأفضل للـ deployment)
            $credentialsBase64 = env('FIREBASE_CREDENTIALS_BASE64');

            if ($credentialsBase64) {
                try {
                    $this->debugInfo['method_attempt'] = 'base64';
                    // استخدام credentials من Base64
                    $credentialsJson = base64_decode($credentialsBase64);

                    // التحقق من صحة JSON
                    if (!json_decode($credentialsJson)) {
                        throw new \Exception("Invalid JSON in Base64 credentials");
                    }

                    $factory = (new Factory)->withServiceAccount($credentialsJson);

                    Log::info('FirebaseCloudMessagingService: Initialized with Base64 credentials');
                    $this->debugInfo['method'] = 'base64';
                } catch (\Exception $e) {
                    $this->debugInfo['base64_error'] = $e->getMessage();
                    Log::warning('FirebaseCloudMessagingService: Failed to use Base64 credentials, falling back to file', [
                        'error' => $e->getMessage()
                    ]);
                    // Fallback to file will happen below since $factory is null
                }
            }

            if (!$factory) {
                // استخدام ملف الـ credentials
                $credentialsPath = config('services.firebase.credentials');
                $this->debugInfo['method_attempt'] = 'file';
                $this->debugInfo['path'] = $credentialsPath;
                $this->debugInfo['file_exists'] = file_exists($credentialsPath);

                if (!file_exists($credentialsPath)) {
                    $this->initError = "Credentials file not found at: $credentialsPath";
                    Log::warning('FirebaseCloudMessagingService: Credentials file not found and no Base64 provided', [
                        'path' => $credentialsPath,
                    ]);
                    return;
                }

                $factory = (new Factory)->withServiceAccount($credentialsPath);
                $this->debugInfo['method'] = 'file';

                Log::info('FirebaseCloudMessagingService: Initialized with credentials file');
            }

            if ($projectId) {
                $factory = $factory->withProjectId($projectId);
            }

            $this->messaging = $factory->createMessaging();

            // VAPID keys للمندوبين
            $this->delegateVapidKey = env('FIREBASE_DELEGATE_VAPID_KEY', 'BH3zykRdN9qD16ZdwHB9A_mNpnVR4iWKbcB049yOLisNUGkKnkeXpEykKK-Za4BMAELHCqGH2qtvscJb6qCQwzg');
            $this->delegateSenderId = env('FIREBASE_DELEGATE_SENDER_ID', '223597554792');

        } catch (\Exception $e) {
            $this->initError = $e->getMessage();
            Log::error('FirebaseCloudMessagingService: Failed to initialize', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }



    /**
     * Send notification to a single user
     */

    public function sendToUser($userId, $title, $body, $data = [], $appType = 'delegate_mobile')
    {
        if (!$this->messaging) {
            Log::warning('FirebaseCloudMessagingService: Messaging not initialized');
            return false;
        }

        try {
            $tokens = FcmToken::where('user_id', $userId)
                ->where('app_type', $appType)
                ->where('is_active', true)
                ->pluck('token')
                ->toArray();

            if (empty($tokens)) {
                Log::warning('FirebaseCloudMessagingService: No tokens found for user', [
                    'user_id' => $userId,
                    'app_type' => $appType,
                    'total_tokens' => FcmToken::where('user_id', $userId)->count(),
                    'active_tokens' => FcmToken::where('user_id', $userId)->where('is_active', true)->count(),
                ]);
                return false;
            }

            Log::info('FirebaseCloudMessagingService: Sending notification to user', [
                'user_id' => $userId,
                'tokens_count' => count($tokens),
                'title' => $title,
                'body' => $body,
            ]);

            return $this->sendToTokens($tokens, $title, $body, $data);
        } catch (\Exception $e) {
            Log::error('FirebaseCloudMessagingService: Failed to send to user', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return false;
        }
    }

    /**
     * Send notification to multiple users
     */
    public function sendToUsers($userIds, $title, $body, $data = [], $appType = 'delegate_mobile')
    {
        if (!$this->messaging) {
            Log::warning('FirebaseCloudMessagingService: Messaging not initialized');
            return 0;
        }

        try {
            $tokens = FcmToken::whereIn('user_id', $userIds)
                ->where('app_type', $appType)
                ->where('is_active', true)
                ->pluck('token')
                ->toArray();

            if (empty($tokens)) {
                Log::info('FirebaseCloudMessagingService: No tokens found for users', [
                    'user_ids' => $userIds,
                    'app_type' => $appType,
                ]);
                return 0;
            }

            return $this->sendToTokens($tokens, $title, $body, $data);
        } catch (\Exception $e) {
            Log::error('FirebaseCloudMessagingService: Failed to send to users', [
                'user_ids' => $userIds,
                'error' => $e->getMessage(),
            ]);
            return 0;
        }
    }

    /**
     * Send notification to specific tokens
     */
    public function sendToTokens($tokens, $title, $body, $data = [])
    {
        if (!$this->messaging) {
            Log::warning('FirebaseCloudMessagingService: Messaging not initialized');
            return 0;
        }

        if (empty($tokens)) {
            return 0;
        }

        $successCount = 0;
        $invalidTokens = [];

        try {
            // إرسال لكل token على حدة (للتأكد من معالجة الأخطاء بشكل صحيح)
            foreach ($tokens as $token) {
                try {
                    $notification = FirebaseNotification::create($title, $body);

                    // Android Configuration - High Priority with Sound
                    $androidConfig = AndroidConfig::fromArray([
                        'priority' => 'high',
                        'notification' => [
                            'sound' => 'default',
                            'channel_id' => 'high_importance_channel',
                            'priority' => 'high',
                            'default_sound' => true,
                            'default_vibrate_timings' => true,
                            'visibility' => 'public',
                        ],
                    ]);

                    // iOS Configuration - High Priority with Sound
                    $apnsConfig = ApnsConfig::fromArray([
                        'headers' => [
                            'apns-priority' => '10', // High priority
                            'apns-push-type' => 'alert',
                        ],
                        'payload' => [
                            'aps' => [
                                'alert' => [
                                    'title' => $title,
                                    'body' => $body,
                                ],
                                'sound' => 'default',
                                'badge' => 1,
                                'content-available' => 1, // Wake app in background
                            ],
                        ],
                    ]);

                    $message = CloudMessage::withTarget('token', $token)
                        ->withNotification($notification)
                        ->withData(array_merge([
                            'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
                            'sound' => 'default',
                            'priority' => 'high',
                        ], $data))
                        ->withAndroidConfig($androidConfig)
                        ->withApnsConfig($apnsConfig);

                    $result = $this->messaging->send($message);

                    Log::info('FirebaseCloudMessagingService: Notification sent successfully', [
                        'token' => substr($token, 0, 20) . '...',
                        'message_id' => $result,
                        'title' => $title,
                    ]);

                    $successCount++;

                } catch (InvalidArgument $e) {
                    // Token غير صالح - تعطيله
                    Log::warning('FirebaseCloudMessagingService: Invalid token', [
                        'token' => substr($token, 0, 20) . '...',
                        'error' => $e->getMessage(),
                    ]);
                    $invalidTokens[] = $token;
                } catch (\Exception $e) {
                    Log::error('FirebaseCloudMessagingService: Failed to send to token', [
                        'token' => substr($token, 0, 20) . '...',
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            // تعطيل tokens غير الصالحة
            if (!empty($invalidTokens)) {
                FcmToken::whereIn('token', $invalidTokens)
                    ->update(['is_active' => false]);
            }

            Log::info('FirebaseCloudMessagingService: Notifications sent', [
                'total' => count($tokens),
                'success' => $successCount,
                'failed' => count($tokens) - $successCount,
            ]);

            return $successCount;
        } catch (\Exception $e) {
            Log::error('FirebaseCloudMessagingService: Failed to send notifications', [
                'error' => $e->getMessage(),
            ]);
            return 0;
        }
    }

    /**
     * Send order notification
     */
    public function sendOrderNotification(Order $order, $type = 'order_created')
    {
        try {
            $order->load('delegate');

            $titles = [
                'order_created' => 'طلب جديد',
                'order_confirmed' => 'تم تقييد الطلب',
                'order_deleted' => 'تم حذف الطلب',
            ];

            $title = $titles[$type] ?? 'إشعار طلب';
            $body = "{$title}: {$order->order_number}";

            $data = [
                'type' => $type,
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'screen' => 'order_details',
            ];

            // إرسال للمندوب فقط إذا كان موجوداً
            if ($order->delegate_id) {
                return $this->sendToUser($order->delegate_id, $title, $body, $data, 'delegate_mobile');
            }

            return false;
        } catch (\Exception $e) {
            Log::error('FirebaseCloudMessagingService: Failed to send order notification', [
                'order_id' => $order->id,
                'type' => $type,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Send message notification
     */
    public function sendMessageNotification($conversationId, $senderId, $recipientId, $messageText)
    {
        try {
            $sender = User::find($senderId);
            if (!$sender) {
                return false;
            }

            $title = 'رسالة جديدة';
            $body = "رسالة من {$sender->name}: " . mb_substr($messageText, 0, 50);

            $data = [
                'type' => 'message',
                'conversation_id' => $conversationId,
                'sender_id' => $senderId,
                'screen' => 'chat',
            ];

            return $this->sendToUser($recipientId, $title, $body, $data, 'delegate_mobile');
        } catch (\Exception $e) {
            Log::error('FirebaseCloudMessagingService: Failed to send message notification', [
                'conversation_id' => $conversationId,
                'recipient_id' => $recipientId,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Send shipment status notification
     */
    public function sendShipmentNotification(AlWaseetShipment $shipment, $order, $oldStatus, $newStatus)
    {
        try {
            if (!$order) {
                $order = $shipment->order;
            }

            if (!$order || !$order->delegate_id) {
                return false;
            }

            $title = 'تغيير حالة الشحنة';
            $body = "تم تغيير حالة شحنة الطلب {$order->order_number} من '{$oldStatus}' إلى '{$newStatus}'";

            $data = [
                'type' => 'shipment_status_changed',
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'shipment_id' => $shipment->id,
                'old_status' => $oldStatus,
                'new_status' => $newStatus,
                'screen' => 'order_details',
            ];

            return $this->sendToUser($order->delegate_id, $title, $body, $data, 'delegate_mobile');
        } catch (\Exception $e) {
            Log::error('FirebaseCloudMessagingService: Failed to send shipment notification', [
                'shipment_id' => $shipment->id,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Test sending notification to a specific token
     * Useful for debugging and testing
     */
    public function testNotification($token, $title = 'Test Notification', $body = 'This is a test notification')
    {
        if (!$this->messaging) {
            Log::warning('FirebaseCloudMessagingService: Messaging not initialized');
            return [
                'success' => false,
                'message' => 'Firebase messaging not initialized: ' . ($this->initError ?? 'Unknown error'),
                'debug_info' => $this->debugInfo,
            ];
        }

        try {
            $data = [
                'type' => 'test',
                'screen' => 'home',
            ];

            $result = $this->sendToTokens([$token], $title, $body, $data);

            if ($result > 0) {
                return [
                    'success' => true,
                    'message' => 'Test notification sent successfully',
                    'tokens_sent' => $result,
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Failed to send test notification',
                ];
            }
        } catch (\Exception $e) {
            Log::error('FirebaseCloudMessagingService: Test notification failed', [
                'token' => substr($token, 0, 20) . '...',
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Error: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Get user's FCM tokens for debugging
     */
    public function getUserTokens($userId, $appType = 'delegate_mobile')
    {
        $tokens = FcmToken::where('user_id', $userId)
            ->where('app_type', $appType)
            ->get();

        return [
            'user_id' => $userId,
            'total_tokens' => $tokens->count(),
            'active_tokens' => $tokens->where('is_active', true)->count(),
            'tokens' => $tokens->map(function ($token) {
                return [
                    'id' => $token->id,
                    'device_type' => $token->device_type,
                    'is_active' => $token->is_active,
                    'token_preview' => substr($token->token, 0, 20) . '...',
                    'created_at' => $token->created_at,
                ];
            }),
        ];
    }
}

