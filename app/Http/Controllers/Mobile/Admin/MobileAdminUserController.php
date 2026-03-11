<?php

namespace App\Http\Controllers\Mobile\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MobileAdminUserController extends Controller
{
    /**
     * جلب قائمة المستخدمين مع الترقيم والبحث
     */
    public function index(Request $request)
    {
        $currentUser = Auth::user();

        if (!$currentUser || !$currentUser->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'غير مصرح للوصول لهذه البيانات.',
            ], 403);
        }

        try {
            $query = User::query();

            // البحث
            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('name', 'LIKE', "%{$search}%")
                      ->orWhere('email', 'LIKE', "%{$search}%")
                      ->orWhere('phone', 'LIKE', "%{$search}%")
                      ->orWhere('code', 'LIKE', "%{$search}%");
                });
            }

            // الفلترة حسب الدور (اختياري)
            if ($request->filled('role')) {
                $query->where('role', $request->role);
            }

            $perPage = $request->input('per_page', 20);
            $users = $query->latest()->paginate($perPage);

            // تنسيق البيانات لتناسب الـ UserModel في الفلاتر
            $users->getCollection()->transform(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email ?? '',
                    'phone' => $user->phone ?? '',
                    'code' => $user->code ?? '',
                    'role' => $user->role,
                    'page_name' => $user->page_name ?? '',
                    'profile_image' => $user->profile_image,
                    'profile_image_url' => $user->getProfileImageUrl(),
                    'created_at' => $user->created_at->toIso8601String(),
                    'updated_at' => $user->updated_at->toIso8601String(),
                ];
            });

            return response()->json([
                'success' => true,
                'data' => [
                    'users' => $users
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء جلب قائمة المستخدمين: ' . $e->getMessage(),
            ], 500);
        }
    }
}
