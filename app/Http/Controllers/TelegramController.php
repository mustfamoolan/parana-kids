<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\TelegramService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Cache;
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
        $text = trim($message['text'] ?? '');
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

        // Check if user is in password verification step
        $pendingLink = Cache::get("telegram_link_{$chatId}");
        if ($pendingLink) {
            $this->handlePasswordVerification($chatId, $text, $pendingLink, $from);
            return;
        }

        // Handle phone number or code for linking (accepts alphanumeric codes)
        // Phone: +1234567890 or 1234567890 (10-15 digits)
        // Code: alphanumeric (3-20 chars) like SUP999, ABC123, etc.
        if (preg_match('/^(\+?\d{10,15})$/', $text) || preg_match('/^[A-Za-z0-9]{3,20}$/', $text)) {
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
        // Check if this chat_id is already linked to any user
        $linkedChat = \App\Models\UserTelegramChat::where('chat_id', $chatId)->first();

        if ($linkedChat) {
            $user = $linkedChat->user;
            $this->sendMessage(
                $chatId,
                "✅ مرحباً {$user->name}!\n\nأنت مربوط بالفعل بحسابك في النظام.\n\nيمكنك إلغاء الربط من هذا الجهاز بإرسال /unlink"
            );
            return;
        }

        // Clear any pending link
        Cache::forget("telegram_link_{$chatId}");

        // Ask for phone number or code
        $message = "👋 مرحباً بك في بوت إشعارات الطلبات!\n\n";
        $message .= "لربط حسابك، يرجى إرسال:\n";
        $message .= "📱 رقم هاتفك المسجل في النظام\n";
        $message .= "أو\n";
        $message .= "🔢 الكود الخاص بك\n\n";
        $message .= "مثال:\n";
        $message .= "• 07901234567 (رقم الهاتف)\n";
        $message .= "• SUP999 (الكود)";

        $this->sendMessage($chatId, $message);
    }

    /**
     * Handle link request (phone or code) - Step 1
     */
    protected function handleLinkRequest($chatId, $identifier)
    {
        // Try to find user by phone (exact match first)
        $user = User::where('phone', $identifier)->first();

        // If not found, try by code (exact match)
        if (!$user) {
            $user = User::where('code', $identifier)->first();
        }

        // If still not found, try partial phone match
        if (!$user && preg_match('/^\d+$/', $identifier)) {
            $user = User::where('phone', 'like', '%' . $identifier)->first();
        }

        if (!$user) {
            $this->sendMessage(
                $chatId,
                "❌ لم يتم العثور على حساب بهذا الرقم أو الكود.\n\nيرجى التحقق من البيانات والمحاولة مرة أخرى.\n\nأو إرسال /start للبدء من جديد."
            );
            return;
        }

        // Check if user is admin, supplier, or delegate
        if (!in_array($user->role, ['admin', 'supplier', 'delegate', 'private_supplier'])) {
            $this->sendMessage(
                $chatId,
                "❌ هذا الحساب ليس لديه صلاحية لاستقبال إشعارات الطلبات.\n\nفقط المديرين والمجهزين والمندوبين يمكنهم ربط حساباتهم."
            );
            return;
        }

        // Check if this specific chat_id is already linked to this user
        if ($user->isChatIdLinked($chatId)) {
            $this->sendMessage(
                $chatId,
                "✅ هذا الجهاز مربوط بالفعل بحسابك.\n\nلا حاجة للربط مرة أخرى."
            );
            return;
        }

        // Save pending link info in cache (expires in 5 minutes)
        Cache::put("telegram_link_{$chatId}", [
            'user_id' => $user->id,
            'identifier' => $identifier,
        ], now()->addMinutes(5));

        // Ask for password
        $this->sendMessage(
            $chatId,
            "✅ تم العثور على الحساب: {$user->name}\n\n🔐 يرجى إرسال كلمة المرور للتحقق:\n\n(ستنتهي العملية بعد 5 دقائق)"
        );
    }

    /**
     * Handle password verification - Step 2
     */
    protected function handlePasswordVerification($chatId, $password, $pendingLink, $from = [])
    {
        $user = User::find($pendingLink['user_id']);

        if (!$user) {
            Cache::forget("telegram_link_{$chatId}");
            $this->sendMessage(
                $chatId,
                "❌ حدث خطأ. يرجى إرسال /start للبدء من جديد."
            );
            return;
        }

        // Verify password
        if (!Hash::check($password, $user->password)) {
            $this->sendMessage(
                $chatId,
                "❌ كلمة المرور غير صحيحة.\n\nيرجى المحاولة مرة أخرى أو إرسال /start للبدء من جديد."
            );
            return;
        }

        // Password is correct, link the user
        Cache::forget("telegram_link_{$chatId}");

        // Get device name from Telegram user info (optional)
        $deviceName = null;
        if (isset($from['first_name'])) {
            $deviceName = $from['first_name'];
            if (isset($from['last_name'])) {
                $deviceName .= ' ' . $from['last_name'];
            }
        }

        $user->linkToTelegram($chatId, $deviceName);

        // عد الأجهزة المربوطة
        $devicesCount = $user->telegramChats()->count();

        $this->sendMessage(
            $chatId,
            "✅ تم ربط حسابك بنجاح!\n\nمرحباً {$user->name}!\n\n📱 عدد الأجهزة المربوطة: {$devicesCount}\n\nستصلك إشعارات الطلبات الجديدة تلقائياً على جميع أجهزتك.\n\nيمكنك إلغاء ربط هذا الجهاز بإرسال /unlink"
        );

        Log::info('User linked to Telegram', [
            'user_id' => $user->id,
            'chat_id' => $chatId,
            'device_name' => $deviceName,
            'total_devices' => $devicesCount,
        ]);
    }

    /**
     * Handle /unlink command
     */
    protected function handleUnlinkCommand($chatId)
    {
        $linkedChat = \App\Models\UserTelegramChat::where('chat_id', $chatId)->first();

        if (!$linkedChat) {
            $this->sendMessage($chatId, "❌ هذا الجهاز غير مربوط.");
            return;
        }

        $user = $linkedChat->user;
        $user->unlinkFromTelegram($chatId);

        $remainingDevices = $user->telegramChats()->count();

        $message = "✅ تم إلغاء ربط هذا الجهاز بنجاح.\n\n";
        if ($remainingDevices > 0) {
            $message .= "📱 لا تزال لديك {$remainingDevices} أجهزة أخرى مربوطة تستقبل الإشعارات.\n\n";
        } else {
            $message .= "لن تصلك إشعارات بعد الآن.\n\n";
        }
        $message .= "يمكنك إعادة ربط هذا الجهاز بإرسال /start";

        $this->sendMessage($chatId, $message);

        Log::info('User unlinked from Telegram', [
            'user_id' => $user->id,
            'chat_id' => $chatId,
            'remaining_devices' => $remainingDevices,
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

