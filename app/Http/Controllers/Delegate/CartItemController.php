<?php

namespace App\Http\Controllers\Delegate;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use App\Models\ProductSize;
use App\Models\StockReservation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CartItemController extends Controller
{
    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'cart_id' => 'required|exists:carts,id',
            'product_id' => 'required|exists:products,id',
            'items' => 'required|array|min:1',
            'items.*.size_id' => 'required|exists:product_sizes,id',
            'items.*.quantity' => 'required|integer|min:1',
        ]);

        $cart = Cart::findOrFail($request->cart_id);

        // التأكد من أن السلة تخص المندوب الحالي
        if ($cart->delegate_id !== auth()->id()) {
            abort(403);
        }

        $product = Product::findOrFail($request->product_id);

        // معالجة كل عنصر في المصفوفة
        foreach ($request->items as $item) {
            $size = ProductSize::findOrFail($item['size_id']);

            // التأكد من أن القياس يخص المنتج المحدد
            if ($size->product_id !== $product->id) {
                return back()->withErrors(['size_id' => 'القياس المحدد لا يخص هذا المنتج']);
            }

            // التحقق من توفر الكمية
            $availableQuantity = $size->available_quantity;
            if ($availableQuantity < $item['quantity']) {
                return back()->withErrors(['quantity' => 'الكمية المطلوبة غير متوفرة للقياس ' . $size->size_name . '. المتوفر: ' . $availableQuantity]);
            }

            // التحقق من وجود نفس المنتج والقياس في السلة
            $existingItem = $cart->items()
                ->where('product_id', $product->id)
                ->where('size_id', $size->id)
                ->with('stockReservation')
                ->first();

            if ($existingItem) {
                // تحديث الكمية الموجودة
                $newQuantity = $existingItem->quantity + $item['quantity'];
                if ($availableQuantity < $newQuantity) {
                    return back()->withErrors(['quantity' => 'الكمية الإجمالية المطلوبة غير متوفرة للقياس ' . $size->size_name . '. المتوفر: ' . $availableQuantity]);
                }

                $existingItem->update(['quantity' => $newQuantity]);

                // تحديث الحجز إذا كان موجوداً
                if ($existingItem->stockReservation) {
                    $existingItem->stockReservation->update(['quantity_reserved' => $newQuantity]);
                } else {
                    // إنشاء حجز جديد إذا لم يكن موجوداً
                    StockReservation::create([
                        'product_size_id' => $size->id,
                        'cart_item_id' => $existingItem->id,
                        'quantity_reserved' => $newQuantity,
                    ]);
                }
            } else {
                // إضافة منتج جديد للسلة
                DB::transaction(function() use ($cart, $product, $size, $item) {
                    $cartItem = CartItem::create([
                        'cart_id' => $cart->id,
                        'product_id' => $product->id,
                        'size_id' => $size->id,
                        'quantity' => $item['quantity'],
                        'price' => $product->selling_price,
                    ]);

                    StockReservation::create([
                        'product_size_id' => $size->id,
                        'cart_item_id' => $cartItem->id,
                        'quantity_reserved' => $item['quantity'],
                    ]);
                });
            }
        }

        return back()->with('success', 'تم إضافة المنتجات إلى السلة بنجاح');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, CartItem $cartItem)
    {
        $request->validate([
            'quantity' => 'required|integer|min:1',
        ]);

        // التأكد من أن العنصر يخص المندوب الحالي
        if ($cartItem->cart->delegate_id !== auth()->id()) {
            abort(403);
        }

        // تحميل العلاقات المطلوبة
        $cartItem->load('stockReservation');
        $size = $cartItem->size;
        $availableQuantity = $size->available_quantity + $cartItem->quantity; // إضافة الكمية المحجوزة حالياً

        if ($availableQuantity < $request->quantity) {
            return back()->withErrors(['quantity' => 'الكمية المطلوبة غير متوفرة. المتوفر: ' . $availableQuantity]);
        }

        DB::transaction(function() use ($cartItem, $request) {
            $cartItem->update(['quantity' => $request->quantity]);

            // تحديث الحجز إذا كان موجوداً
            if ($cartItem->stockReservation) {
                $cartItem->stockReservation->update(['quantity_reserved' => $request->quantity]);
            } else {
                // إنشاء حجز جديد إذا لم يكن موجوداً
                StockReservation::create([
                    'product_size_id' => $cartItem->size_id,
                    'cart_item_id' => $cartItem->id,
                    'quantity_reserved' => $request->quantity,
                ]);
            }
        });

        return back()->with('success', 'تم تحديث الكمية بنجاح');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(CartItem $cartItem)
    {
        // التأكد من أن العنصر يخص المندوب الحالي
        if ($cartItem->cart->delegate_id !== auth()->id()) {
            abort(403);
        }

        DB::transaction(function() use ($cartItem) {
            // حذف الحجز (إرجاع للمخزون)
            $cartItem->stockReservation()->delete();

            // حذف المنتج من السلة
            $cartItem->delete();
        });

        return back()->with('success', 'تم حذف المنتج من السلة بنجاح');
    }
}
