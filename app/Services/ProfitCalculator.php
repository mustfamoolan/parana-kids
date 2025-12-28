<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Product;
use App\Models\Warehouse;
use App\Models\ProfitRecord;
use App\Models\Treasury;
use App\Models\Expense;
use App\Models\OrderItem;
use Illuminate\Support\Facades\DB;

class ProfitCalculator
{
    /**
     * حساب ربح الطلب
     */
    public function calculateOrderProfit(Order $order): float
    {
        $profit = 0;

        foreach ($order->items as $item) {
            if ($item->product && $item->product->purchase_price) {
                $itemProfit = ($item->unit_price - $item->product->purchase_price) * $item->quantity;
                $profit += $itemProfit;
            }
        }

        return $profit;
    }

    /**
     * حساب قيمة المنتج
     */
    public function calculateProductValue(Product $product): float
    {
        if (!$product->purchase_price) {
            return 0;
        }

        $totalQuantity = $product->sizes()->sum('quantity');
        return $product->purchase_price * $totalQuantity;
    }

    /**
     * حساب قيمة المخزن
     */
    public function calculateWarehouseValue(Warehouse $warehouse): float
    {
        $value = 0;

        foreach ($warehouse->products as $product) {
            $value += $this->calculateProductValue($product);
        }

        return $value;
    }

    /**
     * حساب الربح المتوقع للمخزن
     * يحسب: (effective_price - purchase_price) × quantity لكل منتج
     */
    public function calculateWarehouseExpectedProfit(Warehouse $warehouse): float
    {
        $expectedProfit = 0;

        foreach ($warehouse->products as $product) {
            if (!$product->purchase_price) {
                continue;
            }

            $totalQuantity = $product->sizes()->sum('quantity');
            if ($totalQuantity <= 0) {
                continue;
            }

            // استخدام effective_price (يأخذ في الاعتبار التخفيضات)
            $effectivePrice = $product->effective_price;
            $productExpectedProfit = ($effectivePrice - $product->purchase_price) * $totalQuantity;
            $expectedProfit += $productExpectedProfit;
        }

        return $expectedProfit;
    }

