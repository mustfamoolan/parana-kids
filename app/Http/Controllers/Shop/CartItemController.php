<?php

namespace App\Http\Controllers\Shop;

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
     * إضافة منتج إلى السلة
     */
    public function store(Request $request)
    {
        $isAjax = $request->expectsJson() || $request->wantsJson() || $request->ajax();

        try {
            $request->validate([
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

        // الحصول على أو إنشاء Cart للـ session
        $sessionId = session()->getId();
        $cart = Cart::getOrCreateGuestCart($sessionId);
        session(['shop_cart_id' => $cart->id]);

        $product = Product::where('is_hidden', false)->findOrFail($request->product_id);

        // معالجة كل عنصر
        foreach ($request->items as $item) {
            $size = ProductSize::findOrFail($item['size_id']);

            // التأكد من أن القياس يخص المنتج
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

                // تحديث الحجز
                if ($existingItem->stockReservation) {
                    $existingItem->stockReservation->update(['quantity_reserved' => $newQuantity]);
                } else {
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
                        'price' => $product->effective_price,
                    ]);

                    StockReservation::create([
                        'product_size_id' => $size->id,
                        'cart_item_id' => $cartItem->id,
                        'quantity_reserved' => $item['quantity'],
                    ]);
                });
            }
        }

        if ($isAjax) {
            return response()->json([
                'success' => true,
                'message' => 'تم إضافة المنتجات إلى السلة بنجاح',
                'cart_id' => $cart->id
            ]);
        }

        return back()->with('success', 'تم إضافة المنتجات إلى السلة بنجاح');
    }

    /**
     * تحديث كمية منتج في السلة
     */
    public function update(Request $request, CartItem $cartItem)
    {
        $isAjax = $request->expectsJson() || $request->wantsJson() || $request->ajax();

        $request->validate([
            'quantity' => 'required|integer|min:1',
        ]);

        // التأكد من أن العنصر يخص session الحالي
        if ($cartItem->cart->session_id !== session()->getId()) {
            if ($isAjax) {
                return response()->json([
                    'success' => false,
                    'message' => 'غير مصرح لك بالوصول'
                ], 403);
            }
            abort(403);
        }

        $cartItem->load('stockReservation');
        $size = $cartItem->size;
        $availableQuantity = $size->available_quantity + $cartItem->quantity;

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

            if ($cartItem->stockReservation) {
                $cartItem->stockReservation->update(['quantity_reserved' => $request->quantity]);
            } else {
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
     * حذف منتج من السلة
     */
    public function destroy(CartItem $cartItem)
    {
        $isAjax = request()->expectsJson() || request()->wantsJson() || request()->ajax();

        // التأكد من أن العنصر يخص session الحالي
        if ($cartItem->cart->session_id !== session()->getId()) {
            if ($isAjax) {
                return response()->json([
                    'success' => false,
                    'message' => 'غير مصرح لك بالوصول'
                ], 403);
            }
            abort(403);
        }

        DB::transaction(function() use ($cartItem) {
            $cartItem->stockReservation()->delete();
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
