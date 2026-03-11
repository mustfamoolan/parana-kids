<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Expense;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductMovement;
use App\Models\ReturnItem;
use App\Models\SalesReport;
use App\Models\Setting;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\Investment;
use App\Models\InvestmentTarget;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class SalesReportApiController extends Controller
{
    private $investmentCache = [];
    private $adminPercentageCache = [];

    public function index(Request $request)
    {
        // التأكد من أن المستخدم مدير فقط
        if (!Auth::user()->isAdmin()) {
            return response()->json(['success' => false, 'message' => 'غير مصرح لك بالوصول إلى هذه الصفحة.'], 403);
        }

        // إعدادات افتراضية للتواريخ
        $dateFrom = $request->filled('date_from') ? $request->date_from : now()->subDays(30)->format('Y-m-d');
        $dateTo = $request->filled('date_to') ? $request->date_to : now()->format('Y-m-d');

        // الحصول على الإعدادات
        $deliveryFee = Setting::getDeliveryFee();
        $profitMargin = Setting::getProfitMargin();

        // بناء query للطلبات
        $ordersQuery = Order::query()->where('status', 'confirmed');

        if ($request->filled('delegate_id')) {
            $ordersQuery->where('delegate_id', $request->delegate_id);
        }

        if ($request->filled('warehouse_id')) {
            $ordersQuery->whereHas('items.product', function ($q) use ($request) {
                $q->where('warehouse_id', $request->warehouse_id);
            });
        }

        $ordersQuery->whereBetween(DB::raw('DATE(confirmed_at)'), [$dateFrom, $dateTo]);

        // جلب البيانات مرة واحدة فقط
        $orders = $ordersQuery->with(['items.product.warehouse'])->get();
        $orderIds = $orders->pluck('id');

        // جلب بنود الطلب دفعة واحدة مع المنتجات والمخازن
        $orderItemsQuery = OrderItem::whereIn('order_id', $orderIds);
        if ($request->filled('warehouse_id')) {
            $orderItemsQuery->whereHas('product', fn($q) => $q->where('warehouse_id', $request->warehouse_id));
        }
        $allOrderItems = $orderItemsQuery->with(['product.warehouse'])->get();

        // جلب المصروفات دفعة واحدة
        $allExpenses = Expense::byDateRange($dateFrom, $dateTo)->get();

        // حساب الإحصائيات (Ported logic)
        $statistics = $this->calculateStatistics($orders, $allOrderItems, $allExpenses, $deliveryFee, $profitMargin, $request, $dateFrom, $dateTo);

        // حساب أرباح المخازن
        $warehouseProfitsData = $this->calculateWarehouseProfits(
            $orders,
            $allOrderItems,
            $allExpenses,
            $request,
            $dateFrom,
            $dateTo,
            $statistics['total_expenses'],
            $statistics['items_count']
        );

        // حساب أرباح المنتجات (مع pagination للـ API)
        $productProfitsResult = $this->calculateProductProfitsPaginated($allOrderItems, $request);

        // حساب بيانات الجارتات
        $chartData = $this->calculateChartData($orders, $request, $dateFrom, $dateTo);

        // جلب بيانات الفلاتر (المخازن، المناديب)
        $filterOptions = [
            'warehouses' => Warehouse::select('id', 'name')->get(),
            'delegates' => User::where('role', 'delegate')->select('id', 'name')->get(),
        ];

        return response()->json([
            'success' => true,
            'data' => [
                'statistics' => $statistics,
                'chart_data' => $chartData,
                'warehouse_profits' => $warehouseProfitsData['warehouses'],
                'product_profits' => $productProfitsResult['paginated'],
                'product_totals' => $productProfitsResult['totals'],
                'filter_options' => $filterOptions,
                'date_range' => [
                    'from' => $dateFrom,
                    'to' => $dateTo
                ]
            ]
        ]);
    }

    private function calculateStatistics($orders, $orderItems, $expenses, $deliveryFee, $profitMargin, $request, $dateFrom, $dateTo)
    {
        $totalAmountWithoutDelivery = $orderItems->sum('subtotal');
        $totalExpenses = $expenses->sum('amount');

        $adminProfitFromInvestments = 0;
        $regularOrdersProfit = 0;
        $totalProfitWithoutMargin = 0;

        foreach ($orderItems as $item) {
            $product = $item->product;
            if ($product && $product->purchase_price > 0) {
                $itemProfit = ($item->unit_price - $product->purchase_price) * $item->quantity;

                if ($this->checkProductHasActiveInvestors($product)) {
                    $adminShare = $itemProfit * ($this->getAdminProfitPercentage($product) / 100);
                    $adminProfitFromInvestments += $adminShare;
                    $totalProfitWithoutMargin += $adminShare;
                } else {
                    $regularOrdersProfit += $itemProfit;
                    $totalProfitWithoutMargin += $itemProfit;
                }
            }
        }

        // حساب الفروقات (Margin)
        $totalMarginAmount = 0;
        foreach ($orders as $order) {
            if ($order->status !== 'returned' || $order->is_partial_return) {
                $ratio = 1;
                if ($request->filled('warehouse_id')) {
                    $orderOrderItems = $order->items;
                    $totalQty = $orderOrderItems->sum('quantity');
                    $whQty = $orderOrderItems->filter(fn($i) => $i->product && $i->product->warehouse_id == $request->warehouse_id)->sum('quantity');
                    $ratio = $totalQty > 0 ? $whQty / $totalQty : 0;
                }
                $totalMarginAmount += ($order->profit_margin_at_confirmation ?? $profitMargin) * $ratio;
            }
        }

        $totalProfitWithMargin = $totalProfitWithoutMargin + $totalMarginAmount;
        $itemsCount = $orderItems->sum('quantity');

        return [
            'total_sales' => round($totalAmountWithoutDelivery, 2),
            'admin_investment_profit' => round($adminProfitFromInvestments, 2),
            'regular_orders_profit' => round($regularOrdersProfit, 2),
            'total_margin' => round($totalMarginAmount, 2),
            'gross_profit' => round($totalProfitWithMargin, 2),
            'total_expenses' => round($totalExpenses, 2),
            'net_profit' => round($totalProfitWithMargin - $totalExpenses, 2),
            'orders_count' => $orders->count(),
            'items_count' => (int) $itemsCount,
            'admin_investment_profit_raw' => $adminProfitFromInvestments,
            'regular_orders_profit_raw' => $regularOrdersProfit,
        ];
    }

    private function calculateChartData($orders, $request, $dateFrom, $dateTo)
    {
        $dataByDate = [];

        foreach ($orders as $order) {
            $date = $order->confirmed_at->format('Y-m-d');
            if ($date < $dateFrom || $date > $dateTo)
                continue;

            if (!isset($dataByDate[$date])) {
                $dataByDate[$date] = ['sales' => 0, 'profits' => 0, 'profits_with_margin' => 0];
            }

            $orderItems = $order->items;
            if ($request->filled('warehouse_id')) {
                $orderItems = $orderItems->filter(fn($i) => $i->product && $i->product->warehouse_id == $request->warehouse_id);
            }

            $orderAmount = $orderItems->sum('subtotal');
            $dataByDate[$date]['sales'] += $orderAmount;

            $dayProfit = 0;
            foreach ($orderItems as $item) {
                $product = $item->product;
                if ($product && $product->purchase_price > 0) {
                    $profit = ($item->unit_price - $product->purchase_price) * $item->quantity;
                    if ($this->checkProductHasActiveInvestors($product)) {
                        $dayProfit += $profit * ($this->getAdminProfitPercentage($product) / 100);
                    } else {
                        $dayProfit += $profit;
                    }
                }
            }
            $dataByDate[$date]['profits'] += $dayProfit;

            if ($order->status !== 'returned' || $order->is_partial_return) {
                $ratio = 1;
                if ($request->filled('warehouse_id')) {
                    $totalQty = $order->items->sum('quantity');
                    $whQty = $orderItems->sum('quantity');
                    $ratio = $totalQty > 0 ? $whQty / $totalQty : 0;
                }
                $dataByDate[$date]['profits_with_margin'] += $dayProfit + (($order->profit_margin_at_confirmation ?? Setting::getProfitMargin()) * $ratio);
            } else {
                $dataByDate[$date]['profits_with_margin'] += $dayProfit;
            }
        }

        ksort($dataByDate);

        return [
            'labels' => array_keys($dataByDate),
            'sales' => array_column($dataByDate, 'sales'),
            'profits' => array_column($dataByDate, 'profits'),
            'profits_with_margin' => array_column($dataByDate, 'profits_with_margin'),
        ];
    }

    private function calculateProductProfitsPaginated($orderItems, $request)
    {
        $groupedItems = $orderItems->groupBy('product_id');
        $productProfits = [];

        foreach ($groupedItems as $productId => $items) {
            $firstItem = $items->first();
            $product = $firstItem->product;
            if (!$product)
                continue;

            $totalQty = $items->sum('quantity');
            $totalSales = $items->sum('subtotal');
            $purchasePrice = $product->purchase_price ?? 0;
            $avgSellingPrice = $totalQty > 0 ? $totalSales / $totalQty : 0;
            $grossProfit = ($avgSellingPrice - $purchasePrice) * $totalQty;

            $adminProfit = $this->checkProductHasActiveInvestors($product)
                ? $grossProfit * ($this->getAdminProfitPercentage($product) / 100)
                : $grossProfit;

            $productProfits[] = [
                'id' => $product->id,
                'name' => $product->name,
                'code' => $product->code,
                'warehouse' => $product->warehouse->name ?? 'N/A',
                'quantity' => (int) $totalQty,
                'sales' => round($totalSales, 2),
                'profit' => round($adminProfit, 2),
            ];
        }

        usort($productProfits, fn($a, $b) => $b['profit'] <=> $a['profit']);

        $perPage = (int) $request->get('per_page', 10);
        $page = (int) $request->get('page', 1);
        $total = count($productProfits);
        $pagedData = array_slice($productProfits, ($page - 1) * $perPage, $perPage);

        return [
            'paginated' => [
                'current_page' => $page,
                'data' => $pagedData,
                'last_page' => (int) ceil($total / $perPage),
                'per_page' => $perPage,
                'total' => $total,
            ],
            'totals' => [
                'total_profit' => round(collect($productProfits)->sum('profit'), 2),
                'total_items' => collect($productProfits)->sum('quantity'),
            ]
        ];
    }

    private function calculateWarehouseProfits($orders, $allOrderItems, $expenses, $request, $dateFrom, $dateTo, $totalExpenses, $totalItemsCount)
    {
        $warehouses = Warehouse::all();
        $warehouseExpensesMap = $expenses->whereNotNull('warehouse_id')->groupBy('warehouse_id')->map->sum('amount');
        $generalExpenses = $expenses->whereNull('warehouse_id')->sum('amount');
        $generalExpensePerItem = $totalItemsCount > 0 ? ($generalExpenses / $totalItemsCount) : 0;

        $groupedItems = $allOrderItems->groupBy(fn($i) => $i->product->warehouse_id);

        $warehouseProfits = [];
        $profitMargin = Setting::getProfitMargin();

        foreach ($warehouses as $warehouse) {
            $wItems = $groupedItems->get($warehouse->id, collect());
            if ($wItems->isEmpty() && !($warehouseExpensesMap->has($warehouse->id)))
                continue;

            $itemsCount = $wItems->sum('quantity');
            $profitWithoutMargin = 0;

            foreach ($wItems as $item) {
                $product = $item->product;
                if ($product && $product->purchase_price > 0) {
                    $itemProfit = ($item->unit_price - $product->purchase_price) * $item->quantity;
                    $profitWithoutMargin += $this->checkProductHasActiveInvestors($product)
                        ? $itemProfit * ($this->getAdminProfitPercentage($product) / 100)
                        : $itemProfit;
                }
            }

            $warehouseMarginAmount = 0;
            foreach ($orders as $order) {
                if ($order->status !== 'returned' || $order->is_partial_return) {
                    $orderOrderItems = $order->items;
                    $totalQty = $orderOrderItems->sum('quantity');
                    $whQty = $orderOrderItems->filter(fn($i) => $i->product && $i->product->warehouse_id == $warehouse->id)->sum('quantity');
                    $ratio = $totalQty > 0 ? $whQty / $totalQty : 0;
                    $warehouseMarginAmount += ($order->profit_margin_at_confirmation ?? $profitMargin) * $ratio;
                }
            }

            $profitWithMargin = $profitWithoutMargin + $warehouseMarginAmount;
            $warehouseExpenses = ($warehouseExpensesMap->get($warehouse->id, 0)) + ($generalExpensePerItem * $itemsCount);

            $warehouseProfits[] = [
                'id' => $warehouse->id,
                'name' => $warehouse->name,
                'items_count' => (int) $itemsCount,
                'profit_without_margin' => round($profitWithoutMargin, 2),
                'profit_with_margin' => round($profitWithMargin, 2),
                'expenses' => round($warehouseExpenses, 2),
                'net_profit' => round($profitWithMargin - $warehouseExpenses, 2),
            ];
        }

        return ['warehouses' => $warehouseProfits];
    }

    private function checkProductHasActiveInvestors(Product $product): bool
    {
        $cacheKey = "warehouse_{$product->warehouse_id}";
        if (isset($this->investmentCache[$cacheKey]))
            return $this->investmentCache[$cacheKey];

        $hasInvestors = InvestmentTarget::where('target_type', 'warehouse')
            ->where('target_id', $product->warehouse_id)
            ->whereHas('investment', function ($q) {
                $q->where('status', 'active')
                    ->where('start_date', '<=', now())
                    ->where(fn($q2) => $q2->whereNull('end_date')->orWhere('end_date', '>=', now()))
                    ->whereHas('investors');
            })->exists() || Investment::where('warehouse_id', $product->warehouse_id)
                ->where('status', 'active')->whereNotNull('investor_id')
                ->where('start_date', '<=', now())
                ->where(fn($q) => $q->whereNull('end_date')->orWhere('end_date', '>=', now()))
                ->exists();

        $this->investmentCache[$cacheKey] = $hasInvestors;
        return $hasInvestors;
    }

    private function getAdminProfitPercentage(Product $product): float
    {
        $cacheKey = "warehouse_{$product->warehouse_id}_admin";
        if (isset($this->adminPercentageCache[$cacheKey]))
            return $this->adminPercentageCache[$cacheKey];

        $investment = Investment::whereHas('targets', fn($q) => $q->where('target_type', 'warehouse')->where('target_id', $product->warehouse_id))
            ->where('status', 'active')->where('start_date', '<=', now())
            ->where(fn($q) => $q->whereNull('end_date')->orWhere('end_date', '>=', now()))->first();

        if ($investment) {
            $adminShare = $investment->investors()->whereHas('investor', fn($q) => $q->where('is_admin', true))->sum('profit_percentage');
            $result = $adminShare > 0 ? $adminShare : ($investment->admin_profit_percentage ?? 0);
        } else {
            $oldInvestment = Investment::where('warehouse_id', $product->warehouse_id)
                ->where('status', 'active')->whereNotNull('investor_id')
                ->where('start_date', '<=', now())
                ->where(fn($q) => $q->whereNull('end_date')->orWhere('end_date', '>=', now()))->first();
            $result = $oldInvestment ? ($oldInvestment->admin_profit_percentage ?? 0) : 100;
        }

        $this->adminPercentageCache[$cacheKey] = (float) $result;
        return (float) $result;
    }
}