    /**
     * تسجيل ربح الطلب عند التقييد
     */
    public function recordOrderProfit(Order $order): void
    {
        DB::transaction(function() use ($order) {
            $order->load('items.product.warehouse', 'items.size');

            // حساب الربح الإجمالي للطلب
            $orderProfit = $this->calculateOrderProfit($order);
            $orderTotalAmount = $order->total_amount;

            // حساب إجمالي عدد القطع المباعة في الطلب
            $totalItemsCount = $order->items->sum('quantity');

            // حساب المصروفات من نطاق تاريخي (شهر الطلب)
            $orderDate = $order->created_at ?? now();
            $dateFrom = $orderDate->copy()->startOfMonth()->toDateString();
            $dateTo = $orderDate->copy()->endOfMonth()->toDateString();
            
            // جلب جميع المصروفات في الفترة
            $allExpenses = Expense::byDateRange($dateFrom, $dateTo)->get();
            
            // تصنيف المصروفات
            $productExpenses = []; // مصروفات مرتبطة بمنتجات معينة [product_id => total]
            $warehouseExpenses = []; // مصروفات مرتبطة بمخازن معينة [warehouse_id => total]
            $generalExpenses = 0; // مصروفات عامة (بدون warehouse_id و product_id)
            
            foreach ($allExpenses as $expense) {
                if ($expense->product_id) {
                    // مصروف مرتبط بمنتج معين
                    if (!isset($productExpenses[$expense->product_id])) {
                        $productExpenses[$expense->product_id] = 0;
                    }
                    $productExpenses[$expense->product_id] += $expense->amount;
                } elseif ($expense->warehouse_id) {
                    // مصروف مرتبط بمخزن معين
                    if (!isset($warehouseExpenses[$expense->warehouse_id])) {
                        $warehouseExpenses[$expense->warehouse_id] = 0;
                    }
                    $warehouseExpenses[$expense->warehouse_id] += $expense->amount;
                } else {
                    // مصروف عام
                    $generalExpenses += $expense->amount;
                }
            }
            
            // حساب إجمالي القطع المباعة في الفترة (للمصروفات العامة)
            $totalItemsInPeriod = OrderItem::whereHas('order', function($q) use ($dateFrom, $dateTo) {
                $q->whereBetween(DB::raw('DATE(created_at)'), [$dateFrom, $dateTo])
                  ->where('status', 'confirmed');
            })->sum('quantity');

            // حساب مصروفات كل قطعة للمصروفات العامة
            $generalExpensePerItem = $totalItemsInPeriod > 0 ? ($generalExpenses / $totalItemsInPeriod) : 0;

            // حساب مصروفات الطلب (المصروفات العامة فقط)
            $orderExpenses = $generalExpensePerItem * $totalItemsCount;
            $grossProfit = $orderProfit; // الربح الإجمالي قبل خصم المصروفات
            $netProfit = max(0, $orderProfit - $orderExpenses); // الربح الصافي بعد خصم المصروفات
            
            ProfitRecord::create([
                'order_id' => $order->id,
                'delegate_id' => $order->delegate_id,
                'record_date' => now()->toDateString(),
                'gross_profit' => $grossProfit,
                'actual_profit' => $netProfit,
                'expenses_amount' => $orderExpenses,
                'items_count' => $totalItemsCount,
                'total_amount' => $orderTotalAmount,
                'record_type' => 'order',
                'status' => 'confirmed',
            ]);

            // تجميع المنتجات والمخازن المتأثرة
            $productsData = [];
            $warehousesData = [];

            foreach ($order->items as $item) {
                if (!$item->product) {
                    continue;
                }

                $productId = $item->product_id;
                $warehouseId = $item->product->warehouse_id;

                // حساب ربح هذا المنتج في الطلب
                if ($item->product->purchase_price) {
                    $itemProfit = ($item->unit_price - $item->product->purchase_price) * $item->quantity;
                } else {
                    $itemProfit = 0;
                }

                // تجميع بيانات المنتج
                if (!isset($productsData[$productId])) {
                    $productsData[$productId] = [
                        'product_id' => $productId,
                        'warehouse_id' => $warehouseId,
                        'actual_profit' => 0,
                        'total_amount' => 0,
                        'items_count' => 0,
                    ];
                }
                $productsData[$productId]['actual_profit'] += $itemProfit;
                $productsData[$productId]['total_amount'] += $item->subtotal;
                $productsData[$productId]['items_count'] += $item->quantity;

                // تجميع بيانات المخزن
                if ($warehouseId && !isset($warehousesData[$warehouseId])) {
                    $warehousesData[$warehouseId] = [
                        'warehouse_id' => $warehouseId,
                        'actual_profit' => 0,
                        'total_amount' => 0,
                        'items_count' => 0,
                    ];
                }
                if ($warehouseId) {
                    $warehousesData[$warehouseId]['actual_profit'] += $itemProfit;
                    $warehousesData[$warehouseId]['total_amount'] += $item->subtotal;
                    $warehousesData[$warehouseId]['items_count'] += $item->quantity;
                }
            }

            // إنشاء/تحديث سجلات الأرباح للمنتجات
            foreach ($productsData as $data) {
                $product = Product::find($data['product_id']);
                if ($product) {
                    $productValue = $this->calculateProductValue($product);
                    
                    // حساب مصروفات المنتج
                    $productSpecificExpenses = $productExpenses[$data['product_id']] ?? 0; // مصروفات مرتبطة بالمنتج مباشرة
                    $warehouseSpecificExpenses = 0; // جزء من مصروفات المخزن
                    if ($data['warehouse_id'] && isset($warehouseExpenses[$data['warehouse_id']])) {
                        // حساب عدد القطع من هذا المخزن في الفترة
                        $warehouseItemsInPeriod = OrderItem::whereHas('order', function($q) use ($dateFrom, $dateTo) {
                            $q->whereBetween(DB::raw('DATE(created_at)'), [$dateFrom, $dateTo])
                              ->where('status', 'confirmed');
                        })->whereHas('product', function($q) use ($data) {
                            $q->where('warehouse_id', $data['warehouse_id']);
                        })->sum('quantity');
                        
                        if ($warehouseItemsInPeriod > 0) {
                            // توزيع مصروفات المخزن على القطع
                            $warehouseExpensePerItem = $warehouseExpenses[$data['warehouse_id']] / $warehouseItemsInPeriod;
                            $warehouseSpecificExpenses = $warehouseExpensePerItem * $data['items_count'];
                        }
                    }
                    $generalExpensesForProduct = $generalExpensePerItem * $data['items_count']; // جزء من المصروفات العامة
                    
                    $totalProductExpenses = $productSpecificExpenses + $warehouseSpecificExpenses + $generalExpensesForProduct;
                    
                    $grossProfit = $data['actual_profit']; // الربح الإجمالي قبل خصم المصروفات
                    $netProfit = max(0, $data['actual_profit'] - $totalProductExpenses); // الربح الصافي بعد خصم المصروفات

                    ProfitRecord::create([
                        'product_id' => $data['product_id'],
                        'warehouse_id' => $data['warehouse_id'],
                        'order_id' => $order->id,
                        'delegate_id' => $order->delegate_id,
                        'record_date' => now()->toDateString(),
                        'product_value' => $productValue,
                        'gross_profit' => $grossProfit,
                        'actual_profit' => $netProfit,
                        'expenses_amount' => $totalProductExpenses,
                        'items_count' => $data['items_count'],
                        'total_amount' => $data['total_amount'],
                        'record_type' => 'product',
                        'status' => 'confirmed',
                    ]);
                }
            }

            // إنشاء/تحديث سجلات الأرباح للمخازن
            foreach ($warehousesData as $data) {
                $warehouse = Warehouse::find($data['warehouse_id']);
                if ($warehouse) {
                    $warehouseValue = $this->calculateWarehouseValue($warehouse);
                    
                    // حساب مصروفات المخزن
                    $warehouseSpecificExpenses = $warehouseExpenses[$data['warehouse_id']] ?? 0; // مصروفات مرتبطة بالمخزن مباشرة
                    
                    // حساب عدد القطع من هذا المخزن في الفترة
                    $warehouseItemsInPeriod = OrderItem::whereHas('order', function($q) use ($dateFrom, $dateTo) {
                        $q->whereBetween(DB::raw('DATE(created_at)'), [$dateFrom, $dateTo])
                          ->where('status', 'confirmed');
                    })->whereHas('product', function($q) use ($data) {
                        $q->where('warehouse_id', $data['warehouse_id']);
                    })->sum('quantity');
                    
                    // توزيع مصروفات المخزن على القطع
                    $warehouseExpensePerItem = $warehouseItemsInPeriod > 0 ? ($warehouseSpecificExpenses / $warehouseItemsInPeriod) : 0;
                    $warehouseExpensesForOrder = $warehouseExpensePerItem * $data['items_count'];
                    
                    // جزء من المصروفات العامة
                    $generalExpensesForWarehouse = $generalExpensePerItem * $data['items_count'];
                    
                    $totalWarehouseExpenses = $warehouseExpensesForOrder + $generalExpensesForWarehouse;
                    
                    $grossProfit = $data['actual_profit']; // الربح الإجمالي قبل خصم المصروفات
                    $netProfit = max(0, $data['actual_profit'] - $totalWarehouseExpenses); // الربح الصافي بعد خصم المصروفات

                    ProfitRecord::create([
                        'warehouse_id' => $data['warehouse_id'],
                        'order_id' => $order->id,
                        'delegate_id' => $order->delegate_id,
                        'record_date' => now()->toDateString(),
                        'warehouse_value' => $warehouseValue,
                        'gross_profit' => $grossProfit,
                        'actual_profit' => $netProfit,
                        'expenses_amount' => $totalWarehouseExpenses,
                        'items_count' => $data['items_count'],
                        'total_amount' => $data['total_amount'],
                        'record_type' => 'warehouse',
                        'status' => 'confirmed',
                    ]);
                }
            }

            // توزيع الأرباح على المستثمرين
            $totalInvestorProfit = 0;
            if (config('features.investor_profits_enabled', true)) {
                $investorCalculator = app(InvestorProfitCalculator::class);
                $totalInvestorProfit = $investorCalculator->distributeOrderProfits($order);
            }

            // ملاحظة: ربح المدير من الاستثمارات يُسجل في InvestorProfit (مثل باقي المستثمرين)
            // أرباح الطلبات العادية (بدون استثمار) تظهر في كشف المبيعات بدون حاجة لإيداع في خزنة
            // لا حاجة لإيداع أرباح الطلبات في خزنة على الإطلاق
        });
    }

    /**
     * تسجيل نقصان الربح عند الاسترجاع
     */
    public function recordReturnProfit(Order $order, float $returnProfit): void
    {
        ProfitRecord::create([
            'order_id' => $order->id,
            'delegate_id' => $order->delegate_id,
            'record_date' => now()->toDateString(),
            'return_amount' => abs($returnProfit), // قيمة موجبة للطرح
            'actual_profit' => -abs($returnProfit), // سالبة للربح
            'record_type' => 'order',
            'status' => 'returned',
        ]);
    }
}

