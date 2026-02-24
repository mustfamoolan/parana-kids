<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\Investor;
use App\Models\Product;
use App\Models\Warehouse;
use App\Models\Investment;
use App\Models\InvestmentTarget;
use App\Models\InvestmentInvestor;
use App\Models\Treasury;
use App\Services\ProfitCalculator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class MobileAdminProjectController extends Controller
{
    protected $profitCalculator;

    public function __construct(ProfitCalculator $profitCalculator)
    {
        $this->profitCalculator = $profitCalculator;
    }

    /**
     * جلب قائمة المشاريع
     */
    public function index(Request $request)
    {
        $projects = Project::with(['investments.investors', 'creator'])
            ->latest()
            ->paginate($request->get('limit', 20));

        return response()->json([
            'status' => 'success',
            'data' => $projects
        ]);
    }

    /**
     * تفاصيل المشروع
     */
    public function show(Project $project)
    {
        $project->load([
            'treasury.transactions' => function ($q) {
                $q->latest()->limit(20);
            },
            'investments.targets',
            'investments.investors.investor',
            'creator'
        ]);

        $totalValue = $project->getTotalValue();
        $totalInvestment = $project->getTotalInvestment();

        return response()->json([
            'status' => 'success',
            'data' => [
                'project' => $project,
                'stats' => [
                    'total_value' => $totalValue,
                    'total_investment' => $totalInvestment,
                ]
            ]
        ]);
    }

    /**
     * إنشاء مشروع جديد
     */
    public function store(Request $request)
    {
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

        return DB::transaction(function () use ($validated) {
            $project = Project::create([
                'name' => $validated['project_name'],
                'project_type' => 'investors',
                'status' => 'active',
                'created_by' => Auth::id(),
            ]);

            $projectTreasury = Treasury::create([
                'name' => "خزنة مشروع: {$project->name} (API)",
                'initial_capital' => 0,
                'current_balance' => 0,
                'created_by' => Auth::id(),
            ]);

            $project->update(['treasury_id' => $projectTreasury->id]);

            $investmentData = $validated['investment'];
            $calculatedTotalValue = 0;
            $targetValues = [];

            foreach ($investmentData['targets'] as $target) {
                $targetId = is_array($target) ? ($target['id'] ?? $target[0] ?? null) : $target;
                if ($investmentData['type'] === 'warehouse' && $targetId) {
                    $warehouse = Warehouse::find($targetId);
                    if ($warehouse) {
                        $value = $this->profitCalculator->calculateWarehouseValue($warehouse);
                        $targetValues[] = ['type' => 'warehouse', 'id' => $warehouse->id, 'value' => $value];
                        $calculatedTotalValue += $value;
                    }
                }
            }

            $totalValue = isset($investmentData['total_value']) ? (float) $investmentData['total_value'] : $calculatedTotalValue;

            // حساب نسبة المدير المتبقية تلقائياً إذا لم توجد في القائمة
            $totalInvestorsProfitPercentage = collect($investmentData['investors'])->sum('profit_percentage');
            $adminInvestor = Investor::where('is_admin', true)->first();
            $adminInvestorInList = $adminInvestor && collect($investmentData['investors'])->contains('investor_id', $adminInvestor->id);

            if ($adminInvestorInList) {
                $adminData = collect($investmentData['investors'])->firstWhere('investor_id', $adminInvestor->id);
                $adminProfitPercentage = $adminData['profit_percentage'];
            } else {
                $adminProfitPercentage = 100 - $totalInvestorsProfitPercentage;
            }

            $investment = Investment::create([
                'project_id' => $project->id,
                'investment_type' => $investmentData['type'],
                'admin_profit_percentage' => $adminProfitPercentage,
                'total_value' => $totalValue,
                'start_date' => now(),
                'status' => 'active',
                'created_by' => Auth::id(),
            ]);

            foreach ($targetValues as $tv) {
                InvestmentTarget::create([
                    'investment_id' => $investment->id,
                    'target_type' => $tv['type'],
                    'target_id' => $tv['id'],
                    'value' => $tv['value'],
                ]);
            }

            foreach ($investmentData['investors'] as $investorData) {
                InvestmentInvestor::create([
                    'investment_id' => $investment->id,
                    'investor_id' => $investorData['investor_id'],
                    'cost_percentage' => $investorData['cost_percentage'],
                    'profit_percentage' => $investorData['profit_percentage'],
                    'investment_amount' => 0,
                ]);
            }

            return response()->json([
                'status' => 'success',
                'message' => 'تم إنشاء المشروع بنجاح',
                'data' => $project->load('investments.investors')
            ], 201);
        });
    }

    /**
     * حساب قيمة الاستثمار (API مساعد)
     */
    public function calculateValue(Request $request)
    {
        $validated = $request->validate([
            'type' => 'required|in:warehouse',
            'targets' => 'required|array|min:1',
            'targets.*.id' => 'required|integer',
        ]);

        $totalValue = 0;
        $individualValues = [];

        foreach ($validated['targets'] as $target) {
            if ($validated['type'] === 'warehouse') {
                $warehouse = Warehouse::find($target['id']);
                if ($warehouse) {
                    $value = $this->profitCalculator->calculateWarehouseValue($warehouse);
                    $individualValues[] = [
                        'id' => $warehouse->id,
                        'name' => $warehouse->name,
                        'value' => $value
                    ];
                    $totalValue += $value;
                }
            }
        }

        return response()->json([
            'status' => 'success',
            'data' => [
                'total_value' => $totalValue,
                'individual_values' => $individualValues
            ]
        ]);
    }
}
