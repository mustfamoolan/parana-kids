<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\User;
use App\Models\Product;
use App\Models\ProductSize;
use App\Models\ProductMovement;
use App\Models\ReturnItem;
use App\Models\ExchangeItem;
use App\Models\OrderItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    /**
     * صفحة إدارة الطلبات الموحدة (pending + confirmed فقط كبداية)
     */
    public function management(Request $request)
    {
        $this->authorize('viewAny', Order::class);

        // جلب قائمة المخازن حسب الصلاحيات
        if (Auth::user()->isSupplier()) {
            $warehouses = Auth::user()->warehouses;
        } else {
            $warehouses = \App\Models\Warehouse::all();
        }

        // Base query
        $query = Order::query();

        // للمجهز: عرض الطلبات التي تحتوي على منتجات من مخازن له صلاحية الوصول إليها
        if (Auth::user()->isSupplier()) {
            $accessibleWarehouseIds = Auth::user()->warehouses->pluck('id')->toArray();

            $query->whereHas('items.product', function($q) use ($accessibleWarehouseIds) {
                $q->whereIn('warehouse_id', $accessibleWarehouseIds);
            });
        }

        // فلتر الحالة
        if ($request->status === 'deleted') {
            // عرض الطلبات المحذوفة فقط
            $query->onlyTrashed()->with(['deletedByUser']);
        } elseif ($request->filled('status') && in_array($request->status, ['pending', 'confirmed', 'returned'])) {
            $query->where('status', $request->status);
        } else {
            // افتراضياً: عرض pending و confirmed و returned معاً (بدون المحذوفة)
            $query->whereIn('status', ['pending', 'confirmed', 'returned']);
        }

        // فلتر المخزن
        if ($request->filled('warehouse_id')) {
            $query->whereHas('items.product', function($q) use ($request) {
                $q->where('warehouse_id', $request->warehouse_id);
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
                  ->orWhere('delivery_code', 'like', "%{$searchTerm}%")
                  ->orWhereHas('delegate', function($delegateQuery) use ($searchTerm) {
                      $delegateQuery->where('name', 'like', "%{$searchTerm}%");
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

        // فلتر حسب تاريخ التقييد (للطلبات المقيدة)
        if ($request->filled('confirmed_from')) {
            $query->whereDate('confirmed_at', '>=', $request->confirmed_from);
        }

        if ($request->filled('confirmed_to')) {
            $query->whereDate('confirmed_at', '<=', $request->confirmed_to);
        }

        // فلتر حسب تاريخ الإرجاع (للطلبات المسترجعة)
        if ($request->filled('returned_from')) {
            $query->whereDate('returned_at', '>=', $request->returned_from);
        }

        if ($request->filled('returned_to')) {
            $query->whereDate('returned_at', '<=', $request->returned_to);
        }

        $perPage = $request->input('per_page', 15);
        $orders = $query->with(['delegate', 'items.product.warehouse', 'items.product.primaryImage', 'confirmedBy', 'processedBy'])
                       ->latest()
                       ->paginate($perPage)
                       ->appends($request->except('page'));

        return view('admin.orders.management', compact('orders', 'warehouses'));
    }

    /**
     * Display a unified listing of all orders with filters.
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', Order::class);

        // جلب قائمة المخازن حسب الصلاحيات
        if (Auth::user()->isSupplier()) {
            $warehouses = Auth::user()->warehouses;
        } else {
            $warehouses = \App\Models\Warehouse::all();
        }

        // Base query
        $query = Order::query();

        // للمجهز: عرض الطلبات التي تحتوي على منتجات من مخازن له صلاحية الوصول إليها
        if (Auth::user()->isSupplier()) {
            $accessibleWarehouseIds = Auth::user()->warehouses->pluck('id')->toArray();

            $query->whereHas('items.product', function($q) use ($accessibleWarehouseIds) {
                $q->whereIn('warehouse_id', $accessibleWarehouseIds);
            });
        }

        // فلتر الحالة
        if ($request->status === 'deleted') {
            $query->onlyTrashed()->with(['deletedByUser']);
        } elseif ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // فلتر المخزن
        if ($request->filled('warehouse_id')) {
            $query->whereHas('items.product', function($q) use ($request) {
                $q->where('warehouse_id', $request->warehouse_id);
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
                  ->orWhere('delivery_code', 'like', "%{$searchTerm}%")
                  ->orWhereHas('delegate', function($delegateQuery) use ($searchTerm) {
                      $delegateQuery->where('name', 'like', "%{$searchTerm}%");
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

        // فلتر حسب تاريخ التقييد (للطلبات المقيدة)
        if ($request->filled('confirmed_from')) {
            $query->whereDate('confirmed_at', '>=', $request->confirmed_from);
        }

        if ($request->filled('confirmed_to')) {
            $query->whereDate('confirmed_at', '<=', $request->confirmed_to);
        }

        // فلتر حسب تاريخ الإرجاع (للطلبات المسترجعة)
        if ($request->filled('returned_from')) {
            $query->whereDate('returned_at', '>=', $request->returned_from);
        }

        if ($request->filled('returned_to')) {
            $query->whereDate('returned_at', '<=', $request->returned_to);
        }

        // فلتر حسب تاريخ الاستبدال (للطلبات المستبدلة)
        if ($request->filled('exchanged_from')) {
            $query->whereDate('exchanged_at', '>=', $request->exchanged_from);
        }

        if ($request->filled('exchanged_to')) {
            $query->whereDate('exchanged_at', '<=', $request->exchanged_to);
        }

        $orders = $query->with(['delegate', 'items.product.warehouse', 'items.product.primaryImage', 'confirmedBy', 'processedBy'])
                       ->latest()
                       ->paginate(15);

        return view('admin.orders.index', compact('orders', 'warehouses'));
    }

    /**
     * Display the specified order.
     */
    public function show(Order $order)
    {
        $this->authorize('view', $order);

        $order->load([
            'delegate',
            'items.product.primaryImage',
            'items.product.warehouse',
            'cart',
            'confirmedBy',
            'processedBy'
        ]);

        return view('admin.orders.show', compact('order'));
    }

    /**
     * Get materials list for all pending orders.
     */
    public function getMaterialsList()
    {
        $this->authorize('viewAny', Order::class);

        // جلب الطلبات حسب الصلاحيات
        $orders = $this->getAccessibleOrders();

        // تجميع المواد
        $materials = [];
        foreach ($orders as $order) {
            foreach ($order->items as $item) {
                // التأكد من وجود المنتج
                if (!$item->product) {
                    continue;
                }

                $key = $item->product_id . '_' . $item->size_name;

                if (!isset($materials[$key])) {
                    $materials[$key] = [
                        'product' => $item->product,
                        'size_name' => $item->size_name,
                        'total_quantity' => 0,
                        'orders' => []
                    ];
                }

                $materials[$key]['total_quantity'] += $item->quantity;
                $materials[$key]['orders'][] = [
                    'order_number' => $order->order_number,
                    'quantity' => $item->quantity,
                    'order_id' => $order->id
                ];
            }
        }

        return view('admin.orders.materials-list', compact('materials'));
    }

    /**
     * Get materials list for management page with warehouse filter support.
     */
    public function getMaterialsListManagement(Request $request)
    {
        $this->authorize('viewAny', Order::class);

        // Base query
        $query = Order::query();

        // فلتر الصلاحيات
        if (Auth::user()->isSupplier()) {
            $accessibleWarehouseIds = Auth::user()->warehouses->pluck('id')->toArray();
            $query->whereHas('items.product', function($q) use ($accessibleWarehouseIds) {
                $q->whereIn('warehouse_id', $accessibleWarehouseIds);
            });
        }

        // فلتر الحالة (pending بشكل افتراضي)
        if ($request->filled('status')) {
            if ($request->status === 'deleted') {
                $query->onlyTrashed();
            } else {
                $query->where('status', $request->status);
            }
        } else {
            $query->where('status', 'pending');
        }

        // فلتر المخزن ⭐ الميزة الجديدة
        if ($request->filled('warehouse_id')) {
            $query->whereHas('items.product', function($q) use ($request) {
                $q->where('warehouse_id', $request->warehouse_id);
            });
        }

        $orders = $query->with([
            'delegate',
            'items.product.primaryImage',
            'items.product.warehouse'
        ])->get();

        // تجميع المواد
        $materials = [];
        foreach ($orders as $order) {
            foreach ($order->items as $item) {
                if (!$item->product) continue;

                $key = $item->product_id . '_' . $item->size_name;

                if (!isset($materials[$key])) {
                    $materials[$key] = [
                        'product' => $item->product,
                        'size_name' => $item->size_name,
                        'total_quantity' => 0,
                        'orders' => []
                    ];
                }

                $materials[$key]['total_quantity'] += $item->quantity;
                $materials[$key]['orders'][] = [
                    'order_number' => $order->order_number,
                    'quantity' => $item->quantity,
                    'order_id' => $order->id
                ];
            }
        }

        return view('admin.orders.materials-list', compact('materials'));
    }

    /**
     * Get orders accessible by current user.
     */
    private function getAccessibleOrders()
    {
        $query = Order::where('status', 'pending');

        // للمجهز: عرض الطلبات التي تحتوي على منتجات من مخازن له صلاحية الوصول إليها
        if (Auth::user()->isSupplier()) {
            $accessibleWarehouseIds = Auth::user()->warehouses->pluck('id')->toArray();

            $query->whereHas('items.product', function($q) use ($accessibleWarehouseIds) {
                $q->whereIn('warehouse_id', $accessibleWarehouseIds);
            });
        }

        return $query->with([
            'delegate',
            'items.product.primaryImage',
            'items.product.warehouse'
        ])->get();
    }

    /**
     * Show the form for processing an order.
     */
    public function process(Order $order)
    {
        $this->authorize('update', $order);

        $order->load([
            'delegate',
            'items.product.primaryImage',
            'items.product.warehouse',
            'cart'
        ]);

        return view('admin.orders.process', compact('order'));
    }

    /**
     * Show the comprehensive order processing page.
     */
    public function showProcess(Order $order)
    {
        $this->authorize('process', $order);

        // التحقق من أن الطلب غير مقيد
        if ($order->status !== 'pending') {
            return redirect()->route('admin.orders.show', $order)
                            ->withErrors(['order' => 'لا يمكن تجهيز الطلبات المقيدة']);
        }

        // تحميل العلاقات
        $order->load(['items.product.primaryImage', 'items.size', 'delegate']);

        // جلب المنتجات المتوفرة للمخازن التي يمكن للمستخدم الوصول إليها
        $warehouses = $this->getAccessibleWarehouses();
        $products = Product::whereIn('warehouse_id', $warehouses->pluck('id'))
                          ->with(['primaryImage', 'sizes' => function($q) {
                              $q->where('quantity', '>', 0);
                          }])
                          ->get();

        return view('admin.orders.process', compact('order', 'products'));
    }

    /**
     * Process the order with comprehensive modifications.
     */
    public function processOrder(Request $request, Order $order)
    {
        $this->authorize('process', $order);

        if ($order->status !== 'pending') {
            return redirect()->route('admin.orders.show', $order)
                            ->withErrors(['order' => 'لا يمكن تجهيز الطلبات المقيدة']);
        }

        $request->validate([
            'customer_name' => 'required|string|max:255',
            'customer_phone' => 'required|string|max:20',
            'customer_address' => 'required|string',
            'customer_social_link' => 'required|string|max:255',
            'delivery_code' => 'required|string|max:100',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.size_id' => 'required|exists:product_sizes,id',
            'items.*.quantity' => 'required|integer|min:1',
        ]);

        DB::transaction(function() use ($order, $request) {
            // تحديث معلومات الطلب
            $order->update([
                'customer_name' => $request->customer_name,
                'customer_phone' => $request->customer_phone,
                'customer_address' => $request->customer_address,
                'customer_social_link' => $request->customer_social_link,
                'delivery_code' => $request->delivery_code,
                'notes' => $request->notes,
                'status' => 'confirmed',
                'confirmed_at' => now(),
                'confirmed_by' => auth()->id(),
            ]);

            // حذف جميع المنتجات القديمة وإرجاعها للمخزون
            foreach ($order->items as $item) {
                if ($item->size) {
                    $item->size->increment('quantity', $item->quantity);
                }
            }
            $order->items()->delete();

            // إضافة المنتجات الجديدة
            $totalAmount = 0;
            foreach ($request->items as $itemData) {
                $product = Product::findOrFail($itemData['product_id']);
                $size = ProductSize::findOrFail($itemData['size_id']);

                // التحقق من التوفر
                if ($size->quantity < $itemData['quantity']) {
                    throw new \Exception("الكمية غير متوفرة للقياس {$size->size_name}");
                }

                $subtotal = $itemData['quantity'] * $product->selling_price;

                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $product->id,
                    'size_id' => $size->id,
                    'product_name' => $product->name,
                    'product_code' => $product->code,
                    'size_name' => $size->size_name,
                    'quantity' => $itemData['quantity'],
                    'unit_price' => $product->selling_price,
                    'subtotal' => $subtotal,
                ]);

                // خصم من المخزون
                $size->decrement('quantity', $itemData['quantity']);

                // تسجيل حركة البيع
                ProductMovement::record([
                    'product_id' => $product->id,
                    'size_id' => $size->id,
                    'warehouse_id' => $product->warehouse_id,
                    'order_id' => $order->id,
                    'movement_type' => 'sale',
                    'quantity' => -$itemData['quantity'],
                    'balance_after' => $size->refresh()->quantity,
                    'order_status' => $order->status,
                    'notes' => "بيع من طلب #{$order->order_number}"
                ]);

                $totalAmount += $subtotal;
            }

            $order->update(['total_amount' => $totalAmount]);
        });

        return redirect()->route('admin.orders.confirmed')
                        ->with('success', 'تم تجهيز وتقييد الطلب بنجاح');
    }

    /**
     * Get warehouses accessible by current user.
     */
    private function getAccessibleWarehouses()
    {
        if (Auth::user()->isAdmin()) {
            return \App\Models\Warehouse::all();
        }

        if (Auth::user()->isSupplier()) {
            return Auth::user()->warehouses;
        }

        return collect();
    }

    /**
     * Confirm the order.
     */
    public function confirm(Request $request, Order $order)
    {
        $this->authorize('update', $order);

        $request->validate([
            'delivery_code' => 'required|string|max:255',
            'customer_name' => 'required|string|max:255',
            'customer_phone' => 'required|string|max:20',
            'customer_address' => 'required|string',
            'customer_social_link' => 'required|string|max:255',
            'notes' => 'nullable|string',
        ]);

        $order->update([
            'delivery_code' => $request->delivery_code,
            'customer_name' => $request->customer_name,
            'customer_phone' => $request->customer_phone,
            'customer_address' => $request->customer_address,
            'customer_social_link' => $request->customer_social_link,
            'notes' => $request->notes,
            'status' => 'confirmed',
            'confirmed_at' => now(),
            'confirmed_by' => auth()->id(),
        ]);

        return redirect()->route('admin.orders.confirmed')
                        ->with('success', 'تم تقييد الطلب بنجاح');
    }

    /**
     * Display confirmed orders.
     */
    public function confirmed(Request $request)
    {
        $this->authorize('viewAny', Order::class);

        $query = Order::where('status', 'confirmed');

        // للمجهز: عرض الطلبات من مخازنه فقط
        if (Auth::user()->isSupplier()) {
            $accessibleWarehouseIds = Auth::user()->warehouses->pluck('id')->toArray();

            $query->whereHas('items.product', function($q) use ($accessibleWarehouseIds) {
                $q->whereIn('warehouse_id', $accessibleWarehouseIds);
            });
        }

        // فلاتر البحث
        if ($request->filled('search')) {
            $searchTerm = $request->search;
            $query->where(function($q) use ($searchTerm) {
                $q->where('order_number', 'like', "%{$searchTerm}%")
                  ->orWhere('customer_name', 'like', "%{$searchTerm}%")
                  ->orWhere('customer_phone', 'like', "%{$searchTerm}%")
                  ->orWhere('customer_social_link', 'like', "%{$searchTerm}%")
                  ->orWhere('delivery_code', 'like', "%{$searchTerm}%")
                  ->orWhereHas('delegate', function($delegateQuery) use ($searchTerm) {
                      $delegateQuery->where('name', 'like', "%{$searchTerm}%");
                  });
            });
        }

        // فلاتر التاريخ
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        // فلتر حسب الوقت للطلب
        if ($request->filled('time_from')) {
            $dateFrom = $request->date_from ?? now()->format('Y-m-d');
            $query->where('created_at', '>=', $dateFrom . ' ' . $request->time_from . ':00');
        }

        if ($request->filled('time_to')) {
            $dateTo = $request->date_to ?? now()->format('Y-m-d');
            $query->where('created_at', '<=', $dateTo . ' ' . $request->time_to . ':00');
        }

        if ($request->filled('confirmed_from')) {
            $query->whereDate('confirmed_at', '>=', $request->confirmed_from);
        }

        if ($request->filled('confirmed_to')) {
            $query->whereDate('confirmed_at', '<=', $request->confirmed_to);
        }

        // فلتر حسب الوقت للتقييد
        if ($request->filled('confirmed_time_from')) {
            $confirmedDateFrom = $request->confirmed_from ?? now()->format('Y-m-d');
            $query->where('confirmed_at', '>=', $confirmedDateFrom . ' ' . $request->confirmed_time_from . ':00');
        }

        if ($request->filled('confirmed_time_to')) {
            $confirmedDateTo = $request->confirmed_to ?? now()->format('Y-m-d');
            $query->where('confirmed_at', '<=', $confirmedDateTo . ' ' . $request->confirmed_time_to . ':00');
        }

        $orders = $query->with(['delegate', 'confirmedBy', 'items.product.primaryImage'])
                       ->latest('confirmed_at')
                       ->paginate(15);

        return view('admin.orders.confirmed', compact('orders'));
    }

    /**
     * Show the form for editing the order.
     */
    public function edit(Order $order)
    {
        $this->authorize('update', $order);

        // السماح بالتعديل للطلبات pending أو المقيدة خلال 5 ساعات
        if ($order->status !== 'pending' && !$order->canBeEdited()) {
            return back()->withErrors(['error' => 'لا يمكن تعديل هذا الطلب (مر أكثر من 5 ساعات على التقييد)']);
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

        return view('admin.orders.edit', compact('order', 'products'));
    }

    /**
     * Update the order.
     */
    public function update(Request $request, Order $order)
    {
        $this->authorize('update', $order);

        // السماح بالتعديل للطلبات pending أو المقيدة خلال 5 ساعات
        if ($order->status !== 'pending' && !$order->canBeEdited()) {
            return back()->withErrors(['error' => 'لا يمكن تعديل هذا الطلب (مر أكثر من 5 ساعات على التقييد)']);
        }

        $request->validate([
            'delivery_code' => 'nullable|string|max:255',
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
                // تحميل العناصر القديمة قبل الحذف
                $oldItems = $order->items()->get();

                // تحديث معلومات الطلب
                $order->update($request->only([
                    'delivery_code',
                    'customer_name',
                    'customer_phone',
                    'customer_address',
                    'customer_social_link',
                    'notes',
                ]));

                // إذا كان الطلب مقيد (confirmed)، نرجع المنتجات القديمة للمخزن أولاً
                if ($order->status === 'confirmed') {
                    foreach ($oldItems as $oldItem) {
                        if ($oldItem->size) {
                            $oldItem->size->increment('quantity', $oldItem->quantity);

                            // تسجيل حركة الإرجاع
                            ProductMovement::record([
                                'product_id' => $oldItem->product_id,
                                'size_id' => $oldItem->size_id,
                                'warehouse_id' => $oldItem->product->warehouse_id,
                                'order_id' => $order->id,
                                'movement_type' => 'cancel',
                                'quantity' => $oldItem->quantity,
                                'balance_after' => $oldItem->size->quantity,
                                'order_status' => $order->status,
                                'notes' => "تعديل طلب #{$order->order_number} - إرجاع المنتجات القديمة"
                            ]);
                        }
                    }
                }

                // حذف المنتجات القديمة
                $order->items()->delete();

                // إضافة المنتجات الجديدة
                $totalAmount = 0;
                foreach ($request->items as $item) {
                    $product = Product::findOrFail($item['product_id']);
                    $size = ProductSize::findOrFail($item['size_id']);

                    // التحقق من توفر الكمية (للطلبات المقيدة فقط)
                    if ($order->status === 'confirmed') {
                        if ($size->quantity < $item['quantity']) {
                            throw new \Exception("الكمية المتوفرة من {$product->name} - {$size->size_name} غير كافية. المتوفر: {$size->quantity}");
                        }
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

                    // خصم من المخزن (للطلبات المقيدة فقط)
                    if ($order->status === 'confirmed') {
                        $size->decrement('quantity', $item['quantity']);

                        // تسجيل حركة البيع الجديدة
                        ProductMovement::record([
                            'product_id' => $item['product_id'],
                            'size_id' => $item['size_id'],
                            'warehouse_id' => $product->warehouse_id,
                            'order_id' => $order->id,
                            'movement_type' => 'sell',
                            'quantity' => -$item['quantity'],
                            'balance_after' => $size->quantity,
                            'order_status' => $order->status,
                            'notes' => "تعديل طلب #{$order->order_number} - إضافة منتج جديد"
                        ]);
                    }
                }

                // تحديث المبلغ الإجمالي
                $order->update(['total_amount' => $totalAmount]);
            });

            return redirect()->route('admin.orders.show', $order)
                            ->with('success', 'تم تحديث الطلب بنجاح');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'حدث خطأ أثناء تحديث الطلب: ' . $e->getMessage()])->withInput();
        }
    }

    /**
     * عرض صفحة الإرجاع
     */
    public function showReturn(Order $order)
    {
        $this->authorize('update', $order);

        if ($order->status !== 'confirmed') {
            return back()->withErrors(['error' => 'لا يمكن إرجاع منتجات هذا الطلب']);
        }

        $order->load(['items.product.primaryImage', 'items.size']);

        return view('admin.orders.return', compact('order'));
    }

    /**
     * تنفيذ الإرجاع
     */
    public function processReturn(Request $request, Order $order)
    {
        $this->authorize('update', $order);

        $request->validate([
            'return_items' => 'required|array|min:1',
            'return_items.*.order_item_id' => 'required|exists:order_items,id',
            'return_items.*.product_id' => 'required|exists:products,id',
            'return_items.*.size_id' => 'required|exists:product_sizes,id',
            'return_items.*.quantity' => 'required|integer|min:1',
            'return_items.*.reason' => 'required|string',
            'notes' => 'nullable|string',
        ]);

        try {
            $returnData = collect($request->return_items)->map(function($item) use ($request) {
                $item['notes'] = $request->notes;
                return $item;
            })->toArray();

            $order->processReturn($returnData, auth()->id());

            return redirect()->route('admin.orders.returned')
                            ->with('success', 'تم إرجاع المنتجات بنجاح');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    /**
     * عرض صفحة الاستبدال
     */
    public function showExchange(Order $order)
    {
        $this->authorize('update', $order);

        if ($order->status !== 'confirmed') {
            return back()->withErrors(['error' => 'لا يمكن استبدال منتجات هذا الطلب']);
        }

        $order->load(['items.product.primaryImage', 'items.size']);

        // تحضير المنتجات مع الصور بشكل صحيح
        $products = Product::with(['sizes', 'primaryImage'])->get()->map(function($product) {
            return [
                'id' => $product->id,
                'name' => $product->name,
                'code' => $product->code,
                'image' => $product->primaryImage ? Storage::url($product->primaryImage->path) : '/assets/images/no-image.png',
                'sizes' => $product->sizes
            ];
        });

        return view('admin.orders.exchange', compact('order', 'products'));
    }

    /**
     * تنفيذ الاستبدال
     */
    public function processExchange(Request $request, Order $order)
    {
        $this->authorize('update', $order);

        $request->validate([
            'exchanges' => 'required|array|min:1',
            'exchanges.*.order_item_id' => 'required|exists:order_items,id',
            'exchanges.*.old_product_id' => 'required|exists:products,id',
            'exchanges.*.old_size_id' => 'required|exists:product_sizes,id',
            'exchanges.*.old_quantity' => 'required|integer|min:1',
            'exchanges.*.new_product_id' => 'required|exists:products,id',
            'exchanges.*.new_size_id' => 'required|exists:product_sizes,id',
            'exchanges.*.new_quantity' => 'required|integer|min:1',
            'exchanges.*.reason' => 'required|string',
        ]);

        try {
            $order->processExchange($request->exchanges, auth()->id());
            return redirect()->route('admin.orders.exchanged')
                            ->with('success', 'تم استبدال المنتجات بنجاح');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    /**
     * إلغاء الطلب (كلي فقط)
     */
    public function cancel(Request $request, Order $order)
    {
        $this->authorize('update', $order);

        $request->validate([
            'cancellation_reason' => 'required|string|max:500',
        ]);

        if ($order->status !== 'confirmed') {
            return back()->withErrors(['error' => 'لا يمكن إلغاء هذا الطلب']);
        }

        try {
            // تحميل العلاقات المطلوبة
            $order->load('items.size');

            $order->cancel($request->cancellation_reason, auth()->id());
            return redirect()->route('admin.orders.cancelled')
                            ->with('success', 'تم إلغاء الطلب بنجاح');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    /**
     * عرض الطلبات الملغية
     */
    public function cancelled(Request $request)
    {
        $this->authorize('viewAny', Order::class);

        $query = Order::where('status', 'cancelled');

        // للمجهز: عرض الطلبات من مخازنه فقط
        if (Auth::user()->isSupplier()) {
            $accessibleWarehouseIds = Auth::user()->warehouses->pluck('id')->toArray();

            $query->whereHas('items.product', function($q) use ($accessibleWarehouseIds) {
                $q->whereIn('warehouse_id', $accessibleWarehouseIds);
            });
        }

        // فلاتر البحث
        if ($request->filled('search')) {
            $searchTerm = $request->search;
            $query->where(function($q) use ($searchTerm) {
                $q->where('order_number', 'like', "%{$searchTerm}%")
                  ->orWhere('customer_name', 'like', "%{$searchTerm}%")
                  ->orWhere('customer_phone', 'like', "%{$searchTerm}%")
                  ->orWhere('customer_social_link', 'like', "%{$searchTerm}%")
                  ->orWhere('cancellation_reason', 'like', "%{$searchTerm}%")
                  ->orWhereHas('delegate', function($delegateQuery) use ($searchTerm) {
                      $delegateQuery->where('name', 'like', "%{$searchTerm}%");
                  });
            });
        }

        // فلاتر التاريخ
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        // فلتر حسب الوقت للطلب
        if ($request->filled('time_from')) {
            $dateFrom = $request->date_from ?? now()->format('Y-m-d');
            $query->where('created_at', '>=', $dateFrom . ' ' . $request->time_from . ':00');
        }

        if ($request->filled('time_to')) {
            $dateTo = $request->date_to ?? now()->format('Y-m-d');
            $query->where('created_at', '<=', $dateTo . ' ' . $request->time_to . ':00');
        }

        if ($request->filled('cancelled_from')) {
            $query->whereDate('cancelled_at', '>=', $request->cancelled_from);
        }

        if ($request->filled('cancelled_to')) {
            $query->whereDate('cancelled_at', '<=', $request->cancelled_to);
        }

        // فلتر حسب الوقت للإلغاء
        if ($request->filled('cancelled_time_from')) {
            $cancelledDateFrom = $request->cancelled_from ?? now()->format('Y-m-d');
            $query->where('cancelled_at', '>=', $cancelledDateFrom . ' ' . $request->cancelled_time_from . ':00');
        }

        if ($request->filled('cancelled_time_to')) {
            $cancelledDateTo = $request->cancelled_to ?? now()->format('Y-m-d');
            $query->where('cancelled_at', '<=', $cancelledDateTo . ' ' . $request->cancelled_time_to . ':00');
        }

        $orders = $query->with(['delegate', 'processedBy', 'items.product.primaryImage'])
                       ->latest('cancelled_at')
                       ->paginate(15);

        return view('admin.orders.cancelled', compact('orders'));
    }

    /**
     * عرض الطلبات المسترجعة
     */
    public function returned(Request $request)
    {
        $this->authorize('viewAny', Order::class);

        $query = Order::where('status', 'returned');

        // للمجهز: عرض الطلبات من مخازنه فقط
        if (Auth::user()->isSupplier()) {
            $accessibleWarehouseIds = Auth::user()->warehouses->pluck('id')->toArray();

            $query->whereHas('items.product', function($q) use ($accessibleWarehouseIds) {
                $q->whereIn('warehouse_id', $accessibleWarehouseIds);
            });
        }

        // فلاتر البحث
        if ($request->filled('search')) {
            $searchTerm = $request->search;
            $query->where(function($q) use ($searchTerm) {
                $q->where('order_number', 'like', "%{$searchTerm}%")
                  ->orWhere('customer_name', 'like', "%{$searchTerm}%")
                  ->orWhere('customer_phone', 'like', "%{$searchTerm}%")
                  ->orWhere('customer_social_link', 'like', "%{$searchTerm}%")
                  ->orWhere('return_notes', 'like', "%{$searchTerm}%")
                  ->orWhereHas('delegate', function($delegateQuery) use ($searchTerm) {
                      $delegateQuery->where('name', 'like', "%{$searchTerm}%");
                  });
            });
        }

        // فلاتر التاريخ
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        // فلتر حسب الوقت للطلب
        if ($request->filled('time_from')) {
            $dateFrom = $request->date_from ?? now()->format('Y-m-d');
            $query->where('created_at', '>=', $dateFrom . ' ' . $request->time_from . ':00');
        }

        if ($request->filled('time_to')) {
            $dateTo = $request->date_to ?? now()->format('Y-m-d');
            $query->where('created_at', '<=', $dateTo . ' ' . $request->time_to . ':00');
        }

        if ($request->filled('returned_from')) {
            $query->whereDate('returned_at', '>=', $request->returned_from);
        }

        if ($request->filled('returned_to')) {
            $query->whereDate('returned_at', '<=', $request->returned_to);
        }

        // فلتر حسب الوقت للاسترجاع
        if ($request->filled('returned_time_from')) {
            $returnedDateFrom = $request->returned_from ?? now()->format('Y-m-d');
            $query->where('returned_at', '>=', $returnedDateFrom . ' ' . $request->returned_time_from . ':00');
        }

        if ($request->filled('returned_time_to')) {
            $returnedDateTo = $request->returned_to ?? now()->format('Y-m-d');
            $query->where('returned_at', '<=', $returnedDateTo . ' ' . $request->returned_time_to . ':00');
        }

        $orders = $query->with(['delegate', 'processedBy', 'items.product.primaryImage', 'returnItems'])
                       ->latest('returned_at')
                       ->paginate(15);

        return view('admin.orders.returned', compact('orders'));
    }

    /**
     * عرض الطلبات المستبدلة
     */
    public function exchanged(Request $request)
    {
        $this->authorize('viewAny', Order::class);

        $query = Order::where('status', 'exchanged');

        // للمجهز: عرض الطلبات من مخازنه فقط
        if (Auth::user()->isSupplier()) {
            $accessibleWarehouseIds = Auth::user()->warehouses->pluck('id')->toArray();

            $query->whereHas('items.product', function($q) use ($accessibleWarehouseIds) {
                $q->whereIn('warehouse_id', $accessibleWarehouseIds);
            });
        }

        // فلاتر البحث
        if ($request->filled('search')) {
            $searchTerm = $request->search;
            $query->where(function($q) use ($searchTerm) {
                $q->where('order_number', 'like', "%{$searchTerm}%")
                  ->orWhere('customer_name', 'like', "%{$searchTerm}%")
                  ->orWhere('customer_phone', 'like', "%{$searchTerm}%")
                  ->orWhere('customer_social_link', 'like', "%{$searchTerm}%")
                  ->orWhereHas('delegate', function($delegateQuery) use ($searchTerm) {
                      $delegateQuery->where('name', 'like', "%{$searchTerm}%");
                  });
            });
        }

        // فلاتر التاريخ
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        // فلتر حسب الوقت للطلب
        if ($request->filled('time_from')) {
            $dateFrom = $request->date_from ?? now()->format('Y-m-d');
            $query->where('created_at', '>=', $dateFrom . ' ' . $request->time_from . ':00');
        }

        if ($request->filled('time_to')) {
            $dateTo = $request->date_to ?? now()->format('Y-m-d');
            $query->where('created_at', '<=', $dateTo . ' ' . $request->time_to . ':00');
        }

        if ($request->filled('exchanged_from')) {
            $query->whereDate('exchanged_at', '>=', $request->exchanged_from);
        }

        if ($request->filled('exchanged_to')) {
            $query->whereDate('exchanged_at', '<=', $request->exchanged_to);
        }

        // فلتر حسب الوقت للاستبدال
        if ($request->filled('exchanged_time_from')) {
            $exchangedDateFrom = $request->exchanged_from ?? now()->format('Y-m-d');
            $query->where('exchanged_at', '>=', $exchangedDateFrom . ' ' . $request->exchanged_time_from . ':00');
        }

        if ($request->filled('exchanged_time_to')) {
            $exchangedDateTo = $request->exchanged_to ?? now()->format('Y-m-d');
            $query->where('exchanged_at', '<=', $exchangedDateTo . ' ' . $request->exchanged_time_to . ':00');
        }

        $orders = $query->with(['delegate', 'processedBy', 'items.product.primaryImage', 'exchangeItems'])
                       ->latest('exchanged_at')
                       ->paginate(15);

        return view('admin.orders.exchanged', compact('orders'));
    }

    /**
     * عرض تفاصيل الإرجاع
     */
    public function returnDetails(Order $order)
    {
        $this->authorize('view', $order);

        $order->load(['returnItems.product.primaryImage', 'returnItems.size']);
        return view('admin.orders.return-details', compact('order'));
    }

    /**
     * عرض تفاصيل الاستبدال
     */
    public function exchangeDetails(Order $order)
    {
        $this->authorize('view', $order);

        $order->load([
            'exchangeItems.oldProduct.primaryImage',
            'exchangeItems.newProduct.primaryImage',
            'exchangeItems.oldSize',
            'exchangeItems.newSize'
        ]);
        return view('admin.orders.exchange-details', compact('order'));
    }

    /**
     * استرجاع مباشر للطلب (بسيط جداً)
     */
    public function returnDirect(Order $order)
    {
        $this->authorize('update', $order);

        if ($order->status !== 'confirmed') {
            return redirect()->route('admin.orders.confirmed')
                            ->withErrors(['error' => 'لا يمكن استرجاع هذا الطلب']);
        }

        try {
            DB::transaction(function() use ($order) {
                // تحميل العلاقات المطلوبة
                $order->load('items.size');

                // إرجاع جميع المنتجات للمخزن
                foreach ($order->items as $item) {
                    if ($item->size) {
                        $item->size->increment('quantity', $item->quantity);

                        // تسجيل حركة الاسترجاع
                        ProductMovement::record([
                            'product_id' => $item->product_id,
                            'size_id' => $item->size_id,
                            'warehouse_id' => $item->product->warehouse_id,
                            'order_id' => $order->id,
                            'movement_type' => 'return',
                            'quantity' => $item->quantity,
                            'balance_after' => $item->size->quantity,
                            'order_status' => $order->status,
                            'notes' => "استرجاع من طلب #{$order->order_number}"
                        ]);
                    }
                }

                // تحديث حالة الطلب فقط
                $order->update([
                    'status' => 'returned',
                    'returned_at' => now(),
                    'processed_by' => auth()->id(),
                ]);
            });

            return redirect()->route('admin.orders.confirmed')
                            ->with('success', 'تم استرجاع الطلب بنجاح وإرجاع جميع المنتجات للمخزن');
        } catch (\Exception $e) {
            return redirect()->route('admin.orders.confirmed')
                            ->withErrors(['error' => 'حدث خطأ أثناء استرجاع الطلب: ' . $e->getMessage()]);
        }
    }

    /**
     * حذف الطلب (soft delete) مع إرجاع المنتجات للمخزن
     */
    public function destroy(Order $order)
    {
        $this->authorize('delete', $order);

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
                            'order_id' => $order->id,
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

            return redirect()->back()
                            ->with('success', 'تم حذف الطلب بنجاح وإرجاع جميع المنتجات للمخزن');
        } catch (\Exception $e) {
            return redirect()->back()
                            ->withErrors(['error' => 'حدث خطأ أثناء حذف الطلب: ' . $e->getMessage()]);
        }
    }

    /**
     * عرض الطلبات المحذوفة
     */
    public function deleted(Request $request)
    {
        $this->authorize('viewAny', Order::class);

        $query = Order::onlyTrashed();

        // للمجهز والمندوب: عرض الطلبات من مخازنهم فقط
        if (Auth::user()->isSupplier() || Auth::user()->isDelegate()) {
            $warehouseIds = Auth::user()->warehouses()->pluck('warehouse_id');
            $query->whereHas('items.product', function($q) use ($warehouseIds) {
                $q->whereIn('warehouse_id', $warehouseIds);
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
                  ->orWhereHas('delegate', function($delegateQuery) use ($searchTerm) {
                      $delegateQuery->where('name', 'like', "%{$searchTerm}%");
                  });
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

        $orders = $query->with(['delegate', 'deletedBy', 'items.product.primaryImage'])
                       ->latest('deleted_at')
                       ->paginate(15);

        return view('admin.orders.deleted', compact('orders'));
    }

    /**
     * التحقق من توفر المنتجات للاسترجاع
     */
    public function checkRestoreAvailability(Order $order)
    {
        $this->authorize('restore', $order);

        $order->load('items.size');

        $availability = [];
        $allAvailable = true;

        foreach ($order->items as $item) {
            $available = $item->size ? $item->size->quantity : 0;
            $needed = $item->quantity;
            $shortage = max(0, $needed - $available);

            $availability[] = [
                'product_name' => $item->product->name,
                'size' => $item->size ? $item->size->size : 'غير محدد',
                'needed' => $needed,
                'available' => $available,
                'shortage' => $shortage,
                'is_available' => $available >= $needed
            ];

            if ($available < $needed) {
                $allAvailable = false;
            }
        }

        return response()->json([
            'order' => $order,
            'availability' => $availability,
            'all_available' => $allAvailable
        ]);
    }

    /**
     * استرجاع الطلب مع خصم المنتجات من المخزن
     */
    public function restore(Order $order)
    {
        $this->authorize('restore', $order);

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
                            'order_id' => $order->id,
                            'movement_type' => 'restore',
                            'quantity' => -$item->quantity,
                            'balance_after' => $item->size->refresh()->quantity,
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

            return redirect()->route('admin.orders.index')
                            ->with('success', 'تم استرجاع الطلب بنجاح وخصم المنتجات من المخزن');
        } catch (\Exception $e) {
            return redirect()->back()
                            ->withErrors(['error' => 'حدث خطأ أثناء استرجاع الطلب: ' . $e->getMessage()]);
        }
    }

    /**
     * الحذف النهائي للطلب (لا يمكن استرجاعه)
     */
    public function forceDelete($id)
    {
        try {
            $order = Order::withTrashed()->findOrFail($id);

            $this->authorize('forceDelete', $order);

            DB::transaction(function () use ($order) {
                // حذف حركات المنتجات المرتبطة بالطلب
                ProductMovement::where('order_id', $order->id)->delete();

                // حذف عناصر الطلب
                $order->items()->forceDelete();

                // الحذف النهائي للطلب
                $order->forceDelete();
            });

            return redirect()->route('admin.orders.management', ['status' => 'deleted'])
                            ->with('success', 'تم حذف الطلب نهائياً بنجاح');
        } catch (\Exception $e) {
            return redirect()->back()
                            ->withErrors(['error' => 'حدث خطأ أثناء الحذف النهائي: ' . $e->getMessage()]);
        }
    }
}
