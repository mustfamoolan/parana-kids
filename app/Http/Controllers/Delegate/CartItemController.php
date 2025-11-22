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
        // تحديد إذا كان الطلب AJAX
        $isAjax = $request->expectsJson() || $request->wantsJson() || $request->ajax();

        // إذا لم يُرسل cart_id، استخدم السلة النشطة من request أو session
        $cartId = $request->cart_id ?? $request->input('cart_id') ?? session('current_cart_id');

        if (!$cartId) {
            if ($isAjax) {
                return response()->json([
                    'success' => false,
                    'message' => 'ابدأ طلباً جديداً أولاً'
                ], 400);
            }
            return redirect()->route('delegate.orders.start')
                           ->with('info', 'ابدأ طلباً جديداً أولاً');
        }

        $request->merge(['cart_id' => $cartId]);

        try {
            $request->validate([
                'cart_id' => 'required|exists:carts,id',
                'product_id' => 'required|exists:products,id',
                'items' => 'required|array|min:1',
                'items.*.size_id' => 'required|exists:product_sizes,id',
                'items.*.quantity' => 'required|integer|min:1',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            if ($isAjax) {
                return response()->json([
                    'success' => false,
                    'message' => 'خطأ في البيانات المدخلة',
                    'errors' => $e->errors()
                ], 422);
            }
            throw $e;
        }

        $cart = Cart::findOrFail($cartId);

        // التأكد من أن السلة تخص المندوب الحالي
        if ($cart->delegate_id !== auth()->id()) {
            if ($isAjax) {
                return response()->json([
                    'success' => false,
                    'message' => 'غير مصرح لك بالوصول إلى هذه السلة'
                ], 403);
            }
            abort(403);
        }

        $product = Product::findOrFail($request->product_id);

        // معالجة كل عنصر في المصفوفة
        foreach ($request->items as $item) {
            $size = ProductSize::findOrFail($item['size_id']);

            // التأكد من أن القياس يخص المنتج المحدد
            if ($size->product_id !== $product->id) {
                $errorMsg = 'القياس المحدد لا يخص هذا المنتج';
                if ($isAjax) {
                    return response()->json([
                        'success' => false,
                        'message' => $errorMsg
                    ], 400);
                }
                return back()->withErrors(['size_id' => $errorMsg]);
            }

            // التحقق من توفر الكمية
            $availableQuantity = $size->available_quantity;
            if ($availableQuantity < $item['quantity']) {
                $errorMsg = 'الكمية المطلوبة غير متوفرة للقياس ' . $size->size_name . '. المتوفر: ' . $availableQuantity;
                if ($isAjax) {
                    return response()->json([
                        'success' => false,
                        'message' => $errorMsg
                    ], 400);
                }
                return back()->withErrors(['quantity' => $errorMsg]);
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
                    $errorMsg = 'الكمية الإجمالية المطلوبة غير متوفرة للقياس ' . $size->size_name . '. المتوفر: ' . $availableQuantity;
                    if ($isAjax) {
                        return response()->json([
                            'success' => false,
                            'message' => $errorMsg
                        ], 400);
                    }
                    return back()->withErrors(['quantity' => $errorMsg]);
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
                        'price' => $product->effective_price, // استخدام السعر الفعلي (يشمل التخفيضات)
                    ]);

                    StockReservation::create([
                        'product_size_id' => $size->id,
                        'cart_item_id' => $cartItem->id,
                        'quantity_reserved' => $item['quantity'],
                    ]);
                });
            }
        }

        // إرجاع JSON للطلبات AJAX
        if ($isAjax) {
            return response()->json([
                'success' => true,
                'message' => 'تم إضافة المنتجات إلى السلة بنجاح'
            ]);
        }

        return back()->with('success', 'تم إضافة المنتجات إلى السلة بنجاح');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, CartItem $cartItem)
    {
        // تحديد إذا كان الطلب AJAX
        $isAjax = $request->expectsJson() || $request->wantsJson() || $request->ajax();

        $request->validate([
            'quantity' => 'required|integer|min:1',
        ]);

        // التأكد من أن العنصر يخص المندوب الحالي
        if ($cartItem->cart->delegate_id !== auth()->id()) {
            if ($isAjax) {
                return response()->json([
                    'success' => false,
                    'message' => 'غير مصرح لك بالوصول'
                ], 403);
            }
            abort(403);
        }

        // تحميل العلاقات المطلوبة
        $cartItem->load('stockReservation');
        $size = $cartItem->size;
        $availableQuantity = $size->available_quantity + $cartItem->quantity; // إضافة الكمية المحجوزة حالياً

        if ($availableQuantity < $request->quantity) {
            $errorMsg = 'الكمية المطلوبة غير متوفرة. المتوفر: ' . $availableQuantity;
            if ($isAjax) {
                return response()->json([
                    'success' => false,
                    'message' => $errorMsg
                ], 400);
            }
            return back()->withErrors(['quantity' => $errorMsg]);
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

        if ($isAjax) {
            return response()->json([
                'success' => true,
                'message' => 'تم تحديث الكمية بنجاح'
            ]);
        }

        return back()->with('success', 'تم تحديث الكمية بنجاح');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(CartItem $cartItem)
    {
        // تحديد إذا كان الطلب AJAX
        $isAjax = request()->expectsJson() || request()->wantsJson() || request()->ajax();

        // التأكد من أن العنصر يخص المندوب الحالي
        if ($cartItem->cart->delegate_id !== auth()->id()) {
            if ($isAjax) {
                return response()->json([
                    'success' => false,
                    'message' => 'غير مصرح لك بالوصول'
                ], 403);
            }
            abort(403);
        }

        DB::transaction(function() use ($cartItem) {
            // حذف الحجز (إرجاع للمخزون)
            $cartItem->stockReservation()->delete();

            // حذف المنتج من السلة
            $cartItem->delete();
        });

        if ($isAjax) {
            return response()->json([
                'success' => true,
                'message' => 'تم حذف المنتج بنجاح'
            ]);
        }

        return back()->with('success', 'تم حذف المنتج من السلة بنجاح');
    }
}
