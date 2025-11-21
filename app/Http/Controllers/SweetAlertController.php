<?php

namespace App\Http\Controllers;

use App\Services\SweetAlertService;
use App\Http\Controllers\PwaTokenController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SweetAlertController extends Controller
{
    protected $sweetAlertService;

    public function __construct(SweetAlertService $sweetAlertService)
    {
        $this->sweetAlertService = $sweetAlertService;
    }

    /**
     * Get unread alerts for the current user
     */
    public function getUnread(Request $request)
    {
        $user = Auth::user();

        // إذا لم يكن المستخدم مسجلاً عبر session، جرب PWA token
        if (!$user) {
            $pwaToken = $request->header('X-PWA-Token');
            if ($pwaToken) {
                $user = PwaTokenController::validateToken($pwaToken);
                if ($user) {
                    // تسجيل دخول المستخدم مؤقتاً للطلب الحالي
                    Auth::setUser($user);
                }
            }
        }

        if (!$user) {
            \Log::warning('SweetAlertController: Unauthorized request');
            return response()->json(['error' => 'غير مصرح'], 401);
        }

        \Log::info('SweetAlertController: Fetching unread alerts for user', ['user_id' => $user->id]);

        $alerts = $this->sweetAlertService->getUnreadForUser($user->id);

        \Log::info('SweetAlertController: Found alerts', [
            'user_id' => $user->id,
            'count' => $alerts->count(),
        ]);

        return response()->json([
            'success' => true,
            'alerts' => $alerts->map(function($alert) {
                return [
                    'id' => $alert->id,
                    'type' => $alert->type,
                    'title' => $alert->title,
                    'message' => $alert->message,
                    'icon' => $alert->icon,
                    'data' => $alert->data,
                    'created_at' => $alert->created_at->format('Y-m-d H:i:s'),
                ];
            }),
        ]);
    }

    /**
     * Mark alert as read
     */
    public function markAsRead(Request $request, $id)
    {
        $user = Auth::user();

        // إذا لم يكن المستخدم مسجلاً عبر session، جرب PWA token
        if (!$user) {
            $pwaToken = $request->header('X-PWA-Token');
            if ($pwaToken) {
                $user = PwaTokenController::validateToken($pwaToken);
                if ($user) {
                    // تسجيل دخول المستخدم مؤقتاً للطلب الحالي
                    Auth::setUser($user);
                }
            }
        }

        if (!$user) {
            return response()->json(['error' => 'غير مصرح'], 401);
        }

        $alert = \App\Models\SweetAlert::find($id);
        if (!$alert) {
            return response()->json(['error' => 'الإشعار غير موجود'], 404);
        }

        // التأكد من أن الإشعار يخص المستخدم الحالي
        if ($alert->user_id !== $user->id) {
            return response()->json(['error' => 'غير مصرح'], 403);
        }

        $this->sweetAlertService->markAsRead($id);

        return response()->json([
            'success' => true,
            'message' => 'تم حذف الإشعار',
        ]);
    }

    /**
     * Check if user has unread alert for a specific order
     */
    public function checkOrder(Request $request, $orderId)
    {
        $user = Auth::user();

        // إذا لم يكن المستخدم مسجلاً عبر session، جرب PWA token
        if (!$user) {
            $pwaToken = $request->header('X-PWA-Token');
            if ($pwaToken) {
                $user = PwaTokenController::validateToken($pwaToken);
                if ($user) {
                    Auth::setUser($user);
                }
            }
        }

        if (!$user) {
            return response()->json(['error' => 'غير مصرح'], 401);
        }

        $hasUnread = $this->sweetAlertService->hasUnreadAlertForOrder($orderId, $user->id);

        return response()->json([
            'success' => true,
            'has_unread' => $hasUnread,
        ]);
    }

    /**
     * Check if user has unread alert for a specific conversation
     */
    public function checkConversation(Request $request, $conversationId)
    {
        $user = Auth::user();

        // إذا لم يكن المستخدم مسجلاً عبر session، جرب PWA token
        if (!$user) {
            $pwaToken = $request->header('X-PWA-Token');
            if ($pwaToken) {
                $user = PwaTokenController::validateToken($pwaToken);
                if ($user) {
                    Auth::setUser($user);
                }
            }
        }

        if (!$user) {
            return response()->json(['error' => 'غير مصرح'], 401);
        }

        $hasUnread = $this->sweetAlertService->hasUnreadAlertForConversation($conversationId, $user->id);

        return response()->json([
            'success' => true,
            'has_unread' => $hasUnread,
        ]);
    }
}
