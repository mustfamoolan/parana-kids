<?php

namespace App\Http\Controllers\Delegate;

use App\Http\Controllers\Controller;
use App\Models\Cart;

class CartController extends Controller
{
    /**
     * عرض السلة الحالية (النظام الجديد)
     */
    public function view()
    {
        $cartId = session('current_cart_id');

        if (!$cartId) {
            return redirect()->route('delegate.orders.start')
                           ->with('info', 'لا يوجد طلب نشط حالياً');
        }

        try {
            $cart = Cart::with(['items.product.primaryImage', 'items.size'])
                       ->findOrFail($cartId);

            // التأكد من أن السلة تخص المندوب الحالي
            if ($cart->delegate_id !== auth()->id()) {
                abort(403);
            }

            $customerData = session('customer_data');

            // التحقق من وجود بيانات الزبون
            if (!$customerData) {
                return redirect()->route('delegate.orders.start')
                               ->with('error', 'بيانات الزبون غير موجودة. يرجى إنشاء طلب جديد');
            }

            return view('delegate.carts.view', compact('cart', 'customerData'));
        } catch (\Exception $e) {
            \Log::error('Error in CartController@view: ' . $e->getMessage());
            return redirect()->route('delegate.products.all')
                           ->with('error', 'حدث خطأ أثناء عرض السلة: ' . $e->getMessage());
        }
    }

    /**
     * عرض تفاصيل سلة محددة (النظام القديم)
     */
    public function show(Cart $cart)
    {
        // التأكد من أن السلة تخص المندوب الحالي
        if ($cart->delegate_id !== auth()->id()) {
            abort(403);
        }

        $cart->load(['items.product.primaryImage', 'items.size']);

        return view('delegate.carts.show', compact('cart'));
    }

    /**
     * عرض قائمة السلات (النظام القديم)
     */
    public function index()
    {
        $carts = Cart::where('delegate_id', auth()->id())
                    ->with('items')
                    ->latest()
                    ->paginate(15);

        return view('delegate.carts.index', compact('carts'));
    }

    /**
     * إنشاء سلة جديدة (النظام القديم)
     */
    public function store()
    {
        $cart = Cart::create([
            'delegate_id' => auth()->id(),
            'cart_name' => 'سلة ' . now()->format('Y-m-d H:i'),
            'status' => 'active',
            'expires_at' => now()->addHours(24),
        ]);

        return redirect()->route('delegate.carts.show', $cart)
                        ->with('success', 'تم إنشاء السلة بنجاح');
    }

    /**
     * حذف سلة (النظام القديم)
     */
    public function destroy(Cart $cart)
    {
        // التأكد من أن السلة تخص المندوب الحالي
        if ($cart->delegate_id !== auth()->id()) {
            abort(403);
        }

        // حذف الحجوزات أولاً
        foreach ($cart->items as $item) {
            $item->stockReservation()->delete();
        }

        // حذف السلة
        $cart->delete();

        return redirect()->route('delegate.carts.index')
                        ->with('success', 'تم حذف السلة بنجاح');
    }

    /**
     * تمديد صلاحية السلة (النظام القديم)
     */
    public function extend(Cart $cart)
    {
        // التأكد من أن السلة تخص المندوب الحالي
        if ($cart->delegate_id !== auth()->id()) {
            abort(403);
        }

        $cart->update([
            'expires_at' => now()->addHours(24)
        ]);

        return back()->with('success', 'تم تمديد صلاحية السلة لـ 24 ساعة');
    }
}
