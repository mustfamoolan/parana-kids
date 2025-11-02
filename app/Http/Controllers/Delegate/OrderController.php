<?php

namespace App\Http\Controllers\Delegate;

use App\Http\Controllers\Controller;
use App\Models\ArchivedOrder;
use App\Models\Cart;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
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
        // 1. تحديد نوع الطلبات المطلوبة بناءً على status
        if ($request->status === 'deleted') {
            // عرض فقط الطلبات المحذوفة التي حذفها المجهز (لها deleted_by و deletion_reason)
            $query = Order::onlyTrashed()
                ->where('delegate_id', auth()->id())
                ->whereNotNull('deleted_by')
                ->whereNotNull('deletion_reason')
                ->with(['items', 'deletedByUser']);

            // تطبيق البحث في الطلبات المحذوفة
            if ($request->filled('search')) {
                $searchTerm = $request->search;
                $query->where(function($q) use ($searchTerm) {
                    $q->where('order_number', 'like', "%{$searchTerm}%")
                      ->orWhere('customer_name', 'like', "%{$searchTerm}%")
                      ->orWhere('customer_phone', 'like', "%{$searchTerm}%")
                      ->orWhere('customer_social_link', 'like', "%{$searchTerm}%")
                      ->orWhere('customer_address', 'like', "%{$searchTerm}%")
                      ->orWhere('notes', 'like', "%{$searchTerm}%")
                      ->orWhere('deletion_reason', 'like', "%{$searchTerm}%")
                      ->orWhereHas('items', function($itemQuery) use ($searchTerm) {
                          $itemQuery->where('product_name', 'like', "%{$searchTerm}%")
                                   ->orWhere('product_code', 'like', "%{$searchTerm}%")
                                   ->orWhere('size_name', 'like', "%{$searchTerm}%");
                      });
                });
            }

            // فلتر حسب التاريخ (للطلبات المحذوفة)
            if ($request->filled('date_from')) {
                $query->whereDate('deleted_at', '>=', $request->date_from);
            }

            if ($request->filled('date_to')) {
                $query->whereDate('deleted_at', '<=', $request->date_to);
            }

            // فلتر حسب الوقت (للطلبات المحذوفة)
            if ($request->filled('time_from')) {
                $dateFrom = $request->date_from ?? now()->format('Y-m-d');
                $query->where('deleted_at', '>=', $dateFrom . ' ' . $request->time_from . ':00');
            }

            if ($request->filled('time_to')) {
                $dateTo = $request->date_to ?? now()->format('Y-m-d');
                $query->where('deleted_at', '<=', $dateTo . ' ' . $request->time_to . ':00');
            }

            $perPage = $request->input('per_page', 15);
            $orders = $query->latest('deleted_at')->paginate($perPage)->appends($request->except('page'));

            return view('delegate.orders.index', compact('orders'));
        }
        // الطلبات العادية (pending/confirmed) فقط (بدون الأرشفة)
        else {
            // إذا لم يكن هناك فلتر status، نجلب كل الطلبات النشطة فقط
            if (!$request->filled('status')) {
                // 1. جلب الطلبات النشطة
                $activeOrders = Order::where('delegate_id', auth()->id())
                    ->with(['items']);

                // تطبيق البحث على الطلبات النشطة
                if ($request->filled('search')) {
                    $searchTerm = $request->search;
                    $activeOrders->where(function($q) use ($searchTerm) {
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

                // تطبيق فلاتر التاريخ على الطلبات النشطة
                if ($request->filled('date_from')) {
                    $activeOrders->whereDate('created_at', '>=', $request->date_from);
                }
                if ($request->filled('date_to')) {
                    $activeOrders->whereDate('created_at', '<=', $request->date_to);
                }
                if ($request->filled('time_from')) {
                    $dateFrom = $request->date_from ?? now()->format('Y-m-d');
                    $activeOrders->where('created_at', '>=', $dateFrom . ' ' . $request->time_from . ':00');
                }
                if ($request->filled('time_to')) {
                    $dateTo = $request->date_to ?? now()->format('Y-m-d');
                    $activeOrders->where('created_at', '<=', $dateTo . ' ' . $request->time_to . ':00');
                }

                $activeOrdersList = $activeOrders->get();

                // 2. دمج الطلبات وترتيبها (فقط النشطة)
                $allOrders = $activeOrdersList
                    ->sortByDesc(function($order) {
                        return $order->created_at;
                    });

                // 5. تطبيق pagination يدوياً
                $currentPage = $request->get('page', 1);
                $perPage = $request->input('per_page', 15);
                $orders = new \Illuminate\Pagination\LengthAwarePaginator(
                    $allOrders->forPage($currentPage, $perPage),
                    $allOrders->count(),
                    $perPage,
                    $currentPage,
                    ['path' => $request->url(), 'query' => $request->query()]
                );

                return view('delegate.orders.index', compact('orders'));
            }

            // إذا كان هناك فلتر status (pending/confirmed)
            $query = Order::where('delegate_id', auth()->id())->with(['items']);

            // تطبيق فلتر الحالة (pending/confirmed)
            if ($request->filled('status') && in_array($request->status, ['pending', 'confirmed'])) {
                $query->where('status', $request->status);
            }

            // تطبيق البحث النصي
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

            // فلاتر التاريخ والوقت
            if ($request->filled('date_from')) {
                $query->whereDate('created_at', '>=', $request->date_from);
            }

            if ($request->filled('date_to')) {
                $query->whereDate('created_at', '<=', $request->date_to);
            }

            if ($request->filled('time_from')) {
                $dateFrom = $request->date_from ?? now()->format('Y-m-d');
                $query->where('created_at', '>=', $dateFrom . ' ' . $request->time_from . ':00');
            }

            if ($request->filled('time_to')) {
                $dateTo = $request->date_to ?? now()->format('Y-m-d');
                $query->where('created_at', '<=', $dateTo . ' ' . $request->time_to . ':00');
            }

            $perPage = $request->input('per_page', 15);
            $orders = $query->latest()->paginate($perPage)->appends($request->except('page'));

            return view('delegate.orders.index', compact('orders'));
        }
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
        // resolveRouteBinding في Order model يستخدم withTrashed() تلقائياً
        if ($order->delegate_id !== auth()->id()) {
            abort(403);
        }

        $order->load(['items.product', 'cart', 'deletedByUser']);

        return view('delegate.orders.show', compact('order'));
    }

    /**
     * Show the form for editing the order.
     */
    public function edit(Order $order)
    {
        // التحقق من ملكية الطلب
        if ($order->delegate_id !== auth()->id()) {
            abort(403);
        }

        // التحقق من أن الطلب pending فقط
        if ($order->status !== 'pending') {
            return back()->withErrors(['error' => 'يمكن تعديل الطلبات غير المقيدة فقط']);
        }

        $order->load([
            'delegate',
            'items.product.primaryImage',
            'items.product.warehouse',
            'items.size',
            'cart'
        ]);

        // تحميل جميع المنتجات للبحث والإضافة
        $products = Product::with(['sizes', 'primaryImage'])->get();

        return view('delegate.orders.edit', compact('order', 'products'));
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
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.size_id' => 'required|exists:product_sizes,id',
            'items.*.quantity' => 'required|integer|min:1',
        ]);

        try {
            DB::transaction(function() use ($request, $order) {
                // تحميل العناصر القديمة
                $oldItems = $order->items()->get();

                // إرجاع المنتجات القديمة للمخزون
                foreach ($oldItems as $oldItem) {
                    if ($oldItem->size) {
                        $oldItem->size->increment('quantity', $oldItem->quantity);
                    }
                }

                // حذف المنتجات القديمة
                $order->items()->delete();

                // تحديث معلومات الزبون
                $order->update($request->only([
                    'customer_name',
                    'customer_phone',
                    'customer_address',
                    'customer_social_link',
                    'notes',
                ]));

                // إضافة المنتجات الجديدة
                $totalAmount = 0;
                foreach ($request->items as $item) {
                    $product = Product::findOrFail($item['product_id']);
                    $size = ProductSize::findOrFail($item['size_id']);

                    // التحقق من توفر الكمية
                    if ($size->quantity < $item['quantity']) {
                        throw new \Exception("الكمية المتوفرة من {$product->name} - {$size->size_name} غير كافية. المتوفر: {$size->quantity}");
                    }

                    $subtotal = $product->selling_price * $item['quantity'];
                    $totalAmount += $subtotal;

                    $order->items()->create([
                        'product_id' => $item['product_id'],
                        'size_id' => $item['size_id'],
                        'product_code' => $product->code,
                        'product_name' => $product->name,
                        'size_name' => $size->size_name,
                        'quantity' => $item['quantity'],
                        'unit_price' => $product->selling_price,
                        'subtotal' => $subtotal,
                    ]);

                    // خصم من المخزون
                    $size->decrement('quantity', $item['quantity']);
                }

                // تحديث المبلغ الإجمالي
                $order->update(['total_amount' => $totalAmount]);
            });

            return redirect()->route('delegate.orders.show', $order)
                            ->with('success', 'تم تحديث الطلب بنجاح');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'حدث خطأ أثناء تحديث الطلب: ' . $e->getMessage()])->withInput();
        }
    }

    /**
     * Cancel the specified order (old system).
     */
    public function cancelOld(Order $order)
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
                        ProductMovement::record([
                            'product_id' => $item->product_id,
                            'size_id' => $item->size_id,
                            'warehouse_id' => $item->product->warehouse_id,
                            'movement_type' => 'delete',
                            'quantity' => $item->quantity,
                            'balance_after' => $item->size->quantity,
                            'order_status' => $order->status,
                            'notes' => "حذف طلب #{$order->order_number}"
                        ]);
                    }
                }

                // تسجيل من قام بالحذف
                $order->deleted_by = auth()->id();
                $order->save();

                // soft delete للطلب
                $order->delete();
            });

            return redirect()->route('delegate.orders.index')
                            ->with('success', 'تم حذف الطلب بنجاح وإرجاع جميع المنتجات للمخزن');
        } catch (\Exception $e) {
            return redirect()->route('delegate.orders.index')
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

        // فلتر حسب الوقت
        if ($request->filled('time_from')) {
            $dateFrom = $request->date_from ?? now()->format('Y-m-d');
            $query->where('deleted_at', '>=', $dateFrom . ' ' . $request->time_from . ':00');
        }

        if ($request->filled('time_to')) {
            $dateTo = $request->date_to ?? now()->format('Y-m-d');
            $query->where('deleted_at', '<=', $dateTo . ' ' . $request->time_to . ':00');
        }

        $perPage = $request->input('per_page', 15);
        $orders = $query->with(['items.product.primaryImage'])
                       ->latest('deleted_at')
                       ->paginate($perPage)
                       ->appends($request->except('page'));

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
                        ProductMovement::record([
                            'product_id' => $item->product_id,
                            'size_id' => $item->size_id,
                            'warehouse_id' => $item->product->warehouse_id,
                            'movement_type' => 'restore',
                            'quantity' => -$item->quantity,
                            'balance_after' => $item->size->quantity,
                            'order_status' => $order->status,
                            'notes' => "استرجاع من حذف طلب #{$order->order_number}"
                        ]);
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

    /**
     * Start a new order - show customer info form
     */
    public function start()
    {
        return view('delegate.orders.start');
    }

    /**
     * Initialize new order with customer info
     */
    public function initialize(Request $request)
    {
        $request->validate([
            'customer_name' => 'required|string|max:255',
            'customer_phone' => 'required|string|max:20',
            'customer_address' => 'required|string',
            'customer_social_link' => 'required|string|max:255',
            'notes' => 'nullable|string',
        ]);

        // حذف أي سلة نشطة قديمة للمندوب (لتجنب التكرار)
        Cart::where('delegate_id', auth()->id())
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

        // إنشاء سلة جديدة
        $cart = Cart::create([
            'delegate_id' => auth()->id(),
            'cart_name' => 'طلب: ' . $request->customer_name,
            'status' => 'active',
            'expires_at' => now()->addHours(24),
        ]);

        // حفظ معلومات الزبون في session
        session([
            'current_cart_id' => $cart->id,
            'customer_data' => $request->only([
                'customer_name',
                'customer_phone',
                'customer_address',
                'customer_social_link',
                'notes'
            ])
        ]);

        // التوجيه لصفحة المنتجات
        return redirect()->route('delegate.products.all')
                        ->with('success', 'تم بدء الطلب! الآن اختر المنتجات');
    }

    /**
     * Submit the current order
     */
    public function submit()
    {
        $cartId = session('current_cart_id');
        $customerData = session('customer_data');

        if (!$cartId || !$customerData) {
            return redirect()->route('delegate.orders.start')
                           ->with('error', 'لا يوجد طلب نشط');
        }

        $cart = Cart::with('items.product', 'items.size')->findOrFail($cartId);

        // التأكد من أن السلة تخص المندوب الحالي
        if ($cart->delegate_id !== auth()->id()) {
            abort(403);
        }

        if ($cart->items->count() === 0) {
            return back()->withErrors(['cart' => 'أضف منتجات أولاً']);
        }

        // إنشاء الطلب
        $order = DB::transaction(function() use ($cart, $customerData) {
            $order = Order::create([
                'cart_id' => $cart->id,
                'delegate_id' => $cart->delegate_id,
                'customer_name' => $customerData['customer_name'],
                'customer_phone' => $customerData['customer_phone'],
                'customer_address' => $customerData['customer_address'],
                'customer_social_link' => $customerData['customer_social_link'],
                'notes' => $customerData['notes'] ?? null,
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

            // تحديث حالة السلة
            $cart->update(['status' => 'completed']);

            return $order;
        });

        // مسح session
        session()->forget(['current_cart_id', 'customer_data']);

        return redirect()->route('delegate.dashboard')
                        ->with('success', 'تم إرسال الطلب بنجاح! رقم الطلب: ' . $order->order_number);
    }

    /**
     * Cancel the current order
     */
    public function cancel()
    {
        $cartId = session('current_cart_id');

        if ($cartId) {
            $cart = Cart::with('items.stockReservation')->find($cartId);
            if ($cart && $cart->delegate_id === auth()->id()) {
                // إرجاع الحجوزات
                foreach ($cart->items as $item) {
                    if ($item->stockReservation) {
                        $item->stockReservation->delete();
                    }
                }
                $cart->delete();
            }
        }

        session()->forget(['current_cart_id', 'customer_data']);

        return redirect()->route('delegate.dashboard')
                        ->with('info', 'تم إلغاء الطلب');
    }

    /**
     * الحذف النهائي للطلب (بدون إرجاع للمخزن لأنه محذوف أساساً)
     */
    public function forceDelete($id)
    {
        try {
            $order = Order::withTrashed()->findOrFail($id);

            // التأكد من أن الطلب يخص المندوب الحالي
            if ($order->delegate_id !== auth()->id()) {
                abort(403);
            }

            // التأكد من أن الطلب محذوف (soft deleted)
            if (!$order->trashed()) {
                return redirect()->back()
                                ->withErrors(['error' => 'يمكن الحذف النهائي فقط للطلبات المحذوفة']);
            }

            DB::transaction(function () use ($order) {
                // حذف عناصر الطلب نهائياً
                $order->items()->forceDelete();

                // حذف الطلب نهائياً
                $order->forceDelete();
            });

            return redirect()->route('delegate.orders.index', ['status' => 'deleted'])
                            ->with('success', 'تم الحذف النهائي للطلب بنجاح');
        } catch (\Exception $e) {
            return redirect()->back()
                            ->withErrors(['error' => 'حدث خطأ أثناء الحذف النهائي: ' . $e->getMessage()]);
        }
    }
}
