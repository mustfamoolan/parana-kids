<?php

namespace App\Services;

use Telegram\Bot\Api;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Config;

class NewTelegramService
{
    protected $telegram;

    public function __construct()
    {
        $botToken = Config::get('services.telegram_new.bot_token');

        if (empty($botToken)) {
            Log::warning('NewTelegramService: BOT_TOKEN not configured');
            return;
        }

        try {
            $this->telegram = new Api($botToken);
        } catch (\Exception $e) {
            Log::error('NewTelegramService: Failed to initialize Telegram API', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Send message to a specific chat with formatting and fallback
     */
    public function sendMessage($chatId, $message, $parseMode = 'Markdown')
    {
        if (!$this->telegram) {
            Log::warning('NewTelegramService: Telegram API not initialized');
            return false;
        }

        try {
            // First try sending with the requested formatting
            $this->telegram->sendMessage([
                'chat_id' => $chatId,
                'text' => $message,
                'parse_mode' => $parseMode,
            ]);
            return true;
        } catch (\Exception $e) {
            Log::warning('NewTelegramService: Failed sending formatted message, trying plain text', [
                'chat_id' => $chatId,
                'error' => $e->getMessage(),
            ]);
            
            try {
                // Fallback: send as raw text if markdown parsing failed
                $this->telegram->sendMessage([
                    'chat_id' => $chatId,
                    'text' => $message,
                ]);
                return true;
            } catch (\Exception $eFallback) {
                Log::error('NewTelegramService: Global send message failed', [
                    'chat_id' => $chatId,
                    'error' => $eFallback->getMessage(),
                ]);
                return false;
            }
        }
    }

    /**
     * Send a typing chat action (to show the user we are generating response)
     */
    public function sendTypingAction($chatId)
    {
        if (!$this->telegram) {
            return false;
        }

        try {
            $this->telegram->sendChatAction([
                'chat_id' => $chatId,
                'action' => 'typing',
            ]);
            return true;
        } catch (\Exception $e) {
            Log::warning('NewTelegramService: Failed to send typing action', [
                'chat_id' => $chatId,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Send a photo to a specific chat with optional caption and fallback
     */
    public function sendPhoto($chatId, $photoUrl, $caption = '', $parseMode = 'Markdown')
    {
        if (!$this->telegram) {
            Log::warning('NewTelegramService: Telegram API not initialized');
            return false;
        }

        try {
            $this->telegram->sendPhoto([
                'chat_id' => $chatId,
                'photo' => $photoUrl,
                'caption' => $caption,
                'parse_mode' => $parseMode,
            ]);
            return true;
        } catch (\Exception $e) {
            Log::warning('NewTelegramService: Failed sending formatted photo caption, trying plain text', [
                'chat_id' => $chatId,
                'error' => $e->getMessage(),
            ]);
            
            try {
                // Fallback: send with plain text caption (no markdown)
                $this->telegram->sendPhoto([
                    'chat_id' => $chatId,
                    'photo' => $photoUrl,
                    'caption' => $caption,
                ]);
                return true;
            } catch (\Exception $eFallback) {
                Log::error('NewTelegramService: Global send photo failed, sending message instead', [
                    'chat_id' => $chatId,
                    'error' => $eFallback->getMessage(),
                ]);
                
                // Fallback to sending just the text message if photo sending failed completely
                $fallbackMessage = $caption;
                if (!empty($caption) && preg_match('/(صورة|تفضل|هاي)/u', $caption)) {
                    if (mb_strlen($caption) < 50) {
                        $fallbackMessage = "عذراً عيني، واجهت مشكلة بتحميل صورة المنتج حالياً. 🌸";
                    } else {
                        // If it's a longer message containing details, replace the typical intro prefix
                        $fallbackMessage = preg_replace(
                            '/^(تفضل|هلا|صار|من عيوني)[^:]*:\s*/u',
                            'عذراً عيني، واجهت مشكلة بتحميل الصورة، بس تفاصيل المنتج هي: ',
                            $caption
                        );
                    }
                }
                return $this->sendMessage($chatId, $fallbackMessage);
            }
        }
    }
}
