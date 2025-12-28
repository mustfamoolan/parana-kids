<?php

namespace App\Http\Controllers\Investor;

use App\Http\Controllers\Controller;
use App\Models\Investor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

class InvestorController extends Controller
{
    /**
     * الحصول على المستثمر الحالي من Session
     */
    protected function getCurrentInvestor()
    {
        $investorId = Session::get('investor_id');
        if (!$investorId) {
            abort(403, 'غير مصرح لك بالوصول');
        }

        $investor = Investor::with('treasury')->find($investorId);
        if (!$investor || $investor->status !== 'active') {
            Session::forget('investor_id');
            Session::forget('investor_name');
            abort(403, 'حسابك غير نشط');
        }

        return $investor;
    }

    /**
     * عرض Dashboard المستثمر
     */
    public function dashboard(Request $request)
    {
        $investor = $this->getCurrentInvestor();
        $investor->load('treasury');

        // جلب خزنة المستثمر
        $treasury = $investor->treasury;

        // جلب جميع الاستثمارات المرتبطة بهذا المستثمر (لفلترة الأرباح)
        $investmentInvestorIds = \App\Models\InvestmentInvestor::where('investor_id', $investor->id)
            ->pluck('investment_id')
            ->toArray();
        
        $oldInvestmentIds = \App\Models\Investment::where('investor_id', $investor->id)
            ->whereNull('project_id')
            ->pluck('id')
            ->toArray();
        
        $allInvestmentIds = array_unique(array_merge($investmentInvestorIds, $oldInvestmentIds));

        // بناء query للأرباح مع فلتر التاريخ والاستثمارات
        $profitQuery = \App\Models\InvestorProfit::where('investor_id', $investor->id);
        
        // فلترة الأرباح حسب الاستثمارات الفعلية فقط
        if (!empty($allInvestmentIds)) {
            $profitQuery->whereIn('investment_id', $allInvestmentIds);
        } else {
            $profitQuery->whereRaw('1 = 0');
        }

        // فلتر التاريخ
        if ($request->filled('date_from') || $request->filled('date_to')) {
            $profitQuery->whereHas('order', function($q) use ($request) {
                if ($request->filled('date_from')) {
                    $q->where('confirmed_at', '>=', $request->date_from);
                }
                if ($request->filled('date_to')) {
                    $q->where('confirmed_at', '<=', $request->date_to . ' 23:59:59');
                }
            });
        }

        // حساب الأرباح المعلقة والمدفوعة وإجمالي الأرباح
        $totalProfit = (clone $profitQuery)->sum('profit_amount');
        $pendingProfits = (clone $profitQuery)->where('status', 'pending')->sum('profit_amount');
        $paidProfits = (clone $profitQuery)->where('status', 'paid')->sum('profit_amount');

        // جلب جميع الاستثمارات (لحساب المبالغ الإجمالية)
        $allInvestmentInvestors = \App\Models\InvestmentInvestor::where('investor_id', $investor->id)
            ->with(['investment.project', 'investment.targets'])
            ->latest()
            ->get();

        $allOldInvestments = $investor->investments()
            ->whereNull('project_id')
            ->with(['product', 'warehouse', 'privateWarehouse'])
            ->latest()
            ->get();

        // جلب الاستثمارات للعرض (آخر 10)
        $investmentInvestors = $allInvestmentInvestors->take(10);
        $oldInvestments = $allOldInvestments->take(10);

        // دمج الاستثمارات
        $allInvestments = collect();
        foreach ($investmentInvestors as $investmentInvestor) {
            $investment = $investmentInvestor->investment;
            if ($investment) {
                $allInvestments->push([
                    'type' => 'new',
                    'investment' => $investment,
                    'investmentInvestor' => $investmentInvestor,
                    'project' => $investment->project,
                ]);
            }
        }
        foreach ($oldInvestments as $oldInvestment) {
            $allInvestments->push([
                'type' => 'old',
                'investment' => $oldInvestment,
                'investmentInvestor' => null,
                'project' => null,
            ]);
        }

        // جلب الحركات مع فلتر التاريخ
        $transactions = [];
        if ($treasury) {
            $transactionsQuery = $treasury->transactions()->with('creator')->latest();
            
            // فلتر نوع الحركة
            if ($request->filled('transaction_type')) {
                $transactionsQuery->where('transaction_type', $request->transaction_type);
            }
            
            // فلتر نوع المرجع
            if ($request->filled('reference_type')) {
                $transactionsQuery->where('reference_type', $request->reference_type);
            }
            
            // فلتر التاريخ
            if ($request->filled('date_from')) {
                $transactionsQuery->where('created_at', '>=', $request->date_from);
            }
            if ($request->filled('date_to')) {
                $transactionsQuery->where('created_at', '<=', $request->date_to . ' 23:59:59');
            }
            
            $transactions = $transactionsQuery->limit(10)->get();
        }

        // حساب إجمالي مبلغ الاستثمار (من جميع الاستثمارات)
        $totalInvestmentAmount = 0;
        foreach ($allInvestmentInvestors as $investmentInvestor) {
            $investment = $investmentInvestor->investment;
            if ($investment) {
                $costPercentage = $investmentInvestor->cost_percentage ?? 0;
                $totalValue = $investment->total_value ?? 0;
                $totalInvestmentAmount += ($costPercentage / 100) * $totalValue;
            }
        }
        foreach ($allOldInvestments as $oldInvestment) {
            $totalInvestmentAmount += $oldInvestment->investment_amount ?? 0;
        }

        // حساب قيمة المخزن الحالي حسب نسبة المستثمر (متغير حسب الكمية الباقية)
        $currentWarehouseValue = 0;
        
        // من الاستثمارات الجديدة (البنية الجديدة) - جميع الاستثمارات
        foreach ($allInvestmentInvestors as $investmentInvestor) {
            $investment = $investmentInvestor->investment;
            if ($investment && $investment->targets) {
                foreach ($investment->targets as $target) {
                    if ($target->target_type === 'warehouse') {
                        $warehouse = \App\Models\Warehouse::find($target->target_id);
                        if ($warehouse) {
                            // حساب قيمة المنتجات الحالية في المخزن
                            $warehouseCurrentValue = 0;
                            $products = \App\Models\Product::where('warehouse_id', $warehouse->id)->with('sizes')->get();
                            
                            foreach ($products as $product) {
                                if ($product->purchase_price) {
                                    $totalQuantity = $product->sizes->sum('quantity');
                                    $warehouseCurrentValue += $product->purchase_price * $totalQuantity;
                                }
                            }
                            
                            // تطبيق نسبة المستثمر (cost_percentage)
                            $investorShare = ($investmentInvestor->cost_percentage ?? 0) / 100 * $warehouseCurrentValue;
                            $currentWarehouseValue += $investorShare;
                        }
                    }
                }
            }
        }
        
        // من الاستثمارات القديمة (إذا كان استثمار في مخزن) - جميع الاستثمارات
        foreach ($allOldInvestments as $oldInvestment) {
            if ($oldInvestment->warehouse_id || $oldInvestment->private_warehouse_id) {
                $warehouse = $oldInvestment->warehouse_id 
                    ? \App\Models\Warehouse::find($oldInvestment->warehouse_id)
                    : \App\Models\PrivateWarehouse::find($oldInvestment->private_warehouse_id);
                    
                if ($warehouse) {
                    // حساب قيمة المنتجات الحالية في المخزن
                    $warehouseCurrentValue = 0;
                    $products = \App\Models\Product::where('warehouse_id', $warehouse->id)->with('sizes')->get();
                    
                    foreach ($products as $product) {
                        if ($product->purchase_price) {
                            $totalQuantity = $product->sizes->sum('quantity');
                            $warehouseCurrentValue += $product->purchase_price * $totalQuantity;
                        }
                    }
                    
                    // تطبيق نسبة المستثمر (من investment_amount / total_value)
                    $investorPercentage = $oldInvestment->total_value > 0 
                        ? ($oldInvestment->investment_amount / $oldInvestment->total_value) * 100 
                        : 0;
                    $investorShare = $investorPercentage / 100 * $warehouseCurrentValue;
                    $currentWarehouseValue += $investorShare;
                }
            }
        }


        // حساب إجمالي المصروفات
        $totalExpenses = 0;
        if ($treasury) {
            $totalExpenses = $treasury->transactions()
                ->where('transaction_type', 'withdrawal')
                ->where('reference_type', 'expense')
                ->sum('amount') - 
                $treasury->transactions()
                ->where('transaction_type', 'deposit')
                ->where('reference_type', 'expense')
                ->sum('amount');
        }

        return view('investor.dashboard', compact(
            'investor',
            'treasury',
            'pendingProfits',
            'paidProfits',
            'totalProfit',
            'totalInvestmentAmount',
            'currentWarehouseValue',
            'totalExpenses',
            'allInvestments',
            'transactions'
        ));
    }

