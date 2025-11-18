<?php

namespace App\Http\Controllers;

use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    protected $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * جلب عدد الإشعارات غير المقروءة
     */
    public function getUnreadCount(Request $request)
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json(['error' => 'غير مصرح'], 401);
        }

        $type = $request->input('type'); // message, order, product, etc.

        $count = $this->notificationService->getUnreadCount($user->id, $type);

        return response()->json([
            'success' => true,
            'count' => $count,
            'type' => $type ?? 'all',
        ]);
    }

    /**
     * جلب الإشعارات
     */
    public function getNotifications(Request $request)
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json(['error' => 'غير مصرح'], 401);
        }

        $type = $request->input('type');
        $limit = $request->input('limit', 10);

        $notifications = $this->notificationService->getUnreadNotifications($user->id, $type, $limit);

        return response()->json([
            'success' => true,
            'notifications' => $notifications->map(function ($notification) {
                return [
                    'id' => $notification->id,
                    'type' => $notification->type,
                    'title' => $notification->title,
                    'body' => $notification->message, // استخدام message من الجدول
                    'data' => $notification->data,
                    'created_at' => $notification->created_at->toIso8601String(),
                ];
            }),
        ]);
    }

    /**
     * تحديد إشعار كمقروء
     */
    public function markAsRead(Request $request, $id)
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json(['error' => 'غير مصرح'], 401);
        }

        $result = $this->notificationService->markAsRead($id, $user->id);

        return response()->json([
            'success' => $result,
        ]);
    }

    /**
     * تحديد جميع الإشعارات كمقروءة
     */
    public function markAllAsRead(Request $request)
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json(['error' => 'غير مصرح'], 401);
        }

        $type = $request->input('type');

        $count = $this->notificationService->markAllAsRead($user->id, $type);

        return response()->json([
            'success' => true,
            'count' => $count,
        ]);
    }
}
