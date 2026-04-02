<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\PwaToken;
use App\Models\FcmToken;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class CustomerApiController extends Controller
{
    /**
     * تسجيل الدخول للزبائن عبر مفتاح جوجل (Google Login)
     * أو إنشاء حساب جديد إذا لم يكن موجوداً مسبقاً
     */
    public function googleLogin(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'name' => 'required|string',
            'google_id' => 'required|string',
            'profile_image' => 'nullable|string',
        ]);

        $email = $request->email;
        $googleId = $request->google_id;
        $name = $request->name;
        $profileImage = $request->profile_image;

        // البحث عن الزبون باستخدام Google ID أو الإيميل (إذا كان يملك حساباً مسبقاً)
        $user = User::where('google_id', $googleId)
                    ->orWhere('email', $email)
                    ->first();

        if ($user) {
            // تحديث الـ Google ID إن كان فارغاً (لتأمين حسابات الايميل السابقة إذا وُجدت)
            if (empty($user->google_id)) {
                $user->update(['google_id' => $googleId]);
            }
            // تحديث الصورة אם تغيرت أو لم تكن موجودة
            if (!empty($profileImage) && $user->profile_image !== $profileImage) {
                $user->update(['profile_image' => $profileImage]);
            }
        } else {
            // إنشاء زبون "Customer" جديد لأن الحساب غير موجود
            $user = User::create([
                'name' => $name,
                'email' => $email,
                'google_id' => $googleId,
                'role' => 'customer',
                'profile_image' => $profileImage,
                // كلمة مرور وهمية ومستحيلة التخمين لكون الدخول يتم عبر Google فقط
                'password' => Hash::make(Str::random(32)),
            ]);
        }

        // إنشاء وتوليد PwaToken (وهو التوكن المعتمد في المشروع)
        $pwaToken = PwaToken::generateToken($user->id);

        return response()->json([
            'success' => true,
            'message' => 'تم تسجيل الدخول كزبون بنجاح',
            'token' => $pwaToken->token,
            'expires_at' => $pwaToken->expires_at->toIso8601String(),
            'user' => $this->formatUserData($user),
        ]);
    }

    /**
     * تسجيل وتحديث مفتاح الإشعارات (FCM Token) للزبون
     */
    public function registerFcmToken(Request $request)
    {
        $request->validate([
            'token' => 'required|string',
            'device_type' => 'required|string|in:android,ios,web',
            'device_id' => 'nullable|string',
            'device_info' => 'nullable|array',
        ]);

        $user = $request->user();

        // تحديث أو إنشاء التوكن
        $fcmToken = FcmToken::updateOrCreate(
            [
                'user_id' => $user->id,
                'device_id' => $request->device_id ?? 'unknown_customer_' . $user->id,
                'app_type' => 'customer_mobile',
            ],
            [
                'token' => $request->token,
                'device_type' => $request->device_type,
                'device_info' => $request->device_info,
                'is_active' => true,
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'تم تسجيل مفتاح الإشعارات بنجاح',
        ]);
    }

    /**
     * إرجاع وتنظيم كائن المستخدم قبل إرساله في الـ API كـ JSON
     */
    private function formatUserData(User $user)
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'role' => $user->role,
            'profile_image_url' => $user->getProfileImageUrl(),
            'created_at' => $user->created_at->toIso8601String(),
        ];
    }
}
