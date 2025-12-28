<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Investor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class InvestorController extends Controller
{
    /**
     * عرض قائمة المستثمرين - إعادة توجيه إلى صفحة المشاريع
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', Investor::class);

        // إعادة توجيه إلى صفحة المشاريع مع فتح تبويب المستثمرين
        $queryParams = $request->query();
        $queryParams['tab'] = 'investors';
        
        // تحويل معاملات البحث للمستثمرين
        if ($request->filled('search')) {
            $queryParams['investor_search'] = $request->search;
            unset($queryParams['search']);
        }
        if ($request->filled('status')) {
            $queryParams['investor_status'] = $request->status;
            unset($queryParams['status']);
        }

        return redirect()->route('admin.projects.index', $queryParams);
    }

    /**
     * عرض صفحة إضافة مستثمر
     */
    public function create()
    {
        $this->authorize('create', Investor::class);
        return view('admin.investors.create');
    }

    /**
     * حفظ مستثمر جديد
     */
    public function store(Request $request)
    {
        $this->authorize('create', Investor::class);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string|unique:investors,phone',
            'password' => 'required|string|min:6',
            'treasury_name' => 'required|string|max:255',
            'notes' => 'nullable|string',
        ]);

        return DB::transaction(function () use ($validated) {
            // إنشاء المستثمر
            // كلمة المرور سيتم hashها تلقائياً في boot() method
            $investor = Investor::create([
                'name' => $validated['name'],
                'phone' => $validated['phone'],
                'password' => $validated['password'], // سيتم hashها تلقائياً
                'balance' => 0,
                'status' => 'active',
                'notes' => $validated['notes'] ?? null,
            ]);

            // إنشاء خزنة للمستثمر (بدون رأس مال أولي)
            $treasury = \App\Models\Treasury::create([
                'name' => $validated['treasury_name'],
                'initial_capital' => 0,
                'current_balance' => 0,
                'created_by' => Auth::id(),
                'investor_id' => $investor->id, // ربط الخزنة بالمستثمر
            ]);

            // لا نسجل رأس المال الأولي كإيداع - فقط الإيداعات اليدوية تُسجل

            return redirect()->route('admin.investors.index')
                ->with('success', 'تم إنشاء المستثمر وخزنته بنجاح');
        });
    }

    /**
     * عرض تفاصيل المستثمر
     */
    public function show(Request $request, Investor $investor)
    {
        $this->authorize('view', $investor);

        $investor->load([
            'investments.product',
            'investments.warehouse',
            'investments.privateWarehouse',
            'transactions.createdBy'
        ]);

        // جلب جميع الاستثمارات المرتبطة بهذا المستثمر (البنية الجديدة والقديمة)
        $investmentInvestorIds = \App\Models\InvestmentInvestor::where('investor_id', $investor->id)
            ->pluck('investment_id')
            ->toArray();
        
        $oldInvestmentIds = \App\Models\Investment::where('investor_id', $investor->id)
            ->whereNull('project_id')
            ->pluck('id')
            ->toArray();
        
        $allInvestmentIds = array_unique(array_merge($investmentInvestorIds, $oldInvestmentIds));

        // بناء query لفلترة الأرباح حسب الاستثمارات الفعلية فقط
        $profitQuery = \App\Models\InvestorProfit::where('investor_id', $investor->id);
        
        // إذا كان لدى المستثمر استثمارات، قم بفلترة الأرباح حسبها فقط
        if (!empty($allInvestmentIds)) {
            $profitQuery->whereIn('investment_id', $allInvestmentIds);
        } else {
            // إذا لم يكن لدى المستثمر أي استثمارات، لا تظهر أي أرباح
            $profitQuery->whereRaw('1 = 0');
        }

        // فلتر التاريخ حسب تاريخ تقييد الطلب
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

        // إحصائيات (مع تطبيق الفلاتر)
        $totalProfit = (clone $profitQuery)->sum('profit_amount');
        $pendingProfitQuery = clone $profitQuery;
        $pendingProfit = $pendingProfitQuery->where('status', 'pending')->sum('profit_amount');
        $paidProfitQuery = clone $profitQuery;
        $paidProfit = $paidProfitQuery->where('status', 'paid')->sum('profit_amount');

        // جلب خزنة المستثمر من العلاقة المباشرة
        $investorTreasury = $investor->treasury;

        $treasuryDeposits = 0;
        $treasuryWithdrawals = 0;
        $pendingProfits = 0; // إجمالي الأرباح المعلقة
        $paidProfits = 0; // إجمالي الأرباح المدفوعة
        $totalInvestorExpenses = 0; // إجمالي المصروفات المخصومة
        
        // حساب الأرباح المعلقة والمدفوعة (مع تطبيق الفلاتر)
        $pendingProfitsQuery = clone $profitQuery;
        $pendingProfits = $pendingProfitsQuery->where('status', 'pending')->sum('profit_amount');
        $paidProfitsQuery = clone $profitQuery;
        $paidProfits = $paidProfitsQuery->where('status', 'paid')->sum('profit_amount');
        
        // بناء query لفلترة معاملات الخزنة
        $treasuryTransactionQuery = $investorTreasury ? $investorTreasury->transactions() : null;
        
        if ($investorTreasury && $treasuryTransactionQuery) {
            $investorTreasury->load('transactions');
            
            // فلترة المعاملات: إظهار جميع المعاملات ما عدا cost_return غير المرتبطة بالاستثمارات
            $treasuryTransactionQuery->where(function($q) use ($investor, $allInvestmentIds) {
                // المعاملات التي ليست cost_return تظهر دائماً (profit, manual, expense, null)
                $q->where(function($subQ) {
                    $subQ->whereNull('reference_type')
                         ->orWhereIn('reference_type', ['profit', 'manual', 'expense']);
                })
                // أو معاملات cost_return المفلترة حسب الاستثمارات الفعلية
                ->orWhere(function($q2) use ($investor, $allInvestmentIds) {
                    $q2->where('reference_type', 'cost_return')
                        ->whereHas('order', function($q3) use ($investor, $allInvestmentIds) {
                            $q3->whereHas('investorProfits', function($q4) use ($investor, $allInvestmentIds) {
                                $q4->where('investor_id', $investor->id);
                                if (!empty($allInvestmentIds)) {
                                    $q4->whereIn('investment_id', $allInvestmentIds);
                                } else {
                                    $q4->whereRaw('1 = 0');
                                }
                            });
                        });
                });
            });
            
            // فلتر نوع الحركة
            if ($request->filled('transaction_type')) {
                $treasuryTransactionQuery->where('transaction_type', $request->transaction_type);
            }
            
            // فلتر نوع المرجع
            if ($request->filled('reference_type')) {
                $treasuryTransactionQuery->where('reference_type', $request->reference_type);
            }
            
            // فلتر التاريخ حسب تاريخ تقييد الطلب للمعاملات المرتبطة بطلبات
            if ($request->filled('date_from') || $request->filled('date_to')) {
                $treasuryTransactionQuery->where(function($q) use ($request) {
                    // للمعاملات المرتبطة بطلبات، نفلتر حسب تاريخ تقييد الطلب
                    $q->where(function($q2) use ($request) {
                        $q2->where('reference_type', 'order')
                            ->whereHas('order', function($q3) use ($request) {
                                if ($request->filled('date_from')) {
                                    $q3->where('confirmed_at', '>=', $request->date_from);
                                }
                                if ($request->filled('date_to')) {
                                    $q3->where('confirmed_at', '<=', $request->date_to . ' 23:59:59');
                                }
                            });
                    })
                    // للمعاملات غير المرتبطة بطلبات، نفلتر حسب تاريخ المعاملة نفسها
                    ->orWhere(function($q2) use ($request) {
                        $q2->where('reference_type', '!=', 'order');
                        if ($request->filled('date_from')) {
                            $q2->where('created_at', '>=', $request->date_from);
                        }
                        if ($request->filled('date_to')) {
                            $q2->where('created_at', '<=', $request->date_to . ' 23:59:59');
                        }
                    });
                });
            }

            // الإيداعات والسحوبات اليدوية فقط (بدون فلترة - هذه معاملات يدوية)
            $treasuryDeposits = $investorTreasury->transactions()
                ->where('transaction_type', 'deposit')
                ->where('reference_type', 'manual')
                ->sum('amount');
            
            $treasuryWithdrawals = $investorTreasury->transactions()
                ->where('transaction_type', 'withdrawal')
                ->where('reference_type', 'manual')
                ->sum('amount');
            
            // حساب إجمالي المصروفات (صافي: السحوبات - الإيداعات المرجعة)
            // عند حذف أو تعديل مصروف، يتم إرجاع المبلغ كـ deposit مع reference_type = 'expense'
            // المصروفات لا تُفلتر حسب التاريخ/المندوب/المجهز - هي مصروفات على المخزن
            $expenseWithdrawals = $investorTreasury->transactions()
                ->where('transaction_type', 'withdrawal')
                ->where('reference_type', 'expense')
                ->sum('amount');
            
            $expenseDeposits = $investorTreasury->transactions()
                ->where('transaction_type', 'deposit')
                ->where('reference_type', 'expense')
                ->sum('amount');
            
            $totalInvestorExpenses = $expenseWithdrawals - $expenseDeposits;
        }
        
        // الربح الصافي = الأرباح المدفوعة فقط (مع تطبيق الفلاتر)
        // المصروفات تؤثر على الرصيد الحالي فقط وليس على الربح
        $netProfit = $paidProfits;

        // جلب الاستثمارات من البنية الجديدة (المشاريع) والبنية القديمة
        // البنية الجديدة: InvestmentInvestor -> Investment -> Project
        $investmentInvestors = \App\Models\InvestmentInvestor::where('investor_id', $investor->id)
            ->with(['investment.project', 'investment.targets'])
            ->latest()
            ->get();

        // البنية القديمة: Investment مباشرة (للاستثمارات القديمة)
        $oldInvestments = $investor->investments()
            ->whereNull('project_id')
            ->with(['product', 'warehouse', 'privateWarehouse'])
            ->latest()
            ->get();

        // دمج الاستثمارات في قائمة واحدة للعرض
        $allInvestments = collect();
        
        // إضافة الاستثمارات من البنية الجديدة
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
        
        // إضافة الاستثمارات القديمة
        foreach ($oldInvestments as $oldInvestment) {
            $allInvestments->push([
                'type' => 'old',
                'investment' => $oldInvestment,
                'investmentInvestor' => null,
                'project' => null,
            ]);
        }

        // فلترة جدول الأرباح (مع تطبيق الفلاتر)
        $profitsQuery = $profitQuery->with(['order', 'product', 'warehouse'])->latest();
        $profits = $profitsQuery->paginate(20)->appends($request->except('page'));

        // حساب إجمالي الاستثمارات
        $totalInvestments = $investmentInvestors->count() + $oldInvestments->count();
        $activeInvestments = $investmentInvestors->where('investment.status', 'active')->count() + 
                           $oldInvestments->where('status', 'active')->count();

        // حساب قيمة المخزن الحالي حسب نسبة المستثمر (متغير حسب الكمية الباقية)
        $currentWarehouseValue = 0;
        
        // من الاستثمارات الجديدة (البنية الجديدة)
        foreach ($investmentInvestors as $investmentInvestor) {
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
        
        // من الاستثمارات القديمة (إذا كان استثمار في مخزن)
        foreach ($oldInvestments as $oldInvestment) {
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

        // حساب المصروفات والربح الصافي لكل منتج
        $productExpensesData = [];
        
        // جمع جميع المنتجات المستثمر بها من الاستثمارات الجديدة
        $productIds = collect();
        foreach ($investmentInvestors as $investmentInvestor) {
            $investment = $investmentInvestor->investment;
            if ($investment && $investment->targets) {
                foreach ($investment->targets as $target) {
                    if ($target->target_type === 'product') {
                        $productIds->push($target->target_id);
                    }
                }
            }
        }
        
        // جمع المنتجات من الاستثمارات القديمة
        foreach ($oldInvestments as $oldInvestment) {
            if ($oldInvestment->product_id) {
                $productIds->push($oldInvestment->product_id);
            }
        }
        
        $productIds = $productIds->unique();
        
        // حساب البيانات لكل منتج
        foreach ($productIds as $productId) {
            $product = \App\Models\Product::find($productId);
            if (!$product) {
                continue;
            }
            
            // جلب جميع ProfitRecords للمنتج
            $profitRecords = \App\Models\ProfitRecord::where('product_id', $productId)
                ->where('status', 'confirmed')
                ->get();
            
            // حساب إجمالي القطع المباعة
            $totalItemsSold = $profitRecords->sum('items_count');
            
            // حساب إجمالي المصروفات من ProfitRecords
            $totalExpenses = $profitRecords->sum('expenses_amount');
            
            // حساب مصروفات كل قطعة
            $expensePerItem = $totalItemsSold > 0 ? ($totalExpenses / $totalItemsSold) : 0;
            
            // حساب الربح الإجمالي من ProfitRecord (gross_profit)
            $grossProfit = $profitRecords->sum('gross_profit');
            
            // حساب الربح الصافي من ProfitRecord (actual_profit) أو من InvestorProfit
            $netProfitFromRecords = $profitRecords->sum('actual_profit');
            $netProfitFromInvestor = \App\Models\InvestorProfit::where('investor_id', $investor->id)
                ->where('product_id', $productId)
                ->when(!empty($allInvestmentIds), function($q) use ($allInvestmentIds) {
                    return $q->whereIn('investment_id', $allInvestmentIds);
                })
                ->sum('profit_amount');
            
            // استخدام الربح الصافي من ProfitRecord إذا كان متاحاً، وإلا من InvestorProfit
            $netProfit = $netProfitFromRecords > 0 ? $netProfitFromRecords : $netProfitFromInvestor;
            
            $productExpensesData[] = [
                'product_id' => $productId,
                'product_name' => $product->name,
                'product_code' => $product->code ?? '-',
                'total_items_sold' => $totalItemsSold,
                'expense_per_item' => $expensePerItem,
                'total_expenses' => $totalExpenses,
                'gross_profit' => $grossProfit,
                'net_profit' => $netProfit,
            ];
        }

        // جلب جميع معاملات الخزنة (مع تطبيق الفلاتر)
        $treasuryTransactions = null;
        if ($investorTreasury && $treasuryTransactionQuery) {
            $treasuryTransactions = $treasuryTransactionQuery
                ->with('creator')
                ->latest()
                ->paginate(20)
                ->appends($request->except('page'));
        }

        return view('admin.investors.show', compact(
            'investor',
            'totalInvestments',
            'activeInvestments',
            'totalProfit',
            'pendingProfit',
            'paidProfit',
            'investorTreasury',
            'treasuryDeposits',
            'treasuryWithdrawals',
            'allInvestments',
            'profits',
            'productExpensesData',
            'pendingProfits',
            'paidProfits',
            'totalInvestorExpenses',
            'netProfit',
            'treasuryTransactions',
            'currentWarehouseValue'
        ));
    }

    /**
     * عرض صفحة تعديل مستثمر
     */
    public function edit(Investor $investor)
    {
        $this->authorize('update', $investor);
        $investor->load('treasury');
        return view('admin.investors.edit', compact('investor'));
    }

    /**
     * تحديث بيانات المستثمر
     */
    public function update(Request $request, Investor $investor)
    {
        $this->authorize('update', $investor);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string|unique:investors,phone,' . $investor->id,
            'password' => 'nullable|string|min:6',
            'notes' => 'nullable|string',
            'status' => 'required|in:active,inactive',
        ]);

        if (empty($validated['password'])) {
            unset($validated['password']);
        }
        // كلمة المرور سيتم hashها تلقائياً في boot() method

        return DB::transaction(function () use ($investor, $validated) {
            // تحديث بيانات المستثمر
            if (empty($validated['password'])) {
                unset($validated['password']);
            }
        $investor->update($validated);

        return redirect()->route('admin.investors.show', $investor)
            ->with('success', 'تم تحديث بيانات المستثمر بنجاح');
        });
    }

    /**
     * حذف مستثمر
     */
    public function destroy(Investor $investor)
    {
        $this->authorize('delete', $investor);

        // منع حذف المستثمر المدير
        if ($investor->is_admin) {
            return back()->withErrors(['error' => 'لا يمكن حذف حساب المدير']);
        }

        // التحقق من وجود استثمارات نشطة
        if ($investor->investments()->where('status', 'active')->exists()) {
            return back()->withErrors(['error' => 'لا يمكن حذف مستثمر لديه استثمارات نشطة']);
        }

        return DB::transaction(function () use ($investor) {
            // حذف الخزنة المرتبطة مع جميع معاملاتها
            if ($investor->treasury) {
                $treasury = $investor->treasury;
                // حذف جميع معاملات الخزنة
                $treasury->transactions()->delete();
                // حذف الخزنة
                $treasury->delete();
            }

            // حذف المستثمر
        $investor->delete();

        return redirect()->route('admin.investors.index')
            ->with('success', 'تم حذف المستثمر بنجاح');
        });
    }

    /**
     * تصفير حسابات المستثمر
     */
