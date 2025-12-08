<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use Illuminate\Http\Request;

class CartController extends Controller
{
    /**
     * عرض السلة الحالية
     */
    public function view(Request $request)
    {
        // قراءة cart_id من session
        $cartId = session('shop_cart_id');

        if (!$cartId) {
            return redirect()->route('shop.index')
                           ->with('info', 'السلة فارغة');
        }

        try {
            $cart = Cart::with(['items.product.primaryImage', 'items.size'])
                       ->findOrFail($cartId);

            // التأكد من أن السلة تخص session الحالي
            if ($cart->session_id !== session()->getId()) {
                // إذا كانت السلة تخص session آخر، مسح session واعادة التوجيه
                session()->forget('shop_cart_id');
                return redirect()->route('shop.index')
                               ->with('info', 'تم إعادة تعيين السلة');
            }

            return view('shop.cart', compact('cart'));
        } catch (\Exception $e) {
            \Log::error('Error in Shop CartController@view: ' . $e->getMessage());
            session()->forget('shop_cart_id');
            return redirect()->route('shop.index')
                           ->with('error', 'حدث خطأ أثناء عرض السلة');
        }
    }
}
