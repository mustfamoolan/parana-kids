<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\PwaToken;
use App\Models\Warehouse;
use App\Models\PrivateWarehouse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class UserApiController extends Controller
{
    /**
     * تسجيل دخول للمدير والمجهز
     */
    public function loginAdmin(Request $request)
    {
        $request->validate([
            'login_field' => 'required|string',
            'password' => 'required|string',
        ]);

        $loginField = $request->login_field;
        $password = $request->password;

        // Try to find user by phone (for admin) or code (for supplier)
        $user = User::where('phone', $loginField)
                   ->orWhere('code', $loginField)
                   ->first();

        if ($user && Hash::check($password, $user->password)) {
            // Check if user is admin, supplier, or private_supplier
            if ($user->isAdmin() || $user->isSupplier() || $user->isPrivateSupplier()) {
                // إنشاء PWA token
                $pwaToken = PwaToken::generateToken($user->id);

                return response()->json([
                    'success' => true,
                    'message' => 'تم تسجيل الدخول بنجاح',
                    'token' => $pwaToken->token,
                    'expires_at' => $pwaToken->expires_at->toIso8601String(),
                    'user' => $this->formatUserData($user),
                ]);
            }
        }

        return response()->json([
            'success' => false,
            'message' => 'بيانات الدخول غير صحيحة.',
        ], 401);
    }

    /**
     * تسجيل دخول للمندوب
     */
    public function loginDelegate(Request $request)
    {
        $request->validate([
            'code' => 'required|string',
            'password' => 'required|string',
        ]);

        $code = $request->code;
        $password = $request->password;

        // Find user by code
        $user = User::where('code', $code)->first();

        if ($user && Hash::check($password, $user->password)) {
            // Check if user is delegate
            if ($user->isDelegate()) {
                // إنشاء PWA token
                $pwaToken = PwaToken::generateToken($user->id);

                return response()->json([
                    'success' => true,
                    'message' => 'تم تسجيل الدخول بنجاح',
                    'token' => $pwaToken->token,
                    'expires_at' => $pwaToken->expires_at->toIso8601String(),
                    'user' => $this->formatUserData($user),
                ]);
            }
        }

        return response()->json([
            'success' => false,
            'message' => 'بيانات الدخول غير صحيحة.',
        ], 401);
    }

    /**
     * معلومات المستخدم الحالي
     */
    public function me(Request $request)
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'غير مصرح.',
            ], 401);
        }

        return response()->json([
            'success' => true,
            'user' => $this->formatUserData($user),
        ]);
    }

    /**
     * تحديث بيانات المستخدم الحالي
     */
    public function update(Request $request)
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'غير مصرح.',
            ], 401);
        }

        $rules = [
            'name' => 'required|string|max:255',
            'phone' => 'required|string|unique:users,phone,' . $user->id,
            'role' => 'required|in:admin,supplier,delegate,private_supplier',
        ];

        // كود مطلوب للمجهز والمندوب والمورد
        if (in_array($request->role, ['supplier', 'delegate', 'private_supplier'])) {
            $rules['code'] = 'required|string|unique:users,code,' . $user->id;
        }

        // اسم البيج للمندوب فقط (اختياري)
        if ($request->role === 'delegate') {
            $rules['page_name'] = 'nullable|string|max:255';
        }

        // البريد اختياري
        if ($request->filled('email')) {
            $rules['email'] = 'email|unique:users,email,' . $user->id;
        }

        // كلمة المرور اختيارية في التعديل
        if ($request->filled('password')) {
            $rules['password'] = 'string|min:6';
        }

        $validated = $request->validate($rules);

        if ($request->filled('password')) {
            $validated['password'] = Hash::make($validated['password']);
        } else {
            unset($validated['password']);
        }

        DB::transaction(function() use ($user, $validated, $request) {
            $user->update($validated);

            // تحديث المخازن للمجهزين والمندوبين (ليس للمورد private_supplier)
            if (in_array($request->role, ['supplier', 'delegate'])) {
                $user->warehouses()->sync($request->warehouses ?? []);
            } else {
                // إزالة المخازن إذا لم يكن المستخدم مجهز أو مندوب
                $user->warehouses()->sync([]);
            }

            // تحديث المخزن الخاص للموردين (private_supplier)
            if ($request->role === 'private_supplier') {
                $user->update(['private_warehouse_id' => $request->private_warehouse_id ?? null]);
            } else {
                // إزالة المخزن الخاص إذا لم يكن المستخدم مورد
                $user->update(['private_warehouse_id' => null]);
            }
        });

        // إعادة تحميل المستخدم للحصول على البيانات المحدثة
        $user->refresh();

        return response()->json([
            'success' => true,
            'message' => 'تم تحديث المستخدم بنجاح',
            'user' => $this->formatUserData($user),
        ]);
    }

    /**
     * إنشاء مستخدم جديد (للمدير فقط)
     */
    public function store(Request $request)
    {
        $currentUser = Auth::user();

        if (!$currentUser || !$currentUser->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'غير مصرح. فقط المدير يمكنه إنشاء مستخدمين.',
            ], 403);
        }

        $rules = [
            'name' => 'required|string|max:255',
            'phone' => 'required|string|unique:users,phone',
            'password' => 'required|string|min:6',
            'role' => 'required|in:admin,supplier,delegate,private_supplier',
        ];

        // كود مطلوب للمجهز والمندوب والمورد
        if (in_array($request->role, ['supplier', 'delegate', 'private_supplier'])) {
            $rules['code'] = 'required|string|unique:users,code';
        }

        // اسم البيج للمندوب فقط (اختياري)
        if ($request->role === 'delegate') {
            $rules['page_name'] = 'nullable|string|max:255';
        }

        // البريد اختياري لكن يجب أن يكون فريد
        if ($request->filled('email')) {
            $rules['email'] = 'email|unique:users,email';
        }

        $validated = $request->validate($rules);
        $validated['password'] = Hash::make($validated['password']);

        $user = null;
        DB::transaction(function() use ($validated, $request, &$user) {
            $user = User::create($validated);

            // ربط المخازن للمجهزين والمندوبين (ليس للمورد private_supplier)
            if (in_array($request->role, ['supplier', 'delegate']) && $request->filled('warehouses')) {
                $user->warehouses()->attach($request->warehouses);
            }

            // ربط المخزن الخاص للموردين (private_supplier)
            if ($request->role === 'private_supplier' && $request->filled('private_warehouse_id')) {
                $user->update(['private_warehouse_id' => $request->private_warehouse_id]);
            }
        });

        // إعادة تحميل المستخدم للحصول على العلاقات
        $user->refresh();
        $user->load(['warehouses', 'privateWarehouse']);

        return response()->json([
            'success' => true,
            'message' => 'تم إضافة المستخدم بنجاح',
            'user' => $this->formatUserData($user),
        ], 201);
    }

    /**
     * تنسيق بيانات المستخدم للإرجاع
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

