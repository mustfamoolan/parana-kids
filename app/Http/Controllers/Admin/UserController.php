<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class UserController extends Controller
{
    /**
     * عرض قائمة المستخدمين
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', User::class);

        $query = User::query();

        // فلترة حسب النوع
        if ($request->filled('role')) {
            $query->where('role', $request->role);
        }

        // بحث
        if ($request->filled('search')) {
            $query->where(function($q) use ($request) {
                $q->where('name', 'LIKE', "%{$request->search}%")
                  ->orWhere('phone', 'LIKE', "%{$request->search}%")
                  ->orWhere('code', 'LIKE', "%{$request->search}%")
                  ->orWhere('email', 'LIKE', "%{$request->search}%");
            });
        }

        $users = $query->with('warehouses')->latest()->paginate(20);

        return view('admin.users.index', compact('users'));
    }

    /**
     * عرض صفحة إضافة مستخدم
     */
    public function create()
    {
        $this->authorize('create', User::class);
        $warehouses = Warehouse::all();
        return view('admin.users.create', compact('warehouses'));
    }

    /**
     * حفظ مستخدم جديد
     */
    public function store(Request $request)
    {
        $this->authorize('create', User::class);

        $rules = [
            'name' => 'required|string|max:255',
            'phone' => 'required|string|unique:users,phone',
            'password' => 'required|string|min:6',
            'role' => 'required|in:admin,supplier,delegate',
        ];

        // كود مطلوب للمجهز والمندوب
        if (in_array($request->role, ['supplier', 'delegate'])) {
            $rules['code'] = 'required|string|unique:users,code';
        }

        // البريد اختياري لكن يجب أن يكون فريد
        if ($request->filled('email')) {
            $rules['email'] = 'email|unique:users,email';
        }

        $validated = $request->validate($rules);
        $validated['password'] = Hash::make($validated['password']);

        DB::transaction(function() use ($validated, $request) {
            $user = User::create($validated);

            // ربط المخازن للموردين والمندوبين
            if (in_array($request->role, ['supplier', 'delegate']) && $request->filled('warehouses')) {
                $user->warehouses()->attach($request->warehouses);
            }
        });

        return redirect()->route('admin.users.index')
                        ->with('success', 'تم إضافة المستخدم بنجاح');
    }

    /**
     * عرض صفحة التعديل
     */
    public function edit(User $user)
    {
        $this->authorize('update', $user);
        $warehouses = Warehouse::all();
        return view('admin.users.edit', compact('user', 'warehouses'));
    }

    /**
     * تحديث المستخدم
     */
    public function update(Request $request, User $user)
    {
        $this->authorize('update', $user);

        $rules = [
            'name' => 'required|string|max:255',
            'phone' => 'required|string|unique:users,phone,' . $user->id,
            'role' => 'required|in:admin,supplier,delegate',
        ];

        // كود مطلوب للمجهز والمندوب
        if (in_array($request->role, ['supplier', 'delegate'])) {
            $rules['code'] = 'required|string|unique:users,code,' . $user->id;
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

            // تحديث المخازن للموردين والمندوبين
            if (in_array($request->role, ['supplier', 'delegate'])) {
                $user->warehouses()->sync($request->warehouses ?? []);
            }
        });

        return redirect()->route('admin.users.index')
                        ->with('success', 'تم تحديث المستخدم بنجاح');
    }

    /**
     * حذف المستخدم
     */
    public function destroy(User $user)
    {
        $this->authorize('delete', $user);

        // منع حذف المدير الوحيد
        if ($user->isAdmin() && User::where('role', 'admin')->count() === 1) {
            return back()->withErrors(['error' => 'لا يمكن حذف المدير الوحيد']);
        }

        $user->delete();

        return redirect()->route('admin.users.index')
                        ->with('success', 'تم حذف المستخدم بنجاح');
    }
}
