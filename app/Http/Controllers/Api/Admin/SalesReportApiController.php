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

        // فلترة المندوب
        if ($request->filled('delegate_id')) {
            $ordersQuery->where('delegate_id', $request->delegate_id);
        }

        // فلترة المخزن
        if ($request->filled('warehouse_id')) {
            $ordersQuery->whereHas('items.product', function ($q) use ($request) {
                $q->where('warehouse_id', $request->warehouse_id);
            });
        }

        // فلترة التاريخ (على confirmed_at)
        $ordersQuery->whereBetween(DB::raw('DATE(confirmed_at)'), [$dateFrom, $dateTo]);

        // جلب الطلبات مع بنود الطلب والمنتجات (لتحسين الأداء وتجنب N+1)
        $orders = $ordersQuery->with(['items.product.warehouse'])->get();
        $orderIds = $orders->pluck('id');

        // حساب الإحصائيات (Ported logic)
        $statistics = $this->calculateStatistics($orders, $orderIds, $deliveryFee, $profitMargin, $request, $dateFrom, $dateTo);

        // حساب بيانات الجارتات
        $chartData = $this->calculateChartData($orders, $orderIds, $dateFrom, $dateTo, $request);

        // حساب أرباح المنتجات (مع pagination للـ API)
        $productProfitsResult = $this->calculateProductProfitsPaginated($orders, $orderIds, $request, $dateFrom, $dateTo, $statistics['total_expenses'], $statistics['items_count']);

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

    private function calculateStatistics($orders, $orderIds, $deliveryFee, $profitMargin, $request, $dateFrom, $dateTo)
    {
        $orderItemsQuery = OrderItem::whereIn('order_id', $orderIds);
        if ($request->filled('warehouse_id')) {
            $orderItemsQuery->whereHas('product', function ($q) use ($request) {
                $q->where('warehouse_id', $request->warehouse_id);
            });
        }
        $orderItems = $orderItemsQuery->with(['product.warehouse'])->get();

        $totalAmountWithoutDelivery = $orderItems->sum('subtotal');

        // المصروفات
        $totalExpenses = Expense::byDateRange($dateFrom, $dateTo)->sum('amount');

        $adminProfitFromInvestments = 0;
        $regularOrdersProfit = 0;
        $totalProfitWithoutMargin = 0;
        $totalMarginAmount = 0;

        foreach ($orderItems as $item) {
            if ($item->product && $item->product->purchase_price > 0) {
                $sellingPrice = $item->unit_price ?? 0;
                $purchasePrice = $item->product->purchase_price ?? 0;
                $quantity = $item->quantity ?? 0;
                $itemProfit = ($sellingPrice - $purchasePrice) * $quantity;

                $hasInvestors = $this->checkProductHasActiveInvestors($item->product);
                if ($hasInvestors) {
                    $adminProfitPercentage = $this->getAdminProfitPercentage($item->product);
                    $adminShare = $itemProfit * ($adminProfitPercentage / 100);
                    $adminProfitFromInvestments += $adminShare;
                    $totalProfitWithoutMargin += $adminShare;
                } else {
                    $regularOrdersProfit += $itemProfit;
                    $totalProfitWithoutMargin += $itemProfit;
                }
            }
        }

        // حساب الفروقات (Margin)
        foreach ($orders as $order) {
            if ($order->status !== 'returned' || $order->is_partial_return) {
                if ($request->filled('warehouse_id')) {
                    $allOrderItems = $order->items;
                    $warehouseOrderItems = $allOrderItems->filter(fn($i) => $i->product && $i->product->warehouse_id == $request->warehouse_id);
                    $totalQty = $allOrderItems->sum('quantity');
                    $whQty = $warehouseOrderItems->sum('quantity');
                    $ratio = $totalQty > 0 ? $whQty / $totalQty : 0;
                } else {
                    $ratio = 1;
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
        ];
    }

    private function calculateChartData($orders, $orderIds, $dateFrom, $dateTo, $request)
    {
        $salesByDate = [];
        $profitsByDate = [];

        foreach ($orders as $order) {
            if (!$order->confirmed_at)
                continue;
            $date = $order->confirmed_at->format('Y-m-d');
            if ($date < $dateFrom || $date > $dateTo)
                continue;

            if (!isset($salesByDate[$date])) {
                $salesByDate[$date] = 0;
                $profitsByDate[$date] = 0;
            }

            $orderItems = $order->items;
            if ($request->filled('warehouse_id')) {
                $orderItems = $orderItems->filter(fn($i) => $i->product && $i->product->warehouse_id == $request->warehouse_id);
            }

            $salesByDate[$date] += $orderItems->sum('subtotal');

            // الربح لهذا اليوم
            $dayProfit = 0;
            foreach ($orderItems as $item) {
                if ($item->product && $item->product->purchase_price > 0) {
                    $profit = ($item->unit_price - $item->product->purchase_price) * $item->quantity;
                    if ($this->checkProductHasActiveInvestors($item->product)) {
                        $dayProfit += $profit * ($this->getAdminProfitPercentage($item->product) / 100);
                    } else {
                        $dayProfit += $profit;
                    }
                }
            }
            $profitsByDate[$date] += $dayProfit;
        }

        ksort($salesByDate);
        ksort($profitsByDate);

        return [
            'labels' => array_keys($salesByDate),
            'sales' => array_values($salesByDate),
            'profits' => array_values($profitsByDate),
        ];
    }

    private function calculateProductProfitsPaginated($orders, $orderIds, $request, $dateFrom, $dateTo, $totalExpenses, $totalItemsCount)
    {
        // Porting calculateProductProfits logic but keeping it simple for mobile
        // This usually involves a lot of DB queries, we'll try to optimize

        $warehouseId = $request->warehouse_id;

        $query = OrderItem::select(
            'product_id',
            DB::raw('SUM(quantity) as total_quantity'),
            DB::raw('SUM(subtotal) as total_sales')
        )
            ->whereIn('order_id', $orderIds);

        if ($warehouseId) {
            $query->whereHas('product', fn($q) => $q->where('warehouse_id', $warehouseId));
        }

        $items = $query->groupBy('product_id')->get();
        $productIds = $items->pluck('product_id');

        // جلب المنتجات دفعة واحدة
        $products = Product::with('warehouse')->whereIn('id', $productIds)->get()->keyBy('id');

        $productProfits = [];

        foreach ($items as $item) {
            $product = $products->get($item->product_id);
            if (!$product)
                continue;

            $purchasePrice = $product->purchase_price ?? 0;
            $avgSellingPrice = $item->total_quantity > 0 ? $item->total_sales / $item->total_quantity : 0;
            $grossProfit = ($avgSellingPrice - $purchasePrice) * $item->total_quantity;

            // Share
            if ($this->checkProductHasActiveInvestors($product)) {
                $adminProfit = $grossProfit * ($this->getAdminProfitPercentage($product) / 100);
            } else {
                $adminProfit = $grossProfit;
            }

            $productProfits[] = [
                'id' => $product->id,
                'name' => $product->name,
                'code' => $product->code,
                'warehouse' => $product->warehouse->name ?? 'N/A',
                'quantity' => (int) $item->total_quantity,
                'sales' => round($item->total_sales, 2),
                'profit' => round($adminProfit, 2),
            ];
        }

        // Manual Pagination for the result array
        $perPage = $request->get('per_page', 10);
        $page = $request->get('page', 1);
        $total = count($productProfits);

        // Sort by profit descending
        usort($productProfits, fn($a, $b) => $b['profit'] <=> $a['profit']);

        $pagedData = array_slice($productProfits, ($page - 1) * $perPage, $perPage);

        return [
            'paginated' => [
                'current_page' => (int) $page,
                'data' => $pagedData,
                'last_page' => ceil($total / $perPage),
                'per_page' => (int) $perPage,
                'total' => $total,
            ],
            'totals' => [
                'total_profit' => round(collect($productProfits)->sum('profit'), 2),
                'total_items' => collect($productProfits)->sum('quantity'),
            ]
        ];
    }

    private function checkProductHasActiveInvestors(Product $product): bool
    {
        $cacheKey = "warehouse_{$product->warehouse_id}";
        if (isset($this->investmentCache[$cacheKey]))
            return $this->investmentCache[$cacheKey];

        $hasNewInvestors = InvestmentTarget::where('target_type', 'warehouse')
            ->where('target_id', $product->warehouse_id)
            ->whereHas('investment', function ($q) {
                $q->where('status', 'active')
                    ->where('start_date', '<=', now())
                    ->where(fn($q2) => $q2->whereNull('end_date')->orWhere('end_date', '>=', now()))
                    ->whereHas('investors');
            })->exists();

        $hasOldInvestors = Investment::where('warehouse_id', $product->warehouse_id)
            ->where('status', 'active')->whereNotNull('investor_id')
            ->where('start_date', '<=', now())
            ->where(fn($q) => $q->whereNull('end_date')->orWhere('end_date', '>=', now()))
            ->exists();

        $result = $hasNewInvestors || $hasOldInvestors;
        $this->investmentCache[$cacheKey] = $result;
        return $result;
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
            $this->adminPercentageCache[$cacheKey] = (float) $result;
            return (float) $result;
        }

        $oldInvestment = Investment::where('warehouse_id', $product->warehouse_id)
            ->where('status', 'active')->whereNotNull('investor_id')
            ->where('start_date', '<=', now())
            ->where(fn($q) => $q->whereNull('end_date')->orWhere('end_date', '>=', now()))->first();

        $result = $oldInvestment ? ($oldInvestment->admin_profit_percentage ?? 0) : 100;
        $this->adminPercentageCache[$cacheKey] = (float) $result;
        return (float) $result;
    }
}
