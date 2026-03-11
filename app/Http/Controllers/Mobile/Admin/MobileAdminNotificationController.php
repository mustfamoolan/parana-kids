<?php

namespace App\Http\Controllers\Mobile\Admin;

use App\Http\Controllers\Controller;
use App\Models\FcmToken;
use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class MobileAdminNotificationController extends Controller
{
    public function registerToken(Request $request)
    {
        $user = Auth::user();

        if (!$user || $user->role !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Admin access only.',
            ], 403);
        }

        try {
            $validator = Validator::make($request->all(), [
                'token' => 'required|string',
                'device_type' => 'nullable|string|in:android,ios',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation error',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $token = $request->input('token');
            $deviceType = $request->input('device_type', 'android');

            $existingToken = FcmToken::where('token', $token)->first();

            if ($existingToken) {
                $existingToken->update([
                    'user_id' => $user->id,
                    'device_type' => $deviceType,
                    'app_type' => 'admin_mobile',
                    'is_active' => true,
                ]);
            } else {
                FcmToken::create([
                    'user_id' => $user->id,
                    'token' => $token,
                    'device_type' => $deviceType,
                    'app_type' => 'admin_mobile',
                    'is_active' => true,
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Token registered successfully',
            ]);
        } catch (\Exception $e) {
            Log::error('MobileAdminNotificationController: Error registering token: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error registering token',
            ], 500);
        }
    }

    public function getUnreadCount()
    {
        $user = Auth::user();

        if (!$user || $user->role !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized.',
            ], 403);
        }

        $count = Notification::where('user_id', $user->id)
            ->whereNull('read_at')
            ->count();

        return response()->json([
            'success' => true,
            'data' => [
                'unread_count' => $count,
            ],
        ]);
    }
}
