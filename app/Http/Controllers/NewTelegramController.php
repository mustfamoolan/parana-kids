<?php

namespace App\Http\Controllers;

use App\Services\NewTelegramService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

class NewTelegramController extends Controller
{
    protected $telegramService;

    public function __construct(NewTelegramService $telegramService)
    {
        $this->telegramService = $telegramService;
    }

    /**
     * Handle webhook from Telegram
     */
    public function webhook(Request $request)
    {
        try {
            $update = $request->all();

            Log::info('New Telegram webhook received', ['update' => $update]);

            // Handle message
            if (isset($update['message'])) {
                $this->handleMessage($update['message']);
            }

            return response()->json(['ok' => true]);
        } catch (\Exception $e) {
            Log::error('New Telegram webhook error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json(['ok' => false, 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Handle incoming message
     */
    protected function handleMessage($message)
    {
        $chatId = $message['chat']['id'];
        $text = trim($message['text'] ?? '');

        // If the message is empty or doesn't have text, do nothing
        if (empty($text)) {
            return;
        }

        Log::info('New Telegram message received', [
            'chat_id' => $chatId,
            'text' => $text,
        ]);

        // Send "typing..." status indicator on Telegram to make the experience smooth
        $this->telegramService->sendTypingAction($chatId);

        // Call Gemini API and reply
        $this->askGeminiAndReply($chatId, $text);
    }

    /**
     * Retrieve chat history, call Gemini API, update history, and send response back to user
     */
    protected function askGeminiAndReply($chatId, $userText)
    {
        $apiKey = config('services.gemini.api_key');
        
        // Read system instruction from file
        $instructionPath = storage_path('app/gemini_instruction.txt');
        $systemInstruction = '';
        if (file_exists($instructionPath)) {
            $systemInstruction = trim(file_get_contents($instructionPath));
        }

        if (empty($apiKey)) {
            Log::error('Gemini API key is not configured.');
            $this->telegramService->sendMessage($chatId, 'عذراً، نظام الذكاء الاصطناعي غير متاح حالياً.');
            return;
        }

        // Cache key for the conversation history
        $cacheKey = "gemini_chat_{$chatId}";

        // Retrieve existing history from cache (default to empty array)
        // History structure: array of ['role' => 'user'|'model', 'parts' => [['text' => '...']]]
        $history = Cache::get($cacheKey, []);

        // Append the new user message
        $history[] = [
            'role' => 'user',
            'parts' => [
                ['text' => $userText]
            ]
        ];

        // Format system instruction according to Gemini 1.5 API structure if set
        $requestPayload = [
            'contents' => $history
        ];

        if (!empty($systemInstruction)) {
            $requestPayload['systemInstruction'] = [
                'parts' => [
                    ['text' => $systemInstruction]
                ]
            ];
        }

        try {
            // Call Gemini 2.5 Flash Lite API
            $response = Http::timeout(15)->post(
                "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash-lite:generateContent?key={$apiKey}",
                $requestPayload
            );

            if ($response->failed()) {
                Log::error('Gemini API request failed', [
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);
                $this->telegramService->sendMessage($chatId, 'صار عندي خلل بسيط بالاتصال، يرجى المحاولة مرة ثانية.');
                return;
            }

            $result = $response->json();
            $aiText = $result['candidates'][0]['content']['parts'][0]['text'] ?? '';

            if (empty($aiText)) {
                Log::warning('Gemini API returned an empty text response.');
                $this->telegramService->sendMessage($chatId, 'ما گدرت أفهم الرسالة بشكل صحيح، تكدر تعيدها؟');
                return;
            }

            // Append model response to history
            $history[] = [
                'role' => 'model',
                'parts' => [
                    ['text' => $aiText]
                ]
            ];

            // Limit conversation history in cache to last 12 turns (6 user, 6 model turns) to prevent reaching token limits
            if (count($history) > 12) {
                $history = array_slice($history, -12);
            }

            // Save history back to cache for 30 minutes
            Cache::put($cacheKey, $history, now()->addMinutes(30));

            // Send response back via Telegram bot (NewTelegramService handles fallback if Markdown parsing fails)
            $this->telegramService->sendMessage($chatId, $aiText, 'Markdown');

        } catch (\Exception $e) {
            Log::error('Error calling Gemini API or sending message', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            $this->telegramService->sendMessage($chatId, 'حدث خطأ غير متوقع، يرجى المحاولة لاحقاً.');
        }
    }
}
