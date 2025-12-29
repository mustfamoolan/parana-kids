<?php

namespace App\Http\Controllers\Mobile\Delegate;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductSize;
use App\Models\ProductMovement;
use App\Models\StockReservation;
use App\Services\SweetAlertService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MobileDelegateCartController extends Controller
{
    /**
     * بدء طلب جديد (إنشاء Cart)
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function initialize(Request $request)
    {
        $user = Auth::user();

        // التحقق من أن المستخدم مندوب
        if (!$user || !$user->isDelegate()) {
            return response()->json([
                'success' => false,
                'message' => 'غير مصرح. يجب أن تكون مندوباً للوصول إلى هذه البيانات.',
                'error_code' => 'FORBIDDEN',
            ], 403);
        }

        // تنسيق رقم الهاتف
        $normalizedPhone = $this->normalizePhoneNumber($request->customer_phone);
        if ($normalizedPhone === null || strlen($normalizedPhone) !== 11) {
            return response()->json([
                'success' => false,
                'message' => 'رقم الهاتف يجب أن يكون بالضبط 11 رقم بعد التنسيق',
                'error_code' => 'INVALID_PHONE',
            ], 422);
        }
        $request->merge(['customer_phone' => $normalizedPhone]);

        // تنسيق رقم الهاتف الثاني إن وجد
        if ($request->filled('customer_phone2')) {
            $normalizedPhone2 = $this->normalizePhoneNumber($request->customer_phone2);
            if ($normalizedPhone2 !== null && strlen($normalizedPhone2) === 11) {
                $request->merge(['customer_phone2' => $normalizedPhone2]);
            } else {
                $request->merge(['customer_phone2' => null]);
            }
        }

        $request->validate([
            'customer_name' => 'required|string|max:255',
            'customer_phone' => 'required|string|digits:11',
            'customer_phone2' => 'nullable|string|digits:11',
            'customer_address' => 'required|string',
            'customer_social_link' => 'required|string|max:255',
            'notes' => 'nullable|string',
        ], [
            'customer_phone.digits' => 'رقم الهاتف يجب أن يكون بالضبط 11 رقم',
            'customer_phone2.digits' => 'رقم الهاتف الثاني يجب أن يكون بالضبط 11 رقم',
        ]);

        try {
            DB::transaction(function() use ($request, $user) {
                // حذف أي سلة نشطة قديمة للمندوب (لتجنب التكرار)
                Cart::where('delegate_id', $user->id)
                    ->where('status', 'active')
                    ->get()
                    ->each(function($cart) {
                        // إرجاع الحجوزات
                        foreach ($cart->items as $item) {
                            if ($item->stockReservation) {
                                $item->stockReservation->delete();
                            }
                        }
                        $cart->delete();
                    });

                // إنشاء سلة جديدة مع بيانات الزبون
                $cart = Cart::create([
                    'delegate_id' => $user->id,
                    'cart_name' => 'طلب: ' . $request->customer_name,
                    'status' => 'active',
                    'expires_at' => now()->addHours(24),
                    'customer_name' => $request->customer_name,
                    'customer_phone' => $request->customer_phone,
                    'customer_phone2' => $request->customer_phone2,
                    'customer_address' => $request->customer_address,
                    'customer_social_link' => $request->customer_social_link,
                    'notes' => $request->notes,
                ]);
            });

            // جلب السلة المحدثة
            $cart = Cart::where('delegate_id', $user->id)
                ->where('status', 'active')
                ->latest()
                ->first();

            return response()->json([
                'success' => true,
                'message' => 'تم بدء الطلب بنجاح',
                'data' => [
                    'cart' => $this->formatCartData($cart),
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('MobileDelegateCartController: Error initializing order', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء بدء الطلب: ' . $e->getMessage(),
                'error_code' => 'INITIALIZE_ERROR',
            ], 500);
        }
    }

    /**
     * جلب السلة الحالية
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getCurrent(Request $request)
    {
        $user = Auth::user();

        // التحقق من أن المستخدم مندوب
        if (!$user || !$user->isDelegate()) {
            return response()->json([
                'success' => false,
                'message' => 'غير مصرح. يجب أن تكون مندوباً للوصول إلى هذه البيانات.',
                'error_code' => 'FORBIDDEN',
            ], 403);
        }

        // قراءة cart_id من request أو البحث عن سلة نشطة
        $cartId = $request->input('cart_id');

        if ($cartId) {
            $cart = Cart::with(['items.product.primaryImage', 'items.size'])
                ->where('id', $cartId)
                ->where('delegate_id', $user->id)
                ->first();
        } else {
            // البحث عن سلة نشطة للمندوب
            $cart = Cart::with(['items.product.primaryImage', 'items.size'])
                ->where('delegate_id', $user->id)
                ->where('status', 'active')
                ->latest()
                ->first();
        }

        if (!$cart) {
            return response()->json([
                'success' => false,
                'message' => 'لا توجد سلة نشطة',
                'error_code' => 'CART_NOT_FOUND',
            ], 404);
        }

        // التأكد من أن السلة تخص المندوب
        if ($cart->delegate_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'ليس لديك صلاحية للوصول إلى هذه السلة',
                'error_code' => 'FORBIDDEN_CART',
            ], 403);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'cart' => $this->formatCartData($cart),
            ],
        ]);
    }

    /**
     * إضافة منتجات للسلة
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function addItems(Request $request)
    {
        $user = Auth::user();

        // التحقق من أن المستخدم مندوب
        if (!$user || !$user->isDelegate()) {
            return response()->json([
                'success' => false,
                'message' => 'غير مصرح. يجب أن تكون مندوباً للوصول إلى هذه البيانات.',
                'error_code' => 'FORBIDDEN',
            ], 403);
        }

        $request->validate([
            'cart_id' => 'required|exists:carts,id',
            'product_id' => 'required|exists:products,id',
            'items' => 'required|array|min:1',
            'items.*.size_id' => 'required|exists:product_sizes,id',
            'items.*.quantity' => 'required|integer|min:1',
        ]);

        $cart = Cart::findOrFail($request->cart_id);

        // التأكد من أن السلة تخص المندوب الحالي
        if ($cart->delegate_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'ليس لديك صلاحية للوصول إلى هذه السلة',
                'error_code' => 'FORBIDDEN_CART',
            ], 403);
        }

        // التأكد من أن السلة نشطة
        if ($cart->status !== 'active') {
            return response()->json([
                'success' => false,
                'message' => 'السلة غير نشطة',
                'error_code' => 'CART_NOT_ACTIVE',
            ], 400);
        }

        $product = Product::findOrFail($request->product_id);

        try {
            DB::transaction(function() use ($cart, $product, $request) {
                // معالجة كل عنصر في المصفوفة
                foreach ($request->items as $item) {
                    $size = ProductSize::findOrFail($item['size_id']);

                    // التأكد من أن القياس يخص المنتج المحدد
                    if ($size->product_id !== $product->id) {
                        throw new \Exception('القياس المحدد لا يخص هذا المنتج');
                    }

                    // التحقق من توفر الكمية
                    $availableQuantity = $size->available_quantity;
                    if ($availableQuantity < $item['quantity']) {
                        throw new \Exception("الكمية المطلوبة غير متوفرة للقياس {$size->size_name}. المتوفر: {$availableQuantity}");
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
                            throw new \Exception("الكمية الإجمالية المطلوبة غير متوفرة للقياس {$size->size_name}. المتوفر: {$availableQuantity}");
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
                    }
                }
            });

            // إعادة تحميل السلة
            $cart->refresh();
            $cart->load(['items.product.primaryImage', 'items.size']);

            return response()->json([
                'success' => true,
                'message' => 'تم إضافة المنتجات إلى السلة بنجاح',
                'data' => [
                    'cart' => $this->formatCartData($cart),
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('MobileDelegateCartController: Error adding items to cart', [
                'cart_id' => $request->cart_id,
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'error_code' => 'ADD_ITEMS_ERROR',
            ], 400);
        }
    }

    /**
     * تحديث كمية منتج في السلة
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateItem(Request $request, $id)
    {
        $user = Auth::user();

        // التحقق من أن المستخدم مندوب
        if (!$user || !$user->isDelegate()) {
            return response()->json([
                'success' => false,
                'message' => 'غير مصرح. يجب أن تكون مندوباً للوصول إلى هذه البيانات.',
                'error_code' => 'FORBIDDEN',
            ], 403);
        }

        $request->validate([
            'quantity' => 'required|integer|min:1',
        ]);

        $cartItem = CartItem::with('cart', 'stockReservation', 'size')->findOrFail($id);

        // التأكد من أن العنصر يخص المندوب الحالي
        if ($cartItem->cart->delegate_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'ليس لديك صلاحية للوصول إلى هذا العنصر',
                'error_code' => 'FORBIDDEN_ITEM',
            ], 403);
        }

        $size = $cartItem->size;
        $availableQuantity = $size->available_quantity + $cartItem->quantity; // إضافة الكمية المحجوزة حالياً

        if ($availableQuantity < $request->quantity) {
            return response()->json([
                'success' => false,
                'message' => "الكمية المطلوبة غير متوفرة. المتوفر: {$availableQuantity}",
                'error_code' => 'INSUFFICIENT_STOCK',
            ], 400);
        }

        try {
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

            // إعادة تحميل السلة
            $cartItem->cart->refresh();
            $cartItem->cart->load(['items.product.primaryImage', 'items.size']);

            return response()->json([
                'success' => true,
                'message' => 'تم تحديث الكمية بنجاح',
                'data' => [
                    'cart' => $this->formatCartData($cartItem->cart),
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('MobileDelegateCartController: Error updating cart item', [
                'cart_item_id' => $id,
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء تحديث الكمية: ' . $e->getMessage(),
                'error_code' => 'UPDATE_ITEM_ERROR',
            ], 500);
        }
    }

    /**
     * حذف منتج من السلة
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function removeItem($id)
    {
        $user = Auth::user();

        // التحقق من أن المستخدم مندوب
        if (!$user || !$user->isDelegate()) {
            return response()->json([
                'success' => false,
                'message' => 'غير مصرح. يجب أن تكون مندوباً للوصول إلى هذه البيانات.',
                'error_code' => 'FORBIDDEN',
            ], 403);
        }

        $cartItem = CartItem::with('cart')->findOrFail($id);

        // التأكد من أن العنصر يخص المندوب الحالي
        if ($cartItem->cart->delegate_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'ليس لديك صلاحية للوصول إلى هذا العنصر',
                'error_code' => 'FORBIDDEN_ITEM',
            ], 403);
        }

        try {
            DB::transaction(function() use ($cartItem) {
                // حذف الحجز (إرجاع للمخزون)
                $cartItem->stockReservation()->delete();

                // حذف المنتج من السلة
                $cartItem->delete();
            });

            // إعادة تحميل السلة
            $cartItem->cart->refresh();
            $cartItem->cart->load(['items.product.primaryImage', 'items.size']);

            return response()->json([
                'success' => true,
                'message' => 'تم حذف المنتج بنجاح',
                'data' => [
                    'cart' => $this->formatCartData($cartItem->cart),
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('MobileDelegateCartController: Error removing cart item', [
                'cart_item_id' => $id,
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء حذف المنتج: ' . $e->getMessage(),
                'error_code' => 'REMOVE_ITEM_ERROR',
            ], 500);
        }
    }

    /**
     * إرسال الطلب (تحويل Cart إلى Order)
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function submit(Request $request)
    {
        $user = Auth::user();

        // التحقق من أن المستخدم مندوب
        if (!$user || !$user->isDelegate()) {
            return response()->json([
                'success' => false,
                'message' => 'غير مصرح. يجب أن تكون مندوباً للوصول إلى هذه البيانات.',
                'error_code' => 'FORBIDDEN',
            ], 403);
        }

        $request->validate([
            'cart_id' => 'required|exists:carts,id',
        ]);

        // استخدام lockForUpdate() لمنع التكرار
        $cart = DB::transaction(function() use ($request, $user) {
            $cart = Cart::where('id', $request->cart_id)
                ->where('status', 'active') // فقط السلات النشطة
                ->lockForUpdate() // قفل السلة لمنع التكرار
                ->firstOrFail();

            // التأكد من أن السلة تخص المندوب الحالي
            if ($cart->delegate_id !== $user->id) {
                throw new \Exception('ليس لديك صلاحية للوصول إلى هذه السلة');
            }

            // التحقق مرة أخرى من أن السلة لا تزال نشطة
            if ($cart->status !== 'active') {
                throw new \Exception('هذه السلة تم استخدامها بالفعل');
            }

            // تحميل العلاقات بعد القفل
            $cart->load('items.product', 'items.size');

            return $cart;
        });

        if ($cart->items->count() === 0) {
            return response()->json([
                'success' => false,
                'message' => 'أضف منتجات أولاً',
                'error_code' => 'EMPTY_CART',
            ], 400);
        }

        // التحقق من وجود بيانات الزبون في Cart
        if (!$cart->customer_name || !$cart->customer_phone) {
            return response()->json([
                'success' => false,
                'message' => 'بيانات الزبون غير موجودة. يرجى إنشاء طلب جديد',
                'error_code' => 'MISSING_CUSTOMER_DATA',
            ], 400);
        }

        // التحقق من عدم وجود طلب موجود بالفعل من هذه السلة
        $existingOrder = Order::where('cart_id', $cart->id)->first();
        if ($existingOrder) {
            $existingOrder->load(['items.product.primaryImage', 'items.size', 'alwaseetShipment']);
            return response()->json([
                'success' => true,
                'message' => 'تم إنشاء هذا الطلب مسبقاً',
                'data' => [
                    'order' => $this->formatOrderData($existingOrder),
                ],
            ]);
        }

        try {
            // إنشاء الطلب
            $order = DB::transaction(function() use ($cart) {
                // تحديث حالة السلة أولاً لمنع التكرار
                $cart->update(['status' => 'completed']);

                $order = Order::create([
                    'cart_id' => $cart->id,
                    'delegate_id' => $cart->delegate_id,
                    'customer_name' => $cart->customer_name,
                    'customer_phone' => $cart->customer_phone,
                    'customer_phone2' => $cart->customer_phone2,
                    'customer_address' => $cart->customer_address,
                    'customer_social_link' => $cart->customer_social_link,
                    'notes' => $cart->notes,
                    'status' => 'pending',
                    'total_amount' => $cart->total_amount,
                ]);

                // نسخ المنتجات وخصم المخزون
                foreach ($cart->items as $cartItem) {
                    OrderItem::create([
                        'order_id' => $order->id,
                        'product_id' => $cartItem->product_id,
                        'size_id' => $cartItem->size_id,
                        'product_name' => $cartItem->product->name,
                        'product_code' => $cartItem->product->code,
                        'size_name' => $cartItem->size->size_name,
                        'quantity' => $cartItem->quantity,
                        'unit_price' => $cartItem->price,
                        'subtotal' => $cartItem->subtotal,
                    ]);

                    // تحديث المخزون الفعلي (خصم الكمية)
                    $cartItem->size->decrement('quantity', $cartItem->quantity);

                    // تسجيل حركة المواد عند رفع المندوب للطلب
                    ProductMovement::record([
                        'product_id' => $cartItem->product_id,
                        'size_id' => $cartItem->size_id,
                        'warehouse_id' => $cartItem->product->warehouse_id,
                        'order_id' => $order->id,
                        'delegate_id' => $cart->delegate_id,
                        'movement_type' => 'sell',
                        'quantity' => -$cartItem->quantity,
                        'balance_after' => $cartItem->size->refresh()->quantity,
                        'order_status' => 'pending',
                        'notes' => "بيع من طلب #{$order->order_number}"
                    ]);

                    // حذف الحجز
                    if ($cartItem->stockReservation) {
                        $cartItem->stockReservation->delete();
                    }
                }

                return $order;
            });

            // إرسال Event لإنشاء شحنة في الواسط
            event(new \App\Events\OrderCreated($order));

            // إرسال SweetAlert للمجهز (نفس المخزن) أو المدير
            try {
                $sweetAlertService = app(SweetAlertService::class);
                $sweetAlertService->notifyOrderCreated($order);
            } catch (\Exception $e) {
                Log::error('MobileDelegateCartController: Error sending SweetAlert for order_created', [
                    'order_id' => $order->id,
                    'error' => $e->getMessage(),
                ]);
            }

            // إعادة تحميل الطلب مع العلاقات
            $order->refresh();
            $order->load(['items.product.primaryImage', 'items.size', 'alwaseetShipment']);

            return response()->json([
                'success' => true,
                'message' => 'تم إنشاء الطلب بنجاح! رقم الطلب: ' . $order->order_number,
                'data' => [
                    'order' => $this->formatOrderData($order),
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('MobileDelegateCartController: Error submitting order', [
                'cart_id' => $request->cart_id,
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء إرسال الطلب: ' . $e->getMessage(),
                'error_code' => 'SUBMIT_ERROR',
            ], 500);
        }
    }

    /**
     * تنسيق بيانات السلة
     *
     * @param Cart $cart
     * @return array
     */
    private function formatCartData(Cart $cart)
    {
        // التأكد من تحميل العلاقات
        if (!$cart->relationLoaded('items')) {
            $cart->load('items.product.primaryImage', 'items.size');
        }

        // تنسيق عناصر السلة
        $items = $cart->items->map(function($item) {
            return [
                'id' => $item->id,
                'product_id' => $item->product_id,
                'product_name' => $item->product->name ?? null,
                'product_code' => $item->product->code ?? null,
                'size_id' => $item->size_id,
                'size_name' => $item->size->size_name ?? null,
                'quantity' => (int) $item->quantity,
                'price' => (float) $item->price,
                'subtotal' => (float) $item->subtotal,
                'product' => $item->product ? [
                    'id' => $item->product->id,
                    'name' => $item->product->name,
                    'code' => $item->product->code,
                    'primary_image' => $item->product->primaryImage ? $item->product->primaryImage->image_url : null,
                ] : null,
            ];
        });

        return [
            'id' => $cart->id,
            'customer_name' => $cart->customer_name,
            'customer_phone' => $cart->customer_phone,
            'customer_phone2' => $cart->customer_phone2,
            'customer_address' => $cart->customer_address,
            'customer_social_link' => $cart->customer_social_link,
            'notes' => $cart->notes,
            'status' => $cart->status,
            'total_amount' => (float) $cart->total_amount,
            'items_count' => $cart->items->count(),
            'items' => $items,
            'created_at' => $cart->created_at->toIso8601String(),
            'expires_at' => $cart->expires_at ? $cart->expires_at->toIso8601String() : null,
        ];
    }

    /**
     * تنسيق بيانات الطلب (نفس formatOrderData من MobileDelegateOrderController)
     *
     * @param Order $order
     * @return array
     */
    private function formatOrderData(Order $order)
    {
        // التأكد من تحميل العلاقات
        if (!$order->relationLoaded('items')) {
            $order->load('items.product.primaryImage', 'items.size');
        }

        // تنسيق عناصر الطلب
        $items = $order->items->map(function($item) {
            return [
                'id' => $item->id,
                'product_id' => $item->product_id,
                'product_name' => $item->product_name,
                'product_code' => $item->product_code,
                'size_id' => $item->size_id,
                'size_name' => $item->size_name,
                'quantity' => (int) $item->quantity,
                'unit_price' => (float) $item->unit_price,
                'subtotal' => (float) $item->subtotal,
                'product' => $item->product ? [
                    'id' => $item->product->id,
                    'name' => $item->product->name,
                    'code' => $item->product->code,
                    'primary_image' => $item->product->primaryImage ? $item->product->primaryImage->image_url : null,
                ] : null,
            ];
        });

        // تنسيق معلومات الشحنة
        $alwaseetShipment = null;
        if ($order->alwaseetShipment) {
            $shipment = $order->alwaseetShipment;
            $alwaseetShipment = [
                'id' => $shipment->id,
                'alwaseet_order_id' => $shipment->alwaseet_order_id,
                'client_name' => $shipment->client_name,
                'client_mobile' => $shipment->client_mobile,
                'client_mobile2' => $shipment->client_mobile2,
                'city_id' => $shipment->city_id,
                'city_name' => $shipment->city_name,
                'region_id' => $shipment->region_id,
                'region_name' => $shipment->region_name,
                'location' => $shipment->location,
                'price' => (float) $shipment->price,
                'delivery_price' => (float) $shipment->delivery_price,
                'package_size' => $shipment->package_size,
                'type_name' => $shipment->type_name,
                'status_id' => $shipment->status_id,
                'status' => $shipment->status,
                'items_number' => $shipment->items_number,
                'merchant_notes' => $shipment->merchant_notes,
                'issue_notes' => $shipment->issue_notes,
                'replacement' => (bool) $shipment->replacement,
                'qr_id' => $shipment->qr_id,
                'qr_link' => $shipment->qr_link,
                'alwaseet_created_at' => $shipment->alwaseet_created_at ? $shipment->alwaseet_created_at->toIso8601String() : null,
                'alwaseet_updated_at' => $shipment->alwaseet_updated_at ? $shipment->alwaseet_updated_at->toIso8601String() : null,
                'synced_at' => $shipment->synced_at ? $shipment->synced_at->toIso8601String() : null,
            ];
        }

        return [
            'id' => $order->id,
            'order_number' => $order->order_number,
            'customer_name' => $order->customer_name,
            'customer_phone' => $order->customer_phone,
            'customer_phone2' => $order->customer_phone2,
            'customer_address' => $order->customer_address,
            'customer_social_link' => $order->customer_social_link,
            'notes' => $order->notes,
            'status' => $order->status,
            'total_amount' => (float) $order->total_amount,
            'delivery_code' => $order->delivery_code,
            'items' => $items,
            'alwaseet_shipment' => $alwaseetShipment,
            'created_at' => $order->created_at->toIso8601String(),
            'confirmed_at' => $order->confirmed_at ? $order->confirmed_at->toIso8601String() : null,
        ];
    }

    /**
     * تنسيق رقم الهاتف إلى صيغة موحدة
     *
     * @param string $phone
     * @return string|null
     */
    private function normalizePhoneNumber($phone)
    {
        // إزالة كل شيء غير الأرقام
        $cleaned = preg_replace('/[^0-9]/', '', $phone);

        // إزالة البادئات الدولية
        if (strpos($cleaned, '00964') === 0) {
            $cleaned = substr($cleaned, 5);
        } elseif (strpos($cleaned, '964') === 0) {
            $cleaned = substr($cleaned, 3);
        }

        // إضافة 0 في البداية إذا لم تكن موجودة
        if (!empty($cleaned) && !str_starts_with($cleaned, '0')) {
            $cleaned = '0' . $cleaned;
        }

        // التأكد من 11 رقم فقط - إذا كان أكثر من 11، نأخذ أول 11 رقم
        if (strlen($cleaned) > 11) {
            $cleaned = substr($cleaned, 0, 11);
        }

        // إذا كان أقل من 11 رقم، نرفضه
        if (strlen($cleaned) < 11) {
            return null;
        }

        return $cleaned;
    }
}

