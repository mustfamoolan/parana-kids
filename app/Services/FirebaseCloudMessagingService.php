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
            $credentialsBase64 = config('services.firebase.credentials_base64');

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
                }
                catch (\Exception $e) {
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

                // إضافة قائمة ملفات التخزين للمساعدة في التصحيح
                try {
                    // فحص storage/
                    $rootStorage = storage_path();
                    if (is_dir($rootStorage)) {
                        $this->debugInfo['storage_root_files'] = array_values(array_diff(scandir($rootStorage), ['.', '..']));
                    }

                    // فحص storage/app/
                    $storagePath = storage_path('app');
                    $this->debugInfo['storage_app_path'] = $storagePath;
                    if (is_dir($storagePath)) {
                        $this->debugInfo['storage_app_files'] = array_values(array_diff(scandir($storagePath), ['.', '..']));
                    }
                }
                catch (\Exception $e) {
                    $this->debugInfo['scan_error'] = $e->getMessage();
                }

                if (!file_exists($credentialsPath)) {
                    // محاولة أخيرة في مجلد public/app كما فعل المستخدم
                    $publicPath = public_path('app/paranakids-b743f-firebase-adminsdk-fbsvc-4e1340d3ce.json');
                    $this->debugInfo['public_path_attempt'] = $publicPath;
                    $this->debugInfo['public_file_exists'] = file_exists($publicPath);

                    if (file_exists($publicPath)) {
                        $credentialsPath = $publicPath;
                    }
                    else {
                        $this->initError = "Credentials file not found at: $credentialsPath or $publicPath";
                        Log::warning('FirebaseCloudMessagingService: Credentials file not found everywhere', [
                            'config_path' => $credentialsPath,
                            'public_path' => $publicPath,
                        ]);
                        return;
                    }
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
            $this->delegateVapidKey = config('services.firebase.delegate_vapid_key', 'BH3zykRdN9qD16ZdwHB9A_mNpnVR4iWKbcB049yOLisNUGkKnkeXpEykKK-Za4BMAELHCqGH2qtvscJb6qCQwzg');
            $this->delegateSenderId = config('services.firebase.delegate_sender_id', '223597554792');

        }
        catch (\Exception $e) {
            $this->initError = $e->getMessage();
            Log::error('FirebaseCloudMessagingService: Failed to initialize', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            $this->logToDevelopers("❌ <b>فشل تهيئة خدمة FCM</b>\n\nالخطأ: <code>{$e->getMessage()}</code>");
        }
    }

    /**
     * Send log message to developers via Telegram
     */
    protected function logToDevelopers($message)
    {
        try {
            // التحقق من أن الرسالة نصية
            if (is_array($message) || is_object($message)) {
                $message = json_encode($message, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
            }

            $devChatIdsJson = \App\Models\Setting::getValue('developer_telegram_chat_ids', '[]');
            $devChatIds = json_decode($devChatIdsJson, true);

            if (empty($devChatIds)) {
                return;
            }

            $telegramService = app(TelegramService::class);
            foreach ($devChatIds as $chatId) {
                $telegramService->sendMessage($chatId, "🛠 <b>FCM Log:</b>\n\n" . (string)$message);
            }
        } catch (\Exception $e) {
            Log::error('FCM Service: Failed to log to developers', ['error' => $e->getMessage()]);
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
                $fcmCount = FcmToken::where('user_id', $userId)->count();
                $activeCount = FcmToken::where('user_id', $userId)->where('is_active', true)->count();
                
                Log::warning('FirebaseCloudMessagingService: No tokens found for user', [
                    'user_id' => $userId,
                    'app_type' => $appType,
                    'total_tokens' => $fcmCount,
                    'active_tokens' => $activeCount,
                ]);

                $this->logToDevelopers("⚠️ <b>لا توجد توكنات نشطة</b>\n\nالمستخدم: <code>{$userId}</code>\nالنوع: <code>{$appType}</code>\nإجمالي التوكنات: {$fcmCount}\nالنشطة منها: {$activeCount}");
                
                return false;
            }

            Log::info('FirebaseCloudMessagingService: Sending notification to user', [
                'user_id' => $userId,
                'tokens_count' => count($tokens),
                'title' => $title,
                'body' => $body,
            ]);

            return $this->sendToTokens($tokens, $title, $body, $data);
        }
        catch (\Exception $e) {
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
        }
        catch (\Exception $e) {
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

        // التأكد من أن العنوان والرسالة نصوص
        $title = is_array($title) || is_object($title) ? json_encode($title, JSON_UNESCAPED_UNICODE) : (string)$title;
        $body = is_array($body) || is_object($body) ? json_encode($body, JSON_UNESCAPED_UNICODE) : (string)$body;

        try {
            // إرسال لكل token على حدة (للتأكد من معالجة الأخطاء بشكل صحيح)
            foreach ($tokens as $token) {
                try {
                    $token = (string)$token;
                    $notification = FirebaseNotification::create($title, $body);

                    // Android Configuration - High Priority with Sound
                    $androidConfig = AndroidConfig::fromArray([
                        'priority' => 'high',
                        'notification' => [
                            'sound' => 'default',
                            'channel_id' => 'high_importance_channel',
                            // 'priority' is not part of android.notification in v1 API
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

                    // تصفية البيانات للتأكد من أن جميع القيم نصوص (Strings) فقط
                    // FCM Data accepts only string values
                    $sanitizedData = [];
                    foreach (array_merge([
                        'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
                        'sound' => 'default',
                        'priority' => 'high',
                    ], $data) as $key => $value) {
                        if (is_array($value) || is_object($value)) {
                            $sanitizedData[(string)$key] = json_encode($value);
                        } else {
                            $sanitizedData[(string)$key] = (string)$value;
                        }
                    }

                    $message = CloudMessage::withTarget('token', $token)
                        ->withNotification($notification)
                        ->withData($sanitizedData)
                        ->withAndroidConfig($androidConfig)
                        ->withApnsConfig($apnsConfig);

                    $result = $this->messaging->send($message);

                    Log::info('FirebaseCloudMessagingService: Notification sent successfully', [
                        'token' => substr($token, 0, 20) . '...',
                        'message_id' => $result,
                        'title' => $title,
                    ]);

                    $this->logToDevelopers("✅ <b>نجاح الإرسال</b>\n\nالعنوان: " . (string)$title . "\nالتوكن: <code>" . substr((string)$token, 0, 15) . "...</code>\nالمعرف: <code>" . (string)$result . "</code>");

                    $successCount++;

                }
                catch (InvalidArgument $e) {
                    // Token غير صالح - تعطيله
                    $this->debugInfo['last_send_error'] = $e->getMessage();
                    Log::warning('FirebaseCloudMessagingService: Invalid token', [
                        'token' => substr($token, 0, 20) . '...',
                        'error' => $e->getMessage(),
                    ]);
                    $this->logToDevelopers("⚠️ <b>توكن غير صالح (Invalid Token)</b>\n\nالخطأ: <code>{$e->getMessage()}</code>\nالتوكن: <code>" . substr($token, 0, 15) . "...</code>");
                    $invalidTokens[] = $token;
                }
                catch (\Exception $e) {
                    $this->debugInfo['last_send_error'] = $e->getMessage();
                    $errorMessage = (string)$e->getMessage();
                    
                    Log::error('FirebaseCloudMessagingService: Failed to send to token', [
                        'token' => substr($token, 0, 20) . '...',
                        'error' => $errorMessage,
                    ]);

                    // إذا كان الخطأ "Requested entity was not found" فهذا يعني التوكن منتهي/غير صحيح
                    if (stripos($errorMessage, 'Requested entity was not found') !== false || stripos($errorMessage, 'unregistered') !== false) {
                        $this->logToDevelopers("⚠️ <b>توكن غير موجود (Expired/Invalid)</b>\n\nالخطأ: <code>{$errorMessage}</code>\nالتوكن: <code>" . substr($token, 0, 15) . "...</code>");
                        $invalidTokens[] = $token;
                    } else {
                        $this->logToDevelopers("❌ <b>فشل الإرسال لتوكن</b>\n\nالخطأ: <code>{$errorMessage}</code>\nالتوكن: <code>" . substr($token, 0, 15) . "...</code>");
                    }
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
        }
        catch (\Exception $e) {
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
                'order_updated' => 'تعديل على الطلب',
            ];

            $statusText = $titles[$type] ?? 'إشعار طلب';
            $title = $order->customer_name ?? $statusText;
            $body = $statusText;

            $sourceView = 'pending';
            if ($type === 'alwaseet_status_changed' || $type === 'shipment_status_changed') {
                $sourceView = 'alwaseet';
            } elseif ($order->status === 'confirmed') {
                $sourceView = 'restricted';
            } elseif ($order->status === 'deleted' || $order->status === 'cancelled') {
                $sourceView = 'deleted';
            }

            $data = [
                'type' => $type,
                'order_id' => (string)$order->id,
                'customer_name' => $order->customer_name,
                'order_number' => $order->order_number,
                'source_view' => $sourceView,
                'status' => $order->status,
                'screen' => 'order_details',
            ];

            // إرسال للمندوب فقط إذا كان موجوداً
            if ($order->delegate_id) {
                return $this->sendToUser($order->delegate_id, $title, $body, $data, 'delegate_mobile');
            }

            return false;
        }
        catch (\Exception $e) {
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

            $title = $sender->name;
            $body = mb_substr($messageText, 0, 50);

            $data = [
                'type' => 'message',
                'conversation_id' => (string)$conversationId,
                'sender_id' => (string)$senderId,
                'sender_name' => $sender->name,
                'customer_name' => $sender->name,
                'screen' => 'chat',
            ];

            $recipient = User::find($recipientId);
            $appType = ($recipient && ($recipient->isAdmin() || $recipient->isSupplier() || $recipient->isPrivateSupplier()))
                ? 'admin_mobile'
                : 'delegate_mobile';

            return $this->sendToUser($recipientId, $title, $body, $data, $appType);
        }
        catch (\Exception $e) {
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
     * @param AlWaseetShipment $shipment
     * @param mixed $order
     * @param string $oldStatusText Status name (not ID), e.g., 'قيد التجهيز'
     * @param string $newStatusText Status name (not ID), e.g., 'تم التسليم'
     */
    public function sendShipmentNotification(AlWaseetShipment $shipment, $order, $oldStatusText, $newStatusText)
    {
        try {
            if (!$order) {
                $order = $shipment->order;
            }

            if (!$order || !$order->delegate_id) {
                return false;
            }

            $customerName = $order->customer_name ?? 'غير معروف';
            $location = $order->customer_address ?? 'غير محدد';
            $amount = number_format(($order->total_amount ?? 0) + ($order->delivery_fee_at_confirmation ?? 0));
            $alwaseetId = $shipment->alwaseet_order_id ?? '---';

            $title = $customerName;

            // Short and clear body for delegate
            $body = "{$newStatusText} | {$order->order_number}";

            $data = [
                'type' => 'shipment_status_changed',
                'order_id' => (string)$order->id,
                'customer_name' => $order->customer_name,
                'order_number' => $order->order_number,
                'shipment_id' => (string)$shipment->id,
                'old_status' => (string)$oldStatusText,
                'new_status' => (string)$newStatusText,
                'source_view' => 'alwaseet',
                'screen' => 'order_details',
            ];

            return $this->sendToUser($order->delegate_id, $title, $body, $data, 'delegate_mobile');
        }
        catch (\Exception $e) {
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
                    'debug_info' => $this->debugInfo,
                ];
            }
            else {
                return [
                    'success' => false,
                    'message' => 'Failed to send test notification. Check server logs for details.',
                    'debug_info' => $this->debugInfo,
                ];
            }
        }
        catch (\Exception $e) {
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
