<?php

namespace App\Http\Controllers\Mobile\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductSize;
use App\Models\Warehouse;
use App\Models\User;
use App\Models\Expense;
use App\Models\ProfitRecord;
use App\Services\ProfitCalculator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MobileAdminDashboardController extends Controller
{
    /**
     * جلب بيانات التقارير الشاملة للموبايل
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function reports(Request $request)
    {
        $user = Auth::user();

        // التأكد من أن المستخدم مدير فقط (كما في نظام الويب)
        if (!$user || !$user->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'غير مصرح للوصول إلى هذه البيانات.',
                'error_code' => 'FORBIDDEN',
            ], 403);
        }

        try {
            // بيانات الفلاتر (للتأكد من مطابقة الويب)
            $dateFrom = $request->filled('date_from') ? $request->date_from : now()->subDays(30)->format('Y-m-d');
            $dateTo = $request->filled('date_to') ? $request->date_to : now()->format('Y-m-d');

            // 1. حساب الإجماليات (Metrics)
            $metrics = $this->calculateMetrics($request, $dateFrom, $dateTo);

            // 2. بيانات الشارتات (Charts)
            $charts = $this->calculateCharts($request, $dateFrom, $dateTo);

            // 3. تفاصيل المخازن (Warehouse Breakdown)
            $warehouseBreakdown = $this->calculateWarehouseBreakdown($request, $dateFrom, $dateTo);

            // 4. قوائم المنتجات (Product Ranking)
            $rankings = $this->calculateRankings($request, $dateFrom, $dateTo);

            return response()->json([
                'success' => true,
                'data' => [
                    'metrics' => $metrics,
                    'charts' => $charts,
                    'warehouse_breakdown' => $warehouseBreakdown,
                    'rankings' => $rankings,
                    'filters' => [
                        'date_from' => $dateFrom,
                        'date_to' => $dateTo,
                        'warehouse_id' => $request->warehouse_id,
                        'product_id' => $request->product_id,
                        'delegate_id' => $request->delegate_id,
                        'confirmed_by' => $request->confirmed_by,
                        'status' => $request->status,
                    ]
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('MobileAdminDashboardController: Reports failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء جلب بيانات التقارير.',
                'error_code' => 'REPORTS_ERROR',
            ], 500);
        }
    }

    private function calculateMetrics(Request $request, $dateFrom, $dateTo)
    {
        $buildOrdersQuery = function ($status) use ($request, $dateFrom, $dateTo) {
            $ordersQuery = Order::where('status', $status);

            if ($request->filled('warehouse_id')) {
                $ordersQuery->whereHas('items.product', function ($q) use ($request) {
                    $q->where('warehouse_id', $request->warehouse_id);
                });
            }

            if ($request->filled('product_id')) {
                $ordersQuery->whereHas('items', function ($q) use ($request) {
                    $q->where('product_id', $request->product_id);
                });
            }

            if ($request->filled('delegate_id')) {
                $ordersQuery->where('delegate_id', $request->delegate_id);
            }

            if ($request->filled('confirmed_by')) {
                $ordersQuery->where('confirmed_by', $request->confirmed_by);
            }

            if ($status === 'pending') {
                $ordersQuery->whereDate('created_at', '>=', $dateFrom)
                    ->whereDate('created_at', '<=', $dateTo);
            } else if ($status === 'confirmed') {
                $ordersQuery->whereDate('confirmed_at', '>=', $dateFrom)
                    ->whereDate('confirmed_at', '<=', $dateTo);
            }

            return $ordersQuery;
        };

        // Actual Profit & Confirmed Total
        $totalActualProfit = 0;
        $confirmedTotalAmount = 0;
        $totalMarginAmount = 0;
        $totalCommissions = 0;
        $confirmedOrderIds = collect();

        if (!$request->filled('status') || $request->status === 'confirmed') {
            $confirmedOrdersQuery = $buildOrdersQuery('confirmed');
            $confirmedOrderIds = $confirmedOrdersQuery->pluck('id');

            if ($confirmedOrderIds->count() > 0) {
                $totalActualProfit = DB::table('order_items')
                    ->join('products', 'order_items.product_id', '=', 'products.id')
                    ->whereIn('order_items.order_id', $confirmedOrderIds)
                    ->whereNotNull('products.purchase_price')
                    ->where('products.purchase_price', '>', 0)
                    ->selectRaw('SUM((order_items.unit_price - products.purchase_price) * order_items.quantity) as total_profit')
                    ->value('total_profit') ?? 0;

                $confirmedTotalAmount = DB::table('order_items')
                    ->whereIn('order_id', $confirmedOrderIds)
                    ->sum('subtotal') ?? 0;

                $totalMarginAmount = DB::table('orders')
                    ->whereIn('id', $confirmedOrderIds)
                    ->whereNotNull('profit_margin_at_confirmation')
                    ->sum('profit_margin_at_confirmation') ?? 0;

                $totalCommissions = $totalMarginAmount; // في هذا النظام الهامش هو العمولة
            }
        }

        // Expected Profit & Pending Total
        $totalExpectedProfit = 0;
        $pendingTotalAmount = 0;

        if (!$request->filled('status') || $request->status === 'pending') {
            $pendingOrdersQuery = $buildOrdersQuery('pending');
            $pendingOrderIds = $pendingOrdersQuery->pluck('id');

            if ($pendingOrderIds->count() > 0) {
                $totalExpectedProfit = DB::table('order_items')
                    ->join('products', 'order_items.product_id', '=', 'products.id')
                    ->whereIn('order_items.order_id', $pendingOrderIds)
                    ->whereNotNull('products.purchase_price')
                    ->where('products.purchase_price', '>', 0)
                    ->selectRaw('SUM((order_items.unit_price - products.purchase_price) * order_items.quantity) as total_profit')
                    ->value('total_profit') ?? 0;

                $pendingTotalAmount = DB::table('order_items')
                    ->whereIn('order_id', $pendingOrderIds)
                    ->sum('subtotal') ?? 0;
            }
        }

        // Product Value
        $profitCalculator = new ProfitCalculator();
        $totalProductValue = 0;
        $productsQuery = Product::query();
        if ($request->filled('product_id'))
            $productsQuery->where('id', $request->product_id);
        if ($request->filled('warehouse_id'))
            $productsQuery->where('warehouse_id', $request->warehouse_id);

        $productsQuery->chunk(200, function ($products) use ($profitCalculator, &$totalProductValue) {
            foreach ($products as $product) {
                $totalProductValue += $profitCalculator->calculateProductValue($product);
            }
        });

        // Expenses
        $totalExpenses = Expense::byDateRange($dateFrom, $dateTo)->sum('amount') ?? 0;

        // Sold Items
        $statusForSold = $request->get('status', 'confirmed');
        $soldQuery = $buildOrdersQuery($statusForSold);
        $soldOrderIds = $soldQuery->pluck('id');
        $totalSoldItems = 0;
        if ($soldOrderIds->count() > 0) {
            $totalSoldItems = DB::table('order_items')
                ->whereIn('order_id', $soldOrderIds)
                ->sum('quantity') ?? 0;
        }

        // Warehouse Pieces
        $totalWarehousePiecesQuery = ProductSize::query()
            ->join('products', 'product_sizes.product_id', '=', 'products.id')
            ->whereNotNull('products.warehouse_id');
        if ($request->filled('warehouse_id'))
            $totalWarehousePiecesQuery->where('products.warehouse_id', $request->warehouse_id);
        if ($request->filled('product_id'))
            $totalWarehousePiecesQuery->where('product_sizes.product_id', $request->product_id);
        $totalWarehousePieces = $totalWarehousePiecesQuery->sum('product_sizes.quantity') ?? 0;

        return [
            'total_actual_profit' => (float) $totalActualProfit,
            'total_expected_profit' => (float) $totalExpectedProfit,
            'confirmed_total_amount' => (float) $confirmedTotalAmount,
            'pending_total_amount' => (float) $pendingTotalAmount,
            'total_product_value' => (float) $totalProductValue,
            'total_margin_amount' => (float) $totalMarginAmount,
            'total_commissions' => (float) $totalCommissions,
            'total_expenses' => (float) $totalExpenses,
            'total_sold_items' => (int) $totalSoldItems,
            'total_warehouse_pieces' => (int) $totalWarehousePieces,
        ];
    }

    private function calculateWarehouseBreakdown(Request $request, $dateFrom, $dateTo)
    {
        $warehouses = Warehouse::all();
        $breakdown = [];

        foreach ($warehouses as $warehouse) {
            $orderIds = Order::where('status', 'confirmed')
                ->whereBetween(DB::raw('DATE(confirmed_at)'), [$dateFrom, $dateTo])
                ->whereHas('items.product', function ($q) use ($warehouse) {
                    $q->where('warehouse_id', $warehouse->id);
                })
                ->pluck('id');

            if ($orderIds->count() > 0) {
                $soldCount = DB::table('order_items')
                    ->whereIn('order_id', $orderIds)
                    ->sum('quantity') ?? 0;

                $data = DB::table('order_items')
                    ->join('products', 'order_items.product_id', '=', 'products.id')
                    ->whereIn('order_items.order_id', $orderIds)
                    ->selectRaw('
                        SUM(order_items.unit_price * order_items.quantity) as selling_amount,
                        SUM(products.purchase_price * order_items.quantity) as purchase_amount
                    ')
                    ->first();

                $breakdown[] = [
                    'id' => $warehouse->id,
                    'name' => $warehouse->name,
                    'sold_count' => (int) $soldCount,
                    'selling_amount' => (float) $data->selling_amount,
                    'purchase_amount' => (float) $data->purchase_amount,
                    'profit' => (float) ($data->selling_amount - $data->purchase_amount),
                ];
            }
        }

        return $breakdown;
    }

    private function calculateCharts(Request $request, $dateFrom, $dateTo)
    {
        // 1. Line Chart: Daily Profit
        $actualProfitsByDate = DB::table('orders')
            ->join('order_items', 'orders.id', '=', 'order_items.order_id')
            ->join('products', 'order_items.product_id', '=', 'products.id')
            ->where('orders.status', 'confirmed')
            ->whereBetween(DB::raw('DATE(orders.confirmed_at)'), [$dateFrom, $dateTo])
            ->select(DB::raw('DATE(orders.confirmed_at) as date'), DB::raw('SUM((order_items.unit_price - products.purchase_price) * order_items.quantity) as profit'))
            ->groupBy('date')->pluck('profit', 'date')->toArray();

        // 2. Pie Chart: Warehouse Distribution
        $warehouseProfits = DB::table('order_items')
            ->join('products', 'order_items.product_id', '=', 'products.id')
            ->join('warehouses', 'products.warehouse_id', '=', 'warehouses.id')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->where('orders.status', 'confirmed')
            ->whereBetween(DB::raw('DATE(orders.confirmed_at)'), [$dateFrom, $dateTo])
            ->select('warehouses.name', DB::raw('SUM((order_items.unit_price - products.purchase_price) * order_items.quantity) as profit'))
            ->groupBy('warehouses.name')->get()->map(fn($item) => ['label' => $item->name, 'value' => (float) $item->profit]);

        // 3. Bar Chart: Delegate Performance
        $delegateProfits = DB::table('orders')
            ->join('order_items', 'orders.id', '=', 'order_items.order_id')
            ->join('products', 'order_items.product_id', '=', 'products.id')
            ->join('users', 'orders.delegate_id', '=', 'users.id')
            ->where('orders.status', 'confirmed')
            ->whereBetween(DB::raw('DATE(orders.confirmed_at)'), [$dateFrom, $dateTo])
            ->select('users.name', DB::raw('SUM((order_items.unit_price - products.purchase_price) * order_items.quantity) as profit'))
            ->groupBy('users.name')->get()->map(fn($item) => ['label' => $item->name, 'value' => (float) $item->profit]);

        return [
            'daily_profit' => $actualProfitsByDate,
            'warehouse_distribution' => $warehouseProfits,
            'delegate_performance' => $delegateProfits,
        ];
    }

    private function calculateRankings(Request $request, $dateFrom, $dateTo)
    {
        $baseQuery = DB::table('order_items')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->join('products', 'order_items.product_id', '=', 'products.id')
            ->where('orders.status', 'confirmed')
            ->whereBetween(DB::raw('DATE(orders.confirmed_at)'), [$dateFrom, $dateTo])
            ->select('products.id', 'products.name', 'products.code', DB::raw('SUM(order_items.quantity) as total_sold'))
            ->groupBy('products.id', 'products.name', 'products.code');

        $topScaling = (clone $baseQuery)->orderByDesc('total_sold')->limit(10)->get();
        $leastSelling = (clone $baseQuery)->orderBy('total_sold')->limit(10)->get();

        $format = function ($item) {
            $product = Product::with('sizes')->find($item->id);
            return [
                'id' => $item->id,
                'name' => $item->name,
                'code' => $item->code,
                'total_sold' => (int) $item->total_sold,
                'remaining_quantity' => $product ? (int) $product->sizes->sum('quantity') : 0,
            ];
        };

        return [
            'top_selling' => $topScaling->map($format),
            'least_selling' => $leastSelling->map($format),
        ];
    }
}
