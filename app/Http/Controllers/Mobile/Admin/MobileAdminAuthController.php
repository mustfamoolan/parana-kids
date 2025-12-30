<?php

namespace App\Http\Controllers\Mobile\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\PwaToken;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class MobileAdminAuthController extends Controller
{
    /**
     * تسجيل دخول للمدير أو المجهز
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function login(Request $request)
    {
        $request->validate([
            'code' => 'required|string',
            'password' => 'required|string',
        ], [
            'code.required' => 'حقل الكود مطلوب',
            'password.required' => 'حقل كلمة المرور مطلوب',
        ]);

        $code = $request->code;
        $password = $request->password;

        // Find user by code or phone (for admin who might not have code)
        $user = User::where(function($query) use ($code) {
            $query->where('code', $code)
                  ->orWhere('phone', $code);
        })->first();

        if ($user && Hash::check($password, $user->password)) {
            // Check if user is admin, supplier, or private_supplier
            if ($user->isAdmin() || $user->isSupplier() || $user->isPrivateSupplier()) {
                // إنشاء PWA token
                $pwaToken = PwaToken::generateToken($user->id);

                return response()->json([
                    'success' => true,
                    'message' => 'تم تسجيل الدخول بنجاح',
                    'data' => [
                        'token' => $pwaToken->token,
                        'expires_at' => $pwaToken->expires_at->toIso8601String(),
                        'user' => $this->formatUserData($user),
                    ],
                ]);
            }
        }

        return response()->json([
            'success' => false,
            'message' => 'بيانات الدخول غير صحيحة أو المستخدم ليس مديراً أو مجهزاً',
            'error_code' => 'INVALID_CREDENTIALS',
        ], 401);
    }

    /**
     * معلومات المستخدم الحالي (للمدير أو المجهز فقط)
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function me(Request $request)
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'غير مصرح',
                'error_code' => 'UNAUTHORIZED',
            ], 401);
        }

        // التحقق من أن المستخدم مدير أو مجهز
        if (!$user->isAdmin() && !$user->isSupplier() && !$user->isPrivateSupplier()) {
            return response()->json([
                'success' => false,
                'message' => 'هذا API مخصص للمديرين والمجهزين فقط',
                'error_code' => 'FORBIDDEN',
            ], 403);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'user' => $this->formatUserData($user),
            ],
        ]);
    }

    /**
     * تسجيل الخروج
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout(Request $request)
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'غير مصرح',
                'error_code' => 'UNAUTHORIZED',
            ], 401);
        }

        // التحقق من أن المستخدم مدير أو مجهز
        if (!$user->isAdmin() && !$user->isSupplier() && !$user->isPrivateSupplier()) {
            return response()->json([
                'success' => false,
                'message' => 'هذا API مخصص للمديرين والمجهزين فقط',
                'error_code' => 'FORBIDDEN',
            ], 403);
        }

        try {
            // الحصول على token من header
            $token = $request->header('Authorization');
            if ($token && str_starts_with($token, 'Bearer ')) {
                $token = substr($token, 7);
            } else {
                $token = $request->header('X-PWA-Token');
            }

            if ($token) {
                // إلغاء token
                $pwaToken = PwaToken::findByToken($token);
                if ($pwaToken) {
                    $pwaToken->expire();
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'تم تسجيل الخروج بنجاح',
            ]);
        } catch (\Exception $e) {
            Log::error('MobileAdminAuthController: Error in logout', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء تسجيل الخروج',
                'error_code' => 'LOGOUT_ERROR',
            ], 500);
        }
    }

    /**
     * تحديث الملف الشخصي (صورة البروفايل فقط للمدير أو المجهز)
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateProfile(Request $request)
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'غير مصرح',
                'error_code' => 'UNAUTHORIZED',
            ], 401);
        }

        // التحقق من أن المستخدم مدير أو مجهز
        if (!$user->isAdmin() && !$user->isSupplier() && !$user->isPrivateSupplier()) {
            return response()->json([
                'success' => false,
                'message' => 'هذا API مخصص للمديرين والمجهزين فقط',
                'error_code' => 'FORBIDDEN',
            ], 403);
        }

        $request->validate([
            'profile_image' => 'required|image|mimes:jpeg,jpg,png|max:2048',
        ], [
            'profile_image.required' => 'يجب إرسال صورة البروفايل',
            'profile_image.image' => 'الملف يجب أن يكون صورة',
            'profile_image.mimes' => 'نوع الصورة يجب أن يكون: jpeg, jpg, png',
            'profile_image.max' => 'حجم الصورة يجب أن يكون أقل من 2MB',
        ]);

        try {
            // التأكد من وجود المجلد قبل الحفظ
            if (!Storage::disk('public')->exists('profiles')) {
                Storage::disk('public')->makeDirectory('profiles');
            }

            // حذف الصورة القديمة إن وجدت
            if ($user->profile_image) {
                Storage::disk('public')->delete($user->profile_image);
            }

            // حفظ الصورة الجديدة
            $image = $request->file('profile_image');
            $path = $image->storeAs('profiles', $user->id . '_' . time() . '.' . $image->getClientOriginalExtension(), 'public');

            $user->profile_image = $path;
            $user->save();

            // إعادة تحميل المستخدم للحصول على البيانات المحدثة
            $user->refresh();

            return response()->json([
                'success' => true,
                'message' => 'تم تحديث الملف الشخصي بنجاح',
                'data' => [
                    'user' => $this->formatUserData($user),
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('MobileAdminAuthController: Failed to upload profile image', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'فشل رفع الصورة: ' . $e->getMessage(),
                'error_code' => 'UPLOAD_ERROR',
            ], 500);
        }
    }

    /**
     * تنسيق بيانات المستخدم للإرجاع
     *
     * @param User $user
     * @return array
     */
    private function formatUserData(User $user)
    {
        $user->load(['warehouses', 'privateWarehouse']);

        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'code' => $user->code,
            'role' => $user->role,
            'page_name' => $user->page_name,
            'profile_image' => $user->profile_image,
            'profile_image_url' => $user->getProfileImageUrl(),
            'private_warehouse_id' => $user->private_warehouse_id,
            'telegram_chat_id' => $user->telegram_chat_id,
            'warehouses' => $user->warehouses->map(function($warehouse) {
                return [
                    'id' => $warehouse->id,
                    'name' => $warehouse->name,
                    'can_manage' => $warehouse->pivot->can_manage ?? false,
                ];
            }),
            'private_warehouse' => $user->privateWarehouse ? [
                'id' => $user->privateWarehouse->id,
                'name' => $user->privateWarehouse->name,
            ] : null,
            'created_at' => $user->created_at->toIso8601String(),
            'updated_at' => $user->updated_at->toIso8601String(),
        ];
    }
}

