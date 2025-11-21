<?php

namespace App\Http\Controllers;

use App\Models\FcmToken;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class FcmController extends Controller
{
    /**
     * تسجيل FCM token للمستخدم الحالي
     */
    public function registerToken(Request $request)
    {
        $user = Auth::user();

        if (!$user) {
            Log::warning('FCM token registration: Unauthorized request');
            return response()->json(['error' => 'غير مصرح'], 401);
        }

        Log::info('FCM token registration request', [
            'user_id' => $user->id,
            'has_token' => $request->has('token'),
            'token_preview' => $request->has('token') ? substr($request->token, 0, 20) . '...' : 'none',
        ]);

        try {
            $validated = $request->validate([
                'token' => 'required|string|min:10',
                'device_type' => 'nullable|string|in:web,android,ios',
                'device_info' => 'nullable|array',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::warning('FCM token registration: Validation failed', [
                'user_id' => $user->id,
                'errors' => $e->errors(),
            ]);
            return response()->json([
                'error' => 'بيانات غير صحيحة',
                'errors' => $e->errors(),
            ], 422);
        }

        try {
            // البحث عن token موجود
            $fcmToken = FcmToken::where('token', $request->token)->first();

            if ($fcmToken) {
                // تحديث المستخدم إذا كان مختلف
                if ($fcmToken->user_id != $user->id) {
                    Log::info('FCM token registration: Updating token user', [
                        'token_id' => $fcmToken->id,
                        'old_user_id' => $fcmToken->user_id,
                        'new_user_id' => $user->id,
                    ]);
                    $fcmToken->update([
                        'user_id' => $user->id,
                        'device_type' => $request->device_type ?? 'web',
                        'device_info' => $request->device_info ?? [],
                    ]);
                } else {
                    // تحديث معلومات الجهاز
                    Log::info('FCM token registration: Updating existing token device info', [
                        'token_id' => $fcmToken->id,
                        'user_id' => $user->id,
                    ]);
                    $fcmToken->update([
                        'device_type' => $request->device_type ?? $fcmToken->device_type ?? 'web',
                        'device_info' => $request->device_info ?? $fcmToken->device_info ?? [],
                    ]);
                }
            } else {
                // إنشاء token جديد
                Log::info('FCM token registration: Creating new token', [
                    'user_id' => $user->id,
                    'device_type' => $request->device_type ?? 'web',
                ]);
                $fcmToken = FcmToken::create([
                    'user_id' => $user->id,
                    'token' => $request->token,
                    'device_type' => $request->device_type ?? 'web',
                    'device_info' => $request->device_info ?? [],
                ]);
            }

            Log::info('FCM token registered successfully', [
                'user_id' => $user->id,
                'token_id' => $fcmToken->id,
                'token_preview' => substr($request->token, 0, 20) . '...',
                'device_type' => $fcmToken->device_type,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'تم تسجيل token بنجاح',
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to register FCM token', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json([
                'error' => 'فشل تسجيل token',
            ], 500);
        }
    }

    /**
     * حذف FCM token
     */
    public function deleteToken(Request $request)
    {
        $request->validate([
            'token' => 'required|string',
        ]);

        $user = Auth::user();

        if (!$user) {
            return response()->json(['error' => 'غير مصرح'], 401);
        }

        try {
            FcmToken::where('user_id', $user->id)
                ->where('token', $request->token)
                ->delete();

            Log::info('FCM token deleted', [
                'user_id' => $user->id,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'تم حذف token بنجاح',
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to delete FCM token: ' . $e->getMessage());
            return response()->json([
                'error' => 'فشل حذف token',
            ], 500);
        }
    }
}

