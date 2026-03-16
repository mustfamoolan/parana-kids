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
                'device_id' => 'nullable|string',
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
            $deviceId = $request->input('device_id');
            $deviceType = $request->input('device_type', 'android');

            // 1. إذا تم توفير device_id، نبحث عنه أولاً لتحديث التوكن لنفس الجهاز
            if ($deviceId) {
                $existingByDevice = FcmToken::where('device_id', $deviceId)
                    ->where('app_type', 'admin_mobile')
                    ->first();

                if ($existingByDevice) {
                    $existingByDevice->update([
                        'user_id' => $user->id,
                        'token' => $token,
                        'device_type' => $deviceType,
                        'is_active' => true,
                    ]);

                    return response()->json([
                        'success' => true,
                        'message' => 'Token updated by device_id',
                    ]);
                }
            }

            // 2. إذا لم نجد بـ device_id أو لم يتم توفيره، نبحث بالتوكن نفسه
            $existingToken = FcmToken::where('token', $token)->first();

            if ($existingToken) {
                $existingToken->update([
                    'user_id' => $user->id,
                    'device_id' => $deviceId ?? $existingToken->device_id,
                    'device_type' => $deviceType,
                    'app_type' => 'admin_mobile',
                    'is_active' => true,
                ]);
            } else {
                FcmToken::create([
                    'user_id' => $user->id,
                    'device_id' => $deviceId,
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

    public function index(Request $request)
    {
        $user = Auth::user();

        if (!$user || $user->role !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Admin access only.',
                'error_code' => 'FORBIDDEN',
            ], 403);
        }

        try {
            $perPage = $request->input('per_page', 20);
            $type = $request->input('type');

            $query = Notification::where('user_id', $user->id)
                ->active()
                ->orderBy('created_at', 'desc');

            if ($type) {
                $query->ofType($type);
            }

            $notifications = $query->paginate($perPage);

            $formattedNotifications = $notifications->map(function ($notification) {
                return [
                    'id' => $notification->id,
                    'type' => $notification->type,
                    'title' => $notification->title,
                    'message' => $notification->message,
                    'data' => $notification->data,
                    'is_read' => !is_null($notification->read_at),
                    'read_at' => $notification->read_at ? $notification->read_at->toIso8601String() : null,
                    'created_at' => $notification->created_at->toIso8601String(),
                ];
            });

            return response()->json([
                'success' => true,
                'data' => [
                    'notifications' => $formattedNotifications,
                    'pagination' => [
                        'current_page' => $notifications->currentPage(),
                        'last_page' => $notifications->lastPage(),
                        'per_page' => $notifications->perPage(),
                        'total' => $notifications->total(),
                    ],
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('MobileAdminNotificationController: Error fetching notifications', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء جلب الإشعارات: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function markAsRead($id)
    {
        $user = Auth::user();

        if (!$user || $user->role !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Admin access only.',
            ], 403);
        }

        try {
            $notification = Notification::where('user_id', $user->id)->findOrFail($id);

            if (!$notification->isRead()) {
                $notification->markAsRead();
            }

            return response()->json([
                'success' => true,
                'message' => 'تم تحديد الإشعار كمقروء',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'الإشعار غير موجود أو حدث خطأ',
            ], 404);
        }
    }

    public function markAllAsRead()
    {
        $user = Auth::user();

        if (!$user || $user->role !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Admin access only.',
            ], 403);
        }

        try {
            $updated = Notification::where('user_id', $user->id)
                ->unread()
                ->active()
                ->update(['read_at' => now()]);

            return response()->json([
                'success' => true,
                'message' => "تم تحديد {$updated} إشعار كمقروء",
                'data' => [
                    'updated_count' => $updated,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء التحديث',
            ], 500);
        }
    }

    public function unregisterToken(Request $request)
    {
        $user = Auth::user();

        if (!$user || $user->role !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized.',
            ], 403);
        }

        try {
            $validator = Validator::make($request->all(), [
                'token' => 'required|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation error',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $token = $request->input('token');

            $deleted = FcmToken::where('user_id', $user->id)
                ->where('token', $token)
                ->where('app_type', 'admin_mobile')
                ->delete();

            if ($deleted) {
                return response()->json([
                    'success' => true,
                    'message' => 'Token unregistered successfully',
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Token not found',
                ], 404);
            }
        } catch (\Exception $e) {
            Log::error('MobileAdminNotificationController: Error unregistering token: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error unregistering token',
            ], 500);
        }
    }
}
