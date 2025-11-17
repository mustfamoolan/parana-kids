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
        $request->validate([
            'token' => 'required|string',
            'device_type' => 'nullable|string|in:web,android,ios',
            'device_info' => 'nullable|array',
        ]);

        $user = Auth::user();

        if (!$user) {
            return response()->json(['error' => 'غير مصرح'], 401);
        }

        try {
            // البحث عن token موجود
            $fcmToken = FcmToken::where('token', $request->token)->first();

            if ($fcmToken) {
                // تحديث المستخدم إذا كان مختلف
                if ($fcmToken->user_id != $user->id) {
                    $fcmToken->update([
                        'user_id' => $user->id,
                        'device_type' => $request->device_type ?? 'web',
                        'device_info' => $request->device_info ?? [],
                    ]);
                } else {
                    // تحديث معلومات الجهاز
                    $fcmToken->update([
                        'device_type' => $request->device_type ?? $fcmToken->device_type ?? 'web',
                        'device_info' => $request->device_info ?? $fcmToken->device_info ?? [],
                    ]);
                }
            } else {
                // إنشاء token جديد
                $fcmToken = FcmToken::create([
                    'user_id' => $user->id,
                    'token' => $request->token,
                    'device_type' => $request->device_type ?? 'web',
                    'device_info' => $request->device_info ?? [],
                ]);
            }

            Log::info('FCM token registered', [
                'user_id' => $user->id,
                'token' => substr($request->token, 0, 20) . '...',
            ]);

            return response()->json([
                'success' => true,
                'message' => 'تم تسجيل token بنجاح',
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to register FCM token: ' . $e->getMessage());
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

