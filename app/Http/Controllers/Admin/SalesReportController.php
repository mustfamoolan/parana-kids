<?php

namespace App\Http\Controllers\Admin;

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
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class SalesReportController extends Controller
{
    public function index(Request $request)
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

        // الحصول على الإعدادات
        $deliveryFee = Setting::getDeliveryFee();
        $profitMargin = Setting::getProfitMargin();

        // بناء query للطلبات مع جميع الفلاتر
        $ordersQuery = Order::query();

        // فلتر دائم: فقط الطلبات المقيدة
        $ordersQuery->where('status', 'confirmed');

        // فلتر المندوب
        if ($request->filled('delegate_id')) {
            $ordersQuery->where('delegate_id', $request->delegate_id);
        }

        // فلتر المجهز
        if ($request->filled('confirmed_by')) {
            $ordersQuery->where('confirmed_by', $request->confirmed_by);
        }

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

        // فلتر التاريخ
        $dateFrom = $request->filled('date_from') ? $request->date_from : now()->subDays(30)->format('Y-m-d');
        $dateTo = $request->filled('date_to') ? $request->date_to : now()->format('Y-m-d');

        // البحث الذكي
        if ($request->filled('search')) {
            $search = $request->search;
            $ordersQuery->where(function($q) use ($search) {
                $q->where('order_number', 'like', "%{$search}%")
                  ->orWhere('customer_name', 'like', "%{$search}%")
                  ->orWhere('customer_phone', 'like', "%{$search}%")
                  ->orWhere('delivery_code', 'like', "%{$search}%");
            });
        }

        // تطبيق فلتر التاريخ (فقط للطلبات المقيدة باستخدام confirmed_at)
        $ordersQuery->whereBetween(DB::raw('DATE(confirmed_at)'), [$dateFrom, $dateTo]);

        // جلب الطلبات
        $orders = $ordersQuery->get();
        $orderIds = $orders->pluck('id');

        // حساب الإحصائيات
        $statistics = $this->calculateStatistics($orders, $orderIds, $deliveryFee, $profitMargin, $request, $dateFrom, $dateTo);

        // حساب بيانات الجارتات
        $chartData = $this->calculateChartData($orders, $orderIds, $dateFrom, $dateTo, $request);

        // حفظ التقرير في قاعدة البيانات
        $this->saveReport($statistics, $chartData, $request, $dateFrom, $dateTo);

        return view('admin.sales-report.index', compact(
            'warehouses',
            'products',
            'delegates',
            'suppliers',
            'statistics',
            'chartData',
            'deliveryFee',
            'profitMargin'
        ));
    }

    private function calculateStatistics($orders, $orderIds, $deliveryFee, $profitMargin, $request, $dateFrom, $dateTo)
    {
        // جلب جميع order_items للطلبات المحددة
        $orderItemsQuery = OrderItem::whereIn('order_id', $orderIds);

        // فلترة orderItems حسب warehouse_id إذا كان موجوداً
        if ($request->filled('warehouse_id')) {
            $orderItemsQuery->whereHas('product', function($q) use ($request) {
                $q->where('warehouse_id', $request->warehouse_id);
            });
        }

        $orderItems = $orderItemsQuery->get();

        // جلب جميع return_items للطلبات المحددة
        $returnItemsQuery = ReturnItem::whereIn('order_id', $orderIds);

        // فلترة returnItems حسب warehouse_id إذا كان موجوداً
        if ($request->filled('warehouse_id')) {
            $returnItemsQuery->whereHas('orderItem.product', function($q) use ($request) {
                $q->where('warehouse_id', $request->warehouse_id);
            });
        }

        $returnItems = $returnItemsQuery->get();

        // حساب المبلغ الكلي بدون توصيل
        // ملاحظة: subtotal في order_items يتم تحديثه تلقائياً بعد الإرجاع الجزئي
        // لذلك نستخدم القيم المتبقية مباشرة دون خصم الإرجاع مرة أخرى
        $totalAmountWithoutDelivery = $orderItems->sum('subtotal');

        // حساب مبلغ الإرجاع (من return_items) - للعرض فقط، لا نستخدمه في الحسابات
        $returnAmount = 0;
        foreach ($returnItems as $returnItem) {
            $orderItem = $orderItems->firstWhere('id', $returnItem->order_item_id);
            if ($orderItem) {
                $returnAmount += $orderItem->unit_price * $returnItem->quantity_returned;
            }
        }

        // جلب جميع ProductMovement من نوع return_exchange_bulk في نطاق التاريخ
        $exchangeReturnMovementsQuery = ProductMovement::where('movement_type', 'return_exchange_bulk')
            ->whereBetween(DB::raw('DATE(created_at)'), [$dateFrom, $dateTo]);

        // فلترة حسب warehouse_id إذا كان موجوداً
        if ($request->filled('warehouse_id')) {
            $exchangeReturnMovementsQuery->where('warehouse_id', $request->warehouse_id);
        }

        $exchangeReturnMovements = $exchangeReturnMovementsQuery->with('product')->get();

        // حساب المبلغ المخصوم من إرجاع الاستبدال
        $exchangeReturnAmount = 0;
        $exchangeReturnProfit = 0;
        foreach ($exchangeReturnMovements as $movement) {
            if ($movement->product && $movement->product->selling_price) {
                // حساب المبلغ: quantity * selling_price
                $exchangeReturnAmount += $movement->quantity * $movement->product->selling_price;
                
                // حساب الربح المخصوم: (selling_price - purchase_price) * quantity
                if ($movement->product->purchase_price && $movement->product->purchase_price > 0) {
                    $profitPerUnit = $movement->product->selling_price - $movement->product->purchase_price;
                    $exchangeReturnProfit += $profitPerUnit * $movement->quantity;
                }
            }
        }

        // خصم المبلغ من المبيعات
        $totalAmountWithoutDelivery = max(0, $totalAmountWithoutDelivery - $exchangeReturnAmount);

        // عدد الطلبات المقيدة
        $confirmedOrders = $orders->where('status', 'confirmed');
        $confirmedOrderIds = $confirmedOrders->pluck('id');

        // عدد الطلبات المسترجعة كلياً
        $fullyReturnedOrders = $orders->filter(function($order) {
            // طلب مسترجع كلياً إذا كان status = 'returned' وليس is_partial_return
            return $order->status === 'returned' && !$order->is_partial_return;
        });

        // حساب المبلغ الكلي (بدون التوصيل - التوصيل لا يدخل في الحسابات)
        $totalAmountWithDelivery = $totalAmountWithoutDelivery;
        $totalMarginAmount = 0;

        foreach ($confirmedOrders as $order) {
            // فقط للطلبات غير المسترجعة كلياً
            if ($order->status !== 'returned' || $order->is_partial_return) {
                // إذا كان هناك فلتر مخزن، نحسب نسبة المنتجات من ذلك المخزن
                if ($request->filled('warehouse_id')) {
                    // جلب جميع items للطلب
                    $allOrderItems = $order->items;
                    $warehouseOrderItems = $allOrderItems->filter(function($item) use ($request) {
                        return $item->product && $item->product->warehouse_id == $request->warehouse_id;
                    });

                    // حساب نسبة المنتجات من المخزن المحدد
                    $totalQuantity = $allOrderItems->sum('quantity');
                    $warehouseQuantity = $warehouseOrderItems->sum('quantity');
                    $warehouseRatio = $totalQuantity > 0 ? $warehouseQuantity / $totalQuantity : 0;
                } else {
                    $warehouseRatio = 1; // إذا لم يكن هناك فلتر مخزن، نستخدم 100%
                }

                // التوصيل لا يدخل في الحسابات - تم إزالته
                // استخدام ربح الفروقات المحفوظ وقت التقييد (أو القيمة الحالية كبديل)
                $orderProfitMargin = $order->profit_margin_at_confirmation ?? $profitMargin;
                $totalMarginAmount += $orderProfitMargin * $warehouseRatio;
            }
        }

        // حساب الأرباح بدون فروقات
        // الأرباح = (سعر البيع - سعر الشراء) × الكمية لكل منتج
        // ملاحظة: quantity في order_items يتم تحديثه تلقائياً بعد الإرجاع الجزئي
        // لذلك نستخدم القيم المتبقية مباشرة دون خصم الإرجاع مرة أخرى
        $totalProfitWithoutMargin = 0;
        foreach ($orderItems as $item) {
            // التأكد من وجود المنتج وسعر الشراء
            if ($item->product && $item->product->purchase_price && $item->product->purchase_price > 0) {
                // سعر البيع = unit_price (المحفوظ في order_item)
                // سعر الشراء = purchase_price (من جدول products)
                $sellingPrice = $item->unit_price ?? 0;
                $purchasePrice = $item->product->purchase_price ?? 0;
                // quantity هنا هو الكمية المتبقية بعد الإرجاع الجزئي
                $quantity = $item->quantity ?? 0;

                // حساب ربح القطعة الواحدة
                $profitPerUnit = $sellingPrice - $purchasePrice;

                // حساب ربح المنتج الكلي = ربح القطعة × الكمية المتبقية
                $itemProfit = $profitPerUnit * $quantity;
                $totalProfitWithoutMargin += $itemProfit;
            }
        }

        // خصم الربح من إرجاع الاستبدال
        $totalProfitWithoutMargin = max(0, $totalProfitWithoutMargin - $exchangeReturnProfit);

        // حساب الأرباح مع الفروقات
        $totalProfitWithMargin = $totalProfitWithoutMargin + $totalMarginAmount;

        // حساب إجمالي المصروفات حسب نطاق التاريخ
        $totalExpenses = Expense::byDateRange($dateFrom, $dateTo)->sum('amount');

        // حساب الأرباح بعد خصم المصروفات
        $profitAfterExpenses = $totalProfitWithMargin - $totalExpenses;

        // عدد الطلبات
        $ordersCount = $orders->count();

        // عدد المواد (الكمية المتبقية بعد الإرجاع)
        // ملاحظة: quantity في order_items يتم تحديثه تلقائياً بعد الإرجاع الجزئي
        // لذلك نستخدم القيم المتبقية مباشرة دون خصم الإرجاع مرة أخرى
        $itemsCount = $orderItems->sum('quantity');

        return [
            'total_amount_with_delivery' => $totalAmountWithDelivery,
            'total_amount_without_delivery' => $totalAmountWithoutDelivery,
            'total_profit_without_margin' => $totalProfitWithoutMargin,
            'total_profit_with_margin' => $totalProfitWithMargin,
            'total_margin_amount' => $totalMarginAmount,
            'total_expenses' => $totalExpenses,
            'profit_after_expenses' => $profitAfterExpenses,
            'orders_count' => $ordersCount,
            'items_count' => max(0, $itemsCount),
            'return_amount' => $returnAmount,
            'exchange_return_amount' => $exchangeReturnAmount,
        ];
    }

    private function calculateChartData($orders, $orderIds, $dateFrom, $dateTo, $request)
    {
        // Line Chart: المبيعات حسب التاريخ (المبالغ)
        $salesByDate = [];
        $profitsByDate = [];
        $profitsWithMarginByDate = [];

        // تجميع البيانات حسب التاريخ (فقط للطلبات المقيدة)
        foreach ($orders as $order) {
            // فقط الطلبات المقيدة مع confirmed_at
            if (!$order->confirmed_at) {
                continue;
            }

            $date = $order->confirmed_at->format('Y-m-d');

            if ($date >= $dateFrom && $date <= $dateTo) {
                if (!isset($salesByDate[$date])) {
                    $salesByDate[$date] = 0;
                    $profitsByDate[$date] = 0;
                    $profitsWithMarginByDate[$date] = 0;
                }

                // فلترة items حسب warehouse_id إذا كان موجوداً
                $orderItems = $order->items;
                if ($request->filled('warehouse_id')) {
                    $orderItems = $orderItems->filter(function($item) use ($request) {
                        return $item->product && $item->product->warehouse_id == $request->warehouse_id;
                    });
                }

                // حساب المبلغ (بدون توصيل) - فقط للمنتجات من المخزن المحدد
                // التوصيل لا يدخل في الحسابات
                $orderAmount = $orderItems->sum('subtotal');

                $salesByDate[$date] += $orderAmount;

                // حساب الربح - فقط للمنتجات من المخزن المحدد
                // الأرباح = (سعر البيع - سعر الشراء) × الكمية لكل منتج
                // ملاحظة: quantity في order_items يتم تحديثه تلقائياً بعد الإرجاع الجزئي
                // لذلك نستخدم القيم المتبقية مباشرة دون خصم الإرجاع مرة أخرى
                $orderProfit = 0;
                foreach ($orderItems as $item) {
                    // التأكد من وجود المنتج وسعر الشراء
                    if ($item->product && $item->product->purchase_price && $item->product->purchase_price > 0) {
                        // سعر البيع = unit_price (المحفوظ في order_item)
                        // سعر الشراء = purchase_price (من جدول products)
                        $sellingPrice = $item->unit_price ?? 0;
                        $purchasePrice = $item->product->purchase_price ?? 0;
                        // quantity هنا هو الكمية المتبقية بعد الإرجاع الجزئي
                        $quantity = $item->quantity ?? 0;

                        // حساب ربح القطعة الواحدة
                        $profitPerUnit = $sellingPrice - $purchasePrice;

                        // حساب ربح المنتج الكلي = ربح القطعة × الكمية المتبقية
                        $itemProfit = $profitPerUnit * $quantity;
                        $orderProfit += $itemProfit;
                    }
                }

                $profitsByDate[$date] += $orderProfit;

                // إضافة الفروقات فقط للطلبات المقيدة غير المسترجعة كلياً
                if ($order->status !== 'returned' || $order->is_partial_return) {
                    // إذا كان هناك فلتر مخزن، نحسب نسبة المنتجات من ذلك المخزن
                    if ($request->filled('warehouse_id')) {
                        // جلب جميع items للطلب
                        $allOrderItems = $order->items;
                        $warehouseOrderItems = $allOrderItems->filter(function($item) use ($request) {
                            return $item->product && $item->product->warehouse_id == $request->warehouse_id;
                        });

                        // حساب نسبة المنتجات من المخزن المحدد
                        $totalQuantity = $allOrderItems->sum('quantity');
                        $warehouseQuantity = $warehouseOrderItems->sum('quantity');
                        $warehouseRatio = $totalQuantity > 0 ? $warehouseQuantity / $totalQuantity : 0;
                    } else {
                        $warehouseRatio = 1; // إذا لم يكن هناك فلتر مخزن، نستخدم 100%
                    }

                    // استخدام ربح الفروقات المحفوظ وقت التقييد
                    $orderProfitMargin = $order->profit_margin_at_confirmation ?? Setting::getProfitMargin();
                    $profitsWithMarginByDate[$date] += $orderProfit + ($orderProfitMargin * $warehouseRatio);
                } else {
                    $profitsWithMarginByDate[$date] += $orderProfit;
                }
            }
        }

        // خصم مبلغ إرجاع الاستبدال من المبيعات حسب التاريخ
        $exchangeReturnMovementsQuery = ProductMovement::where('movement_type', 'return_exchange_bulk')
            ->whereBetween(DB::raw('DATE(created_at)'), [$dateFrom, $dateTo]);

        if ($request->filled('warehouse_id')) {
            $exchangeReturnMovementsQuery->where('warehouse_id', $request->warehouse_id);
        }

        $exchangeReturnMovements = $exchangeReturnMovementsQuery->with('product')->get();

        foreach ($exchangeReturnMovements as $movement) {
            if ($movement->product && $movement->product->selling_price) {
                $date = $movement->created_at->format('Y-m-d');
                $amount = $movement->quantity * $movement->product->selling_price;
                
                if (isset($salesByDate[$date])) {
                    $salesByDate[$date] = max(0, $salesByDate[$date] - $amount);
                }

                // خصم الربح
                if ($movement->product->purchase_price && $movement->product->purchase_price > 0) {
                    $profitPerUnit = $movement->product->selling_price - $movement->product->purchase_price;
                    $profit = $profitPerUnit * $movement->quantity;
                    
                    if (isset($profitsByDate[$date])) {
                        $profitsByDate[$date] = max(0, $profitsByDate[$date] - $profit);
                    }
                    
                    if (isset($profitsWithMarginByDate[$date])) {
                        $profitsWithMarginByDate[$date] = max(0, $profitsWithMarginByDate[$date] - $profit);
                    }
                }
            }
        }

        // ترتيب التواريخ
        ksort($salesByDate);
        ksort($profitsByDate);
        ksort($profitsWithMarginByDate);

        return [
            'sales_by_date' => [
                'categories' => array_keys($salesByDate),
                'values' => array_values($salesByDate),
            ],
            'profits_by_date' => [
                'categories' => array_keys($profitsByDate),
                'values' => array_values($profitsByDate),
            ],
            'profits_with_margin_by_date' => [
                'categories' => array_keys($profitsWithMarginByDate),
                'values' => array_values($profitsWithMarginByDate),
            ],
        ];
    }

    private function saveReport($statistics, $chartData, $request, $dateFrom, $dateTo)
    {
        // تحضير الفلاتر للمقارنة
        $filters = $request->except(['_token', 'page']);

        // ترتيب المفاتيح لضمان المقارنة الصحيحة
        ksort($filters);
        $filtersJson = json_encode($filters, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        // جلب جميع التقارير بنفس التواريخ في آخر 24 ساعة
        $recentReports = SalesReport::where('date_from', $dateFrom)
            ->where('date_to', $dateTo)
            ->where('created_at', '>=', now()->subHours(24))
            ->get();

        // التحقق من وجود تقرير بنفس الفلاتر
        $existingReport = $recentReports->first(function($report) use ($filtersJson) {
            // تحويل filters من array إلى JSON للمقارنة
            $reportFilters = $report->filters ?? [];
            ksort($reportFilters);
            $reportFiltersJson = json_encode($reportFilters, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            return $reportFiltersJson === $filtersJson;
        });

        // إذا وُجد تقرير في آخر 24 ساعة بنفس الفلاتر، نتخطى الحفظ
        if ($existingReport) {
            return;
        }

        // حفظ تقرير جديد
        SalesReport::create([
            'report_date' => now()->toDateString(),
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
            'filters' => $filters,
            'total_amount_with_delivery' => $statistics['total_amount_with_delivery'],
            'total_amount_without_delivery' => $statistics['total_amount_without_delivery'],
            'total_profit_without_margin' => $statistics['total_profit_without_margin'],
            'total_profit_with_margin' => $statistics['total_profit_with_margin'],
            'total_margin_amount' => $statistics['total_margin_amount'],
            'orders_count' => $statistics['orders_count'],
            'items_count' => $statistics['items_count'],
            'most_sold_product_id' => null,
            'least_sold_product_id' => null,
            'chart_data' => $chartData,
        ]);
    }

    /**
     * Search products for sales report filter (AJAX)
     */
    public function searchProducts(Request $request)
    {
        try {
            $search = $request->get('search', '');

            if (empty($search)) {
                return response()->json([]);
            }

            $products = \App\Models\Product::select('id', 'name', 'code')
                ->where(function($query) use ($search) {
                    $query->where('name', 'LIKE', "%{$search}%")
                          ->orWhere('code', 'LIKE', "%{$search}%");
                })
                ->orderBy('name')
                ->limit(20)
                ->get();

            return response()->json($products);
        } catch (\Exception $e) {
            \Log::error('Error searching products: ' . $e->getMessage());
            return response()->json(['error' => 'حدث خطأ في البحث'], 500);
        }
    }
}
