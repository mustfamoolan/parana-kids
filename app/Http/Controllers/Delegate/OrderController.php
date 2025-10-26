<?php

namespace App\Http\Controllers\Delegate;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\ProductSize;
use App\Models\ProductMovement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Order::where('delegate_id', auth()->id())->with(['items']);

        // البحث في جميع الحقول المطلوبة
        if ($request->filled('search')) {
            $searchTerm = $request->search;
            $query->where(function($q) use ($searchTerm) {
                $q->where('order_number', 'like', "%{$searchTerm}%")
                  ->orWhere('customer_name', 'like', "%{$searchTerm}%")
                  ->orWhere('customer_phone', 'like', "%{$searchTerm}%")
                  ->orWhere('customer_social_link', 'like', "%{$searchTerm}%")
                  ->orWhere('customer_address', 'like', "%{$searchTerm}%")
                  ->orWhere('notes', 'like', "%{$searchTerm}%")
                  ->orWhereHas('items', function($itemQuery) use ($searchTerm) {
                      $itemQuery->where('product_name', 'like', "%{$searchTerm}%")
                               ->orWhere('product_code', 'like', "%{$searchTerm}%")
                               ->orWhere('size_name', 'like', "%{$searchTerm}%");
                  });
            });
        }

        // فلتر حسب الحالة
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // فلتر حسب التاريخ
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $orders = $query->latest()->paginate(10);

        return view('delegate.orders.index', compact('orders'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Cart $cart)
    {
        // التأكد من أن السلة تخص المندوب الحالي
        if ($cart->delegate_id !== auth()->id()) {
            abort(403);
        }

        // التأكد من أن السلة تحتوي على منتجات
        if ($cart->items->count() === 0) {
            return redirect()->route('delegate.carts.show', $cart)
                            ->withErrors(['cart' => 'لا يمكن إتمام الطلب من سلة فارغة']);
        }

        $cart->load(['items.product.primaryImage', 'items.size']);

        return view('delegate.orders.create', compact('cart'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'cart_id' => 'required|exists:carts,id',
            'customer_name' => 'required|string|max:255',
            'customer_phone' => 'required|string|max:20',
            'customer_address' => 'required|string',
            'customer_social_link' => 'required|string|max:255',
            'notes' => 'nullable|string',
        ]);

        $cart = Cart::findOrFail($request->cart_id);

        // التأكد من أن السلة تخص المندوب الحالي
        if ($cart->delegate_id !== auth()->id()) {
            abort(403);
        }

        // التأكد من أن السلة تحتوي على منتجات
        if ($cart->items->count() === 0) {
            return back()->withErrors(['cart' => 'لا يمكن إتمام الطلب من سلة فارغة']);
        }

        $order = DB::transaction(function() use ($cart, $request) {
            // إنشاء الطلب
            $order = Order::create([
                'cart_id' => $cart->id,
                'delegate_id' => $cart->delegate_id,
                'customer_name' => $request->customer_name,
                'customer_phone' => $request->customer_phone,
                'customer_address' => $request->customer_address,
                'customer_social_link' => $request->customer_social_link,
                'notes' => $request->notes,
                'status' => 'pending', // غير مقيد
                'total_amount' => $cart->total_amount,
            ]);

            // نسخ منتجات السلة إلى الطلب
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

                // حذف الحجز
                $cartItem->stockReservation()->delete();
            }

            // تحديث حالة السلة
            $cart->update(['status' => 'completed']);

            return $order;
        });

        return redirect()->route('delegate.orders.show', $order)
                        ->with('success', 'تم إرسال الطلب بنجاح! رقم الطلب: ' . $order->order_number);
    }

    /**
     * Display the specified resource.
     */
    public function show(Order $order)
    {
        // التأكد من أن الطلب يخص المندوب الحالي
        if ($order->delegate_id !== auth()->id()) {
            abort(403);
        }

        $order->load(['items.product', 'cart']);

        return view('delegate.orders.show', compact('order'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Order $order)
    {
        // التأكد من أن الطلب يخص المندوب الحالي
        if ($order->delegate_id !== auth()->id()) {
            abort(403);
        }

        // التحقق من أن الطلب غير مقيد
        if ($order->status !== 'pending') {
            return redirect()->route('delegate.orders.show', $order)
                            ->withErrors(['order' => 'لا يمكن تعديل الطلبات المقيدة']);
        }

        // تحويل الطلب إلى سلة مؤقتة للتعديل
        $cart = Cart::create([
            'delegate_id' => auth()->id(),
            'cart_name' => 'تعديل الطلب ' . $order->order_number,
            'status' => 'active',
            'expires_at' => now()->addHour(),
        ]);

        // نسخ منتجات الطلب إلى السلة المؤقتة
        foreach ($order->items as $orderItem) {
            // البحث عن القياس المناسب للمنتج
            $size = null;
            if ($orderItem->size_id) {
                $size = ProductSize::find($orderItem->size_id);
            } else {
                // إذا لم يكن هناك size_id، ابحث عن القياس بالاسم
                $size = ProductSize::where('product_id', $orderItem->product_id)
                                  ->where('size_name', $orderItem->size_name)
                                  ->first();
            }

            if ($size) {
                $cart->items()->create([
                    'product_id' => $orderItem->product_id,
                    'size_id' => $size->id,
                    'quantity' => $orderItem->quantity,
                    'price' => $orderItem->unit_price,
                ]);
            }
        }

        return redirect()->route('delegate.carts.show', $cart)
                        ->with('success', 'تم تحويل الطلب إلى سلة للتعديل');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Order $order)
    {
        // التأكد من أن الطلب يخص المندوب الحالي
        if ($order->delegate_id !== auth()->id()) {
            abort(403);
        }

        // التحقق من أن الطلب غير مقيد
        if ($order->status !== 'pending') {
            return redirect()->route('delegate.orders.show', $order)
                            ->withErrors(['order' => 'لا يمكن تعديل الطلبات المقيدة']);
        }

        $request->validate([
            'customer_name' => 'required|string|max:255',
            'customer_phone' => 'required|string|max:20',
            'customer_address' => 'required|string',
            'customer_social_link' => 'required|string|max:255',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.id' => 'required|exists:order_items,id',
            'items.*.quantity' => 'required|integer|min:1',
        ]);

        DB::transaction(function() use ($order, $request) {
            // تحديث معلومات الزبون
            $order->update([
                'customer_name' => $request->customer_name,
                'customer_phone' => $request->customer_phone,
                'customer_address' => $request->customer_address,
                'customer_social_link' => $request->customer_social_link,
                'notes' => $request->notes,
            ]);

            $totalAmount = 0;

            // تحديث منتجات الطلب
            foreach ($request->items as $itemData) {
                $orderItem = OrderItem::findOrFail($itemData['id']);

                // التحقق من أن العنصر يخص هذا الطلب
                if ($orderItem->order_id !== $order->id) {
                    continue;
                }

                $oldQuantity = $orderItem->quantity;
                $newQuantity = $itemData['quantity'];

                // تحديث الكمية والمجموع
                $orderItem->update([
                    'quantity' => $newQuantity,
                    'subtotal' => $newQuantity * $orderItem->unit_price,
                ]);

                // تحديث المخزون إذا تغيرت الكمية
                if ($oldQuantity !== $newQuantity) {
                    $size = ProductSize::find($orderItem->size_id);
                    if ($size) {
                        $difference = $newQuantity - $oldQuantity;
                        if ($difference > 0) {
                            // زيادة الكمية - خصم من المخزون
                            $size->decrement('quantity', $difference);
                        } else {
                            // تقليل الكمية - إرجاع للمخزون
                            $size->increment('quantity', abs($difference));
                        }
                    }
                }

                $totalAmount += $orderItem->subtotal;
            }

            // تحديث إجمالي الطلب
            $order->update(['total_amount' => $totalAmount]);
        });

        return redirect()->route('delegate.orders.show', $order)
                        ->with('success', 'تم تحديث الطلب بنجاح');
    }

    /**
     * Cancel the specified order.
     */
    public function cancel(Order $order)
    {
        // التأكد من أن الطلب يخص المندوب الحالي
        if ($order->delegate_id !== auth()->id()) {
            abort(403);
        }

        // التحقق من أن الطلب غير مقيد
        if ($order->status !== 'pending') {
            return redirect()->route('delegate.orders.show', $order)
                            ->withErrors(['order' => 'لا يمكن إلغاء الطلبات المقيدة']);
        }

        DB::transaction(function() use ($order) {
            // إرجاع جميع منتجات الطلب للمخزون
            foreach ($order->items as $item) {
                if ($item->product && $item->size) {
                    $item->size->increment('quantity', $item->quantity);
                }
            }

            // حذف عناصر الطلب
            $order->items()->delete();

            // حذف السلة المرتبطة إذا كانت موجودة
            if ($order->cart) {
                $order->cart->delete();
            }

            // حذف الطلب
            $order->delete();
        });

        return redirect()->route('delegate.orders.index')
                        ->with('success', 'تم إلغاء الطلب وإرجاع المنتجات للمخزن بنجاح');
    }

    /**
     * حذف الطلب (soft delete) مع إرجاع المنتجات للمخزن
     */
    public function destroy(Order $order)
    {
        // التأكد من أن الطلب يخص المندوب الحالي
        if ($order->delegate_id !== auth()->id()) {
            abort(403);
        }

        // التحقق من أن الطلب يمكن حذفه (pending أو confirmed)
        if (!in_array($order->status, ['pending', 'confirmed'])) {
            return redirect()->back()
                            ->withErrors(['error' => 'لا يمكن حذف هذا الطلب']);
        }

        try {
            DB::transaction(function() use ($order) {
                // تحميل العلاقات المطلوبة
                $order->load('items.size');

                // إرجاع جميع المنتجات للمخزن
                foreach ($order->items as $item) {
                    if ($item->size) {
                        $item->size->increment('quantity', $item->quantity);

                        // تسجيل حركة الحذف
                        ProductMovement::record(
                            $item->size,
                            'delete',
                            $item->quantity,
                            $order,
                            "حذف طلب #{$order->order_number}"
                        );
                    }
                }

                // تسجيل من قام بالحذف
                $order->deleted_by = auth()->id();
                $order->save();

                // soft delete للطلب
                $order->delete();
            });

            return redirect()->back()
                            ->with('success', 'تم حذف الطلب بنجاح وإرجاع جميع المنتجات للمخزن');
        } catch (\Exception $e) {
            return redirect()->back()
                            ->withErrors(['error' => 'حدث خطأ أثناء حذف الطلب: ' . $e->getMessage()]);
        }
    }

    /**
     * عرض الطلبات المحذوفة للمندوب
     */
    public function deleted(Request $request)
    {
        $query = Order::onlyTrashed()->where('delegate_id', auth()->id());

        // البحث في الطلبات
        if ($request->filled('search')) {
            $searchTerm = $request->search;
            $query->where(function($q) use ($searchTerm) {
                $q->where('order_number', 'like', "%{$searchTerm}%")
                  ->orWhere('customer_name', 'like', "%{$searchTerm}%")
                  ->orWhere('customer_phone', 'like', "%{$searchTerm}%")
                  ->orWhere('customer_social_link', 'like', "%{$searchTerm}%");
            });
        }

        // فلتر حسب التاريخ
        if ($request->filled('date_from')) {
            $query->whereDate('deleted_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('deleted_at', '<=', $request->date_to);
        }

        $orders = $query->with(['items.product.primaryImage'])
                       ->latest('deleted_at')
                       ->paginate(15);

        return view('delegate.orders.deleted', compact('orders'));
    }

    /**
     * استرجاع الطلب مع خصم المنتجات من المخزن
     */
    public function restore(Order $order)
    {
        // التأكد من أن الطلب يخص المندوب الحالي
        if ($order->delegate_id !== auth()->id()) {
            abort(403);
        }

        try {
            // التحقق من التوفر أولاً
            $order->load('items.size');
            $allAvailable = true;

            foreach ($order->items as $item) {
                $available = $item->size ? $item->size->quantity : 0;
                if ($available < $item->quantity) {
                    $allAvailable = false;
                    break;
                }
            }

            if (!$allAvailable) {
                // جمع تفاصيل النواقص
                $shortages = [];
                foreach ($order->items as $item) {
                    $available = $item->size ? $item->size->quantity : 0;
                    if ($available < $item->quantity) {
                        $shortages[] = "{$item->product->name} ({$item->size->size}): المطلوب {$item->quantity}، المتوفر {$available}";
                    }
                }

                $errorMessage = 'لا يمكن استرجاع الطلب - المنتجات التالية غير متوفرة بالكمية المطلوبة: ' . implode(' | ', $shortages);

                return redirect()->back()
                                ->withErrors(['error' => $errorMessage]);
            }

            DB::transaction(function() use ($order) {
                // خصم المنتجات من المخزن
                foreach ($order->items as $item) {
                    if ($item->size) {
                        $item->size->decrement('quantity', $item->quantity);

                        // تسجيل حركة الاسترجاع من الحذف
                        ProductMovement::record(
                            $item->size,
                            'restore',
                            -$item->quantity,
                            $order,
                            "استرجاع من حذف طلب #{$order->order_number}"
                        );
                    }
                }

                // استرجاع الطلب
                $order->restore();
                $order->status = 'pending';
                $order->deleted_by = null;
                $order->save();
            });

            return redirect()->route('delegate.orders.index')
                            ->with('success', 'تم استرجاع الطلب بنجاح وخصم المنتجات من المخزن');
        } catch (\Exception $e) {
            return redirect()->back()
                            ->withErrors(['error' => 'حدث خطأ أثناء استرجاع الطلب: ' . $e->getMessage()]);
        }
    }
}