public function resetAccounts(Request $request, Investor $investor)
    {
        $this->authorize('update', $investor);

        try {
            DB::beginTransaction();

            // تصفير الخزنة
            $treasury = \App\Models\Treasury::where('investor_id', $investor->id)->first();
            if ($treasury) {
                // حذف جميع معاملات الخزنة
                \App\Models\TreasuryTransaction::where('treasury_id', $treasury->id)->delete();
                
                // تصفير رصيد الخزنة ورأس المال
                $treasury->update([
                    'initial_capital' => 0,
                    'current_balance' => 0,
                ]);
            }

            // حذف جميع معاملات المستثمر
            \App\Models\InvestorTransaction::where('investor_id', $investor->id)->delete();

            // تصفير إحصائيات المستثمر
            $investor->update([
                'balance' => 0,
                'total_profit' => 0,
                'total_withdrawals' => 0,
                'total_deposits' => 0,
            ]);

            DB::commit();

            return redirect()->route('admin.investors.show', $investor)
                ->with('success', 'تم تصفير حسابات المستثمر بنجاح');
        } catch (\Exception $e) {
            DB::rollBack();
            \Illuminate\Support\Facades\Log::error('Error resetting investor accounts', [
                'investor_id' => $investor->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return back()->withErrors(['error' => 'حدث خطأ أثناء تصفير الحسابات. يرجى التحقق من السجلات.']);
        }
    }

    /**
     * رفع الأرباح المعلقة للرصيد الحالي
     */
    public function depositProfits(Request $request, Investor $investor)
    {
        $this->authorize('update', $investor);

        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'notes' => 'nullable|string|max:1000',
        ]);

        $amount = $validated['amount'];
        $notes = $validated['notes'] ?? null;

        // جلب جميع الاستثمارات المرتبطة بهذا المستثمر (لفلترة الأرباح)
        $investmentInvestorIds = \App\Models\InvestmentInvestor::where('investor_id', $investor->id)
            ->pluck('investment_id')
            ->toArray();
        
        $oldInvestmentIds = \App\Models\Investment::where('investor_id', $investor->id)
            ->whereNull('project_id')
            ->pluck('id')
            ->toArray();
        
        $allInvestmentIds = array_unique(array_merge($investmentInvestorIds, $oldInvestmentIds));

        // حساب إجمالي الأرباح المعلقة - مع الفلترة حسب الاستثمارات
        $pendingQuery = \App\Models\InvestorProfit::where('investor_id', $investor->id)
            ->where('status', 'pending');
        
        if (!empty($allInvestmentIds)) {
            $pendingQuery->whereIn('investment_id', $allInvestmentIds);
        } else {
            $pendingQuery->whereRaw('1 = 0');
        }
        
        $pendingAmount = $pendingQuery->sum('profit_amount');

        if ($amount > $pendingAmount) {
            return back()->withErrors(['amount' => 'المبلغ يتجاوز الأرباح المعلقة (' . formatCurrency($pendingAmount) . ')']);
        }

        $treasury = $investor->treasury;
        if (!$treasury) {
            return back()->withErrors(['error' => 'المستثمر لا يملك خزنة']);
        }

        try {
            DB::transaction(function() use ($treasury, $amount, $investor, $notes) {
                // إيداع المبلغ في خزنة المستثمر
                $treasury->deposit(
                    $amount,
                    'profit',
                    null,
                    'رفع أرباح معلقة' . ($notes ? ' - ' . $notes : ''),
                    auth()->id()
                );

                // تحديث حالة InvestorProfit من pending إلى paid
                // توزيع المبلغ على الأرباح المعلقة حسب التاريخ (الأقدم أولاً)
                // مع الفلترة حسب الاستثمارات الفعلية
                $pendingProfitsQuery = \App\Models\InvestorProfit::where('investor_id', $investor->id)
                    ->where('status', 'pending')
                    ->orderBy('profit_date', 'asc')
                    ->orderBy('id', 'asc');
                
                if (!empty($allInvestmentIds)) {
                    $pendingProfitsQuery->whereIn('investment_id', $allInvestmentIds);
                } else {
                    $pendingProfitsQuery->whereRaw('1 = 0');
                }
                
                $pendingProfits = $pendingProfitsQuery->get();

                $remainingAmount = $amount;
                foreach ($pendingProfits as $profit) {
                    if ($remainingAmount <= 0) {
                        break;
                    }

                    // حساب المبلغ الذي سيتم رفعه من هذا الربح
                    $amountToPay = min($profit->profit_amount, $remainingAmount);
                    
                    if ($amountToPay >= $profit->profit_amount) {
                        // المبلغ كافي لتغطية هذا الربح بالكامل
                        $profit->update([
                            'status' => 'paid',
                            'payment_date' => now()->toDateString(),
                            'payment_notes' => $notes,
                        ]);
                        $remainingAmount -= $profit->profit_amount;
                    } else {
                        // المبلغ لا يكفي لتغطية هذا الربح بالكامل
                        // سنقوم بتقسيم الربح إلى جزئين: جزء مدفوع وجزء معلق
                        
                        // تحديث الربح الحالي ليكون المبلغ المتبقي فقط (معلق)
                        $profit->update([
                            'profit_amount' => $profit->profit_amount - $amountToPay,
                        ]);
                        
                        // إنشاء ربح جديد للمبلغ المدفوع
                        \App\Models\InvestorProfit::create([
                            'investor_id' => $profit->investor_id,
                            'investment_id' => $profit->investment_id,
                            'profit_record_id' => $profit->profit_record_id,
                            'order_id' => $profit->order_id,
                            'warehouse_id' => $profit->warehouse_id,
                            'product_id' => $profit->product_id,
                            'profit_amount' => $amountToPay,
                            'base_profit' => $profit->base_profit,
                            'profit_percentage' => $profit->profit_percentage,
                            'profit_date' => $profit->profit_date,
                            'status' => 'paid',
                            'payment_date' => now()->toDateString(),
                            'payment_notes' => $notes,
                        ]);
                        
                        $remainingAmount = 0;
                        break;
                    }
                }
            });

            return back()->with('success', 'تم رفع الأرباح للرصيد الحالي بنجاح');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'حدث خطأ أثناء رفع الأرباح: ' . $e->getMessage()]);
        }
    }
}
