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
    public function sendMessage($chatId, $message, $parseMode = 'HTML')
    {
        if (!$this->telegram) {
            Log::warning('TelegramService: Telegram API not initialized');
            return false;
        }

        try {
            $response = $this->telegram->sendMessage([
                'chat_id' => $chatId,
                'text' => $message,
                'parse_mode' => $parseMode,
            ]);

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
        $message = "🔔 <b>طلب جديد</b>\n\n";
        $message .= "📦 رقم الطلب: <b>{$order->order_number}</b>\n";
        $message .= "👤 العميل: {$order->customer_name}\n";
        $message .= "📞 الهاتف: {$order->customer_phone}\n";
        $message .= "📍 العنوان: {$order->customer_address}\n";
        $message .= "💰 المبلغ الإجمالي: " . number_format($order->total_amount, 2) . " د.ع\n\n";

        if ($order->notes) {
            $message .= "📝 ملاحظات: {$order->notes}\n\n";
        }

        $message .= "⏰ الوقت: " . $order->created_at->format('Y-m-d H:i:s');

        return $this->sendMessage($chatId, $message);
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
}

