<?php

namespace App\Http\Controllers\Delegate;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CartController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $carts = auth()->user()->carts()
            ->where('status', 'active')
            ->where('expires_at', '>', now()) // Only show non-expired carts
            ->with(['items.product', 'items.size'])
            ->get();

        return view('delegate.carts.index', compact('carts'));
    }

    /**
     * Display the specified resource.
     */
    public function show(Cart $cart)
    {
        // التأكد من أن السلة تخص المندوب الحالي
        if ($cart->delegate_id !== auth()->id()) {
            abort(403);
        }

        // التحقق من انتهاء صلاحية السلة
        if ($cart->isExpired()) {
            return redirect()->route('delegate.carts.index')
                            ->withErrors(['cart' => 'انتهت صلاحية هذه السلة']);
        }

        $cart->load(['items.product.primaryImage', 'items.size']);

        return view('delegate.carts.show', compact('cart'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'cart_name' => 'required|string|max:255',
        ]);

        $cart = Cart::create([
            'delegate_id' => auth()->id(),
            'cart_name' => $request->cart_name,
            'status' => 'active',
            'expires_at' => now()->addHour(), // Set expiration to 1 hour from now
        ]);

        // إذا كان AJAX request
        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'cart' => $cart,
                'message' => 'تم إنشاء السلة بنجاح'
            ]);
        }

        return redirect()->route('delegate.carts.show', $cart)
                        ->with('success', 'تم إنشاء السلة بنجاح');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Cart $cart)
    {
        // التأكد من أن السلة تخص المندوب الحالي
        if ($cart->delegate_id !== auth()->id()) {
            abort(403);
        }

        DB::transaction(function() use ($cart) {
            // إرجاع جميع المنتجات للمخزون
            foreach ($cart->items as $item) {
                if ($item->stockReservation) {
                    $item->stockReservation->delete();
                }
            }

            // حذف السلة
            $cart->delete();
        });

        return redirect()->route('delegate.carts.index')
                        ->with('success', 'تم حذف السلة بنجاح');
    }

    /**
     * Extend cart expiration
     */
    public function extend(Cart $cart)
    {
        // التأكد من أن السلة تخص المندوب الحالي
        if ($cart->delegate_id !== auth()->id()) {
            abort(403);
        }

        // التحقق من أن السلة نشطة وغير منتهية الصلاحية
        if ($cart->status !== 'active' || $cart->isExpired()) {
            return redirect()->route('delegate.carts.index')
                            ->withErrors(['cart' => 'لا يمكن تمديد صلاحية هذه السلة']);
        }

        $cart->extendExpiration();

        return redirect()->route('delegate.carts.show', $cart)
                        ->with('success', 'تم تمديد صلاحية السلة بنجاح');
    }
}
