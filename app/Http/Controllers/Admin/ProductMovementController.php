<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ProductMovement;
use App\Models\ProductSize;
use App\Models\Warehouse;
use App\Models\User;
use App\Models\Product;
use Illuminate\Http\Request;

class ProductMovementController extends Controller
{
    /**
     * عرض حركات الطلبات فقط
     */
    public function orderMovements(Request $request)
    {
        $query = ProductMovement::with(['product', 'size', 'warehouse', 'order', 'user'])
            ->whereNotNull('order_id'); // فقط الحركات المرتبطة بطلبات

        // فلتر حسب المخزن
        if ($request->filled('warehouse_id')) {
            $query->byWarehouse($request->warehouse_id);
        }

        // فلتر حسب المنتج
        if ($request->filled('product_id')) {
            $query->byProduct($request->product_id);
        }

        // فلتر حسب القياس
        if ($request->filled('size_id')) {
            $query->bySize($request->size_id);
        }

        // فلتر حسب نوع الحركة
        if ($request->filled('movement_type')) {
            $query->byMovementType($request->movement_type);
        }

        // فلتر حسب المستخدم
        if ($request->filled('user_id')) {
            $query->byUser($request->user_id);
        }

        // فلتر حسب حالة الطلب
        if ($request->filled('order_status')) {
            $query->byOrderStatus($request->order_status);
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

        // فلتر حسب المخازن المخصصة للمستخدم
        if (auth()->user()->isSupplier()) {
            $warehouseIds = auth()->user()->warehouses->pluck('id')->toArray();
            $query->whereIn('warehouse_id', $warehouseIds);
        }

        $perPage = $request->input('per_page', 20);
        $movements = $query->latest()->paginate($perPage)->appends($request->except('page'));

        // البيانات للفلاتر
        $warehouses = Warehouse::all();
        $users = User::all();
        $movementTypes = [
            'add' => 'إضافة',
            'sale' => 'بيع',
            'confirm' => 'تقييد',
            'cancel' => 'إلغاء',
            'return' => 'استرجاع',
            'delete' => 'حذف',
            'restore' => 'استرجاع من الحذف',
        ];

        $orderStatuses = [
            'pending' => 'غير مقيد',
            'confirmed' => 'مقيد',
            'cancelled' => 'ملغي',
            'returned' => 'مسترجعة',
            'exchanged' => 'مستبدلة',
        ];

        // إحصائيات سريعة
        $stats = [
            'total_additions' => ProductMovement::byMovementType('add')->sum('quantity'),
            'total_sales' => abs(ProductMovement::byMovementType('sale')->sum('quantity')),
            'total_returns' => ProductMovement::whereIn('movement_type', ['return', 'cancel', 'delete'])->sum('quantity'),
        ];

        return view('admin.order-movements.index', compact(
            'movements',
            'warehouses',
            'users',
            'movementTypes',
            'orderStatuses',
            'stats'
        ));
    }

    /**
     * عرض حركات قياس معين
     */
    public function show(Request $request, Warehouse $warehouse, Product $product)
    {
        // التحقق من أن المنتج يخص المخزن
        if ($product->warehouse_id !== $warehouse->id) {
            abort(404);
        }

        // التحقق من الصلاحيات
        $this->authorize('view', $warehouse);
        $this->authorize('view', $product);

        // الحصول على size من query parameter
        $sizeId = $request->input('size');
        if (!$sizeId) {
            abort(404, 'القياس مطلوب');
        }

        $size = ProductSize::where('id', $sizeId)
            ->where('product_id', $product->id)
            ->firstOrFail();

        $perPage = $request->input('per_page', 20);
        $movements = ProductMovement::with(['product', 'order', 'user'])
            ->where('size_id', $size->id)
            ->latest()
            ->paginate($perPage)
            ->appends($request->except('page'));

        $movementTypes = [
            'add' => 'إضافة',
            'sale' => 'بيع',
            'confirm' => 'تقييد',
            'cancel' => 'إلغاء',
            'return' => 'استرجاع',
            'delete' => 'حذف',
            'restore' => 'استرجاع من الحذف',
        ];

        // إحصائيات للقياس
        $stats = [
            'current_quantity' => $size->quantity,
            'total_additions' => ProductMovement::where('size_id', $size->id)->byMovementType('add')->sum('quantity'),
            'total_sales' => abs(ProductMovement::where('size_id', $size->id)->byMovementType('sale')->sum('quantity')),
            'total_returns' => ProductMovement::where('size_id', $size->id)->whereIn('movement_type', ['return', 'cancel', 'delete'])->sum('quantity'),
        ];

        return view('admin.movements.show', compact(
            'movements',
            'product',
            'size',
            'warehouse',
            'movementTypes',
            'stats'
        ));
    }

    /**
     * عرض جميع حركات المواد (طلبات + إدارة المخزن)
     */
    public function productMovements(Request $request)
    {
        $query = ProductMovement::with(['product', 'size', 'warehouse', 'order', 'user']);

        // فلتر حسب المخزن
        if ($request->filled('warehouse_id')) {
            $query->byWarehouse($request->warehouse_id);
        }

        // فلتر حسب المنتج
        if ($request->filled('product_id')) {
            $query->byProduct($request->product_id);
        }

        // فلتر حسب القياس
        if ($request->filled('size_id')) {
            $query->bySize($request->size_id);
        }

        // فلتر حسب البحث بالاسم أو الكود
        if ($request->filled('product_search')) {
            $productSearch = $request->product_search;
            $query->whereHas('product', function($q) use ($productSearch) {
                $q->where('name', 'like', '%' . $productSearch . '%')
                  ->orWhere('code', 'like', '%' . $productSearch . '%');
            });
        }

        // فلتر حسب نوع الحركة
        if ($request->filled('movement_type')) {
            $query->byMovementType($request->movement_type);
        }

        // فلتر حسب المستخدم
        if ($request->filled('user_id')) {
            $query->byUser($request->user_id);
        }

        // فلتر حسب حالة الطلب
        if ($request->filled('order_status')) {
            $query->byOrderStatus($request->order_status);
        }

        // فلتر حسب نوع المصدر
        if ($request->filled('source_type')) {
            if ($request->source_type === 'order') {
                $query->whereNotNull('order_id');
            } elseif ($request->source_type === 'manual') {
                $query->whereNull('order_id');
            }
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

        // فلتر حسب المخازن المخصصة للمستخدم
        if (auth()->user()->isSupplier()) {
            $warehouseIds = auth()->user()->warehouses->pluck('id')->toArray();
            $query->whereIn('warehouse_id', $warehouseIds);
        }

        $perPage = $request->input('per_page', 20);
        $movements = $query->latest()->paginate($perPage)->appends($request->except('page'));

        // البيانات للفلاتر
        if (auth()->user()->isAdmin()) {
            $warehouses = Warehouse::all();
        } else {
            $warehouses = auth()->user()->warehouses;
        }
        $users = User::all();
        $movementTypes = [
            'add' => 'إضافة',
            'sale' => 'بيع',
            'confirm' => 'تقييد',
            'cancel' => 'إلغاء',
            'return' => 'استرجاع',
            'delete' => 'حذف',
            'restore' => 'استرجاع من الحذف',
        ];

        $orderStatuses = [
            'pending' => 'غير مقيد',
            'confirmed' => 'مقيد',
            'cancelled' => 'ملغي',
            'returned' => 'مسترجعة',
            'exchanged' => 'مستبدلة',
        ];

        $sourceTypes = [
            'order' => 'طلب',
            'manual' => 'إدارة المخزن',
        ];

        // إحصائيات سريعة
        $stats = [
            'total_additions' => ProductMovement::byMovementType('add')->sum('quantity'),
            'total_sales' => abs(ProductMovement::byMovementType('sale')->sum('quantity')),
            'total_returns' => ProductMovement::whereIn('movement_type', ['return', 'cancel', 'delete'])->sum('quantity'),
            'order_movements' => ProductMovement::whereNotNull('order_id')->count(),
            'manual_movements' => ProductMovement::whereNull('order_id')->count(),
        ];

        // تحميل المنتجات حسب المخازن المتاحة
        if (auth()->user()->isSupplier()) {
            $warehouseIds = auth()->user()->warehouses->pluck('id')->toArray();
            $products = Product::whereIn('warehouse_id', $warehouseIds)->get();
            $sizes = ProductSize::whereHas('product', function($q) use ($warehouseIds) {
                $q->whereIn('warehouse_id', $warehouseIds);
            })->get();
        } else {
            $products = Product::with('warehouse')->get();
            $sizes = ProductSize::with('product')->get();
        }

        return view('admin.product-movements.index', compact(
            'movements',
            'warehouses',
            'products',
            'sizes',
            'users',
            'movementTypes',
            'orderStatuses',
            'sourceTypes',
            'stats'
        ));
    }
}
