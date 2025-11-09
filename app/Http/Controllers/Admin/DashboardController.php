<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ProfitRecord;
use App\Models\Warehouse;
use App\Models\Product;
use App\Models\Order;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        return view('admin.dashboard');
    }

    /**
     * عرض صفحة التقارير (للمدير فقط)
     */
    public function reports(Request $request)
    {
        // التأكد من أن المستخدم مدير فقط
        if (!Auth::user()->isAdmin()) {
            abort(403, 'غير مصرح لك بالوصول إلى هذه الصفحة.');
        }

        // جلب البيانات للفلاتر
        $warehouses = Warehouse::all();
        $products = Product::all();
        $delegates = User::where('role', 'delegate')->get();
        $suppliers = User::whereIn('role', ['admin', 'supplier'])->get();

        // بناء query للفلاتر
        $query = ProfitRecord::query();

        // فلتر المخزن
        if ($request->filled('warehouse_id')) {
            $query->where('warehouse_id', $request->warehouse_id);
        }

        // فلتر المنتج
        if ($request->filled('product_id')) {
            $query->where('product_id', $request->product_id);
        }

        // فلتر المندوب
        if ($request->filled('delegate_id')) {
            $query->where('delegate_id', $request->delegate_id);
        }

        // فلتر المجهز (confirmed_by) - نحتاج البحث في الطلبات المرتبطة
        if ($request->filled('confirmed_by')) {
            $orderIds = Order::where('confirmed_by', $request->confirmed_by)->pluck('id');
            $query->whereIn('order_id', $orderIds);
        }

        // فلتر التاريخ
        if ($request->filled('date_from')) {
            $query->where('record_date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->where('record_date', '<=', $request->date_to);
        }

        // فلتر حالة الطلب
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // حساب الإجماليات للكاردات - نحسب مباشرة من order_items لضمان الدقة 100%
        // لا نستخدم profit_records مباشرة لأن هناك سجلات متعددة (order, product, warehouse) لكل طلب

        // بناء query للطلبات حسب الفلاتر
        $buildOrdersQuery = function($status) use ($request) {
            $ordersQuery = Order::where('status', $status);

            // فلتر المخزن
            if ($request->filled('warehouse_id')) {
                $ordersQuery->whereHas('items.product', function($q) use ($request) {
                    $q->where('warehouse_id', $request->warehouse_id);
                });
            }

            // فلتر المنتج
            if ($request->filled('product_id')) {
                $ordersQuery->whereHas('items', function($q) use ($request) {
                    $q->where('product_id', $request->product_id);
                });
            }

            // فلتر المندوب
            if ($request->filled('delegate_id')) {
                $ordersQuery->where('delegate_id', $request->delegate_id);
            }

            // فلتر المجهز
            if ($request->filled('confirmed_by')) {
                $ordersQuery->where('confirmed_by', $request->confirmed_by);
            }

            // فلتر التاريخ (حسب created_at للطلبات pending و confirmed_at للمقيدة)
            if ($status === 'pending') {
                if ($request->filled('date_from')) {
                    $ordersQuery->whereDate('created_at', '>=', $request->date_from);
                }
                if ($request->filled('date_to')) {
                    $ordersQuery->whereDate('created_at', '<=', $request->date_to);
                }
            } else if ($status === 'confirmed') {
                if ($request->filled('date_from')) {
                    $ordersQuery->whereDate('confirmed_at', '>=', $request->date_from);
                }
                if ($request->filled('date_to')) {
                    $ordersQuery->whereDate('confirmed_at', '<=', $request->date_to);
                }
            }

            // فلتر حالة الطلب (مطبق بالفعل في where('status'))

            return $ordersQuery;
        };

        // حساب الأرباح والمبالغ للطلبات المقيدة (confirmed) - من order_items مباشرة
        $confirmedOrdersQuery = $buildOrdersQuery('confirmed');
        $confirmedOrderIds = $confirmedOrdersQuery->pluck('id');

        $totalActualProfit = 0;
        $confirmedTotalAmount = 0;
        if ($confirmedOrderIds->count() > 0) {
            // حساب الربح الحالي من order_items مباشرة
            $totalActualProfit = DB::table('order_items')
                ->join('products', 'order_items.product_id', '=', 'products.id')
                ->whereIn('order_items.order_id', $confirmedOrderIds)
                ->whereNotNull('products.purchase_price')
                ->where('products.purchase_price', '>', 0)
                ->selectRaw('SUM((order_items.unit_price - products.purchase_price) * order_items.quantity) as total_profit')
                ->value('total_profit') ?? 0;

            // حساب المبلغ الإجمالي من order_items مباشرة
            $confirmedTotalAmount = DB::table('order_items')
                ->whereIn('order_id', $confirmedOrderIds)
                ->sum('subtotal') ?? 0;
        }

        // حساب الأرباح المتوقعة للطلبات غير المقيدة (pending) - من order_items مباشرة
        $pendingOrdersQuery = $buildOrdersQuery('pending');
        $pendingOrderIds = $pendingOrdersQuery->pluck('id');

        $totalExpectedProfit = 0;
        $pendingTotalAmount = 0;
        if ($pendingOrderIds->count() > 0) {
            // حساب الربح المتوقع من order_items مباشرة
            $totalExpectedProfit = DB::table('order_items')
                ->join('products', 'order_items.product_id', '=', 'products.id')
                ->whereIn('order_items.order_id', $pendingOrderIds)
                ->whereNotNull('products.purchase_price')
                ->where('products.purchase_price', '>', 0)
                ->selectRaw('SUM((order_items.unit_price - products.purchase_price) * order_items.quantity) as total_profit')
                ->value('total_profit') ?? 0;

            // حساب المبلغ الإجمالي للطلبات pending
            $pendingTotalAmount = DB::table('order_items')
                ->whereIn('order_id', $pendingOrderIds)
                ->sum('subtotal') ?? 0;
        }

        // المبلغ الإجمالي = confirmed + pending
        $totalAmount = $confirmedTotalAmount + $pendingTotalAmount;

        // حساب إجمالي الفروقات من الطلبات المقيدة
        $totalMarginAmount = 0;
        if ($confirmedOrderIds->count() > 0) {
            $totalMarginAmount = DB::table('orders')
                ->whereIn('id', $confirmedOrderIds)
                ->whereNotNull('profit_margin_at_confirmation')
                ->sum('profit_margin_at_confirmation') ?? 0;
        }

        // حساب قيمة المخازن مباشرة من البيانات الحالية
        $profitCalculator = new \App\Services\ProfitCalculator();
        $totalWarehouseValue = 0;

        // بناء query للمخازن حسب الفلاتر
        $warehousesQuery = Warehouse::query();
        if ($request->filled('warehouse_id')) {
            $warehousesQuery->where('id', $request->warehouse_id);
        }
        $filteredWarehouses = $warehousesQuery->get();

        foreach ($filteredWarehouses as $warehouse) {
            // تطبيق فلتر المنتج إذا كان موجوداً
            if ($request->filled('product_id')) {
                $hasProduct = $warehouse->products()->where('id', $request->product_id)->exists();
                if (!$hasProduct) {
                    continue;
                }
            }
            $totalWarehouseValue += $profitCalculator->calculateWarehouseValue($warehouse);
        }

        // حساب قيمة المنتجات مباشرة من البيانات الحالية
        $totalProductValue = 0;
        $productsQuery = Product::query();
        if ($request->filled('product_id')) {
            $productsQuery->where('id', $request->product_id);
        }
        if ($request->filled('warehouse_id')) {
            $productsQuery->where('warehouse_id', $request->warehouse_id);
        }
        $filteredProducts = $productsQuery->get();

        foreach ($filteredProducts as $product) {
            $totalProductValue += $profitCalculator->calculateProductValue($product);
        }

        // بيانات الشارتات - نحسب من order_items مباشرة لضمان الدقة

        // 1. Line Chart: الأرباح حسب التاريخ
        $dateFrom = $request->filled('date_from') ? $request->date_from : now()->subDays(30)->format('Y-m-d');
        $dateTo = $request->filled('date_to') ? $request->date_to : now()->format('Y-m-d');

        // حساب الأرباح حسب التاريخ من confirmed orders
        $confirmedOrdersByDateQuery = DB::table('orders')
            ->join('order_items', 'orders.id', '=', 'order_items.order_id')
            ->join('products', 'order_items.product_id', '=', 'products.id')
            ->where('orders.status', 'confirmed')
            ->whereNotNull('orders.confirmed_at')
            ->whereNotNull('products.purchase_price')
            ->where('products.purchase_price', '>', 0)
            ->whereBetween(DB::raw('DATE(orders.confirmed_at)'), [$dateFrom, $dateTo]);

        // تطبيق الفلاتر
        if ($request->filled('warehouse_id')) {
            $confirmedOrdersByDateQuery->where('products.warehouse_id', $request->warehouse_id);
        }
        if ($request->filled('product_id')) {
            $confirmedOrdersByDateQuery->where('order_items.product_id', $request->product_id);
        }
        if ($request->filled('delegate_id')) {
            $confirmedOrdersByDateQuery->where('orders.delegate_id', $request->delegate_id);
        }
        if ($request->filled('confirmed_by')) {
            $confirmedOrdersByDateQuery->where('orders.confirmed_by', $request->confirmed_by);
        }

        // حساب الأرباح حسب التاريخ للطلبات المقيدة
        $actualProfitsByDate = $confirmedOrdersByDateQuery
            ->select(
                DB::raw('DATE(orders.confirmed_at) as date'),
                DB::raw('SUM((order_items.unit_price - products.purchase_price) * order_items.quantity) as actual')
            )
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->pluck('actual', 'date')
            ->map(function($value) {
                return (float)$value;
            })
            ->toArray();

        // حساب الأرباح حسب التاريخ للطلبات pending
        $pendingOrdersByDateQuery = DB::table('orders')
            ->join('order_items', 'orders.id', '=', 'order_items.order_id')
            ->join('products', 'order_items.product_id', '=', 'products.id')
            ->where('orders.status', 'pending')
            ->whereNotNull('products.purchase_price')
            ->where('products.purchase_price', '>', 0)
            ->whereBetween(DB::raw('DATE(orders.created_at)'), [$dateFrom, $dateTo]);

        if ($request->filled('warehouse_id')) {
            $pendingOrdersByDateQuery->where('products.warehouse_id', $request->warehouse_id);
        }
        if ($request->filled('product_id')) {
            $pendingOrdersByDateQuery->where('order_items.product_id', $request->product_id);
        }
        if ($request->filled('delegate_id')) {
            $pendingOrdersByDateQuery->where('orders.delegate_id', $request->delegate_id);
        }

        $expectedProfitsByDate = $pendingOrdersByDateQuery
            ->select(
                DB::raw('DATE(orders.created_at) as date'),
                DB::raw('SUM((order_items.unit_price - products.purchase_price) * order_items.quantity) as expected')
            )
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->pluck('expected', 'date')
            ->map(function($value) {
                return (float)$value;
            })
            ->toArray();

        // دمج البيانات حسب التاريخ
        $allDates = array_unique(array_merge(array_keys($actualProfitsByDate), array_keys($expectedProfitsByDate)));
        sort($allDates);

        // التأكد من وجود بيانات على الأقل (حتى لو كانت فارغة)
        if (empty($allDates)) {
            // إذا لم تكن هناك بيانات، نعرض آخر 30 يوم مع قيم 0
            $allDates = [];
            for ($i = 29; $i >= 0; $i--) {
                $allDates[] = now()->subDays($i)->format('Y-m-d');
            }
        }

        $lineChartData = [
            'categories' => $allDates,
            'actual' => array_map(function($date) use ($actualProfitsByDate) {
                return (float)($actualProfitsByDate[$date] ?? 0);
            }, $allDates),
            'expected' => array_map(function($date) use ($expectedProfitsByDate) {
                return (float)($expectedProfitsByDate[$date] ?? 0);
            }, $allDates),
        ];

        // حساب القطع المبيعة لكل مخزن حسب التاريخ
        $confirmedOrdersForSoldItems = $buildOrdersQuery('confirmed');
        $confirmedOrderIdsForSoldItems = $confirmedOrdersForSoldItems->pluck('id');

        $totalSoldItems = 0;
        $soldItemsByWarehouse = collect();

        if ($confirmedOrderIdsForSoldItems->count() > 0) {
            // إجمالي القطع المبيعة
            $totalSoldItems = DB::table('order_items')
                ->whereIn('order_id', $confirmedOrderIdsForSoldItems)
                ->sum('quantity') ?? 0;

            // القطع المبيعة لكل مخزن
            $soldItemsByWarehouseQuery = DB::table('order_items')
                ->join('products', 'order_items.product_id', '=', 'products.id')
                ->whereIn('order_items.order_id', $confirmedOrderIdsForSoldItems)
                ->whereNotNull('products.warehouse_id')
                ->select(
                    'products.warehouse_id',
                    DB::raw('SUM(order_items.quantity) as total_quantity')
                )
                ->groupBy('products.warehouse_id')
                ->get();

            $soldItemsByWarehouse = $soldItemsByWarehouseQuery->map(function($item) {
                $warehouse = Warehouse::find($item->warehouse_id);
                return [
                    'warehouse_id' => $item->warehouse_id,
                    'warehouse_name' => $warehouse ? $warehouse->name : 'غير محدد',
                    'total_quantity' => (int)$item->total_quantity,
                ];
            });
        }

        // 2. Column Chart: مقارنة الربح الحالي vs المتوقع (إجمالي)
        $columnChartData = [
            'actual' => (float)$totalActualProfit,
            'expected' => (float)$totalExpectedProfit,
        ];

        // 3. Pie Chart: توزيع الأرباح حسب المخازن - من order_items مباشرة
        $confirmedOrdersForPie = $buildOrdersQuery('confirmed');
        $confirmedOrderIdsForPie = $confirmedOrdersForPie->pluck('id');

        $profitsByWarehouseArray = [];
        if ($confirmedOrderIdsForPie->count() > 0) {
            $warehouseProfits = DB::table('order_items')
                ->join('products', 'order_items.product_id', '=', 'products.id')
                ->whereIn('order_items.order_id', $confirmedOrderIdsForPie)
                ->whereNotNull('products.purchase_price')
                ->where('products.purchase_price', '>', 0)
                ->whereNotNull('products.warehouse_id')
                ->select(
                    'products.warehouse_id',
                    DB::raw('SUM((order_items.unit_price - products.purchase_price) * order_items.quantity) as total_profit')
                )
                ->groupBy('products.warehouse_id')
                ->get();

            foreach ($warehouseProfits as $profit) {
                $warehouse = Warehouse::find($profit->warehouse_id);
                $profitsByWarehouseArray[] = [
                    'warehouse_id' => $profit->warehouse_id,
                    'total_profit' => (float)$profit->total_profit,
                    'warehouse_name' => $warehouse ? $warehouse->name : 'غير محدد'
                ];
            }
        }

        $profitsByWarehouse = collect($profitsByWarehouseArray);

        $pieChartData = [
            'labels' => $profitsByWarehouse->pluck('warehouse_name')->toArray(),
            'values' => $profitsByWarehouse->pluck('total_profit')->toArray(),
        ];

        // 4. Bar Chart: الأرباح حسب المندوبين - من order_items مباشرة
        $confirmedOrdersForBar = $buildOrdersQuery('confirmed');
        $confirmedOrderIdsForBar = $confirmedOrdersForBar->pluck('id');

        $profitsByDelegateArray = [];
        if ($confirmedOrderIdsForBar->count() > 0) {
            $delegateProfits = DB::table('orders')
                ->join('order_items', 'orders.id', '=', 'order_items.order_id')
                ->join('products', 'order_items.product_id', '=', 'products.id')
                ->whereIn('orders.id', $confirmedOrderIdsForBar)
                ->whereNotNull('products.purchase_price')
                ->where('products.purchase_price', '>', 0)
                ->whereNotNull('orders.delegate_id')
                ->select(
                    'orders.delegate_id',
                    DB::raw('SUM((order_items.unit_price - products.purchase_price) * order_items.quantity) as total_profit')
                )
                ->groupBy('orders.delegate_id')
                ->get();

            foreach ($delegateProfits as $profit) {
                $delegate = User::find($profit->delegate_id);
                $profitsByDelegateArray[] = [
                    'name' => $delegate ? $delegate->name : 'غير محدد',
                    'value' => (float)$profit->total_profit,
                ];
            }
        }

        $profitsByDelegate = collect($profitsByDelegateArray);

        $barChartData = [
            'labels' => $profitsByDelegate->pluck('name')->toArray(),
            'values' => $profitsByDelegate->pluck('value')->toArray(),
        ];

        // 5. Area Chart: قيمة المخازن عبر الزمن
        // إذا كانت هناك سجلات في profit_records، نستخدمها
        // وإلا نعرض القيمة الحالية فقط
        $warehouseValueByDate = (clone $query)
            ->whereNotNull('warehouse_id')
            ->whereBetween('record_date', [$dateFrom, $dateTo])
            ->select(
                DB::raw('DATE(record_date) as date'),
                DB::raw('MAX(warehouse_value) as max_value')
            )
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // إذا لم تكن هناك بيانات تاريخية، نعرض القيمة الحالية
        if ($warehouseValueByDate->isEmpty() && $totalWarehouseValue > 0) {
            $areaChartData = [
                'categories' => [now()->format('Y-m-d')],
                'values' => [(float)$totalWarehouseValue],
            ];
        } else {
            $areaChartData = [
                'categories' => $warehouseValueByDate->pluck('date')->toArray(),
                'values' => $warehouseValueByDate->pluck('max_value')->map(fn($v) => (float)$v)->toArray(),
            ];
        }

        return view('admin.reports.index', compact(
            'warehouses',
            'products',
            'delegates',
            'suppliers',
            'totalActualProfit',
            'totalExpectedProfit',
            'totalWarehouseValue',
            'totalProductValue',
            'totalAmount',
            'totalMarginAmount',
            'totalSoldItems',
            'soldItemsByWarehouse',
            'lineChartData',
            'columnChartData',
            'pieChartData',
            'barChartData',
            'areaChartData'
        ));
    }
}
