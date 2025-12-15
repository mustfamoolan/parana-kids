<?php

namespace App\Services;

use Telegram\Bot\Api;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Config;

class TelegramService
{
    protected $telegram;

    public function __construct()
    {
        $botToken = Config::get('services.telegram.bot_token');

        if (empty($botToken)) {
            Log::warning('TelegramService: BOT_TOKEN not configured');
            return;
        }

        try {
            $this->telegram = new Api($botToken);
        } catch (\Exception $e) {
            Log::error('TelegramService: Failed to initialize Telegram API', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Send message to a specific chat
     */
    public function sendMessage($chatId, $message, $parseMode = 'HTML', $replyMarkup = null)
    {
        if (!$this->telegram) {
            Log::warning('TelegramService: Telegram API not initialized');
            return false;
        }

        try {
            $params = [
                'chat_id' => $chatId,
                'text' => $message,
                'parse_mode' => $parseMode,
            ];

            if ($replyMarkup) {
                $params['reply_markup'] = $replyMarkup;
            }

            $response = $this->telegram->sendMessage($params);

            Log::info('TelegramService: Message sent successfully', [
                'chat_id' => $chatId,
                'message_id' => $response->getMessageId(),
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('TelegramService: Failed to send message', [
                'chat_id' => $chatId,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Send order notification to user
     */
    public function sendOrderNotification($chatId, $order)
    {
        $phone = $order->customer_phone ?? null;
        $socialLink = $order->customer_social_link ?? null;
        $alwaseetOrderId = $order->alwaseetShipment->alwaseet_order_id ?? null;

        // تحميل المستخدم الذي أنشأ الطلب (المندوب)
        $order->load('delegate');
        $delegate = $order->delegate;
        $delegateName = $delegate ? $delegate->name : null;
        $delegateRole = $delegate ? $this->getUserRoleName($delegate->role) : null;

        $message = "🔔 <b>طلب جديد</b>\n\n";
        $message .= "📦 {$order->order_number}\n";
        $message .= "👤 {$order->customer_name}\n";

        if ($phone) {
            $message .= "📞 <code>{$phone}</code>\n";
        }

        if ($order->customer_address) {
            $message .= "📍 {$order->customer_address}\n";
        }

        if ($delegateName) {
            $roleText = $delegateRole ? " ({$delegateRole})" : '';
            $message .= "👨‍💼 {$delegateName}{$roleText}\n";
        }

        $message .= "💰 " . number_format($order->total_amount, 2) . " د.ع\n";

        if ($order->notes) {
            $message .= "📝 {$order->notes}\n";
        }

        $message .= "⏰ " . $order->created_at->format('Y-m-d H:i:s');

        $keyboard = $this->buildOrderKeyboard($alwaseetOrderId, $phone, $socialLink);

        return $this->sendMessage($chatId, $message, 'HTML', $keyboard);
    }

    /**
     * Send message to multiple users
     */
    public function sendToUsers(array $chatIds, $message)
    {
        $successCount = 0;

        foreach ($chatIds as $chatId) {
            if ($this->sendMessage($chatId, $message)) {
                $successCount++;
            }
        }

        return $successCount;
    }

    /**
     * Get bot information
     */
    public function getMe()
    {
        if (!$this->telegram) {
            return null;
        }

        try {
            return $this->telegram->getMe();
        } catch (\Exception $e) {
            Log::error('TelegramService: Failed to get bot info', [
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Format phone number for WhatsApp and call
     */
    protected function formatPhoneForAction($phone)
    {
        // إزالة المسافات والأحرف
        $phone = preg_replace('/[^0-9+]/', '', $phone);

        // إذا كان يبدأ بـ 0، استبدله بـ 964
        if (strpos($phone, '0') === 0) {
            $phone = '964' . substr($phone, 1);
        } elseif (strpos($phone, '+964') === 0) {
            $phone = substr($phone, 1); // إزالة +
        } elseif (strpos($phone, '964') !== 0) {
            $phone = '964' . $phone;
        }

        return $phone;
    }

    /**
     * Build inline keyboard for order notifications
     */
    protected function buildOrderKeyboard($alwaseetOrderId, $phone, $socialLink = null)
    {
        $keyboard = [];

        // زر نسخ رقم الوسيط
        if ($alwaseetOrderId) {
            $keyboard[] = [
                [
                    'text' => '📋 نسخ رقم الوسيط',
                    'callback_data' => 'copy_order_id_' . preg_replace('/[^0-9]/', '', (string)$alwaseetOrderId)
                ]
            ];
        }

        // أزرار الهاتف
        if ($phone) {
            $formattedPhone = $this->formatPhoneForAction($phone);
            $phoneClean = preg_replace('/[^0-9]/', '', (string)$phone);
            $keyboard[] = [
                [
                    'text' => '💬 واتساب',
                    'url' => 'https://wa.me/' . $formattedPhone
                ],
                [
                    'text' => '📋 نسخ رقم الهاتف',
                    'callback_data' => 'copy_phone_' . $phoneClean
                ]
            ];
        }

        // زر رابط السوشال ميديا
        if ($socialLink) {
            $keyboard[] = [
                [
                    'text' => '🔗 رابط السوشال ميديا',
                    'url' => $socialLink
                ]
            ];
        }

        if (empty($keyboard)) {
            return null;
        }

        return json_encode(['inline_keyboard' => $keyboard]);
    }

    /**
     * Send order status notification (from API)
     */
    public function sendOrderStatusNotification($chatId, $shipment, $order)
    {
        $status = $shipment->status ?? 'غير محدد';
        $alwaseetOrderId = $shipment->alwaseet_order_id ?? null;
        $phone = $shipment->client_mobile ?? $order->customer_phone ?? null;
        $socialLink = $order->customer_social_link ?? null;

        // تحميل المستخدم الذي أنشأ الطلب (المندوب)
        $order->load('delegate');
        $delegate = $order->delegate;
        $delegateName = $delegate ? $delegate->name : null;
        $delegateRole = $delegate ? $this->getUserRoleName($delegate->role) : null;

        $message = "📊 <b>{$status}</b>\n\n";
        
        if ($alwaseetOrderId) {
            $message .= "📦 {$order->order_number} | 🔢 <code>{$alwaseetOrderId}</code>\n";
        } else {
            $message .= "📦 {$order->order_number}\n";
        }
        
        $message .= "👤 {$order->customer_name}\n";

        if ($phone) {
            $message .= "📞 <code>{$phone}</code>\n";
        }

        if ($order->customer_address) {
            $message .= "📍 {$order->customer_address}\n";
        }

        if ($delegateName) {
            $roleText = $delegateRole ? " ({$delegateRole})" : '';
            $message .= "👨‍💼 {$delegateName}{$roleText}\n";
        }

        $message .= "💰 " . number_format($order->total_amount, 2) . " د.ع\n";
        $message .= "⏰ " . now()->format('Y-m-d H:i:s');

        $keyboard = $this->buildOrderKeyboard($alwaseetOrderId, $phone, $socialLink);

        return $this->sendMessage($chatId, $message, 'HTML', $keyboard);
    }

    /**
     * Send order deleted notification
     */
    public function sendOrderDeletedNotification($chatId, $order)
    {
        $phone = $order->customer_phone ?? null;
        $socialLink = $order->customer_social_link ?? null;
        $alwaseetOrderId = $order->alwaseetShipment->alwaseet_order_id ?? null;

        // تحميل المستخدم الذي حذف الطلب والمندوب الأصلي
        $order->load(['deletedByUser', 'delegate']);
        $deletedBy = $order->deletedByUser;
        $deletedByName = $deletedBy ? $deletedBy->name : null;
        $deletedByRole = $deletedBy ? $this->getUserRoleName($deletedBy->role) : null;

        $delegate = $order->delegate;
        $delegateName = $delegate ? $delegate->name : null;
        $delegateRole = $delegate ? $this->getUserRoleName($delegate->role) : null;

        $message = "🗑️ <b>تم الحذف</b>\n\n";
        
        if ($alwaseetOrderId) {
            $message .= "📦 {$order->order_number} | 🔢 <code>{$alwaseetOrderId}</code>\n";
        } else {
            $message .= "📦 {$order->order_number}\n";
        }
        
        $message .= "👤 {$order->customer_name}\n";

        if ($phone) {
            $message .= "📞 <code>{$phone}</code>\n";
        }

        if ($order->customer_address) {
            $message .= "📍 {$order->customer_address}\n";
        }

        if ($delegateName) {
            $roleText = $delegateRole ? " ({$delegateRole})" : '';
            $message .= "👨‍💼 المندوب: {$delegateName}{$roleText}\n";
        }

        if ($deletedByName) {
            $roleText = $deletedByRole ? " ({$deletedByRole})" : '';
            $message .= "🗑️ حذفه: {$deletedByName}{$roleText}\n";
        }

        if ($order->deletion_reason) {
            $message .= "📝 {$order->deletion_reason}\n";
        }

        $message .= "💰 " . number_format($order->total_amount, 2) . " د.ع\n";
        $message .= "⏰ " . ($order->deleted_at ? $order->deleted_at->format('Y-m-d H:i:s') : now()->format('Y-m-d H:i:s'));

        $keyboard = $this->buildOrderKeyboard($alwaseetOrderId, $phone, $socialLink);

        return $this->sendMessage($chatId, $message, 'HTML', $keyboard);
    }

    /**
     * Send order restricted notification (confirmed)
     */
    public function sendOrderRestrictedNotification($chatId, $order)
    {
        $phone = $order->customer_phone ?? null;
        $socialLink = $order->customer_social_link ?? null;
        $alwaseetOrderId = $order->alwaseetShipment->alwaseet_order_id ?? null;

        // تحميل المستخدم الذي قيد الطلب والمندوب الأصلي
        $order->load(['confirmedBy', 'delegate']);
        $confirmedBy = $order->confirmedBy;
        $confirmedByName = $confirmedBy ? $confirmedBy->name : null;
        $confirmedByRole = $confirmedBy ? $this->getUserRoleName($confirmedBy->role) : null;

        $delegate = $order->delegate;
        $delegateName = $delegate ? $delegate->name : null;
        $delegateRole = $delegate ? $this->getUserRoleName($delegate->role) : null;

        $message = "🔒 <b>تم التقييد</b>\n\n";
        
        if ($alwaseetOrderId) {
            $message .= "📦 {$order->order_number} | 🔢 <code>{$alwaseetOrderId}</code>\n";
        } else {
            $message .= "📦 {$order->order_number}\n";
        }
        
        $message .= "👤 {$order->customer_name}\n";

        if ($phone) {
            $message .= "📞 <code>{$phone}</code>\n";
        }

        if ($order->customer_address) {
            $message .= "📍 {$order->customer_address}\n";
        }

        if ($delegateName) {
            $roleText = $delegateRole ? " ({$delegateRole})" : '';
            $message .= "👨‍💼 المندوب: {$delegateName}{$roleText}\n";
        }

        if ($confirmedByName) {
            $roleText = $confirmedByRole ? " ({$confirmedByRole})" : '';
            $message .= "🔒 قيّده: {$confirmedByName}{$roleText}\n";
        }

        $message .= "💰 " . number_format($order->total_amount, 2) . " د.ع\n";
        $message .= "⏰ " . ($order->confirmed_at ? $order->confirmed_at->format('Y-m-d H:i:s') : now()->format('Y-m-d H:i:s'));

        $keyboard = $this->buildOrderKeyboard($alwaseetOrderId, $phone, $socialLink);

        return $this->sendMessage($chatId, $message, 'HTML', $keyboard);
    }

    /**
     * Get user role name in Arabic
     */
    protected function getUserRoleName($role)
    {
        $roleNames = [
            'admin' => 'مدير',
            'supplier' => 'مجهز',
            'delegate' => 'مندوب',
            'private_supplier' => 'مورد',
        ];

        return $roleNames[$role] ?? $role;
    }
}

