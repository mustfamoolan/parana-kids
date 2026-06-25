<?php

namespace App\Services;

use Telegram\Bot\Api;
use Telegram\Bot\FileUpload\InputFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;

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

    public function sendMessage($chatId, $message, $parseMode = 'Markdown', $replyMarkup = null)
    {
        if (!$this->telegram) {
            Log::warning('NewTelegramService: Telegram API not initialized');
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

            // First try sending with the requested formatting
            $this->telegram->sendMessage($params);
            return true;
        } catch (\Exception $e) {
            Log::warning('NewTelegramService: Failed sending formatted message, trying plain text', [
                'chat_id' => $chatId,
                'error' => $e->getMessage(),
            ]);
            
            try {
                $fallbackParams = [
                    'chat_id' => $chatId,
                    'text' => $message,
                ];

                if ($replyMarkup) {
                    $fallbackParams['reply_markup'] = $replyMarkup;
                }

                // Fallback: send as raw text if markdown parsing failed
                $this->telegram->sendMessage($fallbackParams);
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

    public function sendPhoto($chatId, $photoUrl, $caption = '', $parseMode = 'Markdown', $replyMarkup = null)
    {
        $botToken = Config::get('services.telegram_new.bot_token');

        if (empty($botToken)) {
            Log::warning('NewTelegramService: BOT_TOKEN not configured for sendPhoto');
            return false;
        }

        try {
            $params = [
                'chat_id' => $chatId,
                'photo' => $photoUrl,
                'caption' => $caption,
                'parse_mode' => $parseMode,
            ];

            if ($replyMarkup) {
                $params['reply_markup'] = $replyMarkup;
            }

            // Send direct HTTP post request to bypass SDK remote stream/validation issues
            $response = Http::timeout(15)->post("https://api.telegram.org/bot{$botToken}/sendPhoto", $params);

            if ($response->successful()) {
                return true;
            }

            Log::warning('NewTelegramService: Direct sendPhoto failed with formatting, trying plain text caption', [
                'chat_id' => $chatId,
                'status' => $response->status(),
                'error' => $response->body(),
            ]);
            
            $fallbackParams = [
                'chat_id' => $chatId,
                'photo' => $photoUrl,
                'caption' => $caption,
            ];

            if ($replyMarkup) {
                $fallbackParams['reply_markup'] = $replyMarkup;
            }

            // Fallback: send with plain text caption (no markdown)
            $responseFallback = Http::timeout(15)->post("https://api.telegram.org/bot{$botToken}/sendPhoto", $fallbackParams);

            if ($responseFallback->successful()) {
                return true;
            }

            throw new \Exception('Telegram API returned error: ' . $responseFallback->body());
        } catch (\Exception $e) {
            Log::error('NewTelegramService: Global send photo failed, sending message instead', [
                'chat_id' => $chatId,
                'error' => $e->getMessage(),
            ]);
            
            // Fallback to sending just the text message if photo sending failed completely
            // If the caption looks like "here is the photo", let's adjust it so it's not confusing
            $fallbackMessage = $caption;
            if (!empty($caption) && preg_match('/(صورة|تفضل|هاي)/u', $caption)) {
                if (mb_strlen($caption) < 50) {
                    $fallbackMessage = "عذراً عيني، واجهت مشكلة بتحميل صورة المنتج حالياً. 🌸";
                } else {
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
