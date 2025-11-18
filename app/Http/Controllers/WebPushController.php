<?php

namespace App\Http\Controllers;

use App\Models\PushSubscription;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class WebPushController extends Controller
{
    /**
     * تسجيل push subscription للمستخدم الحالي
     */
    public function registerSubscription(Request $request)
    {
        $request->validate([
            'endpoint' => 'required|string|url',
            'keys' => 'required|array',
            'keys.p256dh' => 'required|string',
            'keys.auth' => 'required|string',
            'device_type' => 'nullable|string|in:web,android,ios',
            'device_info' => 'nullable|array',
        ]);

        $user = Auth::user();

        if (!$user) {
            return response()->json(['error' => 'غير مصرح'], 401);
        }

        try {
            // البحث عن subscription موجود
            $subscription = PushSubscription::where('endpoint', $request->endpoint)->first();

            if ($subscription) {
                // تحديث المستخدم إذا كان مختلف
                if ($subscription->user_id != $user->id) {
                    $subscription->update([
                        'user_id' => $user->id,
                        'public_key' => $request->keys['p256dh'],
                        'auth_token' => $request->keys['auth'],
                        'device_type' => $request->device_type ?? 'web',
                        'device_info' => $request->device_info ?? [],
                    ]);
                } else {
                    // تحديث معلومات الجهاز
                    $subscription->update([
                        'public_key' => $request->keys['p256dh'],
                        'auth_token' => $request->keys['auth'],
                        'device_type' => $request->device_type ?? $subscription->device_type ?? 'web',
                        'device_info' => $request->device_info ?? $subscription->device_info ?? [],
                    ]);
                }
            } else {
                // إنشاء subscription جديد
                $subscription = PushSubscription::create([
                    'user_id' => $user->id,
                    'endpoint' => $request->endpoint,
                    'public_key' => $request->keys['p256dh'],
                    'auth_token' => $request->keys['auth'],
                    'device_type' => $request->device_type ?? 'web',
                    'device_info' => $request->device_info ?? [],
                ]);
            }

            Log::info('Push subscription registered', [
                'user_id' => $user->id,
                'endpoint' => substr($request->endpoint, 0, 50) . '...',
            ]);

            return response()->json([
                'success' => true,
                'message' => 'تم تسجيل subscription بنجاح',
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to register push subscription: ' . $e->getMessage());
            return response()->json([
                'error' => 'فشل تسجيل subscription',
            ], 500);
        }
    }

    /**
     * حذف push subscription
     */
    public function deleteSubscription(Request $request)
    {
        $request->validate([
            'endpoint' => 'required|string|url',
        ]);

        $user = Auth::user();

        if (!$user) {
            return response()->json(['error' => 'غير مصرح'], 401);
        }

        try {
            PushSubscription::where('user_id', $user->id)
                ->where('endpoint', $request->endpoint)
                ->delete();

            Log::info('Push subscription deleted', [
                'user_id' => $user->id,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'تم حذف subscription بنجاح',
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to delete push subscription: ' . $e->getMessage());
            return response()->json([
                'error' => 'فشل حذف subscription',
            ], 500);
        }
    }
}
