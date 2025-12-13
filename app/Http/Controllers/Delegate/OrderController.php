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
use App\Services\SweetAlertService;
use App\Services\AlWaseetService;
use App\Models\AlWaseetOrderStatus;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class OrderController extends Controller
{
    protected $sweetAlertService;
    protected $alWaseetService;

    public function __construct(SweetAlertService $sweetAlertService, AlWaseetService $alWaseetService)
    {
        $this->sweetAlertService = $sweetAlertService;
        $this->alWaseetService = $alWaseetService;
    }

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
                $searchTerm = trim($request->search);
                if (!empty($searchTerm)) {
                    // تنظيف رقم الهاتف للبحث إذا كان البحث برقم هاتف
                    $normalizedSearchTerm = $this->normalizePhoneNumber($searchTerm);
                    $phoneSearchTerm = $normalizedSearchTerm ?: $searchTerm;

                    // تطبيق البحث الدقيق على الطلبات المحذوفة
                    $this->applyExactSearch($query, $searchTerm, $phoneSearchTerm, true);
                }
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
        // الطلبات العادية (pending/confirmed) والمحذوفة
        else {
            // إذا لم يكن هناك فلتر status، نجلب كل الطلبات (النشطة والمحذوفة)
            if (!$request->filled('status')) {
                $query = Order::withTrashed()
                    ->where('delegate_id', auth()->id())
                    ->with(['items']);

                // تنظيف رقم الهاتف للبحث إذا كان البحث برقم هاتف
                $searchTerm = $request->filled('search') ? trim($request->search) : null;
                $phoneSearchTerm = null;
                if ($searchTerm && !empty($searchTerm)) {
                    $normalizedSearchTerm = $this->normalizePhoneNumber($searchTerm);
                    $phoneSearchTerm = $normalizedSearchTerm ?: $searchTerm;
                }

                // فلتر الحالة: نشطة (pending/confirmed) أو محذوفة (soft deleted)
                // مع تطبيق البحث بشكل صحيح
                $query->where(function($q) use ($searchTerm, $phoneSearchTerm) {
                    // الطلبات النشطة (pending أو confirmed) - غير محذوفة
                    $q->where(function($subQ) use ($searchTerm, $phoneSearchTerm) {
                        $subQ->whereNull('deleted_at')
                             ->whereIn('status', ['pending', 'confirmed']);

                        // تطبيق البحث على الطلبات النشطة
                        if ($searchTerm && !empty($searchTerm)) {
                            $this->applyExactSearch($subQ, $searchTerm, $phoneSearchTerm);
                        }
                    })->orWhere(function($subQ) use ($searchTerm, $phoneSearchTerm) {
                        // الطلبات المحذوفة التي حذفها المجهز/المدير (soft deleted)
                        $subQ->whereNotNull('deleted_at')
                             ->whereNotNull('deleted_by')
                             ->whereNotNull('deletion_reason');

                        // تطبيق البحث على الطلبات المحذوفة
                        if ($searchTerm && !empty($searchTerm)) {
                            $this->applyExactSearch($subQ, $searchTerm, $phoneSearchTerm, true);
                        }
                    });
                });

                // تطبيق فلاتر التاريخ
                if ($request->filled('date_from')) {
                    $query->where(function($q) use ($request) {
                        $q->where(function($subQ) use ($request) {
                            $subQ->whereNull('deleted_at')
                                 ->whereDate('created_at', '>=', $request->date_from);
                        })->orWhere(function($subQ) use ($request) {
                            $subQ->whereNotNull('deleted_at')
                                 ->whereDate('deleted_at', '>=', $request->date_from);
                        });
                    });
                }
                if ($request->filled('date_to')) {
                    $query->where(function($q) use ($request) {
                        $q->where(function($subQ) use ($request) {
                            $subQ->whereNull('deleted_at')
                                 ->whereDate('created_at', '<=', $request->date_to);
                        })->orWhere(function($subQ) use ($request) {
                            $subQ->whereNotNull('deleted_at')
                                 ->whereDate('deleted_at', '<=', $request->date_to);
                        });
                    });
                }
                if ($request->filled('time_from')) {
                    $dateFrom = $request->date_from ?? now()->format('Y-m-d');
                    $query->where(function($q) use ($dateFrom, $request) {
                        $q->where(function($subQ) use ($dateFrom, $request) {
                            $subQ->whereNull('deleted_at')
                                 ->where('created_at', '>=', $dateFrom . ' ' . $request->time_from . ':00');
                        })->orWhere(function($subQ) use ($dateFrom, $request) {
                            $subQ->whereNotNull('deleted_at')
                                 ->where('deleted_at', '>=', $dateFrom . ' ' . $request->time_from . ':00');
                        });
                    });
                }
                if ($request->filled('time_to')) {
                    $dateTo = $request->date_to ?? now()->format('Y-m-d');
                    $query->where(function($q) use ($dateTo, $request) {
                        $q->where(function($subQ) use ($dateTo, $request) {
                            $subQ->whereNull('deleted_at')
                                 ->where('created_at', '<=', $dateTo . ' ' . $request->time_to . ':00');
                        })->orWhere(function($subQ) use ($dateTo, $request) {
                            $subQ->whereNotNull('deleted_at')
                                 ->where('deleted_at', '<=', $dateTo . ' ' . $request->time_to . ':00');
                        });
                    });
                }

                // إضافة deletedByUser للعلاقات
                $query->with('deletedByUser');

                // ترتيب مختلط: للطلبات المحذوفة deleted_at، للباقي created_at
                $perPage = $request->input('per_page', 15);
                $orders = $query->orderByRaw('CASE WHEN deleted_at IS NOT NULL THEN deleted_at ELSE created_at END DESC')
                               ->paginate($perPage)
                               ->appends($request->except('page'));

                return view('delegate.orders.index', compact('orders'));
            }

            // إذا كان هناك فلتر status (pending/confirmed)
            $query = Order::where('delegate_id', auth()->id())->with(['items']);

            // تطبيق البحث أولاً ثم فلتر الحالة
            // تطبيق البحث النصي الشامل
            if ($request->filled('search')) {
                $searchTerm = trim($request->search);
                if (!empty($searchTerm)) {
                    // تنظيف رقم الهاتف للبحث إذا كان البحث برقم هاتف
                    $normalizedSearchTerm = $this->normalizePhoneNumber($searchTerm);
                    $phoneSearchTerm = $normalizedSearchTerm ?: $searchTerm;

                    // تطبيق البحث الدقيق على الطلبات
                    $this->applyExactSearch($query, $searchTerm, $phoneSearchTerm);
                }
            }

            // تطبيق فلتر الحالة (pending/confirmed) بعد البحث
            if ($request->filled('status') && in_array($request->status, ['pending', 'confirmed'])) {
                $query->where('status', $request->status);
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

        // إرسال Event لإنشاء شحنة في الواسط
        event(new \App\Events\OrderCreated($order));

        // إرسال SweetAlert للمجهز (نفس المخزن) أو المدير
        try {
            $this->sweetAlertService->notifyOrderCreated($order);
        } catch (\Exception $e) {
            \Log::error('Delegate/OrderController: Error sending SweetAlert for order_created: ' . $e->getMessage());
        }

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

        // حذف جميع إشعارات الطلب عند فتحه
        try {
            $this->sweetAlertService->deleteOrderAlerts($order->id, auth()->id());
        } catch (\Exception $e) {
            \Log::error('Delegate/OrderController: Error deleting order alerts: ' . $e->getMessage());
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
            'customer_phone2' => 'nullable|string|max:20',
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
                    'customer_phone2',
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

                    // استخدام effective_price (يشمل التخفيضات النشطة)
                    $unitPrice = $product->effective_price;
                    $subtotal = $unitPrice * $item['quantity'];
                    $totalAmount += $subtotal;

                    $order->items()->create([
                        'product_id' => $item['product_id'],
                        'size_id' => $item['size_id'],
                        'product_code' => $product->code,
                        'product_name' => $product->name,
                        'size_name' => $size->size_name,
                        'quantity' => $item['quantity'],
                        'unit_price' => $unitPrice,
                        'subtotal' => $subtotal,
                    ]);

                    // خصم من المخزون
                    $size->decrement('quantity', $item['quantity']);
                }

                // تحديث المبلغ الإجمالي
                $order->update(['total_amount' => $totalAmount]);
            });

            return redirect()->route('delegate.orders.index')
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

                // إرسال SweetAlert للمجهز (نفس المخزن) أو المدير أو المندوب
                try {
                    $this->sweetAlertService->notifyOrderDeleted($order);
                } catch (\Exception $e) {
                    \Log::error('Delegate/OrderController: Error sending SweetAlert for order_deleted: ' . $e->getMessage());
                }

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
     * تنسيق رقم الهاتف إلى صيغة موحدة
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

    /**
     * Initialize new order with customer info
     */
    public function initialize(Request $request)
    {
        // تنسيق رقم الهاتف قبل التحقق
        $normalizedPhone = $this->normalizePhoneNumber($request->customer_phone);

        if ($normalizedPhone === null || strlen($normalizedPhone) !== 11) {
            return redirect()->back()
                ->withErrors(['customer_phone' => 'رقم الهاتف يجب أن يكون بالضبط 11 رقم بعد التنسيق. مثال: 07742209251'])
                ->withInput();
        }

        // استبدال رقم الهاتف بالتنسيق الموحد
        $request->merge(['customer_phone' => $normalizedPhone]);

        // تنسيق رقم الهاتف الثاني إن وجد
        $normalizedPhone2 = null;
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

        // إنشاء سلة جديدة مع بيانات الزبون
        $cart = Cart::create([
            'delegate_id' => auth()->id(),
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

        // حفظ cart_id فقط في session (رقم صغير لا يسبب مشاكل الكوكيز)
        session(['current_cart_id' => $cart->id]);

        // التوجيه لصفحة المنتجات
        return redirect()->route('delegate.products.all')
                        ->with('success', 'تم بدء الطلب! الآن اختر المنتجات');
    }

    /**
     * Get cart data from localStorage (API endpoint)
     */
    public function getCartData(Request $request)
    {
        // البيانات ستأتي من localStorage عبر JavaScript
        $cartId = $request->input('cart_id');
        $customerData = $request->input('customer_data');

        if (!$cartId || !$customerData) {
            return response()->json([
                'success' => false,
                'message' => 'لا يوجد طلب نشط'
            ], 400);
        }

        return response()->json([
            'success' => true,
            'cart_id' => $cartId,
            'customer_data' => $customerData
        ]);
    }

    /**
     * Submit the current order
     */
    public function submit(Request $request)
    {
        // قراءة cart_id من session
        $cartId = session('current_cart_id');

        if (!$cartId) {
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

        // التحقق من وجود بيانات الزبون في Cart
        if (!$cart->customer_name || !$cart->customer_phone) {
            return redirect()->route('delegate.orders.start')
                           ->with('error', 'بيانات الزبون غير موجودة. يرجى إنشاء طلب جديد');
        }

        // إنشاء الطلب
        $order = DB::transaction(function() use ($cart) {
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

        // إرسال Event لإنشاء شحنة في الواسط
        event(new \App\Events\OrderCreated($order));

        // إرسال SweetAlert للمجهز (نفس المخزن) أو المدير
        try {
            $this->sweetAlertService->notifyOrderCreated($order);
        } catch (\Exception $e) {
            \Log::error('Delegate/OrderController: Error sending SweetAlert for order_created: ' . $e->getMessage());
        }

        // مسح session
        session()->forget('current_cart_id');

        return redirect()->route('delegate.dashboard')
                        ->with('success', 'تم إرسال الطلب بنجاح! رقم الطلب: ' . $order->order_number);
    }

    /**
     * Cancel the current order
     */
    public function cancel(Request $request)
    {
        // قراءة cart_id من session
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

        session()->forget('current_cart_id');

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

    /**
     * تطبيق البحث الدقيق على جميع مكونات الطلب
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $searchTerm
     * @param string|null $phoneSearchTerm
     * @param bool $includeDeletedFields - إذا كان true، يضيف البحث في deletion_reason
     */
    private function applyExactSearch($query, $searchTerm, $phoneSearchTerm = null, $includeDeletedFields = false)
    {
        // البحث في جميع الحقول (مطابقة دقيقة)
        $query->where(function($q) use ($searchTerm, $phoneSearchTerm, $includeDeletedFields) {
            $q->where('order_number', '=', $searchTerm)
              ->orWhere('customer_name', '=', $searchTerm)
              ->orWhere('customer_phone', '=', $phoneSearchTerm ?: $searchTerm)
              ->orWhere('customer_social_link', '=', $searchTerm)
              ->orWhere('customer_address', '=', $searchTerm)
              ->orWhere(function($subQ) use ($searchTerm) {
                  $subQ->whereNotNull('delivery_code')
                       ->where('delivery_code', $searchTerm);
              })
              ->orWhere('notes', '=', $searchTerm);

            // إضافة البحث في deletion_reason للطلبات المحذوفة
            if ($includeDeletedFields) {
                $q->orWhere('deletion_reason', '=', $searchTerm);
            }

            // البحث في عناصر الطلب (product_name, product_code, size_name)
            $q->orWhereHas('items', function($itemQuery) use ($searchTerm) {
                $itemQuery->where('product_name', '=', $searchTerm)
                         ->orWhere('product_code', '=', $searchTerm)
                         ->orWhere('size_name', '=', $searchTerm);
            });
        });
    }

    /**
     * تتبع طلبات المندوب (المقيدة والمرسلة للوسيط)
     */
    public function trackOrders(Request $request)
    {
        // Base query - الطلبات المقيدة فقط والتي لها shipment (مرسلة) للمندوب الحالي
        $query = Order::where('status', 'confirmed')
            ->where('delegate_id', auth()->id())
            ->whereHas('alwaseetShipment');

        // فلتر المخزن
        if ($request->filled('warehouse_id')) {
            $query->whereIn('id', function($subQuery) use ($request) {
                $subQuery->select('order_id')
                    ->from('order_items')
                    ->join('products', 'order_items.product_id', '=', 'products.id')
                    ->where('products.warehouse_id', $request->warehouse_id)
                    ->distinct();
            });
        }

        // البحث في الطلبات
        if ($request->filled('search')) {
            $searchTerm = $request->search;
            $query->where(function($q) use ($searchTerm) {
                $q->where('order_number', 'like', "%{$searchTerm}%")
                  ->orWhere('customer_name', 'like', "%{$searchTerm}%")
                  ->orWhere('customer_phone', 'like', "%{$searchTerm}%")
                  ->orWhere('customer_social_link', 'like', "%{$searchTerm}%")
                  ->orWhere('customer_address', 'like', "%{$searchTerm}%")
                  ->orWhere('delivery_code', 'like', "%{$searchTerm}%")
                  ->orWhereHas('items.product', function($productQuery) use ($searchTerm) {
                      $productQuery->where('name', 'like', "%{$searchTerm}%")
                                   ->orWhere('code', 'like', "%{$searchTerm}%");
                  })
                  ->orWhereHas('alwaseetShipment', function($shipmentQuery) use ($searchTerm) {
                      $shipmentQuery->where('alwaseet_order_id', 'like', "%{$searchTerm}%");
                  });
            });
        }

        // فلتر حسب التاريخ
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        // فلتر حسب الوقت
        if ($request->filled('time_from')) {
            $dateFrom = $request->date_from ?? now()->format('Y-m-d');
            $query->where('created_at', '>=', $dateFrom . ' ' . $request->time_from . ':00');
        }

        if ($request->filled('time_to')) {
            $dateTo = $request->date_to ?? now()->format('Y-m-d');
            $query->where('created_at', '<=', $dateTo . ' ' . $request->time_to . ':00');
        }

        // فلتر حسب الساعات
        if ($request->filled('hours_ago')) {
            $hoursAgo = (int)$request->hours_ago;
            if ($hoursAgo > 0) {
                $query->where('created_at', '>=', now()->subHours($hoursAgo));
            }
        }

        // فلتر حسب حالة API مباشرة في SQL query (أسرع وأدق)
        if ($request->filled('api_status_id')) {
            $query->whereHas('alwaseetShipment', function($q) use ($request) {
                $q->where('status_id', $request->api_status_id);
            });
        }

        // جلب الطلبات مع pagination عادي (بدون حد على العدد!)
        $orders = $query->with([
            'delegate',
            'items.product.primaryImage',
            'items.product.warehouse',
            'alwaseetShipment.statusHistory.statusInfo' // إضافة Timeline
        ])->orderBy('created_at', 'desc')->paginate(20);
        
        $ordersForApi = $orders;
        $hasMoreOrders = false;

        // جلب قائمة المدن من API (مع Cache لمدة 24 ساعة - لا حاجة للتحديث المستمر)
        $cities = [];
        try {
            $cacheKey = 'alwaseet_cities_delegate';
            $cities = Cache::remember($cacheKey, now()->addHours(24), function () {
                return $this->alWaseetService->getCities();
            });
        } catch (\Exception $e) {
            Log::error('Delegate/OrderController: Failed to load cities in trackOrders', [
                'error' => $e->getMessage(),
            ]);
        }

        // جلب المناطق للطلبات التي لديها city_id محفوظة (مع Cache - أسرع بكثير)
        $ordersWithRegions = [];
        try {
            $uniqueCityIds = $ordersForApi->pluck('alwaseet_city_id')->filter()->unique()->toArray();
            foreach ($uniqueCityIds as $cityId) {
                $cacheKey = 'alwaseet_regions_' . $cityId;
                $regions = Cache::remember($cacheKey, now()->addHours(24), function () use ($cityId) {
                    return $this->alWaseetService->getRegions($cityId);
                });
                
                // ربط المناطق بجميع الطلبات التي لها نفس city_id
                foreach ($ordersForApi as $order) {
                    if ($order->alwaseet_city_id == $cityId) {
                        $ordersWithRegions[$order->id] = $regions;
                    }
                }
            }
            
            // للطلبات التي لا تحتوي على city_id
            foreach ($ordersForApi as $order) {
                if (!$order->alwaseet_city_id && !isset($ordersWithRegions[$order->id])) {
                    $ordersWithRegions[$order->id] = [];
                }
            }
        } catch (\Exception $e) {
            Log::error('Delegate/OrderController: Failed to load regions in trackOrders', [
                'error' => $e->getMessage(),
            ]);
            // في حالة الفشل، نعطي array فارغ لكل طلب
            foreach ($ordersForApi as $order) {
                if (!isset($ordersWithRegions[$order->id])) {
                    $ordersWithRegions[$order->id] = [];
                }
            }
        }

        // لا حاجة لجلب بيانات API - نستخدم البيانات المحفوظة في قاعدة البيانات
        // Job في الخلفية يقوم بتحديث جميع بيانات API كل 10 دقائق تلقائياً
        $alwaseetOrdersData = []; // فارغ - لا حاجة لاستخدامه بعد الآن

        // جلب قائمة الحالات من قاعدة البيانات مع Cache (أسرع بكثير)
        // Job في الخلفية يقوم بتحديث الحالات من API كل ساعة تلقائياً
        $statusesMap = [];
        $allStatuses = [];
        try {
            $cacheKey = 'delegate_statuses_' . auth()->id();
            $allStatuses = Cache::remember($cacheKey, now()->addHours(1), function () {
                $dbStatuses = AlWaseetOrderStatus::orderBy('display_order')
                    ->orderBy('status_text')
                    ->get();
                
                $statuses = [];
                foreach ($dbStatuses as $dbStatus) {
                    $statuses[] = [
                        'id' => $dbStatus->status_id,
                        'status' => $dbStatus->status_text
                    ];
                }
                return $statuses;
            });
            
            // إنشاء statusesMap
            foreach ($allStatuses as $status) {
                $statusesMap[$status['id']] = $status['status'];
            }
        } catch (\Exception $e) {
            Log::error('Delegate/OrderController: Failed to load order statuses from database in trackOrders', [
                'error' => $e->getMessage(),
            ]);
            $allStatuses = [];
        }

        // حساب عدد الطلبات لكل حالة (من Cache - Job يحدثها كل 5 دقائق تلقائياً)
        $statusCounts = [];
        if (!$request->filled('api_status_id')) {
            $cacheKey = 'delegate_all_status_counts_' . auth()->id();
            
            // محاولة جلب من Cache أولاً (Job يحدثها كل 5 دقائق)
            $statusCounts = Cache::get($cacheKey);
            
            // إذا لم تكن موجودة في Cache، حسابها مباشرة (fallback)
            if ($statusCounts === null) {
                $counts = [];

                // تهيئة جميع الحالات أولاً بقيمة 0
                foreach ($allStatuses as $status) {
                    $statusId = (string)$status['id'];
                    $counts[$statusId] = 0;
                }

                // جلب جميع order IDs للمندوب
                $orderIds = Order::where('status', 'confirmed')
                    ->where('delegate_id', auth()->id())
                    ->whereHas('alwaseetShipment')
                    ->pluck('id')
                    ->toArray();

                if (!empty($orderIds)) {
                    // حساب عدد الطلبات لكل حالة مباشرة من قاعدة البيانات
                    $statusCountsFromDb = \App\Models\AlWaseetShipment::whereIn('order_id', $orderIds)
                        ->whereNotNull('status_id')
                        ->selectRaw('status_id, COUNT(*) as count')
                        ->groupBy('status_id')
                        ->get()
                        ->mapWithKeys(function($item) {
                            return [(string)$item->status_id => (int)$item->count];
                        })
                        ->toArray();

                    // تحديث العدادات للحالات الموجودة
                    foreach ($statusCountsFromDb as $statusId => $count) {
                        $statusIdStr = (string)$statusId;
                        $counts[$statusIdStr] = (int)$count;
                    }
                }

                $statusCounts = $counts;
                
                // حفظ في Cache لمدة 10 دقائق (حتى يتم تحديثها من Job)
                Cache::put($cacheKey, $statusCounts, now()->addMinutes(10));
            }
            
            // التأكد من أن جميع الحالات موجودة في statusCounts (حتى لو كانت 0)
            foreach ($allStatuses as $status) {
                $statusId = (string)$status['id'];
                if (!isset($statusCounts[$statusId])) {
                    $statusCounts[$statusId] = 0;
                }
            }
        }


        // جلب قائمة المخازن (للمندوب: فقط المخازن التي له طلبات منها)
        $warehouses = \App\Models\Warehouse::whereIn('id', function($subQuery) {
            $subQuery->select('products.warehouse_id')
                ->from('products')
                ->join('order_items', 'products.id', '=', 'order_items.product_id')
                ->join('orders', 'order_items.order_id', '=', 'orders.id')
                ->where('orders.delegate_id', auth()->id())
                ->where('orders.status', 'confirmed')
                ->whereExists(function($query) {
                    $query->select(DB::raw(1))
                        ->from('alwaseet_shipments')
                        ->whereColumn('alwaseet_shipments.order_id', 'orders.id');
                })
                ->distinct();
        })->get();

        // تحديد ما إذا كان يجب عرض المربعات أو الطلبات
        // إذا كان هناك بحث أو فلتر api_status_id، عرض الطلبات مباشرة
        $showStatusCards = !$request->filled('api_status_id') && !$request->filled('search');

        // التأكد من أن جميع الحالات موجودة في statusCounts (حتى لو كانت 0)
        if ($showStatusCards && !empty($allStatuses)) {
            foreach ($allStatuses as $status) {
                $statusId = (string)$status['id'];
                if (!isset($statusCounts[$statusId])) {
                    $statusCounts[$statusId] = 0;
                }
            }
        }

        // إذا كان عرض المربعات فقط، لا نحتاج لتعريف $orders
        if ($showStatusCards) {
            $orders = collect(); // Empty collection
        }

        return view('delegate.track-orders', compact(
            'orders',
            'warehouses',
            'cities',
            'ordersWithRegions',
            'alwaseetOrdersData',
            'statusesMap',
            'allStatuses',
            'hasMoreOrders',
            'statusCounts',
            'showStatusCards'
        ));
    }
}
