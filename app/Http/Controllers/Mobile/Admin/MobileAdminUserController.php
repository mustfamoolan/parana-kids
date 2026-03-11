<?php

namespace App\Http\Controllers\Mobile\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\PrivateWarehouse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

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
            $users = $query->with('warehouses', 'privateWarehouse')->latest()->paginate($perPage);

            // تنسيق البيانات لتناسب الـ UserModel في الفلاتر
            $users->getCollection()->transform(function ($user) {
                return $this->formatUser($user);
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

    /**
     * جلب قائمة المخازن والمخازن الخاصة لإسناد الصلاحيات
     */
    public function getWarehouses()
    {
        try {
            $warehouses = Warehouse::select('id', 'name')->get();
            $privateWarehouses = PrivateWarehouse::select('id', 'name')->get();

            return response()->json([
                'success' => true,
                'data' => [
                    'warehouses' => $warehouses,
                    'private_warehouses' => $privateWarehouses,
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * إنشاء مستخدم جديد
     */
    public function store(Request $request)
    {
        $currentUser = Auth::user();

        if (!$currentUser || !$currentUser->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'غير مصرح. فقط المدير يمكنه إضافة مستخدمين.',
            ], 403);
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|unique:users,phone',
            'email' => 'nullable|email|unique:users,email',
            'password' => 'required|string|min:6',
            'role' => 'required|in:admin,supplier,delegate,private_supplier',
            'code' => 'required_if:role,supplier,delegate,private_supplier|nullable|string|unique:users,code',
            'warehouses' => 'nullable|array',
            'private_warehouse_id' => 'nullable|exists:private_warehouses,id',
        ]);

        try {
            return DB::transaction(function() use ($request) {
                $user = User::create([
                    'name' => $request->name,
                    'phone' => $request->phone,
                    'email' => $request->email,
                    'password' => Hash::make($request->password),
                    'role' => $request->role,
                    'code' => $request->code,
                    'page_name' => $request->page_name,
                    'private_warehouse_id' => $request->role === 'private_supplier' ? $request->private_warehouse_id : null,
                ]);

                // ربط المخازن للمجهزين والمندوبين
                if (in_array($request->role, ['supplier', 'delegate']) && $request->filled('warehouses')) {
                    $user->warehouses()->attach($request->warehouses);
                }

                return response()->json([
                    'success' => true,
                    'message' => 'تم إضافة المستخدم بنجاح',
                    'data' => $this->formatUser($user->load('warehouses', 'privateWarehouse')),
                ]);
            });
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء إضافة المستخدم: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * تحديث بيانات مستخدم
     */
    public function update(Request $request, $id)
    {
        $currentUser = Auth::user();

        if (!$currentUser || !$currentUser->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'غير مصرح. فقط المدير يمكنه تعديل بيانات المستخدمين.',
            ], 403);
        }

        $user = User::with('warehouses')->find($id);
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'المستخدم غير موجود'], 404);
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|unique:users,phone,' . $id,
            'email' => 'nullable|email|unique:users,email,' . $id,
            'password' => 'nullable|string|min:6',
            'role' => 'required|in:admin,supplier,delegate,private_supplier',
            'code' => 'required_if:role,supplier,delegate,private_supplier|nullable|string|unique:users,code,' . $id,
            'warehouses' => 'nullable|array',
            'private_warehouse_id' => 'nullable|exists:private_warehouses,id',
        ]);

        try {
            return DB::transaction(function() use ($request, $user) {
                $data = [
                    'name' => $request->name,
                    'phone' => $request->phone,
                    'email' => $request->email,
                    'role' => $request->role,
                    'code' => $request->code,
                    'page_name' => $request->page_name,
                ];

                if ($request->filled('password')) {
                    $data['password'] = Hash::make($request->password);
                }

                if ($request->role === 'private_supplier') {
                    $data['private_warehouse_id'] = $request->private_warehouse_id;
                } else {
                    $data['private_warehouse_id'] = null;
                }

                $user->update($data);

                // تحديث المخازن للمجهزين والمندوبين
                if (in_array($request->role, ['supplier', 'delegate'])) {
                    $user->warehouses()->sync($request->warehouses ?? []);
                } else {
                    $user->warehouses()->sync([]);
                }

                return response()->json([
                    'success' => true,
                    'message' => 'تم تحديث بيانات المستخدم بنجاح',
                    'data' => $this->formatUser($user->load('warehouses', 'privateWarehouse')),
                ]);
            });
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء تحديث المستخدم: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * حذف مستخدم
     */
    public function destroy($id)
    {
        $currentUser = Auth::user();

        if (!$currentUser || !$currentUser->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'غير مصرح. فقط المدير يمكنه حذف المستخدمين.',
            ], 403);
        }

        $user = User::find($id);
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'المستخدم غير موجود'], 404);
        }

        // منع حذف المدير الوحيد
        if ($user->isAdmin() && User::where('role', 'admin')->count() === 1) {
            return response()->json([
                'success' => false,
                'message' => 'لا يمكن حذف المدير الوحيد للنظام.',
            ], 400);
        }

        try {
            $user->delete();
            return response()->json([
                'success' => true,
                'message' => 'تم حذف المستخدم بنجاح',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء حذف المستخدم: ' . $e->getMessage(),
            ], 500);
        }
    }

    private function formatUser($user)
    {
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
            'private_warehouse_id' => $user->private_warehouse_id,
            'warehouses' => $user->warehouses->map(function($w) {
                return [
                    'id' => $w->id,
                    'name' => $w->name,
                    'can_manage' => (bool)$w->pivot?->can_manage,
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