    /**
     * عرض استثمارات المستثمر
     */
    public function investments()
    {
        $investor = $this->getCurrentInvestor();

        // جلب الاستثمارات من البنية الجديدة
        $investmentInvestors = \App\Models\InvestmentInvestor::where('investor_id', $investor->id)
            ->with(['investment.project', 'investment.targets'])
            ->latest()
            ->get();

        // جلب الاستثمارات القديمة
        $oldInvestments = $investor->investments()
            ->whereNull('project_id')
            ->with(['product', 'warehouse', 'privateWarehouse'])
            ->latest()
            ->get();

        // دمج الاستثمارات
        $allInvestments = collect();
        foreach ($investmentInvestors as $investmentInvestor) {
            $investment = $investmentInvestor->investment;
            if ($investment) {
                $allInvestments->push([
                    'type' => 'new',
                    'investment' => $investment,
                    'investmentInvestor' => $investmentInvestor,
                    'project' => $investment->project,
                ]);
            }
        }
        foreach ($oldInvestments as $oldInvestment) {
            $allInvestments->push([
                'type' => 'old',
                'investment' => $oldInvestment,
                'investmentInvestor' => null,
                'project' => null,
            ]);
        }

        return view('investor.investments', compact('investor', 'allInvestments'));
    }

    /**
     * عرض حركات المستثمر
     */
    public function transactions(Request $request)
    {
        $investor = $this->getCurrentInvestor();
        $treasury = $investor->treasury;

        if (!$treasury) {
            $transactions = collect();
        } else {
            $query = $treasury->transactions()->with('creator')->latest();

            // فلترة حسب نوع الحركة
            if ($request->filled('transaction_type')) {
                $query->where('transaction_type', $request->transaction_type);
            }

            // فلترة حسب نوع المرجع
            if ($request->filled('reference_type')) {
                $query->where('reference_type', $request->reference_type);
            }

            // فلترة حسب التاريخ
            if ($request->filled('date_from')) {
                $query->where('created_at', '>=', $request->date_from);
            }
            if ($request->filled('date_to')) {
                $query->where('created_at', '<=', $request->date_to . ' 23:59:59');
            }

            $transactions = $query->paginate(20);
        }

        return view('investor.transactions', compact('investor', 'transactions'));
    }

    /**
     * عرض ملف المستثمر الشخصي
     */
    public function profile()
    {
        $investor = $this->getCurrentInvestor();
        $investor->load('treasury');

        return view('investor.profile', compact('investor'));
    }
}

