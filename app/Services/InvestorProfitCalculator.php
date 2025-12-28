<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Investment;
use App\Models\InvestmentTarget;
use App\Models\InvestorProfit;
use App\Models\ProfitRecord;
use App\Models\Project;
use App\Models\Treasury;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class InvestorProfitCalculator
{
    /**
     * توزيع أرباح الطلب على جميع المستثمرين
     * @return float مجموع ربح المستثمرين
     */
    public function distributeOrderProfits(Order $order): float
    {
        return DB::transaction(function() use ($order) {
            $order->load('items.product.warehouse', 'items.size');

            // تجميع الأرباح حسب المنتج والمخزن
            $productsData = [];
            $warehousesData = [];

            foreach ($order->items as $item) {
                if (!$item->product) {
                    continue;
                }

                $productId = $item->product_id;
                $warehouseId = $item->product->warehouse_id;

                // حساب ربح المنتج
                if ($item->product->purchase_price) {
                    $itemProfit = ($item->unit_price - $item->product->purchase_price) * $item->quantity;
                } else {
                    $itemProfit = 0;
                }

                // تجميع بيانات المنتج - فقط إذا كان له استثمار نشط
                $hasProductInvestment = $this->checkHasActiveInvestment('product', $productId);
                if ($hasProductInvestment) {
                    if (!isset($productsData[$productId])) {
                        $productsData[$productId] = [
                            'product_id' => $productId,
                            'profit' => 0,
                            'profit_record_id' => null,
                        ];
                    }
                    $productsData[$productId]['profit'] += $itemProfit;
                }

                // تجميع بيانات المخزن - فقط إذا كان له استثمار نشط
                if ($warehouseId) {
                    $hasWarehouseInvestment = $this->checkHasActiveInvestment('warehouse', $warehouseId);
                    if ($hasWarehouseInvestment) {
                        if (!isset($warehousesData[$warehouseId])) {
                            $warehousesData[$warehouseId] = [
                                'warehouse_id' => $warehouseId,
                                'profit' => 0,
                                'profit_record_id' => null,
                            ];
                        }
                        $warehousesData[$warehouseId]['profit'] += $itemProfit;
                    }
                }
            }

            // جلب profit_record_id من السجلات التي تم إنشاؤها للتو
            $profitRecords = ProfitRecord::where('order_id', $order->id)
                ->where('status', 'confirmed')
                ->get();

            foreach ($profitRecords as $record) {
                if ($record->record_type === 'product' && $record->product_id) {
                    if (isset($productsData[$record->product_id])) {
                        // استخدام الربح الإجمالي (gross_profit) - بدون خصم المصروفات
                        $productsData[$record->product_id]['profit'] = $record->gross_profit ?? $record->actual_profit ?? $productsData[$record->product_id]['profit'];
                        $productsData[$record->product_id]['profit_record_id'] = $record->id;
                        $productsData[$record->product_id]['expenses_amount'] = $record->expenses_amount ?? 0;
                        $productsData[$record->product_id]['items_count'] = $record->items_count ?? 0;
                    }
                }

                if ($record->record_type === 'warehouse' && $record->warehouse_id) {
                    if (isset($warehousesData[$record->warehouse_id])) {
                        // استخدام الربح الإجمالي (gross_profit) - بدون خصم المصروفات
                        $warehousesData[$record->warehouse_id]['profit'] = $record->gross_profit ?? $record->actual_profit ?? $warehousesData[$record->warehouse_id]['profit'];
                        $warehousesData[$record->warehouse_id]['profit_record_id'] = $record->id;
                        $warehousesData[$record->warehouse_id]['expenses_amount'] = $record->expenses_amount ?? 0;
                        $warehousesData[$record->warehouse_id]['items_count'] = $record->items_count ?? 0;
                    }
                }
            }

            // حساب مجموع ربح المستثمرين قبل التوزيع
            $totalInvestorProfit = 0;
            
            // توزيع أرباح المنتجات (الربح الإجمالي - بدون خصم المصروفات)
            foreach ($productsData as $data) {
                if ($data['profit'] > 0 && $data['profit_record_id']) {
                    $distributed = $this->distributeProductProfit(
                        $data['product_id'],
                        $data['profit'],
                        $order->id,
                        $data['profit_record_id'],
                        $order
                    );
                    $totalInvestorProfit += $distributed;
                }
            }

            // توزيع أرباح المخازن (الربح الإجمالي - بدون خصم المصروفات)
            foreach ($warehousesData as $data) {
                if ($data['profit'] > 0 && $data['profit_record_id']) {
                    $distributed = $this->distributeWarehouseProfit(
                        $data['warehouse_id'],
                        $data['profit'],
                        $order->id,
                        $data['profit_record_id'],
                        $order
                    );
                    $totalInvestorProfit += $distributed;
                }
            }

            // تحديث profit_records لتسجيل أن الأرباح تم توزيعها
            foreach ($profitRecords as $record) {
                $record->update(['investor_profit_distributed' => true]);
            }

            return $totalInvestorProfit;
        });
    }

    /**
     * توزيع ربح المنتج على جميع المستثمرين
     * @return float مجموع ربح المستثمرين الموزع
     */
    public function distributeProductProfit($productId, $profitAmount, $orderId, $profitRecordId, Order $order = null): float
    {
        // التحقق من وجود استثمارات نشطة لهذا المنتج قبل أي عملية
        if (!$this->checkHasActiveInvestment('product', $productId)) {
            return 0; // لا توجد استثمارات نشطة، لا نوزع أي شيء
        }
        
        // جلب جميع الاستثمارات النشطة لهذا المنتج
        $investments = Investment::where('investment_type', 'product')
            ->where('product_id', $productId)
            ->where('status', 'active')
            ->where(function($q) {
                $q->whereNull('end_date')
                  ->orWhere('end_date', '>=', now());
            })
            ->where('start_date', '<=', now())
            ->with('investor')
            ->get();

        $totalDistributed = 0;

        // البحث عن الاستثمارات عبر investment_targets (البنية الجديدة)
        $investmentIds = InvestmentTarget::where('target_type', 'product')
            ->where('target_id', $productId)
            ->pluck('investment_id');

        $newInvestments = Investment::whereIn('id', $investmentIds)
            ->where('status', 'active')
            ->where(function($q) {
                $q->whereNull('end_date')
                  ->orWhere('end_date', '>=', now());
            })
            ->where('start_date', '<=', now())
            ->with('investors')
            ->get();

        // جلب Order إذا لم يتم تمريره
        if (!$order) {
            $order = Order::find($orderId);
        }
        
        // حساب التكلفة الإجمالية المباعة للمنتج من الطلب
        $totalCostSold = 0;
        if ($order) {
            $order->load('items.product');
            foreach ($order->items as $item) {
                if ($item->product_id == $productId && $item->product && $item->product->purchase_price) {
                    $totalCostSold += $item->product->purchase_price * $item->quantity;
                }
            }
        }

        // توزيع الأرباح من الاستثمارات الجديدة
        foreach ($newInvestments as $investment) {
            $investment->load('project.treasury');
            
            // حساب مجموع cost_percentage للمستثمرين من الحقل المحفوظ
            $totalInvestorCostPercentage = 0;
            foreach ($investment->investors as $investmentInvestor) {
                $costPercentage = $investmentInvestor->cost_percentage ?? 0;
                $totalInvestorCostPercentage += $costPercentage;
            }
            
            // نسبة المدير من التكلفة = 100% - مجموع cost_percentage للمستثمرين
            $adminCostPercentage = max(0, 100 - $totalInvestorCostPercentage);
            
            foreach ($investment->investors as $investmentInvestor) {
                $investor = $investmentInvestor->investor;
                $investorProfit = ($profitAmount * $investmentInvestor->profit_percentage) / 100;
                $totalDistributed += $investorProfit;

                // استخدام cost_percentage المحفوظ مباشرة
                $costPercentage = $investmentInvestor->cost_percentage ?? 0;
                
                // حساب الكلفة المسترجعة: cost_percentage من التكلفة الإجمالية المباعة
                $costReturned = ($totalCostSold * $costPercentage) / 100;

                $profitRecord = InvestorProfit::create([
                    'investor_id' => $investmentInvestor->investor_id,
                    'investment_id' => $investment->id,
                    'profit_record_id' => $profitRecordId,
                    'order_id' => $orderId,
                    'product_id' => $productId,
                    'profit_amount' => $investorProfit,
                    'base_profit' => $profitAmount,
                    'profit_percentage' => $investmentInvestor->profit_percentage,
                    'profit_date' => now()->toDateString(),
                    'status' => 'pending',
                ]);

                // تحديث إجمالي ربح المستثمر
                $investor->increment('total_profit', $investorProfit);

                // إيداع الكلفة المسترجعة فقط في خزنة المستثمر (الربح يبقى معلقاً حتى يتم إيداعه يدوياً)
                $treasury = $investor->treasury;
                if ($treasury) {
                    // إيداع الكلفة المسترجعة فقط (تذهب مباشرة للرصيد الحالي)
                    if ($costReturned > 0) {
                        $product = \App\Models\Product::find($productId);
                        $productName = $product ? $product->name : "منتج #{$productId}";
                        $treasury->deposit(
                            $costReturned,
                            'cost_return',
                            $orderId,
                            "إرجاع كلفة من {$productName} - طلب #" . ($order->order_number ?? $orderId),
                            auth()->id() ?? 1
                        );
                    }
                    
                    // الربح يُسجل فقط في InvestorProfit كـ 'pending' بدون إيداع في الخزنة
                    // سيتم إيداعه لاحقاً عند استخدام depositProfits() من قبل المدير
                } else {
                    Log::warning("Investor {$investor->id} ({$investor->name}) does not have a treasury. Cannot deposit cost.");
                }
            }

            // حساب وإيداع ربح المدير وكلفته من هذا الاستثمار في الخزنة الفرعية
            if ($investment->project && $investment->project->treasury) {
                // حساب نسبة المدير: أولاً من InvestmentInvestor إذا كان موجوداً، وإلا من admin_profit_percentage
                $adminInvestorPercentage = $investment->investors()
                    ->whereHas('investor', function($q) {
                        $q->where('is_admin', true);
                    })
                    ->sum('profit_percentage');
                
                $adminPercentage = $adminInvestorPercentage > 0 
                    ? $adminInvestorPercentage 
                    : ($investment->admin_profit_percentage ?? 0);
                
                // إيداع ربح المدير (بدون تقريب لضمان الدقة 100%)
                if ($adminPercentage > 0) {
                    $adminProfit = ($profitAmount * $adminPercentage) / 100;
                    // لا نستخدم roundToNearestCurrency لربح المدير لضمان الدقة 100%
                    if ($adminProfit > 0) {
                        $investment->project->treasury->deposit(
                            $adminProfit,
                            'order',
                            $orderId,
                            "ربح المدير من استثمار #{$investment->id} - منتج #{$productId}",
                            auth()->id()
                        );
                    }
                }
                
                // إيداع كلفة المدير المسترجعة
                if ($adminCostPercentage > 0) {
                    $adminCostReturned = ($totalCostSold * $adminCostPercentage) / 100;
                    if ($adminCostReturned > 0) {
                        $investment->project->treasury->deposit(
                            $adminCostReturned,
                            'order',
                            $orderId,
                            "إرجاع كلفة المدير من استثمار #{$investment->id} - منتج #{$productId}",
                            auth()->id()
                        );
                    }
                }
                
            }
        }

        // البحث عن الاستثمارات القديمة (backward compatibility)
        $oldInvestments = Investment::where('investment_type', 'product')
            ->where('product_id', $productId)
            ->where('status', 'active')
            ->whereNotNull('investor_id')
            ->where(function($q) {
                $q->whereNull('end_date')
                  ->orWhere('end_date', '>=', now());
            })
            ->where('start_date', '<=', now())
            ->with('investor')
            ->get();

        if (!$oldInvestments->isEmpty()) {
            $totalPercentage = $oldInvestments->sum('profit_percentage');

            if ($totalPercentage > 100) {
                Log::warning("Product {$productId}: Total investment percentage exceeds 100% ({$totalPercentage}%)");
            } else {
                foreach ($oldInvestments as $investment) {
                    $investor = $investment->investor;
                    $investorProfit = ($profitAmount * $investment->profit_percentage) / 100;
                    $totalDistributed += $investorProfit;

                    // حساب cost_percentage من investment_amount و total_value (للبنية القديمة)
                    $costPercentage = 0;
                    if ($investment->investment_amount && $investment->total_value > 0) {
                        $costPercentage = ($investment->investment_amount / $investment->total_value) * 100;
                    }
                    
                    // حساب الكلفة المسترجعة
                    $costReturned = ($totalCostSold * $costPercentage) / 100;

                    $profitRecord = InvestorProfit::create([
                        'investor_id' => $investment->investor_id,
                        'investment_id' => $investment->id,
                        'profit_record_id' => $profitRecordId,
                        'order_id' => $orderId,
                        'product_id' => $productId,
                        'profit_amount' => $investorProfit,
                        'base_profit' => $profitAmount,
                        'profit_percentage' => $investment->profit_percentage,
                        'profit_date' => now()->toDateString(),
                        'status' => 'pending',
                    ]);

                    // تحديث إجمالي ربح المستثمر
                    $investor->increment('total_profit', $investorProfit);

                    // إيداع الكلفة المسترجعة فقط في خزنة المستثمر (الربح يبقى معلقاً حتى يتم إيداعه يدوياً)
                    $treasury = $investor->treasury;
                    if ($treasury) {
                        // إيداع الكلفة المسترجعة فقط (تذهب مباشرة للرصيد الحالي)
                        if ($costReturned > 0) {
                            $product = \App\Models\Product::find($productId);
                            $productName = $product ? $product->name : "منتج #{$productId}";
                            $treasury->deposit(
                                $costReturned,
                                'cost_return',
                                $orderId,
                                "إرجاع كلفة من {$productName} - طلب #" . ($order->order_number ?? $orderId),
                                auth()->id() ?? 1
                            );
                        }
                        
                        // الربح يُسجل فقط في InvestorProfit كـ 'pending' بدون إيداع في الخزنة
                        // سيتم إيداعه لاحقاً عند استخدام depositProfits() من قبل المدير
                    } else {
                        Log::warning("Investor {$investor->id} ({$investor->name}) does not have a treasury. Cannot deposit cost.");
                    }
                }
            }
        }

        return $totalDistributed;
    }

    /**
     * توزيع ربح المخزن على جميع المستثمرين
     * @return float مجموع ربح المستثمرين الموزع
     */
    public function distributeWarehouseProfit($warehouseId, $profitAmount, $orderId, $profitRecordId, Order $order = null): float
    {
        // التحقق من وجود استثمارات نشطة لهذا المخزن قبل أي عملية
        if (!$this->checkHasActiveInvestment('warehouse', $warehouseId)) {
            return 0; // لا توجد استثمارات نشطة، لا نوزع أي شيء
        }
        
        $totalDistributed = 0;

        // جلب Order إذا لم يتم تمريره
        if (!$order) {
            $order = Order::find($orderId);
        }
        
        // حساب التكلفة الإجمالية المباعة لجميع منتجات المخزن من الطلب
        $totalCostSold = 0;
        if ($order) {
            $order->load('items.product');
            foreach ($order->items as $item) {
                if ($item->product && $item->product->warehouse_id == $warehouseId && $item->product->purchase_price) {
                    $totalCostSold += $item->product->purchase_price * $item->quantity;
                }
            }
        }

        // البحث عن الاستثمارات عبر investment_targets (البنية الجديدة)
        $investmentIds = InvestmentTarget::where('target_type', 'warehouse')
            ->where('target_id', $warehouseId)
            ->pluck('investment_id');

        $newInvestments = Investment::whereIn('id', $investmentIds)
            ->where('status', 'active')
            ->where(function($q) {
                $q->whereNull('end_date')
                  ->orWhere('end_date', '>=', now());
            })
            ->where('start_date', '<=', now())
            ->with('investors')
            ->get();

        // توزيع الأرباح من الاستثمارات الجديدة
        foreach ($newInvestments as $investment) {
            $investment->load('project.treasury');
            
            // حساب مجموع cost_percentage للمستثمرين من الحقل المحفوظ
            $totalInvestorCostPercentage = 0;
            foreach ($investment->investors as $investmentInvestor) {
                $costPercentage = $investmentInvestor->cost_percentage ?? 0;
                $totalInvestorCostPercentage += $costPercentage;
            }
            
            // نسبة المدير من التكلفة = 100% - مجموع cost_percentage للمستثمرين
            $adminCostPercentage = max(0, 100 - $totalInvestorCostPercentage);
            
            foreach ($investment->investors as $investmentInvestor) {
                $investor = $investmentInvestor->investor;
                $investorProfit = ($profitAmount * $investmentInvestor->profit_percentage) / 100;
                $totalDistributed += $investorProfit;

                // استخدام cost_percentage المحفوظ مباشرة
                $costPercentage = $investmentInvestor->cost_percentage ?? 0;
                
                // حساب الكلفة المسترجعة: cost_percentage من التكلفة الإجمالية المباعة
                $costReturned = ($totalCostSold * $costPercentage) / 100;

                $profitRecord = InvestorProfit::create([
                    'investor_id' => $investmentInvestor->investor_id,
                    'investment_id' => $investment->id,
                    'profit_record_id' => $profitRecordId,
                    'order_id' => $orderId,
                    'warehouse_id' => $warehouseId,
                    'profit_amount' => $investorProfit,
                    'base_profit' => $profitAmount,
                    'profit_percentage' => $investmentInvestor->profit_percentage,
                    'profit_date' => now()->toDateString(),
                    'status' => 'pending',
                ]);

                // تحديث إجمالي ربح المستثمر
                $investor->increment('total_profit', $investorProfit);

                // إيداع الكلفة المسترجعة فقط في خزنة المستثمر (الربح يبقى معلقاً حتى يتم إيداعه يدوياً)
                $treasury = $investor->treasury;
                if ($treasury) {
                    // إيداع الكلفة المسترجعة فقط (تذهب مباشرة للرصيد الحالي)
                    if ($costReturned > 0) {
                        $warehouse = \App\Models\Warehouse::find($warehouseId);
                        $warehouseName = $warehouse ? $warehouse->name : "مخزن #{$warehouseId}";
                        $treasury->deposit(
                            $costReturned,
                            'cost_return',
                            $orderId,
                            "إرجاع كلفة من {$warehouseName} - طلب #" . ($order->order_number ?? $orderId),
                            auth()->id() ?? 1
                        );
                    }
                    
                    // الربح يُسجل فقط في InvestorProfit كـ 'pending' بدون إيداع في الخزنة
                    // سيتم إيداعه لاحقاً عند استخدام depositProfits() من قبل المدير
                } else {
                    Log::warning("Investor {$investor->id} ({$investor->name}) does not have a treasury. Cannot deposit cost.");
                }
            }

            // حساب وإيداع ربح المدير وكلفته من هذا الاستثمار في الخزنة الفرعية
            if ($investment->project && $investment->project->treasury) {
                // حساب نسبة المدير: أولاً من InvestmentInvestor إذا كان موجوداً، وإلا من admin_profit_percentage
                $adminInvestorPercentage = $investment->investors()
                    ->whereHas('investor', function($q) {
                        $q->where('is_admin', true);
                    })
                    ->sum('profit_percentage');
                
                $adminPercentage = $adminInvestorPercentage > 0 
                    ? $adminInvestorPercentage 
                    : ($investment->admin_profit_percentage ?? 0);
                
                // إيداع ربح المدير (بدون تقريب لضمان الدقة 100%)
                if ($adminPercentage > 0) {
                    $adminProfit = ($profitAmount * $adminPercentage) / 100;
                    // لا نستخدم roundToNearestCurrency لربح المدير لضمان الدقة 100%
                    if ($adminProfit > 0) {
                        $investment->project->treasury->deposit(
                            $adminProfit,
                            'order',
                            $orderId,
                            "ربح المدير من استثمار #{$investment->id} - مخزن #{$warehouseId}",
                            auth()->id()
                        );
                    }
                }
                
                // إيداع كلفة المدير المسترجعة
                if ($adminCostPercentage > 0) {
                    $adminCostReturned = ($totalCostSold * $adminCostPercentage) / 100;
                    if ($adminCostReturned > 0) {
                        $investment->project->treasury->deposit(
                            $adminCostReturned,
                            'order',
                            $orderId,
                            "إرجاع كلفة المدير من استثمار #{$investment->id} - مخزن #{$warehouseId}",
                            auth()->id()
                        );
                    }
                }
                
            }
        }

        // البحث عن الاستثمارات القديمة (backward compatibility)
        $oldInvestments = Investment::where('investment_type', 'warehouse')
            ->where('warehouse_id', $warehouseId)
            ->where('status', 'active')
            ->whereNotNull('investor_id')
            ->where(function($q) {
                $q->whereNull('end_date')
                  ->orWhere('end_date', '>=', now());
            })
            ->where('start_date', '<=', now())
            ->with('investor')
            ->get();

        if (!$oldInvestments->isEmpty()) {
            $totalPercentage = $oldInvestments->sum('profit_percentage');

            if ($totalPercentage > 100) {
                Log::warning("Warehouse {$warehouseId}: Total investment percentage exceeds 100%");
            } else {
                foreach ($oldInvestments as $investment) {
                    $investor = $investment->investor;
                    $investorProfit = ($profitAmount * $investment->profit_percentage) / 100;
                    $totalDistributed += $investorProfit;

                    // حساب cost_percentage من investment_amount و total_value (للبنية القديمة)
                    $costPercentage = 0;
                    if ($investment->investment_amount && $investment->total_value > 0) {
                        $costPercentage = ($investment->investment_amount / $investment->total_value) * 100;
                    }
                    
                    // حساب الكلفة المسترجعة
                    $costReturned = ($totalCostSold * $costPercentage) / 100;

                    $profitRecord = InvestorProfit::create([
                        'investor_id' => $investment->investor_id,
                        'investment_id' => $investment->id,
                        'profit_record_id' => $profitRecordId,
                        'order_id' => $orderId,
                        'warehouse_id' => $warehouseId,
                        'profit_amount' => $investorProfit,
                        'base_profit' => $profitAmount,
                        'profit_percentage' => $investment->profit_percentage,
                        'profit_date' => now()->toDateString(),
                        'status' => 'pending',
                    ]);

                    // تحديث إجمالي ربح المستثمر
                    $investor->increment('total_profit', $investorProfit);

                    // إيداع الكلفة المسترجعة والربح في خزنة المستثمر
                    $treasury = $investor->treasury;
                    if ($treasury) {
                        // إيداع الكلفة المسترجعة
                        if ($costReturned > 0) {
                            $warehouse = \App\Models\Warehouse::find($warehouseId);
                            $warehouseName = $warehouse ? $warehouse->name : "مخزن #{$warehouseId}";
                            $treasury->deposit(
                                $costReturned,
                                'cost_return',
                                $orderId,
                                "إرجاع كلفة من {$warehouseName} - طلب #" . ($order->order_number ?? $orderId),
                                auth()->id() ?? 1
                            );
                        }
                        
                        // الربح يُسجل فقط في InvestorProfit كـ 'pending' بدون إيداع في الخزنة
                        // سيتم إيداعه لاحقاً عند استخدام depositProfits() من قبل المدير
                    } else {
                        Log::warning("Investor {$investor->id} ({$investor->name}) does not have a treasury. Cannot deposit cost.");
                    }
                }
            }
        }

        return $totalDistributed;
    }

    /**
     * توزيع ربح المخزن الخاص على جميع المستثمرين
     */
    public function distributePrivateWarehouseProfit($privateWarehouseId, $profitAmount, $orderId, $profitRecordId, Order $order = null): void
    {
        // جلب Order إذا لم يتم تمريره
        if (!$order) {
            $order = Order::find($orderId);
        }
        
        // حساب التكلفة الإجمالية المباعة للمخزن الخاص من الطلب
        // ملاحظة: المخزن الخاص قد يحتوي على منتجات مختلفة، نحتاج حساب التكلفة من OrderItems
        $totalCostSold = 0;
        if ($order) {
            $order->load('items.product');
            // ملاحظة: قد نحتاج ربط OrderItem بالمخزن الخاص بطريقة مختلفة
            // حالياً سنستخدم نفس المنطق للمخزن العادي
            foreach ($order->items as $item) {
                if ($item->product && $item->product->purchase_price) {
                    // يمكن إضافة منطق إضافي للتحقق من أن المنتج ينتمي للمخزن الخاص
                    $totalCostSold += $item->product->purchase_price * $item->quantity;
                }
            }
        }

        $investments = Investment::where('investment_type', 'private_warehouse')
            ->where('private_warehouse_id', $privateWarehouseId)
            ->where('status', 'active')
            ->where(function($q) {
                $q->whereNull('end_date')
                  ->orWhere('end_date', '>=', now());
            })
            ->where('start_date', '<=', now())
            ->with('investor')
            ->get();

        if ($investments->isEmpty()) {
            return;
        }

        $totalPercentage = $investments->sum('profit_percentage');

        if ($totalPercentage > 100) {
            Log::warning("Private Warehouse {$privateWarehouseId}: Total investment percentage exceeds 100%");
            return;
        }

        foreach ($investments as $investment) {
            $investor = $investment->investor;
            $investorProfit = ($profitAmount * $investment->profit_percentage) / 100;

            // حساب cost_percentage من investment_amount و total_value (للبنية القديمة)
            $costPercentage = 0;
            if ($investment->investment_amount && $investment->total_value > 0) {
                $costPercentage = ($investment->investment_amount / $investment->total_value) * 100;
            }
            
            // حساب الكلفة المسترجعة
            $costReturned = ($totalCostSold * $costPercentage) / 100;

            InvestorProfit::create([
                'investor_id' => $investment->investor_id,
                'investment_id' => $investment->id,
                'profit_record_id' => $profitRecordId,
                'order_id' => $orderId,
                'private_warehouse_id' => $privateWarehouseId,
                'profit_amount' => $investorProfit,
                'base_profit' => $profitAmount,
                'profit_percentage' => $investment->profit_percentage,
                'profit_date' => now()->toDateString(),
                'status' => 'pending',
            ]);

            // تحديث إجمالي ربح المستثمر
            $investor->increment('total_profit', $investorProfit);

            // إيداع الكلفة المسترجعة والربح في خزنة المستثمر
            $treasury = $investor->treasury;
            if ($treasury) {
                // إيداع الكلفة المسترجعة
                if ($costReturned > 0) {
                    $privateWarehouse = \App\Models\PrivateWarehouse::find($privateWarehouseId);
                    $warehouseName = $privateWarehouse ? $privateWarehouse->name : "مخزن خاص #{$privateWarehouseId}";
                    $treasury->deposit(
                        $costReturned,
                        'cost_return',
                        $orderId,
                        "إرجاع كلفة من {$warehouseName} - طلب #" . ($order->order_number ?? $orderId),
                        auth()->id() ?? 1
                    );
                }
            } else {
                Log::warning("Investor {$investor->id} ({$investor->name}) does not have a treasury. Cannot deposit cost.");
            }
        }
    }

    /**
     * التحقق من وجود استثمار نشط للمنتج/المخزن
     */
    private function checkHasActiveInvestment(string $targetType, int $targetId): bool
    {
        // البحث في البنية الجديدة
        $hasNewInvestment = InvestmentTarget::where('target_type', $targetType)
            ->where('target_id', $targetId)
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
        
        if ($hasNewInvestment) {
            return true;
        }
        
        // البحث في البنية القديمة
        $query = Investment::where('investment_type', $targetType)
            ->where('status', 'active')
            ->whereNotNull('investor_id')
            ->where('start_date', '<=', now())
            ->where(function($q) {
                $q->whereNull('end_date')
                  ->orWhere('end_date', '>=', now());
            });
        
        if ($targetType === 'product') {
            $query->where('product_id', $targetId);
        } elseif ($targetType === 'warehouse') {
            $query->where('warehouse_id', $targetId);
        }
        
        return $query->exists();
    }
}

