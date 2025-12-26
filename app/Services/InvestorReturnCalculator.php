<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Warehouse;
use App\Models\Investment;
use App\Models\InvestmentInvestor;
use Illuminate\Support\Facades\Log;

class InvestorReturnCalculator
{
    /**
     * معالجة الإرجاع الجزئي وتأثيره على المستثمرين
     * 
     * @param Order $order الطلب المراد إرجاع منتجاته جزئياً
     * @param array $returnItems مصفوفة المنتجات المرجعة
     * @return void
     */
    public function processPartialReturnForInvestors(Order $order, array $returnItems): void
    {
        // ملاحظة: هذه الدالة يتم استدعاؤها من داخل DB::transaction في OrderController
        // لذلك لا نحتاج transaction هنا
        
        foreach ($returnItems as $returnItem) {
            $orderItem = OrderItem::find($returnItem['order_item_id']);
            
            if (!$orderItem) {
                Log::warning("OrderItem not found for return", [
                    'order_item_id' => $returnItem['order_item_id'],
                    'order_id' => $order->id
                ]);
                continue;
            }
            
            $product = Product::find($orderItem->product_id);
            if (!$product) {
                Log::warning("Product not found for return", [
                    'product_id' => $orderItem->product_id,
                    'order_id' => $order->id
                ]);
                continue;
            }
            
            $warehouse = $product->warehouse;
            if (!$warehouse) {
                Log::warning("Warehouse not found for product", [
                    'product_id' => $product->id,
                    'warehouse_id' => $product->warehouse_id,
                    'order_id' => $order->id
                ]);
                continue;
            }
            
            // قيمة الإرجاع = سعر البيع × الكمية المرجعة
            $returnValue = $orderItem->unit_price * $returnItem['quantity'];
            
            // البحث عن الاستثمارات المرتبطة بهذا المخزن وخصم من المستثمرين
            $this->deductFromInvestors($warehouse, $returnValue, $order, $orderItem, $returnItem['quantity']);
        }
    }
    
    /**
     * خصم قيمة الإرجاع من رصيد المستثمرين المرتبطين بالمخزن
     * 
     * @param Warehouse $warehouse المخزن المرتبط بالمنتج المرجع
     * @param float $returnValue قيمة الإرجاع (سعر البيع × الكمية)
     * @param Order $order الطلب
     * @param OrderItem $orderItem عنصر الطلب
     * @param int $quantity الكمية المرجعة
     * @return void
     */
    private function deductFromInvestors(Warehouse $warehouse, float $returnValue, Order $order, OrderItem $orderItem, int $quantity): void
    {
        // البنية الجديدة: InvestmentInvestor (المشاريع)
        $investments = Investment::whereHas('targets', function($q) use ($warehouse) {
            $q->where('target_type', 'warehouse')
              ->where('target_id', $warehouse->id);
        })->with(['investors.investor.treasury'])->get();
        
        foreach ($investments as $investment) {
            foreach ($investment->investors as $investmentInvestor) {
                $investor = $investmentInvestor->investor;
                if (!$investor) {
                    continue;
                }
                
                $costPercentage = $investmentInvestor->cost_percentage ?? 0;
                
                if ($costPercentage <= 0) {
                    continue;
                }
                
                // حساب المبلغ المخصوم = قيمة الإرجاع × نسبة المستثمر
                $deductAmount = ($returnValue * $costPercentage) / 100;
                
                if ($deductAmount > 0) {
                    $this->withdrawFromTreasury(
                        $investor,
                        $deductAmount,
                        $order,
                        $orderItem,
                        $quantity
                    );
                }
            }
        }
        
        // البنية القديمة: Investment مباشرة (غير مرتبطة بمشروع)
        $oldInvestments = Investment::where('warehouse_id', $warehouse->id)
            ->whereNull('project_id')
            ->with('investor.treasury')
            ->get();
            
        foreach ($oldInvestments as $investment) {
            $investor = $investment->investor;
            if (!$investor) {
                continue;
            }
            
            // حساب نسبة المستثمر من الاستثمار القديم
            $costPercentage = $investment->total_value > 0 
                ? ($investment->investment_amount / $investment->total_value) * 100 
                : 0;
                
            if ($costPercentage <= 0) {
                continue;
            }
            
            // حساب المبلغ المخصوم = قيمة الإرجاع × نسبة المستثمر
            $deductAmount = ($returnValue * $costPercentage) / 100;
            
            if ($deductAmount > 0) {
                $this->withdrawFromTreasury(
                    $investor,
                    $deductAmount,
                    $order,
                    $orderItem,
                    $quantity
                );
            }
        }
    }
    
    /**
     * خصم المبلغ من خزنة المستثمر
     * 
     * @param \App\Models\Investor $investor المستثمر
     * @param float $amount المبلغ المراد خصمه
     * @param Order $order الطلب
     * @param OrderItem $orderItem عنصر الطلب
     * @param int $quantity الكمية المرجعة
     * @return void
     */
    private function withdrawFromTreasury($investor, float $amount, Order $order, OrderItem $orderItem, int $quantity): void
    {
        $treasury = $investor->treasury;
        if (!$treasury) {
            Log::warning("Investor has no treasury", [
                'investor_id' => $investor->id,
                'investor_name' => $investor->name,
                'amount' => $amount,
                'order_id' => $order->id
            ]);
            return;
        }
        
        $description = "إرجاع جزئي - طلب #{$order->order_number} - {$quantity} قطعة من {$orderItem->product_name}";
        
        $treasury->withdraw(
            $amount,
            'partial_return',
            $order->id,
            $description,
            auth()->id()
        );
        
        Log::info("Deducted from investor treasury for partial return", [
            'investor_id' => $investor->id,
            'investor_name' => $investor->name,
            'amount' => $amount,
            'order_id' => $order->id,
            'order_number' => $order->order_number,
            'product_id' => $orderItem->product_id,
            'product_name' => $orderItem->product_name,
            'quantity' => $quantity,
            'treasury_id' => $treasury->id,
            'treasury_balance_after' => $treasury->fresh()->current_balance
        ]);
    }
}

