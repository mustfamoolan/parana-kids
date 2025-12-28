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
use App\Models\Investment;
use App\Models\InvestmentTarget;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class SalesReportController extends Controller
{
    // Cache للبيانات الثابتة
    private $investmentCache = [];
    private $adminPercentageCache = [];

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

        // حساب أرباح المخازن
        $warehouseProfitsData = $this->calculateWarehouseProfits(
            $orders,
            $orderIds,
            $request,
            $dateFrom,
            $dateTo,
            $statistics['total_expenses'],
            $statistics['items_count']
        );

        // حساب أرباح المنتجات
        $productProfitsResult = $this->calculateProductProfits(
            $orders,
            $orderIds,
            $request,
            $dateFrom,
            $dateTo,
            $warehouseProfitsData['expense_per_item']
        );
        
        $productProfitsData = $productProfitsResult['paginated'];
        $productProfitsTotals = $productProfitsResult['totals'];

        // حفظ التقرير في قاعدة البيانات
        $this->saveReport($statistics, $chartData, $request, $dateFrom, $dateTo);

        return view('admin.sales-report.index', compact(
            'warehouses',
            'products',
            'delegates',
            'suppliers',
            'statistics',
            'chartData',
            'warehouseProfitsData',
            'productProfitsData',
            'productProfitsTotals',
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
        $totalProfitWithoutMargin = 0; // ربح المدير فقط
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
                
                // التحقق: هل المنتج/المخزن له استثمار نشط؟
                $hasInvestors = $this->checkProductHasActiveInvestors($item->product);
                
                if ($hasInvestors) {
                    // حساب نسبة ربح المدير فقط
                    $adminProfitPercentage = $this->getAdminProfitPercentage($item->product);
                    $totalProfitWithoutMargin += $itemProfit * ($adminProfitPercentage / 100);
                } else {
                    // لا يوجد مستثمرين -> كل الربح للمدير
                    $totalProfitWithoutMargin += $itemProfit;
                }
            }
        }

        // خصم الربح من إرجاع الاستبدال (نحسب نسبة المدير فقط)
        $exchangeReturnProfitAdmin = 0;
        foreach ($exchangeReturnMovements as $movement) {
            if ($movement->product && $movement->product->purchase_price && $movement->product->purchase_price > 0) {
                $profitPerUnit = $movement->product->selling_price - $movement->product->purchase_price;
                $movementProfit = $profitPerUnit * $movement->quantity;
                
                $hasInvestors = $this->checkProductHasActiveInvestors($movement->product);
                if ($hasInvestors) {
                    $adminProfitPercentage = $this->getAdminProfitPercentage($movement->product);
                    $exchangeReturnProfitAdmin += $movementProfit * ($adminProfitPercentage / 100);
                } else {
                    $exchangeReturnProfitAdmin += $movementProfit;
                }
            }
        }
        $totalProfitWithoutMargin = max(0, $totalProfitWithoutMargin - $exchangeReturnProfitAdmin);

        // حساب الأرباح مع الفروقات
        $totalProfitWithMargin = $totalProfitWithoutMargin + $totalMarginAmount;

        // حساب إجمالي المصروفات حسب نطاق التاريخ
        // ملاحظة: هذا للإحصائيات العامة فقط، المصروفات الفعلية تُحسب في calculateWarehouseProfits و calculateProductProfits
        $totalExpenses = Expense::byDateRange($dateFrom, $dateTo)->sum('amount');

        // حساب الأرباح بعد خصم المصروفات (تقديري للإحصائيات العامة)
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

                // حساب الربح - فقط للمنتجات من المخزن المحدد (ربح المدير فقط)
                // الأرباح = (سعر البيع - سعر الشراء) × الكمية لكل منتج
                // ملاحظة: quantity في order_items يتم تحديثه تلقائياً بعد الإرجاع الجزئي
                // لذلك نستخدم القيم المتبقية مباشرة دون خصم الإرجاع مرة أخرى
                $orderProfit = 0; // ربح المدير فقط
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
                        
                        // التحقق: هل المنتج/المخزن له استثمار نشط؟
                        $hasInvestors = $this->checkProductHasActiveInvestors($item->product);
                        
                        if ($hasInvestors) {
                            // حساب نسبة ربح المدير فقط
                            $adminProfitPercentage = $this->getAdminProfitPercentage($item->product);
                            $orderProfit += $itemProfit * ($adminProfitPercentage / 100);
                        } else {
                            // لا يوجد مستثمرين -> كل الربح للمدير
                            $orderProfit += $itemProfit;
                        }
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

                // خصم الربح (نسبة المدير فقط)
                if ($movement->product->purchase_price && $movement->product->purchase_price > 0) {
                    $profitPerUnit = $movement->product->selling_price - $movement->product->purchase_price;
                    $movementProfit = $profitPerUnit * $movement->quantity;
                    
                    // حساب نسبة المدير
                    $hasInvestors = $this->checkProductHasActiveInvestors($movement->product);
                    if ($hasInvestors) {
                        $adminProfitPercentage = $this->getAdminProfitPercentage($movement->product);
                        $profit = $movementProfit * ($adminProfitPercentage / 100);
                    } else {
                        $profit = $movementProfit; // كل الربح للمدير
                    }

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

    private function calculateWarehouseProfits($orders, $orderIds, $request, $dateFrom, $dateTo, $totalExpenses, $totalItemsCount)
    {
        // جلب جميع المخازن (أو المخزن المحدد)
        $warehousesQuery = Warehouse::query();
        if ($request->filled('warehouse_id')) {
            $warehousesQuery->where('id', $request->warehouse_id);
        }
        $warehouses = $warehousesQuery->get();

        // جلب جميع المصروفات في الفترة وتصنيفها
        $allExpenses = Expense::byDateRange($dateFrom, $dateTo)->get();
        $warehouseExpensesMap = []; // [warehouse_id => total]
        $generalExpenses = 0;
        
        foreach ($allExpenses as $expense) {
            if ($expense->product_id) {
                // مصروف مرتبط بمنتج - سيتم حسابه في calculateProductProfits
                continue;
            } elseif ($expense->warehouse_id) {
                // مصروف مرتبط بمخزن
                if (!isset($warehouseExpensesMap[$expense->warehouse_id])) {
                    $warehouseExpensesMap[$expense->warehouse_id] = 0;
                }
                $warehouseExpensesMap[$expense->warehouse_id] += $expense->amount;
            } else {
                // مصروف عام
                $generalExpenses += $expense->amount;
            }
        }

        // حساب مصروفات كل قطعة للمصروفات العامة
        $generalExpensePerItem = $totalItemsCount > 0 ? ($generalExpenses / $totalItemsCount) : 0;

        $warehouseProfits = [];

        foreach ($warehouses as $warehouse) {
            // جلب order_items للمخزن
            $warehouseOrderItems = OrderItem::whereIn('order_id', $orderIds)
                ->whereHas('product', function($q) use ($warehouse) {
                    $q->where('warehouse_id', $warehouse->id);
                })
                ->get();

            // حساب عدد القطع المباعة من هذا المخزن
            $warehouseItemsCount = $warehouseOrderItems->sum('quantity');
            
            // حساب عدد القطع من هذا المخزن في الفترة (للمصروفات المرتبطة بالمخزن)
            $warehouseItemsInPeriod = OrderItem::whereIn('order_id', $orderIds)
                ->whereHas('product', function($q) use ($warehouse) {
                    $q->where('warehouse_id', $warehouse->id);
                })
                ->sum('quantity');

            // حساب ربح المخزن بدون فروقات (ربح المدير فقط)
            $warehouseProfitWithoutMargin = 0;
            foreach ($warehouseOrderItems as $item) {
                if ($item->product && $item->product->purchase_price && $item->product->purchase_price > 0) {
                    $sellingPrice = $item->unit_price ?? 0;
                    $purchasePrice = $item->product->purchase_price ?? 0;
                    $quantity = $item->quantity ?? 0;
                    $profitPerUnit = $sellingPrice - $purchasePrice;
                    $itemProfit = $profitPerUnit * $quantity;
                    
                    // التحقق: هل المنتج/المخزن له استثمار نشط؟
                    $hasInvestors = $this->checkProductHasActiveInvestors($item->product);
                    
                    if ($hasInvestors) {
                        // حساب نسبة ربح المدير فقط
                        $adminProfitPercentage = $this->getAdminProfitPercentage($item->product);
                        $warehouseProfitWithoutMargin += $itemProfit * ($adminProfitPercentage / 100);
                    } else {
                        // لا يوجد مستثمرين -> كل الربح للمدير
                        $warehouseProfitWithoutMargin += $itemProfit;
                    }
                }
            }

            // خصم ربح إرجاع الاستبدال من هذا المخزن (نسبة المدير فقط)
            $warehouseExchangeReturnProfit = 0;
            $warehouseExchangeReturnMovements = ProductMovement::where('movement_type', 'return_exchange_bulk')
                ->where('warehouse_id', $warehouse->id)
                ->whereBetween(DB::raw('DATE(created_at)'), [$dateFrom, $dateTo])
                ->with('product')
                ->get();

            foreach ($warehouseExchangeReturnMovements as $movement) {
                if ($movement->product && $movement->product->purchase_price && $movement->product->purchase_price > 0) {
                    $profitPerUnit = $movement->product->selling_price - $movement->product->purchase_price;
                    $movementProfit = $profitPerUnit * $movement->quantity;
                    
                    // حساب نسبة المدير
                    $hasInvestors = $this->checkProductHasActiveInvestors($movement->product);
                    if ($hasInvestors) {
                        $adminProfitPercentage = $this->getAdminProfitPercentage($movement->product);
                        $warehouseExchangeReturnProfit += $movementProfit * ($adminProfitPercentage / 100);
                    } else {
                        $warehouseExchangeReturnProfit += $movementProfit;
                    }
                }
            }

            $warehouseProfitWithoutMargin = max(0, $warehouseProfitWithoutMargin - $warehouseExchangeReturnProfit);

            // حساب الفروقات للمخزن
            $warehouseMarginAmount = 0;
            $confirmedOrders = $orders->where('status', 'confirmed');

            foreach ($confirmedOrders as $order) {
                if ($order->status !== 'returned' || $order->is_partial_return) {
                    // جلب جميع items للطلب
                    $allOrderItems = $order->items;
                    $warehouseOrderItemsForOrder = $allOrderItems->filter(function($item) use ($warehouse) {
                        return $item->product && $item->product->warehouse_id == $warehouse->id;
                    });

                    // حساب نسبة المنتجات من هذا المخزن
                    $totalQuantity = $allOrderItems->sum('quantity');
                    $warehouseQuantity = $warehouseOrderItemsForOrder->sum('quantity');
                    $warehouseRatio = $totalQuantity > 0 ? ($warehouseQuantity / $totalQuantity) : 0;

                    // استخدام ربح الفروقات المحفوظ وقت التقييد
                    $orderProfitMargin = $order->profit_margin_at_confirmation ?? Setting::getProfitMargin();
                    $warehouseMarginAmount += $orderProfitMargin * $warehouseRatio;
                }
            }

            // ربح المخزن مع الفروقات
            $warehouseProfitWithMargin = $warehouseProfitWithoutMargin + $warehouseMarginAmount;

            // حساب مصروفات المخزن
            $warehouseSpecificExpenses = $warehouseExpensesMap[$warehouse->id] ?? 0;
            $warehouseExpensePerItem = $warehouseItemsInPeriod > 0 ? ($warehouseSpecificExpenses / $warehouseItemsInPeriod) : 0;
            $warehouseExpensesFromSpecific = $warehouseExpensePerItem * $warehouseItemsCount;
            $warehouseExpensesFromGeneral = $generalExpensePerItem * $warehouseItemsCount;
            $warehouseExpenses = $warehouseExpensesFromSpecific + $warehouseExpensesFromGeneral;
            
            // حساب expense_per_item للمخزن (للعرض)
            $warehouseExpensePerItemDisplay = $warehouseItemsCount > 0 ? ($warehouseExpenses / $warehouseItemsCount) : 0;

            // الربح الصافي للمخزن
            $warehouseNetProfit = $warehouseProfitWithMargin - $warehouseExpenses;

            $warehouseProfits[] = [
                'warehouse_id' => $warehouse->id,
                'warehouse_name' => $warehouse->name,
                'profit_without_margin' => $warehouseProfitWithoutMargin,
                'profit_with_margin' => $warehouseProfitWithMargin,
                'margin_amount' => $warehouseMarginAmount,
                'items_count' => $warehouseItemsCount,
                'expense_per_item' => $warehouseExpensePerItemDisplay,
                'warehouse_expenses' => $warehouseExpenses,
                'net_profit' => $warehouseNetProfit,
            ];
        }

        return [
            'warehouses' => $warehouseProfits,
            'expense_per_item' => $generalExpensePerItem, // للمصروفات العامة فقط
        ];
    }

    private function calculateProductProfits($orders, $orderIds, $request, $dateFrom, $dateTo, $expensePerItem)
    {
        // جلب جميع المخازن (أو المخزن المحدد)
        $warehousesQuery = Warehouse::query();
        if ($request->filled('warehouse_id')) {
            $warehousesQuery->where('id', $request->warehouse_id);
        }
        $warehouses = $warehousesQuery->get();

        // جلب جميع المصروفات في الفترة وتصنيفها
        $allExpenses = Expense::byDateRange($dateFrom, $dateTo)->get();
        $productExpensesMap = []; // [product_id => total]
        $warehouseExpensesMap = []; // [warehouse_id => total]
        $generalExpenses = 0;
        
        foreach ($allExpenses as $expense) {
            if ($expense->product_id) {
                // مصروف مرتبط بمنتج
                if (!isset($productExpensesMap[$expense->product_id])) {
                    $productExpensesMap[$expense->product_id] = 0;
                }
                $productExpensesMap[$expense->product_id] += $expense->amount;
            } elseif ($expense->warehouse_id) {
                // مصروف مرتبط بمخزن
                if (!isset($warehouseExpensesMap[$expense->warehouse_id])) {
                    $warehouseExpensesMap[$expense->warehouse_id] = 0;
                }
                $warehouseExpensesMap[$expense->warehouse_id] += $expense->amount;
            } else {
                // مصروف عام
                $generalExpenses += $expense->amount;
            }
        }
        
        // حساب إجمالي القطع المباعة في الفترة (للمصروفات العامة)
        $totalItemsInPeriod = OrderItem::whereIn('order_id', $orderIds)->sum('quantity');
        $generalExpensePerItem = $totalItemsInPeriod > 0 ? ($generalExpenses / $totalItemsInPeriod) : 0;

        $productProfits = [];

        foreach ($warehouses as $warehouse) {
            // جلب order_items للمخزن (تحسين الأداء باستخدام join بدلاً من whereHas)
            $warehouseOrderItems = OrderItem::select('order_items.*')
                ->join('products', 'order_items.product_id', '=', 'products.id')
                ->whereIn('order_items.order_id', $orderIds)
                ->where('products.warehouse_id', $warehouse->id)
                ->with(['product' => function($q) {
                    $q->select('id', 'name', 'code', 'warehouse_id', 'purchase_price', 'selling_price');
                }])
                ->get();

            // تجميع order_items حسب المنتج
            $productsData = [];
            foreach ($warehouseOrderItems as $item) {
                if (!$item->product) {
                    continue;
                }

                $productId = $item->product_id;

                if (!isset($productsData[$productId])) {
                    $productsData[$productId] = [
                        'product_id' => $productId,
                        'product_name' => $item->product->name,
                        'product_code' => $item->product->code,
                        'warehouse_id' => $warehouse->id,
                        'warehouse_name' => $warehouse->name,
                        'items_count' => 0,
                        'profit_without_margin' => 0,
                        'margin_amount' => 0,
                    ];
                }

                // حساب الربح بدون فروقات (ربح المدير فقط)
                if ($item->product->purchase_price && $item->product->purchase_price > 0) {
                    $sellingPrice = $item->unit_price ?? 0;
                    $purchasePrice = $item->product->purchase_price ?? 0;
                    $quantity = $item->quantity ?? 0;
                    $profitPerUnit = $sellingPrice - $purchasePrice;
                    $itemProfit = $profitPerUnit * $quantity;
                    
                    // التحقق: هل المنتج/المخزن له استثمار نشط؟
                    $hasInvestors = $this->checkProductHasActiveInvestors($item->product);
                    
                    if ($hasInvestors) {
                        // حساب نسبة ربح المدير فقط
                        $adminProfitPercentage = $this->getAdminProfitPercentage($item->product);
                        $productsData[$productId]['profit_without_margin'] += $itemProfit * ($adminProfitPercentage / 100);
                    } else {
                        // لا يوجد مستثمرين -> كل الربح للمدير
                        $productsData[$productId]['profit_without_margin'] += $itemProfit;
                    }
                }

                $productsData[$productId]['items_count'] += $item->quantity ?? 0;
            }

            // خصم ربح إرجاع الاستبدال لكل منتج (نسبة المدير فقط)
            $warehouseExchangeReturnMovements = ProductMovement::where('movement_type', 'return_exchange_bulk')
                ->where('warehouse_id', $warehouse->id)
                ->whereBetween(DB::raw('DATE(created_at)'), [$dateFrom, $dateTo])
                ->with('product')
                ->get();

            foreach ($warehouseExchangeReturnMovements as $movement) {
                if ($movement->product && $movement->product_id) {
                    $productId = $movement->product_id;

                    if (isset($productsData[$productId])) {
                        if ($movement->product->purchase_price && $movement->product->purchase_price > 0) {
                            $profitPerUnit = $movement->product->selling_price - $movement->product->purchase_price;
                            $movementProfit = $profitPerUnit * $movement->quantity;
                            
                            // حساب نسبة المدير
                            $hasInvestors = $this->checkProductHasActiveInvestors($movement->product);
                            if ($hasInvestors) {
                                $adminProfitPercentage = $this->getAdminProfitPercentage($movement->product);
                                $productsData[$productId]['profit_without_margin'] -= $movementProfit * ($adminProfitPercentage / 100);
                            } else {
                                $productsData[$productId]['profit_without_margin'] -= $movementProfit;
                            }
                        }
                    }
                }
            }

            // حساب الفروقات لكل منتج
            $confirmedOrders = $orders->where('status', 'confirmed');

            foreach ($confirmedOrders as $order) {
                if ($order->status !== 'returned' || $order->is_partial_return) {
                    $allOrderItems = $order->items;
                    $warehouseOrderItemsForOrder = $allOrderItems->filter(function($item) use ($warehouse) {
                        return $item->product && $item->product->warehouse_id == $warehouse->id;
                    });

                    if ($warehouseOrderItemsForOrder->isEmpty()) {
                        continue;
                    }

                    // حساب نسبة المنتجات من هذا المخزن
                    $totalQuantity = $allOrderItems->sum('quantity');
                    $warehouseQuantity = $warehouseOrderItemsForOrder->sum('quantity');
                    $warehouseRatio = $totalQuantity > 0 ? ($warehouseQuantity / $totalQuantity) : 0;

                    // استخدام ربح الفروقات المحفوظ وقت التقييد
                    $orderProfitMargin = $order->profit_margin_at_confirmation ?? Setting::getProfitMargin();
                    $totalMarginForWarehouse = $orderProfitMargin * $warehouseRatio;

                    // توزيع الفروقات على المنتجات حسب نسبة الكمية
                    $warehouseTotalQuantity = $warehouseOrderItemsForOrder->sum('quantity');

                    foreach ($warehouseOrderItemsForOrder as $orderItem) {
                        if ($orderItem->product && isset($productsData[$orderItem->product_id])) {
                            $productQuantity = $orderItem->quantity ?? 0;
                            $productRatio = $warehouseTotalQuantity > 0 ? ($productQuantity / $warehouseTotalQuantity) : 0;
                            $productsData[$orderItem->product_id]['margin_amount'] += $totalMarginForWarehouse * $productRatio;
                        }
                    }
                }
            }

            // حساب الربح الصافي لكل منتج
            foreach ($productsData as $productId => $productData) {
                $profitWithoutMargin = max(0, $productData['profit_without_margin']);
                $profitWithMargin = $profitWithoutMargin + $productData['margin_amount'];
                
                // حساب مصروفات المنتج
                $productSpecificExpenses = $productExpensesMap[$productId] ?? 0; // مصروفات مرتبطة بالمنتج مباشرة
                
                // حساب جزء من مصروفات المخزن
                $warehouseSpecificExpenses = 0;
                if (isset($warehouseExpensesMap[$productData['warehouse_id']])) {
                    // حساب عدد القطع من هذا المخزن في الفترة
                    $warehouseItemsInPeriod = OrderItem::whereIn('order_id', $orderIds)
                        ->whereHas('product', function($q) use ($productData) {
                            $q->where('warehouse_id', $productData['warehouse_id']);
                        })
                        ->sum('quantity');
                    
                    if ($warehouseItemsInPeriod > 0) {
                        $warehouseExpensePerItem = $warehouseExpensesMap[$productData['warehouse_id']] / $warehouseItemsInPeriod;
                        $warehouseSpecificExpenses = $warehouseExpensePerItem * $productData['items_count'];
                    }
                }
                
                // جزء من المصروفات العامة
                $generalExpensesForProduct = $generalExpensePerItem * $productData['items_count'];
                
                $totalProductExpenses = $productSpecificExpenses + $warehouseSpecificExpenses + $generalExpensesForProduct;
                
                // حساب expense_per_item للمنتج (للعرض)
                $productExpensePerItem = $productData['items_count'] > 0 ? ($totalProductExpenses / $productData['items_count']) : 0;
                
                $netProfit = $profitWithMargin - $totalProductExpenses;

                $productProfits[] = [
                    'warehouse_id' => $productData['warehouse_id'],
                    'warehouse_name' => $productData['warehouse_name'],
                    'product_id' => $productData['product_id'],
                    'product_name' => $productData['product_name'],
                    'product_code' => $productData['product_code'],
                    'items_count' => $productData['items_count'],
                    'profit_without_margin' => $profitWithoutMargin,
                    'profit_with_margin' => $profitWithMargin,
                    'margin_amount' => $productData['margin_amount'],
                    'expense_per_item' => $productExpensePerItem,
                    'product_expenses' => $totalProductExpenses,
                    'net_profit' => $netProfit,
                ];
            }
        }

        // ترتيب حسب المخزن ثم اسم المنتج
        usort($productProfits, function($a, $b) {
            if ($a['warehouse_name'] === $b['warehouse_name']) {
                return strcmp($a['product_name'], $b['product_name']);
            }
            return strcmp($a['warehouse_name'], $b['warehouse_name']);
        });

        // حساب الإجماليات من البيانات المجمعة (بدون جلب كل المنتجات)
        $productProfitsTotals = [
            'total_profit' => collect($productProfits)->sum('profit_with_margin'),
            'total_items' => collect($productProfits)->sum('items_count'),
            'total_expenses' => collect($productProfits)->sum('product_expenses'),
            'total_net_profit' => collect($productProfits)->sum('net_profit'),
        ];

        // Pagination
        $perPage = 50; // عدد المنتجات في كل صفحة
        $currentPage = $request->get('product_page', 1);
        $offset = ($currentPage - 1) * $perPage;
        $currentItems = array_slice($productProfits, $offset, $perPage);

        $paginatedProducts = new LengthAwarePaginator(
            $currentItems,
            count($productProfits),
            $perPage,
            $currentPage,
            [
                'path' => $request->url(),
                'pageName' => 'product_page',
                'query' => $request->except('product_page'),
            ]
        );

        return [
            'paginated' => $paginatedProducts,
            'totals' => $productProfitsTotals,
        ];
    }

    private function calculateProductProfitsAll($orders, $orderIds, $request, $dateFrom, $dateTo, $expensePerItem)
    {
        // نفس منطق calculateProductProfits لكن بدون pagination
        $warehousesQuery = Warehouse::query();
        if ($request->filled('warehouse_id')) {
            $warehousesQuery->where('id', $request->warehouse_id);
        }
        $warehouses = $warehousesQuery->get();

        // جلب جميع المصروفات في الفترة وتصنيفها
        $allExpenses = Expense::byDateRange($dateFrom, $dateTo)->get();
        $productExpensesMap = []; // [product_id => total]
        $warehouseExpensesMap = []; // [warehouse_id => total]
        $generalExpenses = 0;
        
        foreach ($allExpenses as $expense) {
            if ($expense->product_id) {
                // مصروف مرتبط بمنتج
                if (!isset($productExpensesMap[$expense->product_id])) {
                    $productExpensesMap[$expense->product_id] = 0;
                }
                $productExpensesMap[$expense->product_id] += $expense->amount;
            } elseif ($expense->warehouse_id) {
                // مصروف مرتبط بمخزن
                if (!isset($warehouseExpensesMap[$expense->warehouse_id])) {
                    $warehouseExpensesMap[$expense->warehouse_id] = 0;
                }
                $warehouseExpensesMap[$expense->warehouse_id] += $expense->amount;
            } else {
                // مصروف عام
                $generalExpenses += $expense->amount;
            }
        }
        
        // حساب إجمالي القطع المباعة في الفترة (للمصروفات العامة)
        $totalItemsInPeriod = OrderItem::whereIn('order_id', $orderIds)->sum('quantity');
        $generalExpensePerItem = $totalItemsInPeriod > 0 ? ($generalExpenses / $totalItemsInPeriod) : 0;

        $productProfits = [];

        foreach ($warehouses as $warehouse) {
            // تحسين الأداء باستخدام join بدلاً من whereHas
            $warehouseOrderItems = OrderItem::select('order_items.*')
                ->join('products', 'order_items.product_id', '=', 'products.id')
                ->whereIn('order_items.order_id', $orderIds)
                ->where('products.warehouse_id', $warehouse->id)
                ->with(['product' => function($q) {
                    $q->select('id', 'name', 'code', 'warehouse_id', 'purchase_price', 'selling_price');
                }])
                ->get();

            $productsData = [];
            foreach ($warehouseOrderItems as $item) {
                if (!$item->product) {
                    continue;
                }

                $productId = $item->product_id;

                if (!isset($productsData[$productId])) {
                    $productsData[$productId] = [
                        'product_id' => $productId,
                        'product_name' => $item->product->name,
                        'product_code' => $item->product->code,
                        'warehouse_id' => $warehouse->id,
                        'warehouse_name' => $warehouse->name,
                        'items_count' => 0,
                        'profit_without_margin' => 0,
                        'margin_amount' => 0,
                    ];
                }

                if ($item->product->purchase_price && $item->product->purchase_price > 0) {
                    $sellingPrice = $item->unit_price ?? 0;
                    $purchasePrice = $item->product->purchase_price ?? 0;
                    $quantity = $item->quantity ?? 0;
                    $profitPerUnit = $sellingPrice - $purchasePrice;
                    $itemProfit = $profitPerUnit * $quantity;
                    
                    // التحقق: هل المنتج/المخزن له استثمار نشط؟
                    $hasInvestors = $this->checkProductHasActiveInvestors($item->product);
                    
                    if ($hasInvestors) {
                        // حساب نسبة ربح المدير فقط
                        $adminProfitPercentage = $this->getAdminProfitPercentage($item->product);
                        $productsData[$productId]['profit_without_margin'] += $itemProfit * ($adminProfitPercentage / 100);
                    } else {
                        // لا يوجد مستثمرين -> كل الربح للمدير
                        $productsData[$productId]['profit_without_margin'] += $itemProfit;
                    }
                }

                $productsData[$productId]['items_count'] += $item->quantity ?? 0;
            }

            $warehouseExchangeReturnMovements = ProductMovement::where('movement_type', 'return_exchange_bulk')
                ->where('warehouse_id', $warehouse->id)
                ->whereBetween(DB::raw('DATE(created_at)'), [$dateFrom, $dateTo])
                ->with('product')
                ->get();

            foreach ($warehouseExchangeReturnMovements as $movement) {
                if ($movement->product && $movement->product_id) {
                    $productId = $movement->product_id;

                    if (isset($productsData[$productId])) {
                        if ($movement->product->purchase_price && $movement->product->purchase_price > 0) {
                            $profitPerUnit = $movement->product->selling_price - $movement->product->purchase_price;
                            $movementProfit = $profitPerUnit * $movement->quantity;
                            
                            // حساب نسبة المدير
                            $hasInvestors = $this->checkProductHasActiveInvestors($movement->product);
                            if ($hasInvestors) {
                                $adminProfitPercentage = $this->getAdminProfitPercentage($movement->product);
                                $productsData[$productId]['profit_without_margin'] -= $movementProfit * ($adminProfitPercentage / 100);
                            } else {
                                $productsData[$productId]['profit_without_margin'] -= $movementProfit;
                            }
                        }
                    }
                }
            }

            $confirmedOrders = $orders->where('status', 'confirmed');

            foreach ($confirmedOrders as $order) {
                if ($order->status !== 'returned' || $order->is_partial_return) {
                    $allOrderItems = $order->items;
                    $warehouseOrderItemsForOrder = $allOrderItems->filter(function($item) use ($warehouse) {
                        return $item->product && $item->product->warehouse_id == $warehouse->id;
                    });

                    if ($warehouseOrderItemsForOrder->isEmpty()) {
                        continue;
                    }

                    $totalQuantity = $allOrderItems->sum('quantity');
                    $warehouseQuantity = $warehouseOrderItemsForOrder->sum('quantity');
                    $warehouseRatio = $totalQuantity > 0 ? ($warehouseQuantity / $totalQuantity) : 0;

                    $orderProfitMargin = $order->profit_margin_at_confirmation ?? Setting::getProfitMargin();
                    $totalMarginForWarehouse = $orderProfitMargin * $warehouseRatio;

                    $warehouseTotalQuantity = $warehouseOrderItemsForOrder->sum('quantity');

                    foreach ($warehouseOrderItemsForOrder as $orderItem) {
                        if ($orderItem->product && isset($productsData[$orderItem->product_id])) {
                            $productQuantity = $orderItem->quantity ?? 0;
                            $productRatio = $warehouseTotalQuantity > 0 ? ($productQuantity / $warehouseTotalQuantity) : 0;
                            $productsData[$orderItem->product_id]['margin_amount'] += $totalMarginForWarehouse * $productRatio;
                        }
                    }
                }
            }

            foreach ($productsData as $productId => $productData) {
                $profitWithoutMargin = max(0, $productData['profit_without_margin']);
                $profitWithMargin = $profitWithoutMargin + $productData['margin_amount'];
                
                // حساب مصروفات المنتج
                $productSpecificExpenses = $productExpensesMap[$productId] ?? 0; // مصروفات مرتبطة بالمنتج مباشرة
                
                // حساب جزء من مصروفات المخزن
                $warehouseSpecificExpenses = 0;
                if (isset($warehouseExpensesMap[$productData['warehouse_id']])) {
                    // حساب عدد القطع من هذا المخزن في الفترة
                    $warehouseItemsInPeriod = OrderItem::whereIn('order_id', $orderIds)
                        ->whereHas('product', function($q) use ($productData) {
                            $q->where('warehouse_id', $productData['warehouse_id']);
                        })
                        ->sum('quantity');
                    
                    if ($warehouseItemsInPeriod > 0) {
                        $warehouseExpensePerItem = $warehouseExpensesMap[$productData['warehouse_id']] / $warehouseItemsInPeriod;
                        $warehouseSpecificExpenses = $warehouseExpensePerItem * $productData['items_count'];
                    }
                }
                
                // جزء من المصروفات العامة
                $generalExpensesForProduct = $generalExpensePerItem * $productData['items_count'];
                
                $totalProductExpenses = $productSpecificExpenses + $warehouseSpecificExpenses + $generalExpensesForProduct;
                
                // حساب expense_per_item للمنتج (للعرض)
                $productExpensePerItem = $productData['items_count'] > 0 ? ($totalProductExpenses / $productData['items_count']) : 0;
                
                $netProfit = $profitWithMargin - $totalProductExpenses;

                $productProfits[] = [
                    'warehouse_id' => $productData['warehouse_id'],
                    'warehouse_name' => $productData['warehouse_name'],
                    'product_id' => $productData['product_id'],
                    'product_name' => $productData['product_name'],
                    'product_code' => $productData['product_code'],
                    'items_count' => $productData['items_count'],
                    'profit_without_margin' => $profitWithoutMargin,
                    'profit_with_margin' => $profitWithMargin,
                    'margin_amount' => $productData['margin_amount'],
                    'expense_per_item' => $productExpensePerItem,
                    'product_expenses' => $totalProductExpenses,
                    'net_profit' => $netProfit,
                ];
            }
        }

        return $productProfits;
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

    /**
     * التحقق من وجود مستثمرين نشطين للمنتج/المخزن
     */
    private function checkProductHasActiveInvestors(Product $product): bool
    {
        $cacheKey = "warehouse_{$product->warehouse_id}";
        
        if (isset($this->investmentCache[$cacheKey])) {
            return $this->investmentCache[$cacheKey];
        }
        
        // البحث في البنية الجديدة (Projects)
        $hasNewInvestors = InvestmentTarget::where('target_type', 'warehouse')
            ->where('target_id', $product->warehouse_id)
            ->whereHas('investment', function($q) {
                $q->where('status', 'active')
                  ->where('start_date', '<=', now())
                  ->where(function($q2) {
                      $q2->whereNull('end_date')
                         ->orWhere('end_date', '>=', now());
                  })
                  ->whereHas('investors'); // التأكد من وجود مستثمرين
            })
            ->exists();
        
        // البحث في البنية القديمة
        $hasOldInvestors = Investment::where('warehouse_id', $product->warehouse_id)
            ->where('status', 'active')
            ->whereNotNull('investor_id')
            ->where('start_date', '<=', now())
            ->where(function($q) {
                $q->whereNull('end_date')
                  ->orWhere('end_date', '>=', now());
            })
            ->exists();
        
        $result = $hasNewInvestors || $hasOldInvestors;
        $this->investmentCache[$cacheKey] = $result;
        
        return $result;
    }

    /**
     * حساب نسبة ربح المدير للمنتج/المخزن
     */
    private function getAdminProfitPercentage(Product $product): float
    {
        $cacheKey = "warehouse_{$product->warehouse_id}_admin";
        
        if (isset($this->adminPercentageCache[$cacheKey])) {
            return $this->adminPercentageCache[$cacheKey];
        }
        
        // من البنية الجديدة
        $investment = Investment::whereHas('targets', function($q) use ($product) {
            $q->where('target_type', 'warehouse')
              ->where('target_id', $product->warehouse_id);
        })
        ->where('status', 'active')
        ->where('start_date', '<=', now())
        ->where(function($q) {
            $q->whereNull('end_date')
              ->orWhere('end_date', '>=', now());
        })
        ->first();
        
        if ($investment) {
            // جمع نسبة المدير من InvestmentInvestor إذا كان موجوداً
            $adminInvestorPercentage = $investment->investors()
                ->whereHas('investor', function($q) {
                    $q->where('is_admin', true);
                })
                ->sum('profit_percentage');
            
            $result = $adminInvestorPercentage > 0 
                ? $adminInvestorPercentage 
                : ($investment->admin_profit_percentage ?? 0);
            
            $this->adminPercentageCache[$cacheKey] = $result;
            return $result;
        }
        
        // من البنية القديمة
        $oldInvestment = Investment::where('warehouse_id', $product->warehouse_id)
            ->where('status', 'active')
            ->whereNotNull('investor_id')
            ->where('start_date', '<=', now())
            ->where(function($q) {
                $q->whereNull('end_date')
                  ->orWhere('end_date', '>=', now());
            })
            ->first();
        
        $result = $oldInvestment ? ($oldInvestment->admin_profit_percentage ?? 0) : 100;
        $this->adminPercentageCache[$cacheKey] = $result;
        
        return $result;
    }
}
