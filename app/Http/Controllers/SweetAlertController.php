<?php

namespace App\Http\Controllers;

use App\Services\SweetAlertService;
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
    public function getUnread()
    {
        $user = Auth::user();
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
    public function markAsRead($id)
    {
        $user = Auth::user();
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
            'message' => 'تم تحديد الإشعار كمقروء',
        ]);
    }
}
