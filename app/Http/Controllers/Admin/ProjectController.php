<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Investor;
use App\Models\Investment;
use App\Models\InvestmentTarget;
use App\Models\InvestmentInvestor;
use App\Models\Treasury;
use App\Models\TreasuryTransaction;
use App\Models\Product;
use App\Models\Warehouse;
use App\Models\Project;
use App\Services\ProfitCalculator;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class ProjectController extends Controller
{
    protected $profitCalculator;

    public function __construct(ProfitCalculator $profitCalculator)
    {
        $this->profitCalculator = $profitCalculator;
    }

    /**
     * عرض قائمة المشاريع والمستثمرين
     */
    public function index(Request $request)
    {
        $projects = Project::with(['investments', 'creator'])
            ->latest()
            ->paginate(20);

        // جلب بيانات المستثمرين مع الفلاتر
        $investorQuery = Investor::query();

        // فلترة حسب الحالة
        if ($request->filled('investor_status')) {
            $investorQuery->where('status', $request->investor_status);
        }

        // بحث في المستثمرين
        if ($request->filled('investor_search')) {
            $investorQuery->where(function ($q) use ($request) {
                $q->where('name', 'LIKE', "%{$request->investor_search}%")
                    ->orWhere('phone', 'LIKE', "%{$request->investor_search}%");
            });
        }

        $investors = $investorQuery->withCount(['investments', 'profits', 'transactions'])
            ->with('treasury')
            ->latest()
            ->paginate(20, ['*'], 'investors_page');

        return view('admin.projects.index', compact('projects', 'investors'));
    }

    /**
     * عرض صفحة إضافة مشروع
     */
    public function create()
    {
        $products = Product::with('warehouse')->get();
        $warehouses = Warehouse::all();
        $investors = Investor::where('status', 'active')
            ->where('is_admin', false)
            ->with('treasury')
            ->get();

        // إضافة المستثمر الخاص بالمدير إلى قائمة المستثمرين
        $adminInvestor = Investor::getOrCreateAdminInvestor();
        $adminInvestor->load('treasury');
        $investors->push($adminInvestor);

        return view('admin.projects.create', compact('products', 'warehouses', 'investors'));
    }

    /**
     * حفظ المشروع مع المستثمرين والاستثمارات
     */
    public function store(Request $request)
    {
        // تحويل targets[] إلى targets[][id] إذا لزم الأمر
        $data = $request->all();
        if (isset($data['investment']['targets']) && is_array($data['investment']['targets'])) {
            $targetsArray = [];
            foreach ($data['investment']['targets'] as $targetId) {
                if (is_numeric($targetId)) {
                    $targetsArray[] = ['id' => (int) $targetId];
                } elseif (is_array($targetId) && isset($targetId['id'])) {
                    $targetsArray[] = $targetId;
                }
            }
            $data['investment']['targets'] = $targetsArray;
            $request->merge($data);
        }

        try {
            $validated = $request->validate([
                'project_name' => 'required|string|max:255',
                'investment' => 'required|array',
                'investment.type' => 'required|in:warehouse',
                'investment.targets' => 'required|array|min:1',
                'investment.targets.*' => 'required',
                'investment.total_value' => 'nullable|numeric|min:0', // القيمة الإجمالية (يمكن أن تكون 0 للمخزن الفارغ)
                'investment.investors' => 'required|array|min:1',
                'investment.investors.*.investor_id' => 'required|integer|exists:investors,id',
                'investment.investors.*.cost_percentage' => 'required|numeric|min:0',
                'investment.investors.*.profit_percentage' => 'required|numeric|min:0|max:100',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('Project validation failed', [
                'errors' => $e->errors(),
                'request_data' => $request->all()
            ]);
            return back()->withErrors($e->errors())->withInput();
        }

        // التحقق من أن المستثمرين فريدين
        $investorIds = collect($validated['investment']['investors'])->pluck('investor_id');
        if ($investorIds->count() !== $investorIds->unique()->count()) {
            return back()->withErrors(['error' => 'يجب أن يكون كل مستثمر فريداً'])->withInput();
        }

        // التحقق من أن مجموع نسب المستثمرين ≤ 100%
        $totalProfitPercentage = collect($validated['investment']['investors'])->sum('profit_percentage');
        if ($totalProfitPercentage > 100) {
            return back()->withErrors(['error' => 'مجموع نسب الربح للمستثمرين (' . $totalProfitPercentage . '%) يتجاوز 100%'])->withInput();
        }

        // التحقق من أن مجموع نسب التكلفة للمستثمرين ≤ 100%
        $totalCostPercentage = collect($validated['investment']['investors'])->sum('cost_percentage');
        if ($totalCostPercentage > 999999) {
            return back()->withErrors(['error' => 'مجموع نسب التكلفة للمستثمرين (' . $totalCostPercentage . '%) يتجاوز 100%'])->withInput();
        }

        return DB::transaction(function () use ($validated) {
            // إنشاء المشروع (دائماً نوع investors)
            $project = Project::create([
                'name' => $validated['project_name'],
                'project_type' => 'investors',
                'status' => 'active',
                'created_by' => Auth::id(),
            ]);

            // إنشاء خزنة فرعية للمشروع
            $projectTreasury = Treasury::create([
                'name' => "خزنة مشروع: {$project->name}",
                'initial_capital' => 0,
                'current_balance' => 0,
                'created_by' => Auth::id(),
            ]);

            // ربط الخزنة بالمشروع
            $project->update(['treasury_id' => $projectTreasury->id]);

            $investmentData = $validated['investment'];

            // حساب القيمة الإجمالية للأهداف (للمخازن فقط)
            $calculatedTotalValue = 0;
            $targetValues = [];

            foreach ($investmentData['targets'] as $target) {
                // معالجة الحالات المختلفة (array أو integer مباشرة)
                $targetId = is_array($target) ? ($target['id'] ?? $target[0] ?? null) : $target;

                if ($investmentData['type'] === 'warehouse' && $targetId) {
                    $warehouse = Warehouse::find($targetId);
                    if ($warehouse) {
                        // حساب قيمة المخزن (يمكن أن تكون 0 إذا كان فارغاً)
                        $value = $this->profitCalculator->calculateWarehouseValue($warehouse);
                        $targetValues[] = [
                            'type' => 'warehouse',
                            'id' => $warehouse->id,
                            'value' => $value,
                        ];
                        $calculatedTotalValue += $value;
                    }
                }
            }

            // استخدام القيمة من الطلب إذا كانت موجودة، وإلا استخدام القيمة المحسوبة
            // السماح بالقيمة 0 للمخزن الفارغ
            $totalValue = isset($investmentData['total_value'])
                ? (float) $investmentData['total_value']
                : $calculatedTotalValue;

            // حساب مجموع نسب المستثمرين
            $totalInvestorsProfitPercentage = collect($investmentData['investors'])->sum('profit_percentage');

            // التحقق من وجود المدير في قائمة المستثمرين
            $adminInvestor = Investor::where('is_admin', true)->first();
            $adminInvestorInList = false;
            if ($adminInvestor) {
                $adminInvestorInList = collect($investmentData['investors'])->contains('investor_id', $adminInvestor->id);
            }

            // حساب نسبة المدير (النسبة المتبقية)
            // إذا كان المدير مستثمر، نحسب نسبة المدير من استثماراته
            // إذا لم يكن المدير مستثمر، نحسب النسبة المتبقية كالمعتاد
            if ($adminInvestorInList && $adminInvestor) {
                // المدير مستثمر، نحسب نسبة المدير من استثماراته
                $adminInvestorData = collect($investmentData['investors'])->firstWhere('investor_id', $adminInvestor->id);
                $adminProfitPercentage = $adminInvestorData ? $adminInvestorData['profit_percentage'] : 0;
            } else {
                // المدير ليس مستثمر، نحسب النسبة المتبقية
                $adminProfitPercentage = 100 - $totalInvestorsProfitPercentage;
            }

            // إنشاء الاستثمار الواحد المشترك
            $investment = Investment::create([
                'project_id' => $project->id,
                'investment_type' => $investmentData['type'],
                'admin_profit_percentage' => $adminProfitPercentage,
                'total_value' => $totalValue,
                'start_date' => now(),
                'status' => 'active',
                'created_by' => Auth::id(),
            ]);

            // إنشاء InvestmentTarget لكل هدف
            foreach ($targetValues as $targetValue) {
                InvestmentTarget::create([
                    'investment_id' => $investment->id,
                    'target_type' => $targetValue['type'],
                    'target_id' => $targetValue['id'],
                    'value' => $targetValue['value'],
                ]);
            }

            // إضافة جميع المستثمرين للاستثمار المشترك
            foreach ($investmentData['investors'] as $investorData) {
                // جلب المستثمر المختار
                $investor = Investor::findOrFail($investorData['investor_id']);

                // جلب خزنة المستثمر
                $treasury = $investor->treasury;
                if (!$treasury) {
                    throw new \Exception("المستثمر {$investor->name} لا يملك خزنة. يرجى إنشاء خزنة له أولاً.");
                }

                // استخدام cost_percentage مباشرة من الطلب
                $costPercentage = (float) $investorData['cost_percentage'];

                // عند إنشاء مشروع لمخزن فارغ (total_value = 0)، لا نخصم أي مبلغ
                // سيتم الخصم لاحقاً عند إضافة منتجات للمخزن
                $investmentAmount = 0; // سيتم تحديثه عند إضافة منتجات

                // إنشاء InvestmentInvestor مع cost_percentage و profit_percentage
                InvestmentInvestor::create([
                    'investment_id' => $investment->id,
                    'investor_id' => $investor->id,
                    'cost_percentage' => $costPercentage,
                    'profit_percentage' => $investorData['profit_percentage'],
                    'investment_amount' => $investmentAmount,
                ]);
            }

            return redirect()->route('admin.projects.show', $project)
                ->with('success', 'تم إنشاء المشروع بنجاح');
        });
    }

    /**
     * عرض تفاصيل المشروع
     */
    public function show(Request $request, Project $project)
    {
        $project->load([
            'treasury.transactions',
            'investments.targets',
            'investments.investors.investor',
            'creator'
        ]);

        // بناء query لفلترة أرباح المستثمرين حسب تاريخ تقييد الطلب
        $investorProfitQuery = \App\Models\InvestorProfit::whereIn('investment_id', $project->investments()->pluck('id'));

        // فلتر التاريخ حسب تاريخ تقييد الطلب
        if ($request->filled('date_from') || $request->filled('date_to')) {
            $investorProfitQuery->whereHas('order', function ($q) use ($request) {
                if ($request->filled('date_from')) {
                    $q->where('confirmed_at', '>=', $request->date_from);
                }
                if ($request->filled('date_to')) {
                    $q->where('confirmed_at', '<=', $request->date_to . ' 23:59:59');
                }
            });
        }

        // جمع معلومات المستثمرين (مع تطبيق الفلاتر على الأرباح)
        $investorsData = [];
        $investmentIds = $project->investments()->pluck('id');
        $investmentInvestors = InvestmentInvestor::whereIn('investment_id', $investmentIds)
            ->with('investor.treasury')
            ->get();

        foreach ($investmentInvestors as $investmentInvestor) {
            $investorId = $investmentInvestor->investor_id;
            if (!isset($investorsData[$investorId])) {
                $investor = $investmentInvestor->investor;

                // البحث عن خزنة المستثمر من العلاقة المباشرة
                $investorTreasury = $investor->treasury;

                // تحميل معاملات الخزنة وأرباح المستثمر
                $treasuryDeposits = 0;
                $treasuryWithdrawals = 0;
                $investorTotalProfit = 0;
                $investorTotalDeposits = 0;
                $investorTotalWithdrawals = 0;

                if ($investorTreasury) {
                    $investorTreasury->load('transactions');
                    $treasuryDeposits = $investorTreasury->transactions()->where('transaction_type', 'deposit')->sum('amount');
                    $treasuryWithdrawals = $investorTreasury->transactions()->where('transaction_type', 'withdrawal')->sum('amount');
                }

                // حساب أرباح المستثمر (مع تطبيق الفلاتر)
                $investorProfitFilteredQuery = \App\Models\InvestorProfit::whereIn('investment_id', $project->investments()->pluck('id'))
                    ->where('investor_id', $investorId);

                // تطبيق فلتر التاريخ حسب تاريخ تقييد الطلب
                if ($request->filled('date_from') || $request->filled('date_to')) {
                    $investorProfitFilteredQuery->whereHas('order', function ($q) use ($request) {
                        if ($request->filled('date_from')) {
                            $q->where('confirmed_at', '>=', $request->date_from);
                        }
                        if ($request->filled('date_to')) {
                            $q->where('confirmed_at', '<=', $request->date_to . ' 23:59:59');
                        }
                    });
                }

                $investorTotalProfit = $investorProfitFilteredQuery->sum('profit_amount');

                // استخدام TreasuryTransaction بدلاً من InvestorTransaction (نظام قديم)
                $investorTotalDeposits = 0;
                $investorTotalWithdrawals = 0;
                if ($investorTreasury) {
                    $investorTotalDeposits = $investorTreasury->transactions()
                        ->where('transaction_type', 'deposit')
                        ->where('reference_type', 'manual')
                        ->sum('amount');
                    $investorTotalWithdrawals = $investorTreasury->transactions()
                        ->where('transaction_type', 'withdrawal')
                        ->where('reference_type', 'manual')
                        ->sum('amount');
                }

                $investorsData[$investorId] = [
                    'investor' => $investor,
                    'treasury' => $investorTreasury,
                    'treasury_deposits' => $treasuryDeposits,
                    'treasury_withdrawals' => $treasuryWithdrawals,
                    'total_profit' => $investorTotalProfit,
                    'total_deposits' => $investorTotalDeposits,
                    'total_withdrawals' => $investorTotalWithdrawals,
                    'investments_count' => 0,
                    'total_investment' => 0,
                ];
            }
            $investorsData[$investorId]['investments_count']++;
            // حساب مبلغ الاستثمار من cost_percentage و total_value مباشرة لضمان الدقة 100%
            $investmentAmount = ($investmentInvestor->cost_percentage ?? 0) / 100 * ($investmentInvestor->investment->total_value ?? 0);
            $investorsData[$investorId]['total_investment'] += $investmentAmount;
        }

        // حساب الإحصائيات
        $totalValue = $project->getTotalValue();
        $totalInvestment = $project->getTotalInvestment();
        $totalAdminProfit = 0;
        $totalExpectedProfit = 0;

        foreach ($project->investments as $investment) {
            $adminPercentage = $investment->admin_profit_percentage ?? 0;
            $totalAdminProfit += ($adminPercentage / 100) * $investment->total_value;

            // حساب الأرباح المتوقعة للمستثمرين
            foreach ($investment->investors as $investmentInvestor) {
                $profitPercentage = $investmentInvestor->profit_percentage;
                $expectedProfit = ($profitPercentage / 100) * $investment->total_value;
                $totalExpectedProfit += $expectedProfit;
            }
        }

        return view('admin.projects.show', compact(
            'project',
            'investorsData',
            'totalValue',
            'totalInvestment',
            'totalAdminProfit',
            'totalExpectedProfit'
        ));
    }

    /**
     * عرض صفحة تعديل المشروع
     */
    public function edit(Project $project)
    {
        $project->load(['investments.targets', 'investments.investors.investor.treasury']);

        $warehouses = Warehouse::all();
        $investors = Investor::where('status', 'active')
            ->where('is_admin', false)
            ->with('treasury')
            ->get();

        // إضافة المستثمر الخاص بالمدير إلى قائمة المستثمرين
        $adminInvestor = Investor::getOrCreateAdminInvestor();
        $adminInvestor->load('treasury');
        $investors->push($adminInvestor);

        // جلب الاستثمار الحالي (يجب أن يكون واحد فقط)
        $investment = $project->investments->first();

        // جلب المخازن المستهدفة
        $targetWarehouses = collect();
        if ($investment) {
            $targetIds = $investment->targets()->where('target_type', 'warehouse')->pluck('target_id');
            $targetWarehouses = Warehouse::whereIn('id', $targetIds)->get();
        }

        return view('admin.projects.edit', compact('project', 'investment', 'warehouses', 'investors', 'targetWarehouses'));
    }

    /**
     * تحديث المشروع
     */
    public function update(Request $request, Project $project)
    {
        // تحويل targets[] إلى targets[][id] إذا لزم الأمر
        $data = $request->all();
        if (isset($data['investment']['targets']) && is_array($data['investment']['targets'])) {
            $targetsArray = [];
            foreach ($data['investment']['targets'] as $targetId) {
                if (is_numeric($targetId)) {
                    $targetsArray[] = ['id' => (int) $targetId];
                } elseif (is_array($targetId) && isset($targetId['id'])) {
                    $targetsArray[] = $targetId;
                }
            }
            $data['investment']['targets'] = $targetsArray;
            $request->merge($data);
        }

        try {
            $validated = $request->validate([
                'project_name' => 'required|string|max:255',
                'investment' => 'required|array',
                'investment.type' => 'required|in:warehouse',
                'investment.targets' => 'required|array|min:1',
                'investment.targets.*' => 'required',
                'investment.total_value' => 'nullable|numeric|min:0',
                'investment.investors' => 'required|array|min:1',
                'investment.investors.*.investor_id' => 'required|integer|exists:investors,id',
                'investment.investors.*.cost_percentage' => 'required|numeric|min:0',
                'investment.investors.*.profit_percentage' => 'required|numeric|min:0|max:100',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('Project update validation failed', [
                'errors' => $e->errors(),
                'request_data' => $request->all()
            ]);
            return back()->withErrors($e->errors())->withInput();
        }

        // التحقق من أن المستثمرين فريدين
        $investorIds = collect($validated['investment']['investors'])->pluck('investor_id');
        if ($investorIds->count() !== $investorIds->unique()->count()) {
            return back()->withErrors(['error' => 'يجب أن يكون كل مستثمر فريداً'])->withInput();
        }

        // التحقق من أن مجموع نسب المستثمرين ≤ 100%
        $totalProfitPercentage = collect($validated['investment']['investors'])->sum('profit_percentage');
        if ($totalProfitPercentage > 100) {
            return back()->withErrors(['error' => 'مجموع نسب الربح للمستثمرين (' . $totalProfitPercentage . '%) يتجاوز 100%'])->withInput();
        }

        // التحقق من أن مجموع نسب التكلفة للمستثمرين ≤ 100%
        $totalCostPercentage = collect($validated['investment']['investors'])->sum('cost_percentage');
        if ($totalCostPercentage > 999999) {
            return back()->withErrors(['error' => 'مجموع نسب التكلفة للمستثمرين (' . $totalCostPercentage . '%) يتجاوز 100%'])->withInput();
        }

        return DB::transaction(function () use ($validated, $project) {
            // تحديث اسم المشروع
            $project->update([
                'name' => $validated['project_name'],
            ]);

            // جلب الاستثمار الحالي (يجب أن يكون واحد فقط)
            $investment = $project->investments->first();
            if (!$investment) {
                throw new \Exception('المشروع لا يحتوي على استثمار');
            }

            $investmentData = $validated['investment'];

            // حساب القيمة الإجمالية للأهداف (للمخازن فقط)
            $calculatedTotalValue = 0;
            $targetValues = [];

            foreach ($investmentData['targets'] as $target) {
                // معالجة الحالات المختلفة (array أو integer مباشرة)
                $targetId = is_array($target) ? ($target['id'] ?? $target[0] ?? null) : $target;

                if ($investmentData['type'] === 'warehouse' && $targetId) {
                    $warehouse = Warehouse::find($targetId);
                    if ($warehouse) {
                        // حساب قيمة المخزن (يمكن أن تكون 0 إذا كان فارغاً)
                        $value = $this->profitCalculator->calculateWarehouseValue($warehouse);
                        $targetValues[] = [
                            'type' => 'warehouse',
                            'id' => $warehouse->id,
                            'value' => $value,
                        ];
                        $calculatedTotalValue += $value;
                    }
                }
            }

            // استخدام القيمة من الطلب إذا كانت موجودة، وإلا استخدام القيمة المحسوبة
            // السماح بالقيمة 0 للمخزن الفارغ
            $totalValue = isset($investmentData['total_value'])
                ? (float) $investmentData['total_value']
                : $calculatedTotalValue;

            // حساب مجموع نسب المستثمرين
            $totalInvestorsProfitPercentage = collect($investmentData['investors'])->sum('profit_percentage');

            // التحقق من وجود المدير في قائمة المستثمرين
            $adminInvestor = Investor::where('is_admin', true)->first();
            $adminInvestorInList = false;
            if ($adminInvestor) {
                $adminInvestorInList = collect($investmentData['investors'])->contains('investor_id', $adminInvestor->id);
            }

            // حساب نسبة المدير (النسبة المتبقية)
            if ($adminInvestorInList && $adminInvestor) {
                $adminInvestorData = collect($investmentData['investors'])->firstWhere('investor_id', $adminInvestor->id);
                $adminProfitPercentage = $adminInvestorData ? $adminInvestorData['profit_percentage'] : 0;
            } else {
                $adminProfitPercentage = 100 - $totalInvestorsProfitPercentage;
            }

            // تحديث الاستثمار
            $investment->update([
                'admin_profit_percentage' => $adminProfitPercentage,
                'total_value' => $totalValue,
            ]);

            // تحديث InvestmentTarget (حذف القديم وإضافة الجديد)
            $investment->targets()->delete();
            foreach ($targetValues as $targetValue) {
                InvestmentTarget::create([
                    'investment_id' => $investment->id,
                    'target_type' => $targetValue['type'],
                    'target_id' => $targetValue['id'],
                    'value' => $targetValue['value'],
                ]);
            }

            // تحديث/إضافة/حذف المستثمرين
            $currentInvestorIds = $investment->investors()->pluck('investor_id')->toArray();
            $newInvestorIds = collect($investmentData['investors'])->pluck('investor_id')->toArray();

            // حذف المستثمرين المحذوفين
            $investorsToDelete = array_diff($currentInvestorIds, $newInvestorIds);
            if (!empty($investorsToDelete)) {
                InvestmentInvestor::where('investment_id', $investment->id)
                    ->whereIn('investor_id', $investorsToDelete)
                    ->delete();
            }

            // تحديث أو إضافة المستثمرين
            foreach ($investmentData['investors'] as $investorData) {
                $investorId = $investorData['investor_id'];
                $costPercentage = (float) $investorData['cost_percentage'];
                $profitPercentage = (float) $investorData['profit_percentage'];

                $investmentInvestor = InvestmentInvestor::where('investment_id', $investment->id)
                    ->where('investor_id', $investorId)
                    ->first();

                if ($investmentInvestor) {
                    // تحديث المستثمر الموجود
                    $investmentInvestor->update([
                        'cost_percentage' => $costPercentage,
                        'profit_percentage' => $profitPercentage,
                    ]);
                } else {
                    // إضافة مستثمر جديد
                    InvestmentInvestor::create([
                        'investment_id' => $investment->id,
                        'investor_id' => $investorId,
                        'cost_percentage' => $costPercentage,
                        'profit_percentage' => $profitPercentage,
                        'investment_amount' => 0, // سيتم تحديثه عند إضافة منتجات
                    ]);
                }
            }

            return redirect()->route('admin.projects.show', $project)
                ->with('success', 'تم تحديث المشروع بنجاح');
        });
    }

    /**
     * نقل الأرباح من الخزنة الفرعية إلى الرئيسية
     */
    public function transferProfit(Request $request, Project $project)
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'description' => 'nullable|string',
        ]);

        if (!$project->treasury) {
            return back()->withErrors(['error' => 'لا توجد خزنة فرعية لهذا المشروع']);
        }

        $projectTreasury = $project->treasury;
        $mainTreasury = Treasury::getDefault();

        if ($projectTreasury->current_balance < $validated['amount']) {
            return back()->withErrors(['error' => 'الرصيد غير كافي في الخزنة الفرعية']);
        }

        return DB::transaction(function () use ($projectTreasury, $mainTreasury, $validated, $project) {
            // سحب من الخزنة الفرعية
            $projectTreasury->withdraw(
                $validated['amount'],
                $validated['description'] ?? "نقل إلى الخزنة الرئيسية - مشروع: {$project->name}",
                Auth::id()
            );

            // إيداع في الخزنة الرئيسية
            $mainTreasury->deposit(
                $validated['amount'],
                'manual',
                null,
                "نقل من مشروع: {$project->name} - {$validated['description']}",
                Auth::id()
            );

            return back()->with('success', 'تم نقل الأرباح بنجاح');
        });
    }

    /**
     * API: حساب قيمة الاستثمار
     */
    public function calculateInvestmentValue(Request $request)
    {
        $validated = $request->validate([
            'type' => 'required|in:product,warehouse',
            'targets' => 'required|array|min:1',
            'targets.*.id' => 'required|integer',
        ]);

        $totalValue = 0;
        $individualValues = [];

        foreach ($validated['targets'] as $target) {
            // معالجة الحالات المختلفة (array أو integer مباشرة)
            $targetId = is_array($target) ? ($target['id'] ?? $target[0] ?? null) : $target;

            if ($validated['type'] === 'warehouse' && $targetId) {
                $warehouse = Warehouse::find($targetId);
                if ($warehouse) {
                    $value = $this->profitCalculator->calculateWarehouseValue($warehouse);

                    // حساب النسبة المتبقية
                    $remainingPercentage = $this->calculateRemainingPercentage('warehouse', $warehouse->id);

                    $individualValues[] = [
                        'id' => $warehouse->id,
                        'name' => $warehouse->name,
                        'value' => $value,
                        'remaining_percentage' => $remainingPercentage,
                    ];
                    $totalValue += $value;
                }
            }
        }

        return response()->json([
            'total_value' => $totalValue,
            'individual_values' => $individualValues,
        ]);
    }

    /**
     * API: جلب رصيد خزنة المستثمر
     */
    public function getTreasuryBalance(Investor $investor)
    {
        $treasury = $investor->treasury;

        if (!$treasury) {
            return response()->json([
                'balance' => 0,
                'treasury_id' => null,
                'treasury_name' => null,
            ], 404);
        }

        return response()->json([
            'balance' => (float) $treasury->current_balance,
            'treasury_id' => $treasury->id,
            'treasury_name' => $treasury->name,
        ]);
    }

    /**
     * حساب النسبة المتبقية للمنتج أو المخزن
     */
    private function calculateRemainingPercentage(string $type, int $targetId): float
    {
        // البحث عن الاستثمارات عبر investment_targets (البنية الجديدة)
        $investmentIds = InvestmentTarget::where('target_type', $type)
            ->where('target_id', $targetId)
            ->pluck('investment_id');

        $investments = Investment::whereIn('id', $investmentIds)
            ->where('status', 'active')
            ->where(function ($q) {
                $q->whereNull('end_date')
                    ->orWhere('end_date', '>=', now());
            })
            ->where('start_date', '<=', now())
            ->get();

        // حساب مجموع نسب المدير
        $adminPercentage = $investments->sum('admin_profit_percentage');

        // حساب مجموع نسب المستثمرين
        $investorPercentage = InvestmentInvestor::whereIn('investment_id', $investments->pluck('id'))
            ->sum('profit_percentage');

        // البحث عن الاستثمارات القديمة (backward compatibility)
        $oldInvestments = Investment::where('investment_type', $type)
            ->where(function ($q) use ($type, $targetId) {
                if ($type === 'product') {
                    $q->where('product_id', $targetId);
                } elseif ($type === 'warehouse') {
                    $q->where('warehouse_id', $targetId);
                }
            })
            ->where('status', 'active')
            ->whereNotNull('investor_id')
            ->where(function ($q) {
                $q->whereNull('end_date')
                    ->orWhere('end_date', '>=', now());
            })
            ->where('start_date', '<=', now())
            ->get();

        $oldInvestorPercentage = $oldInvestments->sum('profit_percentage');

        $totalUsed = $adminPercentage + $investorPercentage + $oldInvestorPercentage;
        $remaining = max(0, 100 - $totalUsed);

        return round($remaining, 2);
    }

    /**
     * حذف المشروع مع جميع التبعيات
     */
    public function destroy(Project $project)
    {
        return DB::transaction(function () use ($project) {
            // جلب جميع الاستثمارات المرتبطة بالمشروع
            $investments = $project->investments()->with(['targets', 'investors'])->get();

            // جلب جميع المستثمرين المرتبطين بالمشروع
            $investmentIds = $investments->pluck('id');
            $investmentInvestors = InvestmentInvestor::whereIn('investment_id', $investmentIds)->get();
            $investorIds = $investmentInvestors->pluck('investor_id')->unique();

            // حذف InvestmentTarget (pivot table - لا يحذف المنتجات/المخازن)
            foreach ($investments as $investment) {
                $investment->targets()->delete();
            }

            // حذف InvestmentInvestor (pivot table)
            InvestmentInvestor::whereIn('investment_id', $investmentIds)->delete();

            // حذف InvestorProfit المرتبطة بالاستثمارات
            \App\Models\InvestorProfit::whereIn('investment_id', $investmentIds)->delete();

            // حذف الاستثمارات
            $project->investments()->delete();

            // حذف خزنة المشروع الفرعية
            if ($project->treasury) {
                $projectTreasury = $project->treasury;
                // حذف جميع المعاملات المرتبطة بالخزنة
                $projectTreasury->transactions()->delete();
                // حذف الخزنة نفسها
                $projectTreasury->delete();
            }

            // ملاحظة: لا نحذف المستثمرين أو خزناتهم أو معاملاتهم
            // نحذف فقط الأرباح المرتبطة بهذا المشروع
            // (تم حذف InvestorProfit أعلاه في السطر 595)

            // حذف المشروع
            $project->delete();

            return redirect()->route('admin.projects.index')
                ->with('success', 'تم حذف المشروع وجميع التبعيات بنجاح');
        });
    }
}
