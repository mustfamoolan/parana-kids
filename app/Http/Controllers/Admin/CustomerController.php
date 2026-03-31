<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class CustomerController extends Controller
{
    /**
     * عرض قائمة العملاء (Customers)
     */
    public function index()
    {
        // استدعاء جميع المستخدمين ذوي الصلاحية customer
        $customers = User::where('role', 'customer')->latest()->paginate(20);
        return view('admin.customers.index', compact('customers'));
    }

    /**
     * إظهار نموذج تعديل بيانات العميل
     */
    public function edit($id)
    {
        $customer = User::where('role', 'customer')->findOrFail($id);
        return view('admin.customers.edit', compact('customer'));
    }

    /**
     * تحديث بيانات العميل في قاعدة البيانات
     */
    public function update(Request $request, $id)
    {
        $customer = User::where('role', 'customer')->findOrFail($id);

        $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|unique:users,phone,' . $customer->id,
            // Email we might not want them to change if it's from google, but we can allow it
            'email' => 'nullable|email|unique:users,email,' . $customer->id,
        ]);

        $customer->update([
            'name' => $request->name,
            'phone' => $request->phone,
            'email' => $request->email,
        ]);

        return redirect()->route('admin.customers.index')->with('success', 'تم تحديث بيانات العميل بنجاح.');
    }

    /**
     * حذف العميل (Customer) نهائياً أو إيقافه
     */
    public function destroy($id)
    {
        $customer = User::where('role', 'customer')->findOrFail($id);
        
        // قد نود التأكد أن العميل ليس لديه طلبات نشطة (لكن بما أننا نعتمد delegate_id يمكننا حذفه)
        // أو يمكننا استخدام الحذف الناعم (Soft Delete) لو تم إضافته لنموذج Users لاحقاً.
        
        // حذف الصورة المحلية إن وجدت وكانت ليست رابط خارجي
        if ($customer->profile_image && !str_starts_with($customer->profile_image, 'http')) {
            Storage::disk('public')->delete($customer->profile_image);
        }

        $customer->delete();

        return redirect()->route('admin.customers.index')->with('success', 'تم حذف حساب العميل بنجاح.');
    }
}
