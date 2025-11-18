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
            Log::info('WebPush registration request', [
                'user_id' => $user->id,
                'endpoint' => substr($request->endpoint, 0, 50) . '...',
                'has_keys' => !empty($request->keys),
            ]);

            // البحث عن subscription موجود
            $subscription = PushSubscription::where('endpoint', $request->endpoint)->first();

            if ($subscription) {
                Log::info('Updating existing subscription', ['subscription_id' => $subscription->id]);
                // تحديث المستخدم إذا كان مختلف
                if ($subscription->user_id != $user->id) {
                    $subscription->update([
                        'user_id' => $user->id,
                        'public_key' => $request->keys['p256dh'] ?? $subscription->public_key,
                        'auth_token' => $request->keys['auth'] ?? $subscription->auth_token,
                        'device_type' => $request->device_type ?? 'web',
                        'device_info' => $request->device_info ?? [],
                    ]);
                } else {
                    // تحديث معلومات الجهاز
                    $subscription->update([
                        'public_key' => $request->keys['p256dh'] ?? $subscription->public_key,
                        'auth_token' => $request->keys['auth'] ?? $subscription->auth_token,
                        'device_type' => $request->device_type ?? $subscription->device_type ?? 'web',
                        'device_info' => $request->device_info ?? $subscription->device_info ?? [],
                    ]);
                }
            } else {
                Log::info('Creating new subscription');
                // إنشاء subscription جديد
                $subscription = PushSubscription::create([
                    'user_id' => $user->id,
                    'endpoint' => $request->endpoint,
                    'public_key' => $request->keys['p256dh'] ?? null,
                    'auth_token' => $request->keys['auth'] ?? null,
                    'device_type' => $request->device_type ?? 'web',
                    'device_info' => $request->device_info ?? [],
                ]);
            }

            Log::info('Push subscription registered successfully', [
                'user_id' => $user->id,
                'subscription_id' => $subscription->id,
                'endpoint' => substr($request->endpoint, 0, 50) . '...',
            ]);

            return response()->json([
                'success' => true,
                'message' => 'تم تسجيل subscription بنجاح',
                'subscription_id' => $subscription->id,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to register push subscription: ' . $e->getMessage());
            Log::error('Error stack: ' . $e->getTraceAsString());
            return response()->json([
                'error' => 'فشل تسجيل subscription: ' . $e->getMessage(),
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
