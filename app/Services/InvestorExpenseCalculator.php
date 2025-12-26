<?php

namespace App\Services;

use App\Models\Expense;
use App\Models\Investment;
use App\Models\InvestmentTarget;
use App\Models\InvestmentInvestor;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class InvestorExpenseCalculator
{
    /**
     * تقريب المبلغ لأقرب عملة صحيحة عراقية (مضاعفات 250 دينار)
     */
    private function roundToNearestCurrency(float $amount): float
    {
        return round($amount / 250) * 250;
    }

    /**
     * خصم المصروف من خزنة المستثمرين بناءً على حصتهم (cost_percentage)
     */
    public function deductExpenseFromInvestors(Expense $expense): void
    {
        DB::transaction(function() use ($expense) {
            if ($expense->product_id) {
                // مصروف مرتبط بمنتج معين
                $this->deductProductExpense($expense);
            } elseif ($expense->warehouse_id) {
                // مصروف مرتبط بمخزن معين
                $this->deductWarehouseExpense($expense);
            } else {
                // مصروف عام - توزيع على جميع المستثمرين حسب cost_percentage الإجمالي
                $this->deductGeneralExpense($expense);
            }
        });
    }

    /**
     * خصم مصروف منتج من المستثمرين
     */
    private function deductProductExpense(Expense $expense): void
    {
        $productId = $expense->product_id;
        $expenseAmount = $expense->amount;
        $expenseDate = $expense->expense_date ?? now();

        // جلب جميع الاستثمارات النشطة في هذا المنتج (البنية الجديدة)
        // استخدام expense_date لضمان استخدام نفس الاستثمارات النشطة وقت إنشاء المصروف
        $investmentIds = InvestmentTarget::where('target_type', 'product')
            ->where('target_id', $productId)
            ->pluck('investment_id');

        $newInvestments = Investment::whereIn('id', $investmentIds)
            ->where('status', 'active')
            ->where(function($q) use ($expenseDate) {
                $q->whereNull('end_date')
                  ->orWhere('end_date', '>=', $expenseDate);
            })
            ->where('start_date', '<=', $expenseDate)
            ->with('investors')
            ->get();

        foreach ($newInvestments as $investment) {
            foreach ($investment->investors as $investmentInvestor) {
                // حساب cost_percentage من investment_amount و total_value
                $costPercentage = ($investment->total_value > 0) ? ($investmentInvestor->investment_amount / $investment->total_value) * 100 : 0;

                // حساب حصة المستثمر من المصروف
                $investorShare = ($expenseAmount * $costPercentage) / 100;
                $investorShare = $this->roundToNearestCurrency($investorShare);

                if ($investorShare > 0) {
                    $this->deductFromInvestorTreasury(
                        $investmentInvestor->investor,
                        $investorShare,
                        $expense,
                        "مصروفات منتج #{$productId}"
                    );
                }
            }
        }

        // جلب الاستثمارات القديمة (backward compatibility)
        $oldInvestments = Investment::where('investment_type', 'product')
            ->where('product_id', $productId)
            ->where('status', 'active')
            ->whereNotNull('investor_id')
            ->where(function($q) use ($expenseDate) {
                $q->whereNull('end_date')
                  ->orWhere('end_date', '>=', $expenseDate);
            })
            ->where('start_date', '<=', $expenseDate)
            ->with('investor')
            ->get();

        foreach ($oldInvestments as $investment) {
            // حساب cost_percentage من investment_amount و total_value
            $costPercentage = 0;
            if ($investment->investment_amount && $investment->total_value > 0) {
                $costPercentage = ($investment->investment_amount / $investment->total_value) * 100;
            }

            // حساب حصة المستثمر من المصروف
            $investorShare = ($expenseAmount * $costPercentage) / 100;
            $investorShare = $this->roundToNearestCurrency($investorShare);

            if ($investorShare > 0) {
                $this->deductFromInvestorTreasury(
                    $investment->investor,
                    $investorShare,
                    $expense,
                    "مصروفات منتج #{$productId}"
                );
            }
        }
    }

    /**
     * خصم مصروف مخزن من المستثمرين
     */
    private function deductWarehouseExpense(Expense $expense): void
    {
        $warehouseId = $expense->warehouse_id;
        $expenseAmount = $expense->amount;
        $expenseDate = $expense->expense_date ?? now();

        // جلب جميع الاستثمارات النشطة في هذا المخزن (البنية الجديدة)
        // استخدام expense_date لضمان استخدام نفس الاستثمارات النشطة وقت إنشاء المصروف
        $investmentIds = InvestmentTarget::where('target_type', 'warehouse')
            ->where('target_id', $warehouseId)
            ->pluck('investment_id');

        $newInvestments = Investment::whereIn('id', $investmentIds)
            ->where('status', 'active')
            ->where(function($q) use ($expenseDate) {
                $q->whereNull('end_date')
                  ->orWhere('end_date', '>=', $expenseDate);
            })
            ->where('start_date', '<=', $expenseDate)
            ->with('investors')
            ->get();

        foreach ($newInvestments as $investment) {
            foreach ($investment->investors as $investmentInvestor) {
                // استخدام cost_percentage المحفوظ مباشرة
                $costPercentage = $investmentInvestor->cost_percentage ?? 0;

                // حساب حصة المستثمر من المصروف
                $investorShare = ($expenseAmount * $costPercentage) / 100;
                $investorShare = $this->roundToNearestCurrency($investorShare);

                if ($investorShare > 0) {
                    $this->deductFromInvestorTreasury(
                        $investmentInvestor->investor,
                        $investorShare,
                        $expense,
                        "مصروفات مخزن #{$warehouseId}"
                    );
                }
            }
        }

        // جلب الاستثمارات القديمة (backward compatibility)
        $oldInvestments = Investment::where('investment_type', 'warehouse')
            ->where('warehouse_id', $warehouseId)
            ->where('status', 'active')
            ->whereNotNull('investor_id')
            ->where(function($q) use ($expenseDate) {
                $q->whereNull('end_date')
                  ->orWhere('end_date', '>=', $expenseDate);
            })
            ->where('start_date', '<=', $expenseDate)
            ->with('investor')
            ->get();

        foreach ($oldInvestments as $investment) {
            // حساب cost_percentage من investment_amount و total_value
            $costPercentage = 0;
            if ($investment->investment_amount && $investment->total_value > 0) {
                $costPercentage = ($investment->investment_amount / $investment->total_value) * 100;
            }

            // حساب حصة المستثمر من المصروف
            $investorShare = ($expenseAmount * $costPercentage) / 100;
            $investorShare = $this->roundToNearestCurrency($investorShare);

            if ($investorShare > 0) {
                $this->deductFromInvestorTreasury(
                    $investment->investor,
                    $investorShare,
                    $expense,
                    "مصروفات مخزن #{$warehouseId}"
                );
            }
        }
    }

    /**
     * خصم مصروف عام من جميع المستثمرين
     */
    private function deductGeneralExpense(Expense $expense): void
    {
        $expenseAmount = $expense->amount;
        $expenseDate = $expense->expense_date ?? now();

        // جلب جميع الاستثمارات النشطة
        // استخدام expense_date لضمان استخدام نفس الاستثمارات النشطة وقت إنشاء المصروف
        $allInvestments = Investment::where('status', 'active')
            ->where(function($q) use ($expenseDate) {
                $q->whereNull('end_date')
                  ->orWhere('end_date', '>=', $expenseDate);
            })
            ->where('start_date', '<=', $expenseDate)
            ->with(['investors', 'targets'])
            ->get();

        // حساب إجمالي cost_percentage لجميع المستثمرين
        $totalCostPercentage = 0;
        $investorShares = [];

        foreach ($allInvestments as $investment) {
            foreach ($investment->investors as $investmentInvestor) {
                $costPercentage = ($investment->total_value > 0) ? ($investmentInvestor->investment_amount / $investment->total_value) * 100 : 0;
                $totalCostPercentage += $costPercentage;

                $investorId = $investmentInvestor->investor_id;
                if (!isset($investorShares[$investorId])) {
                    $investorShares[$investorId] = [
                        'investor' => $investmentInvestor->investor,
                        'total_cost_percentage' => 0,
                    ];
                }
                $investorShares[$investorId]['total_cost_percentage'] += $costPercentage;
            }
        }

        // توزيع المصروف على المستثمرين حسب cost_percentage الإجمالي
        if ($totalCostPercentage > 0) {
            foreach ($investorShares as $investorId => $data) {
                $investorShare = ($expenseAmount * $data['total_cost_percentage']) / $totalCostPercentage;
                $investorShare = $this->roundToNearestCurrency($investorShare);

                if ($investorShare > 0) {
                    $this->deductFromInvestorTreasury(
                        $data['investor'],
                        $investorShare,
                        $expense,
                        "مصروفات عامة"
                    );
                }
            }
        }
    }

    /**
     * خصم المبلغ من خزنة المستثمر
     */
    private function deductFromInvestorTreasury($investor, float $amount, Expense $expense, string $description): void
    {
        $treasury = $investor->treasury;
        if (!$treasury) {
            Log::warning("Investor {$investor->id} ({$investor->name}) does not have a treasury. Cannot deduct expense.");
            return;
        }

        // خصم من خزنة المستثمر
        $treasury->withdraw(
            $amount,
            'expense',
            $expense->id,
            $description . " - " . ($expense->expense_type_name ?? $expense->expense_type),
            auth()->id() ?? 1
        );
    }

    /**
     * إرجاع المصروف إلى خزنة المستثمرين بناءً على حصتهم (cost_percentage)
     * @param Expense $expense المصروف المراد إرجاعه
     * @param float|null $amount المبلغ المراد إرجاعه (إذا كان null، يتم استخدام مبلغ المصروف الحالي)
     */
    public function returnExpenseToInvestors(Expense $expense, float $amount = null): void
    {
        DB::transaction(function() use ($expense, $amount) {
            $returnAmount = $amount ?? $expense->amount;

            if ($expense->product_id) {
                // مصروف مرتبط بمنتج معين
                $this->returnProductExpense($expense, $returnAmount);
            } elseif ($expense->warehouse_id) {
                // مصروف مرتبط بمخزن معين
                $this->returnWarehouseExpense($expense, $returnAmount);
            } else {
                // مصروف عام - توزيع على جميع المستثمرين حسب cost_percentage الإجمالي
                $this->returnGeneralExpense($expense, $returnAmount);
            }
        });
    }

    /**
     * إرجاع مصروف منتج إلى المستثمرين
     */
    private function returnProductExpense(Expense $expense, float $returnAmount): void
    {
        $productId = $expense->product_id;
        $expenseDate = $expense->expense_date ?? now();

        // جلب جميع الاستثمارات النشطة في هذا المنتج (البنية الجديدة)
        // استخدام expense_date لضمان استخدام نفس الاستثمارات النشطة وقت إنشاء المصروف
        $investmentIds = InvestmentTarget::where('target_type', 'product')
            ->where('target_id', $productId)
            ->pluck('investment_id');

        $newInvestments = Investment::whereIn('id', $investmentIds)
            ->where('status', 'active')
            ->where(function($q) use ($expenseDate) {
                $q->whereNull('end_date')
                  ->orWhere('end_date', '>=', $expenseDate);
            })
            ->where('start_date', '<=', $expenseDate)
            ->with('investors')
            ->get();

        foreach ($newInvestments as $investment) {
            foreach ($investment->investors as $investmentInvestor) {
                // حساب cost_percentage من investment_amount و total_value
                $costPercentage = ($investment->total_value > 0) ? ($investmentInvestor->investment_amount / $investment->total_value) * 100 : 0;

                // حساب حصة المستثمر من المبلغ المراد إرجاعه
                $investorShare = ($returnAmount * $costPercentage) / 100;
                $investorShare = $this->roundToNearestCurrency($investorShare);

                if ($investorShare > 0) {
                    $this->returnToInvestorTreasury(
                        $investmentInvestor->investor,
                        $investorShare,
                        $expense,
                        "إرجاع مصروفات منتج #{$productId}"
                    );
                }
            }
        }

        // جلب الاستثمارات القديمة (backward compatibility)
        $oldInvestments = Investment::where('investment_type', 'product')
            ->where('product_id', $productId)
            ->where('status', 'active')
            ->whereNotNull('investor_id')
            ->where(function($q) use ($expenseDate) {
                $q->whereNull('end_date')
                  ->orWhere('end_date', '>=', $expenseDate);
            })
            ->where('start_date', '<=', $expenseDate)
            ->with('investor')
            ->get();

        foreach ($oldInvestments as $investment) {
            // حساب cost_percentage من investment_amount و total_value
            $costPercentage = 0;
            if ($investment->investment_amount && $investment->total_value > 0) {
                $costPercentage = ($investment->investment_amount / $investment->total_value) * 100;
            }

            // حساب حصة المستثمر من المبلغ المراد إرجاعه
            $investorShare = ($returnAmount * $costPercentage) / 100;
            $investorShare = $this->roundToNearestCurrency($investorShare);

            if ($investorShare > 0) {
                $this->returnToInvestorTreasury(
                    $investment->investor,
                    $investorShare,
                    $expense,
                    "إرجاع مصروفات منتج #{$productId}"
                );
            }
        }
    }

    /**
     * إرجاع مصروف مخزن إلى المستثمرين
     */
    private function returnWarehouseExpense(Expense $expense, float $returnAmount): void
    {
        $warehouseId = $expense->warehouse_id;
        $expenseDate = $expense->expense_date ?? now();

        // جلب جميع الاستثمارات النشطة في هذا المخزن (البنية الجديدة)
        // استخدام expense_date لضمان استخدام نفس الاستثمارات النشطة وقت إنشاء المصروف
        $investmentIds = InvestmentTarget::where('target_type', 'warehouse')
            ->where('target_id', $warehouseId)
            ->pluck('investment_id');

        $newInvestments = Investment::whereIn('id', $investmentIds)
            ->where('status', 'active')
            ->where(function($q) use ($expenseDate) {
                $q->whereNull('end_date')
                  ->orWhere('end_date', '>=', $expenseDate);
            })
            ->where('start_date', '<=', $expenseDate)
            ->with('investors')
            ->get();

        foreach ($newInvestments as $investment) {
            foreach ($investment->investors as $investmentInvestor) {
                // استخدام cost_percentage المحفوظ مباشرة
                $costPercentage = $investmentInvestor->cost_percentage ?? 0;

                // حساب حصة المستثمر من المبلغ المراد إرجاعه
                $investorShare = ($returnAmount * $costPercentage) / 100;
                $investorShare = $this->roundToNearestCurrency($investorShare);

                if ($investorShare > 0) {
                    $this->returnToInvestorTreasury(
                        $investmentInvestor->investor,
                        $investorShare,
                        $expense,
                        "إرجاع مصروفات مخزن #{$warehouseId}"
                    );
                }
            }
        }

        // جلب الاستثمارات القديمة (backward compatibility)
        $oldInvestments = Investment::where('investment_type', 'warehouse')
            ->where('warehouse_id', $warehouseId)
            ->where('status', 'active')
            ->whereNotNull('investor_id')
            ->where(function($q) use ($expenseDate) {
                $q->whereNull('end_date')
                  ->orWhere('end_date', '>=', $expenseDate);
            })
            ->where('start_date', '<=', $expenseDate)
            ->with('investor')
            ->get();

        foreach ($oldInvestments as $investment) {
            // حساب cost_percentage من investment_amount و total_value
            $costPercentage = 0;
            if ($investment->investment_amount && $investment->total_value > 0) {
                $costPercentage = ($investment->investment_amount / $investment->total_value) * 100;
            }

            // حساب حصة المستثمر من المبلغ المراد إرجاعه
            $investorShare = ($returnAmount * $costPercentage) / 100;
            $investorShare = $this->roundToNearestCurrency($investorShare);

            if ($investorShare > 0) {
                $this->returnToInvestorTreasury(
                    $investment->investor,
                    $investorShare,
                    $expense,
                    "إرجاع مصروفات مخزن #{$warehouseId}"
                );
            }
        }
    }

    /**
     * إرجاع مصروف عام إلى جميع المستثمرين
     */
    private function returnGeneralExpense(Expense $expense, float $returnAmount): void
    {
        $expenseDate = $expense->expense_date ?? now();

        // جلب جميع الاستثمارات النشطة
        // استخدام expense_date لضمان استخدام نفس الاستثمارات النشطة وقت إنشاء المصروف
        $allInvestments = Investment::where('status', 'active')
            ->where(function($q) use ($expenseDate) {
                $q->whereNull('end_date')
                  ->orWhere('end_date', '>=', $expenseDate);
            })
            ->where('start_date', '<=', $expenseDate)
            ->with(['investors', 'targets'])
            ->get();

        // حساب إجمالي cost_percentage لجميع المستثمرين
        $totalCostPercentage = 0;
        $investorShares = [];

        foreach ($allInvestments as $investment) {
            foreach ($investment->investors as $investmentInvestor) {
                $costPercentage = ($investment->total_value > 0) ? ($investmentInvestor->investment_amount / $investment->total_value) * 100 : 0;
                $totalCostPercentage += $costPercentage;

                $investorId = $investmentInvestor->investor_id;
                if (!isset($investorShares[$investorId])) {
                    $investorShares[$investorId] = [
                        'investor' => $investmentInvestor->investor,
                        'total_cost_percentage' => 0,
                    ];
                }
                $investorShares[$investorId]['total_cost_percentage'] += $costPercentage;
            }
        }

        // توزيع المبلغ المراد إرجاعه على المستثمرين حسب cost_percentage الإجمالي
        if ($totalCostPercentage > 0) {
            foreach ($investorShares as $investorId => $data) {
                $investorShare = ($returnAmount * $data['total_cost_percentage']) / $totalCostPercentage;
                $investorShare = $this->roundToNearestCurrency($investorShare);

                if ($investorShare > 0) {
                    $this->returnToInvestorTreasury(
                        $data['investor'],
                        $investorShare,
                        $expense,
                        "إرجاع مصروفات عامة"
                    );
                }
            }
        }
    }

    /**
     * إرجاع المبلغ إلى خزنة المستثمر
     */
    private function returnToInvestorTreasury($investor, float $amount, Expense $expense, string $description): void
    {
        $treasury = $investor->treasury;
        if (!$treasury) {
            Log::warning("Investor {$investor->id} ({$investor->name}) does not have a treasury. Cannot return expense.");
            return;
        }

        // إيداع في خزنة المستثمر
        $treasury->deposit(
            $amount,
            'expense',
            $expense->id,
            $description . " - " . ($expense->expense_type_name ?? $expense->expense_type),
            auth()->id() ?? 1
        );
    }
}

