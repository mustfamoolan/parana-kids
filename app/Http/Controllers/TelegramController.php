<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\TelegramService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Telegram\Bot\Api;

class TelegramController extends Controller
{
    protected $telegramService;

    public function __construct(TelegramService $telegramService)
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

            Log::info('Telegram webhook received', ['update' => $update]);

            // Handle message
            if (isset($update['message'])) {
                $this->handleMessage($update['message']);
            }

            return response()->json(['ok' => true]);
        } catch (\Exception $e) {
            Log::error('Telegram webhook error', [
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
        $text = $message['text'] ?? '';
        $from = $message['from'] ?? [];

        Log::info('Telegram message received', [
            'chat_id' => $chatId,
            'text' => $text,
            'from' => $from,
        ]);

        // Handle /start command
        if ($text === '/start') {
            $this->handleStartCommand($chatId, $from);
            return;
        }

        // Handle /unlink command
        if ($text === '/unlink') {
            $this->handleUnlinkCommand($chatId);
            return;
        }

        // Handle phone number or code for linking
        if (preg_match('/^(\+?\d{10,15}|\d{4,10})$/', $text)) {
            $this->handleLinkRequest($chatId, $text);
            return;
        }

        // Unknown command
        $this->sendMessage($chatId, "❌ أمر غير معروف. يرجى إرسال /start للبدء.");
    }

    /**
     * Handle /start command
     */
    protected function handleStartCommand($chatId, $from)
    {
        // Check if user is already linked
        $user = User::where('telegram_chat_id', $chatId)->first();

        if ($user) {
            $this->sendMessage(
                $chatId,
                "✅ مرحباً {$user->name}!\n\nأنت مربوط بالفعل بحسابك في النظام.\n\nيمكنك إلغاء الربط بإرسال /unlink"
            );
            return;
        }

        // Ask for phone number or code
        $message = "👋 مرحباً بك في بوت إشعارات الطلبات!\n\n";
        $message .= "لربط حسابك، يرجى إرسال:\n";
        $message .= "📱 رقم هاتفك المسجل في النظام\n";
        $message .= "أو\n";
        $message .= "🔢 الكود الخاص بك\n\n";
        $message .= "مثال: 07901234567 أو 1234";

        $this->sendMessage($chatId, $message);
    }

    /**
     * Handle link request (phone or code)
     */
    protected function handleLinkRequest($chatId, $identifier)
    {
        // Try to find user by phone
        $user = User::where('phone', $identifier)
            ->orWhere('phone', 'like', '%' . $identifier)
            ->first();

        // If not found, try by code
        if (!$user) {
            $user = User::where('code', $identifier)->first();
        }

        if (!$user) {
            $this->sendMessage(
                $chatId,
                "❌ لم يتم العثور على حساب بهذا الرقم أو الكود.\n\nيرجى التحقق من البيانات والمحاولة مرة أخرى.\n\nأو إرسال /start للبدء من جديد."
            );
            return;
        }

        // Check if user is supplier or admin
        if (!$user->isAdminOrSupplier()) {
            $this->sendMessage(
                $chatId,
                "❌ هذا الحساب ليس لديه صلاحية لاستقبال إشعارات الطلبات.\n\nفقط المجهزين والمديرين يمكنهم ربط حساباتهم."
            );
            return;
        }

        // Check if user is already linked to another Telegram account
        if ($user->telegram_chat_id && $user->telegram_chat_id != $chatId) {
            $this->sendMessage(
                $chatId,
                "⚠️ هذا الحساب مربوط بالفعل بحساب تليجرام آخر.\n\nيرجى إلغاء الربط من الحساب السابق أولاً."
            );
            return;
        }

        // Link user
        $user->linkToTelegram($chatId);

        $this->sendMessage(
            $chatId,
            "✅ تم ربط حسابك بنجاح!\n\nمرحباً {$user->name}!\n\nستصلك إشعارات الطلبات الجديدة تلقائياً.\n\nيمكنك إلغاء الربط بإرسال /unlink"
        );

        Log::info('User linked to Telegram', [
            'user_id' => $user->id,
            'chat_id' => $chatId,
        ]);
    }

    /**
     * Handle /unlink command
     */
    protected function handleUnlinkCommand($chatId)
    {
        $user = User::where('telegram_chat_id', $chatId)->first();

        if (!$user) {
            $this->sendMessage($chatId, "❌ حسابك غير مربوط.");
            return;
        }

        $user->unlinkFromTelegram();

        $this->sendMessage(
            $chatId,
            "✅ تم إلغاء ربط حسابك بنجاح.\n\nلن تصلك إشعارات بعد الآن.\n\nيمكنك إعادة الربط بإرسال /start"
        );

        Log::info('User unlinked from Telegram', [
            'user_id' => $user->id,
            'chat_id' => $chatId,
        ]);
    }

    /**
     * Send message via Telegram API
     */
    protected function sendMessage($chatId, $text)
    {
        try {
            $botToken = config('services.telegram.bot_token');

            if (empty($botToken)) {
                Log::error('TelegramController: BOT_TOKEN not configured');
                return false;
            }

            $telegram = new Api($botToken);

            $telegram->sendMessage([
                'chat_id' => $chatId,
                'text' => $text,
                'parse_mode' => 'HTML',
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('TelegramController: Failed to send message', [
                'chat_id' => $chatId,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }
}

