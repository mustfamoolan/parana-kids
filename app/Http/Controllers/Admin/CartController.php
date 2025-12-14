<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CartController extends Controller
{
    /**
     * عرض السلة الحالية
     */
    public function view(Request $request)
    {
        // التأكد من أن المستخدم مدير أو مجهز
        if (!Auth::user()->isAdmin() && !Auth::user()->isSupplier()) {
            abort(403, 'غير مصرح لك بالوصول إلى هذه الصفحة.');
        }

        // قراءة cart_id من session
        $cartId = session('current_cart_id');

        if (!$cartId) {
            return redirect()->route('admin.orders.create.start')
                           ->with('info', 'لا يوجد طلب نشط حالياً');
        }

        try {
            $cart = Cart::with(['items.product.primaryImage', 'items.size'])
                       ->findOrFail($cartId);

            // التأكد من أن السلة تخص المدير/المجهز الحالي
            if ($cart->created_by !== auth()->id()) {
                abort(403);
            }

            // التحقق من وجود بيانات الزبون في Cart
            if (!$cart->customer_name || !$cart->customer_phone) {
                return redirect()->route('admin.orders.create.start')
                               ->with('error', 'بيانات الزبون غير موجودة. يرجى إنشاء طلب جديد');
            }

            // تحضير customer_data من Cart
            $customerData = [
                'customer_name' => $cart->customer_name,
                'customer_phone' => $cart->customer_phone,
                'customer_phone2' => $cart->customer_phone2,
                'customer_address' => $cart->customer_address,
                'customer_social_link' => $cart->customer_social_link,
                'notes' => $cart->notes,
            ];

            return view('admin.carts.view', compact('cart', 'customerData'));
        } catch (\Exception $e) {
            return redirect()->route('admin.products.index')
                           ->with('error', 'حدث خطأ أثناء عرض السلة: ' . $e->getMessage());
        }
    }
}
