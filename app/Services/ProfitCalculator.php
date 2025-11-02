<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Product;
use App\Models\Warehouse;
use App\Models\ProfitRecord;
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
     * تسجيل ربح الطلب عند التقييد
     */
    public function recordOrderProfit(Order $order): void
    {
        DB::transaction(function() use ($order) {
            $order->load('items.product.warehouse', 'items.size');

            // حساب الربح الإجمالي للطلب
            $orderProfit = $this->calculateOrderProfit($order);
            $orderTotalAmount = $order->total_amount;

            // إنشاء سجل ربح للطلب
            ProfitRecord::create([
                'order_id' => $order->id,
                'delegate_id' => $order->delegate_id,
                'record_date' => now()->toDateString(),
                'actual_profit' => $orderProfit,
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
                    ];
                }
                $productsData[$productId]['actual_profit'] += $itemProfit;
                $productsData[$productId]['total_amount'] += $item->subtotal;

                // تجميع بيانات المخزن
                if ($warehouseId && !isset($warehousesData[$warehouseId])) {
                    $warehousesData[$warehouseId] = [
                        'warehouse_id' => $warehouseId,
                        'actual_profit' => 0,
                        'total_amount' => 0,
                    ];
                }
                if ($warehouseId) {
                    $warehousesData[$warehouseId]['actual_profit'] += $itemProfit;
                    $warehousesData[$warehouseId]['total_amount'] += $item->subtotal;
                }
            }

            // إنشاء/تحديث سجلات الأرباح للمنتجات
            foreach ($productsData as $data) {
                $product = Product::find($data['product_id']);
                if ($product) {
                    $productValue = $this->calculateProductValue($product);

                    ProfitRecord::create([
                        'product_id' => $data['product_id'],
                        'warehouse_id' => $data['warehouse_id'],
                        'order_id' => $order->id,
                        'delegate_id' => $order->delegate_id,
                        'record_date' => now()->toDateString(),
                        'product_value' => $productValue,
                        'actual_profit' => $data['actual_profit'],
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

                    ProfitRecord::create([
                        'warehouse_id' => $data['warehouse_id'],
                        'order_id' => $order->id,
                        'delegate_id' => $order->delegate_id,
                        'record_date' => now()->toDateString(),
                        'warehouse_value' => $warehouseValue,
                        'actual_profit' => $data['actual_profit'],
                        'total_amount' => $data['total_amount'],
                        'record_type' => 'warehouse',
                        'status' => 'confirmed',
                    ]);
                }
            }
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

