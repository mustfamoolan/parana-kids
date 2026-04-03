<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\FirebaseCloudMessagingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class FirebaseNotificationController extends Controller
{
    protected $fcmService;

    public function __construct(FirebaseCloudMessagingService $fcmService)
    {
        $this->fcmService = $fcmService;
    }

    /**
     * Show the mass notification form
     */
    public function index()
    {
        return view('admin.notifications.send');
    }

    /**
     * Get users by role for searchable dropdown
     */
    public function getUsersByRole(Request $request)
    {
        $role = $request->query('role');
        $search = $request->query('search');

        $query = User::query();

        if ($role === 'supplier') {
            $query->whereIn('role', ['supplier', 'private_supplier']);
        } elseif ($role) {
            $query->where('role', $role);
        }

        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $users = $query->limit(20)->get(['id', 'name', 'phone', 'role']);

        return response()->json($users);
    }

    /**
     * Send the notification
     */
    public function send(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'message' => 'required|string',
            'target_group' => 'required|in:customer,supplier,delegate',
            'target_scope' => 'required|in:all,specific',
            'user_id' => 'required_if:target_scope,specific|exists:users,id',
        ]);

        $title = $request->title;
        $body = $request->message;
        $targetGroup = $request->target_group;
        $targetScope = $request->target_scope;
        
        // Map target group to Firebase app_type
        $appType = match($targetGroup) {
            'customer' => 'customer_mobile',
            'delegate' => 'delegate_mobile',
            'supplier' => 'admin_mobile',
        };

        try {
            $sentCount = 0;
            
            if ($targetScope === 'all') {
                $roles = ($targetGroup === 'supplier') ? ['supplier', 'private_supplier'] : [$targetGroup];
                $sentCount = $this->fcmService->sendToRole($roles, $title, $body, ['type' => 'mass_notification'], $appType);
            } else {
                $sentCount = $this->fcmService->sendToUsers([$request->user_id], $title, $body, ['type' => 'mass_notification'], $appType);
            }

            if ($sentCount > 0) {
                return back()->with('success', "تم إرسال الإشعار بنجاح إلى {$sentCount} جهاز.");
            } else {
                return back()->with('warning', 'لم يتم العثور على أجهزة نشطة (Tokens) للمستخدمين المحددين.');
            }

        } catch (\Exception $e) {
            Log::error('FirebaseNotificationController: Failed to send mass notification: ' . $e->getMessage());
            return back()->with('error', 'فشل في إرسال الإشعار: ' . $e->getMessage());
        }
    }
}
