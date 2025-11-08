<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
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

        // فلتر حالة الطلب (مقيدة/غير مقيدة/الكل)
        if ($request->filled('order_status')) {
            if ($request->order_status === 'confirmed') {
                $ordersQuery->where('status', 'confirmed');
            } elseif ($request->order_status === 'pending') {
                $ordersQuery->where('status', 'pending');
            }
            // إذا كان 'all' أو فارغ، لا نضيف فلتر
        }

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

        // فلتر الطلبات المسترجعة (مسترجعة/غير مسترجعة/الكل)
        if ($request->filled('orders_returned')) {
            if ($request->orders_returned === 'returned') {
                $ordersQuery->where('status', 'returned');
            } elseif ($request->orders_returned === 'not_returned') {
                $ordersQuery->where('status', '!=', 'returned');
            }
        }

        // فلتر المواد المسترجعة (مسترجعة/غير مسترجعة/الكل)
        // هذا يحتاج إلى منطق معقد - سنتعامل معه في الحسابات

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

        // تطبيق فلتر التاريخ
        $ordersQuery->where(function($q) use ($dateFrom, $dateTo) {
            $q->where(function($subQ) use ($dateFrom, $dateTo) {
                // للطلبات المقيدة: استخدام confirmed_at
                $subQ->where('status', 'confirmed')
                     ->whereBetween(DB::raw('DATE(confirmed_at)'), [$dateFrom, $dateTo]);
            })->orWhere(function($subQ) use ($dateFrom, $dateTo) {
                // للطلبات غير المقيدة: استخدام created_at
                $subQ->where('status', 'pending')
                     ->whereBetween(DB::raw('DATE(created_at)'), [$dateFrom, $dateTo]);
            })->orWhere(function($subQ) use ($dateFrom, $dateTo) {
                // للطلبات المسترجعة: استخدام returned_at
                $subQ->where('status', 'returned')
                     ->whereBetween(DB::raw('DATE(returned_at)'), [$dateFrom, $dateTo]);
            });
        });

        // جلب الطلبات
        $orders = $ordersQuery->get();
        $orderIds = $orders->pluck('id');

        // حساب الإحصائيات
        $statistics = $this->calculateStatistics($orders, $orderIds, $deliveryFee, $profitMargin, $request);

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

    private function calculateStatistics($orders, $orderIds, $deliveryFee, $profitMargin, $request)
    {
        // جلب جميع order_items للطلبات المحددة
        $orderItems = OrderItem::whereIn('order_id', $orderIds)->get();

        // جلب جميع return_items للطلبات المحددة
        $returnItems = ReturnItem::whereIn('order_id', $orderIds)->get();

        // حساب المبلغ الكلي بدون توصيل
        $totalAmountWithoutDelivery = $orderItems->sum('subtotal');

        // حساب مبلغ الإرجاع (من return_items)
        $returnAmount = 0;
        foreach ($returnItems as $returnItem) {
            $orderItem = $orderItems->firstWhere('id', $returnItem->order_item_id);
            if ($orderItem) {
                $returnAmount += $orderItem->unit_price * $returnItem->quantity_returned;
            }
        }

        // المبلغ الكلي بدون توصيل بعد خصم الإرجاع
        $totalAmountWithoutDelivery -= $returnAmount;

        // عدد الطلبات المقيدة
        $confirmedOrders = $orders->where('status', 'confirmed');
        $confirmedOrderIds = $confirmedOrders->pluck('id');

        // عدد الطلبات المسترجعة كلياً
        $fullyReturnedOrders = $orders->filter(function($order) {
            // طلب مسترجع كلياً إذا كان status = 'returned' وليس is_partial_return
            return $order->status === 'returned' && !$order->is_partial_return;
        });

        // عدد الطلبات المقيدة غير المسترجعة كلياً
        $confirmedNonFullyReturnedCount = $confirmedOrders->filter(function($order) {
            return $order->status !== 'returned' || $order->is_partial_return;
        })->count();

        // حساب المبلغ الكلي مع التوصيل
        $totalAmountWithDelivery = $totalAmountWithoutDelivery + ($confirmedNonFullyReturnedCount * $deliveryFee);

        // حساب الأرباح بدون فروقات
        $totalProfitWithoutMargin = 0;
        foreach ($orderItems as $item) {
            if ($item->product && $item->product->purchase_price) {
                $itemProfit = ($item->unit_price - $item->product->purchase_price) * $item->quantity;
                $totalProfitWithoutMargin += $itemProfit;
            }
        }

        // خصم الأرباح من المواد المسترجعة
        foreach ($returnItems as $returnItem) {
            $orderItem = $orderItems->firstWhere('id', $returnItem->order_item_id);
            if ($orderItem && $orderItem->product && $orderItem->product->purchase_price) {
                $returnProfit = ($orderItem->unit_price - $orderItem->product->purchase_price) * $returnItem->quantity_returned;
                $totalProfitWithoutMargin -= $returnProfit;
            }
        }

        // حساب مبلغ الفروقات (فقط للطلبات المقيدة غير المسترجعة كلياً)
        $totalMarginAmount = $confirmedNonFullyReturnedCount * $profitMargin;

        // حساب الأرباح مع الفروقات
        $totalProfitWithMargin = $totalProfitWithoutMargin + $totalMarginAmount;

        // عدد الطلبات
        $ordersCount = $orders->count();

        // عدد المواد (ناقص المواد المسترجعة)
        $itemsCount = $orderItems->sum('quantity') - $returnItems->sum('quantity_returned');

        // المنتج الأكثر مبيعاً
        $mostSoldProduct = $orderItems
            ->groupBy('product_id')
            ->map(function($items) {
                return $items->sum('quantity') - $items->sum(function($item) {
                    return $item->returnItems()->sum('quantity_returned');
                });
            })
            ->sortDesc()
            ->keys()
            ->first();

        // المنتج الأقل مبيعاً
        $leastSoldProduct = $orderItems
            ->groupBy('product_id')
            ->map(function($items) {
                return $items->sum('quantity') - $items->sum(function($item) {
                    return $item->returnItems()->sum('quantity_returned');
                });
            })
            ->sort()
            ->keys()
            ->first();

        return [
            'total_amount_with_delivery' => $totalAmountWithDelivery,
            'total_amount_without_delivery' => $totalAmountWithoutDelivery,
            'total_profit_without_margin' => $totalProfitWithoutMargin,
            'total_profit_with_margin' => $totalProfitWithMargin,
            'total_margin_amount' => $totalMarginAmount,
            'orders_count' => $ordersCount,
            'items_count' => max(0, $itemsCount),
            'most_sold_product_id' => $mostSoldProduct,
            'least_sold_product_id' => $leastSoldProduct,
            'return_amount' => $returnAmount,
        ];
    }

    private function calculateChartData($orders, $orderIds, $dateFrom, $dateTo, $request)
    {
        // Line Chart: المبيعات حسب التاريخ (المبالغ)
        $salesByDate = [];
        $profitsByDate = [];
        $profitsWithMarginByDate = [];

        // تجميع البيانات حسب التاريخ
        foreach ($orders as $order) {
            $date = null;
            if ($order->status === 'confirmed' && $order->confirmed_at) {
                $date = $order->confirmed_at->format('Y-m-d');
            } elseif ($order->status === 'pending') {
                $date = $order->created_at->format('Y-m-d');
            } elseif ($order->status === 'returned' && $order->returned_at) {
                $date = $order->returned_at->format('Y-m-d');
            }

            if ($date && $date >= $dateFrom && $date <= $dateTo) {
                if (!isset($salesByDate[$date])) {
                    $salesByDate[$date] = 0;
                    $profitsByDate[$date] = 0;
                    $profitsWithMarginByDate[$date] = 0;
                }

                // حساب المبلغ
                $orderAmount = $order->items->sum('subtotal');
                $salesByDate[$date] += $orderAmount;

                // حساب الربح
                $orderProfit = 0;
                foreach ($order->items as $item) {
                    if ($item->product && $item->product->purchase_price) {
                        $itemProfit = ($item->unit_price - $item->product->purchase_price) * $item->quantity;
                        $orderProfit += $itemProfit;
                    }
                }

                // خصم الإرجاع
                $returnProfit = 0;
                foreach ($order->returnItems as $returnItem) {
                    $orderItem = $order->items->firstWhere('id', $returnItem->order_item_id);
                    if ($orderItem && $orderItem->product && $orderItem->product->purchase_price) {
                        $returnProfit += ($orderItem->unit_price - $orderItem->product->purchase_price) * $returnItem->quantity_returned;
                    }
                }
                $orderProfit -= $returnProfit;

                $profitsByDate[$date] += $orderProfit;

                // إضافة الفروقات فقط للطلبات المقيدة غير المسترجعة كلياً
                if ($order->status === 'confirmed' && ($order->status !== 'returned' || $order->is_partial_return)) {
                    $profitsWithMarginByDate[$date] += $orderProfit + Setting::getProfitMargin();
                } else {
                    $profitsWithMarginByDate[$date] += $orderProfit;
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
        SalesReport::create([
            'report_date' => now()->toDateString(),
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
            'filters' => $request->except(['_token', 'page']),
            'total_amount_with_delivery' => $statistics['total_amount_with_delivery'],
            'total_amount_without_delivery' => $statistics['total_amount_without_delivery'],
            'total_profit_without_margin' => $statistics['total_profit_without_margin'],
            'total_profit_with_margin' => $statistics['total_profit_with_margin'],
            'total_margin_amount' => $statistics['total_margin_amount'],
            'orders_count' => $statistics['orders_count'],
            'items_count' => $statistics['items_count'],
            'most_sold_product_id' => $statistics['most_sold_product_id'],
            'least_sold_product_id' => $statistics['least_sold_product_id'],
            'chart_data' => $chartData,
        ]);
    }
}
