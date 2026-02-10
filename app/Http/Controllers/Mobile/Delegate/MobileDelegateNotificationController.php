<?php

namespace App\Http\Controllers\Mobile\Delegate;

use App\Http\Controllers\Controller;
use App\Models\FcmToken;
use App\Models\Notification;
use App\Services\FirebaseCloudMessagingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class MobileDelegateNotificationController extends Controller
{
    /**
     * تسجيل FCM token للمندوب
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function registerToken(Request $request)
    {
        $user = Auth::user();

        // التحقق من أن المستخدم مندوب
        if (!$user || !$user->isDelegate()) {
            return response()->json([
                'success' => false,
                'message' => 'غير مصرح. يجب أن تكون مندوباً للوصول إلى هذه البيانات.',
                'error_code' => 'FORBIDDEN',
            ], 403);
        }

        try {
            $validator = Validator::make($request->all(), [
                'token' => 'required|string',
                'device_type' => 'nullable|string|in:android,ios,web',
                'device_info' => 'nullable|array',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'خطأ في التحقق من البيانات',
                    'errors' => $validator->errors(),
                    'error_code' => 'VALIDATION_ERROR',
                ], 422);
            }

            $token = $request->input('token');
            $deviceType = $request->input('device_type', 'android');
            $deviceInfo = $request->input('device_info', []);

            // البحث عن token موجود
            $existingToken = FcmToken::where('token', $token)->first();

            if ($existingToken) {
                // تحديث token موجود
                $existingToken->update([
                    'user_id' => $user->id,
                    'device_type' => $deviceType,
                    'device_info' => $deviceInfo,
                    'app_type' => 'delegate_mobile',
                    'is_active' => true,
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'تم تحديث تسجيل الجهاز بنجاح',
                ]);
            } else {
                // إنشاء token جديد
                FcmToken::create([
                    'user_id' => $user->id,
                    'token' => $token,
                    'device_type' => $deviceType,
                    'device_info' => $deviceInfo,
                    'app_type' => 'delegate_mobile',
                    'is_active' => true,
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'تم تسجيل الجهاز بنجاح',
                ]);
            }
        } catch (\Exception $e) {
            Log::error('MobileDelegateNotificationController: Error registering token', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء تسجيل الجهاز: ' . $e->getMessage(),
                'error_code' => 'TOKEN_REGISTRATION_ERROR',
            ], 500);
        }
    }

    /**
     * جلب قائمة الإشعارات للمندوب
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $user = Auth::user();

        // التحقق من أن المستخدم مندوب
        if (!$user || !$user->isDelegate()) {
            return response()->json([
                'success' => false,
                'message' => 'غير مصرح. يجب أن تكون مندوباً للوصول إلى هذه البيانات.',
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
            Log::error('MobileDelegateNotificationController: Error fetching notifications', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء جلب الإشعارات: ' . $e->getMessage(),
                'error_code' => 'FETCH_NOTIFICATIONS_ERROR',
            ], 500);
        }
    }

    /**
     * جلب عدد الإشعارات غير المقروءة
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getUnreadCount()
    {
        $user = Auth::user();

        // التحقق من أن المستخدم مندوب
        if (!$user || !$user->isDelegate()) {
            return response()->json([
                'success' => false,
                'message' => 'غير مصرح. يجب أن تكون مندوباً للوصول إلى هذه البيانات.',
                'error_code' => 'FORBIDDEN',
            ], 403);
        }

        try {
            $unreadCount = Notification::where('user_id', $user->id)
                ->unread()
                ->active()
                ->count();

            return response()->json([
                'success' => true,
                'data' => [
                    'unread_count' => $unreadCount,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('MobileDelegateNotificationController: Error fetching unread count', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء جلب عدد الإشعارات: ' . $e->getMessage(),
                'error_code' => 'FETCH_UNREAD_COUNT_ERROR',
            ], 500);
        }
    }

    /**
     * تحديد إشعار كمقروء
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function markAsRead($id)
    {
        $user = Auth::user();

        // التحقق من أن المستخدم مندوب
        if (!$user || !$user->isDelegate()) {
            return response()->json([
                'success' => false,
                'message' => 'غير مصرح. يجب أن تكون مندوباً للوصول إلى هذه البيانات.',
                'error_code' => 'FORBIDDEN',
            ], 403);
        }

        try {
            $notification = Notification::where('user_id', $user->id)
                ->findOrFail($id);

            if ($notification->isRead()) {
                return response()->json([
                    'success' => true,
                    'message' => 'الإشعار مقروء بالفعل',
                ]);
            }

            $notification->markAsRead();

            return response()->json([
                'success' => true,
                'message' => 'تم تحديد الإشعار كمقروء',
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'الإشعار غير موجود',
                'error_code' => 'NOTIFICATION_NOT_FOUND',
            ], 404);
        } catch (\Exception $e) {
            Log::error('MobileDelegateNotificationController: Error marking notification as read', [
                'user_id' => $user->id,
                'notification_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء تحديد الإشعار كمقروء: ' . $e->getMessage(),
                'error_code' => 'MARK_READ_ERROR',
            ], 500);
        }
    }

    /**
     * تحديد جميع الإشعارات كمقروءة
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function markAllAsRead()
    {
        $user = Auth::user();

        // التحقق من أن المستخدم مندوب
        if (!$user || !$user->isDelegate()) {
            return response()->json([
                'success' => false,
                'message' => 'غير مصرح. يجب أن تكون مندوباً للوصول إلى هذه البيانات.',
                'error_code' => 'FORBIDDEN',
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
            Log::error('MobileDelegateNotificationController: Error marking all notifications as read', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء تحديد جميع الإشعارات كمقروءة: ' . $e->getMessage(),
                'error_code' => 'MARK_ALL_READ_ERROR',
            ], 500);
        }
    }

    /**
     * إلغاء تسجيل FCM token
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function unregisterToken(Request $request)
    {
        $user = Auth::user();

        // التحقق من أن المستخدم مندوب
        if (!$user || !$user->isDelegate()) {
            return response()->json([
                'success' => false,
                'message' => 'غير مصرح. يجب أن تكون مندوباً للوصول إلى هذه البيانات.',
                'error_code' => 'FORBIDDEN',
            ], 403);
        }

        try {
            $validator = Validator::make($request->all(), [
                'token' => 'required|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'خطأ في التحقق من البيانات',
                    'errors' => $validator->errors(),
                    'error_code' => 'VALIDATION_ERROR',
                ], 422);
            }

            $token = $request->input('token');

            $deleted = FcmToken::where('user_id', $user->id)
                ->where('token', $token)
                ->where('app_type', 'delegate_mobile')
                ->delete();

            if ($deleted) {
                return response()->json([
                    'success' => true,
                    'message' => 'تم إلغاء تسجيل الجهاز',
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Token غير موجود',
                    'error_code' => 'TOKEN_NOT_FOUND',
                ], 404);
            }
        } catch (\Exception $e) {
            Log::error('MobileDelegateNotificationController: Error unregistering token', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء إلغاء تسجيل الجهاز: ' . $e->getMessage(),
                'error_code' => 'TOKEN_UNREGISTRATION_ERROR',
            ], 500);
        }
    }

    /**
     * اختبار إرسال إشعار مباشر
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function testNotification(Request $request)
    {
        $user = Auth::user();

        // التحقق من أن المستخدم مندوب
        if (!$user || !$user->isDelegate()) {
            return response()->json([
                'success' => false,
                'message' => 'غير مصرح. يجب أن تكون مندوباً للوصول إلى هذه البيانات.',
                'error_code' => 'FORBIDDEN',
            ], 403);
        }

        try {
            $fcmService = app(FirebaseCloudMessagingService::class);

            // جلب token من المستخدم
            $token = FcmToken::where('user_id', $user->id)
                ->where('app_type', 'delegate_mobile')
                ->where('is_active', true)
                ->first();

            if (!$token) {
                return response()->json([
                    'success' => false,
                    'message' => 'لا يوجد FCM token مسجل. يرجى تسجيل token أولاً.',
                    'error_code' => 'NO_TOKEN_FOUND',
                ], 404);
            }

            $title = $request->input('title', 'إشعار تجريبي');
            $body = $request->input('body', 'هذا إشعار تجريبي لاختبار Push Notifications');

            $result = $fcmService->testNotification($token->token, $title, $body);

            return response()->json([
                'success' => $result['success'],
                'message' => $result['message'],
                'data' => [
                    'token_id' => $token->id,
                    'token_preview' => substr($token->token, 0, 30) . '...',
                    'device_type' => $token->device_type,
                    'debug_info' => $result['debug_info'] ?? null,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('MobileDelegateNotificationController: Error testing notification', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء اختبار الإشعار: ' . $e->getMessage(),
                'error_code' => 'TEST_NOTIFICATION_ERROR',
            ], 500);
        }
    }

    /**
     * جلب معلومات FCM tokens للمستخدم (للتشخيص)
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getTokensInfo()
    {
        $user = Auth::user();

        // التحقق من أن المستخدم مندوب
        if (!$user || !$user->isDelegate()) {
            return response()->json([
                'success' => false,
                'message' => 'غير مصرح. يجب أن تكون مندوباً للوصول إلى هذه البيانات.',
                'error_code' => 'FORBIDDEN',
            ], 403);
        }

        try {
            $fcmService = app(FirebaseCloudMessagingService::class);
            $tokensInfo = $fcmService->getUserTokens($user->id, 'delegate_mobile');

            return response()->json([
                'success' => true,
                'data' => $tokensInfo,
            ]);
        } catch (\Exception $e) {
            Log::error('MobileDelegateNotificationController: Error getting tokens info', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء جلب معلومات Tokens: ' . $e->getMessage(),
                'error_code' => 'GET_TOKENS_INFO_ERROR',
            ], 500);
        }
    }
}

