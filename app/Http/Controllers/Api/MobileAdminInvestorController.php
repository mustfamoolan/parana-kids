<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Investor;
use App\Models\InvestorProfit;
use App\Models\Treasury;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class MobileAdminInvestorController extends Controller
{
    /**
     * جلب قائمة المستثمرين
     */
    public function index(Request $request)
    {
        $query = Investor::query();

        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'LIKE', "%{$request->search}%")
                    ->orWhere('phone', 'LIKE', "%{$request->search}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $investors = $query->with('treasury')
            ->withCount(['investments'])
            ->latest()
            ->paginate($request->get('limit', 20));

        return response()->json([
            'status' => 'success',
            'data' => $investors
        ]);
    }

    /**
     * تفاصيل المستثمر مع الإحصائيات
     */
    public function show(Investor $investor)
    {
        $investor->load([
            'treasury.transactions' => function ($q) {
                $q->latest()->limit(50);
            }
        ]);

        // جلب جميع استثمارات المستثمر
        $investmentInvestorIds = \App\Models\InvestmentInvestor::where('investor_id', $investor->id)
            ->pluck('investment_id')
            ->toArray();

        $oldInvestmentIds = \App\Models\Investment::where('investor_id', $investor->id)
            ->whereNull('project_id')
            ->pluck('id')
            ->toArray();

        $allInvestmentIds = array_unique(array_merge($investmentInvestorIds, $oldInvestmentIds));

        // حساب الأرباح
        $profitQuery = InvestorProfit::where('investor_id', $investor->id);
        if (!empty($allInvestmentIds)) {
            $profitQuery->whereIn('investment_id', $allInvestmentIds);
        } else {
            $profitQuery->whereRaw('1 = 0');
        }

        $stats = [
            'total_profit' => (clone $profitQuery)->sum('profit_amount'),
            'pending_profit' => (clone $profitQuery)->where('status', 'pending')->sum('profit_amount'),
            'paid_profit' => (clone $profitQuery)->where('status', 'paid')->sum('profit_amount'),
            'current_balance' => $investor->treasury ? $investor->treasury->current_balance : 0,
        ];

        // سجل الأرباح الأخير
        $recentProfits = (clone $profitQuery)
            ->with(['order', 'product'])
            ->latest()
            ->limit(20)
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => [
                'investor' => $investor,
                'stats' => $stats,
                'recent_profits' => $recentProfits
            ]
        ]);
    }

    /**
     * إضافة مستثمر جديد
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string|unique:investors,phone',
            'password' => 'required|string|min:6',
            'treasury_name' => 'required|string|max:255',
            'notes' => 'nullable|string',
        ]);

        return DB::transaction(function () use ($validated) {
            $investor = Investor::create([
                'name' => $validated['name'],
                'phone' => $validated['phone'],
                'password' => $validated['password'],
                'balance' => 0,
                'status' => 'active',
                'notes' => $validated['notes'] ?? null,
            ]);

            Treasury::create([
                'name' => $validated['treasury_name'],
                'initial_capital' => 0,
                'current_balance' => 0,
                'created_by' => Auth::id(),
                'investor_id' => $investor->id,
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'تم إنشاء المستثمر بنجاح',
                'data' => $investor
            ], 201);
        });
    }

    /**
     * تحديث بيانات المستثمر
     */
    public function update(Request $request, Investor $investor)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string|unique:investors,phone,' . $investor->id,
            'password' => 'nullable|string|min:6',
            'notes' => 'nullable|string',
            'status' => 'required|in:active,inactive',
        ]);

        if (isset($validated['password']) && empty($validated['password'])) {
            unset($validated['password']);
        }

        $investor->update($validated);

        return response()->json([
            'status' => 'success',
            'message' => 'تم تحديث البيانات بنجاح',
            'data' => $investor
        ]);
    }

    /**
     * رفع الأرباح للمعلق
     */
    public function depositProfits(Request $request, Investor $investor)
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'notes' => 'nullable|string|max:1000',
        ]);

        $amount = $validated['amount'];
        $notes = $validated['notes'] ?? null;

        $treasury = $investor->treasury;
        if (!$treasury) {
            return response()->json(['status' => 'error', 'message' => 'المستثمر لا يملك خزنة'], 400);
        }

        // جلب الأرباح المعلقة
        $investmentInvestorIds = \App\Models\InvestmentInvestor::where('investor_id', $investor->id)
            ->pluck('investment_id')
            ->toArray();
        $oldInvestmentIds = \App\Models\Investment::where('investor_id', $investor->id)
            ->whereNull('project_id')
            ->pluck('id')
            ->toArray();
        $allInvestmentIds = array_unique(array_merge($investmentInvestorIds, $oldInvestmentIds));

        $pendingAmount = InvestorProfit::where('investor_id', $investor->id)
            ->where('status', 'pending')
            ->when(!empty($allInvestmentIds), function ($q) use ($allInvestmentIds) {
                return $q->whereIn('investment_id', $allInvestmentIds);
            }, function ($q) {
                return $q->whereRaw('1 = 0');
            })
            ->sum('profit_amount');

        if ($amount > $pendingAmount) {
            return response()->json(['status' => 'error', 'message' => 'المبلغ يتجاوز الأرباح المعلقة'], 400);
        }

        try {
            DB::transaction(function () use ($treasury, $amount, $investor, $notes, $allInvestmentIds) {
                $treasury->deposit(
                    $amount,
                    'profit',
                    null,
                    'رفع أرباح معلقة (API)' . ($notes ? ' - ' . $notes : ''),
                    Auth::id()
                );

                $pendingProfits = InvestorProfit::where('investor_id', $investor->id)
                    ->where('status', 'pending')
                    ->when(!empty($allInvestmentIds), function ($q) use ($allInvestmentIds) {
                        return $q->whereIn('investment_id', $allInvestmentIds);
                    })
                    ->orderBy('profit_date', 'asc')
                    ->get();

                $remainingAmount = $amount;
                foreach ($pendingProfits as $profit) {
                    if ($remainingAmount <= 0)
                        break;

                    $amountToPay = min($profit->profit_amount, $remainingAmount);

                    if ($amountToPay >= $profit->profit_amount) {
                        $profit->update([
                            'status' => 'paid',
                            'payment_date' => now()->toDateString(),
                        ]);
                        $remainingAmount -= $profit->profit_amount;
                    } else {
                        $profit->update(['profit_amount' => $profit->profit_amount - $amountToPay]);
                        InvestorProfit::create([
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
                        ]);
                        $remainingAmount = 0;
                    }
                }
            });

            return response()->json(['status' => 'success', 'message' => 'تم رفع الأرباح بنجاح']);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => 'حدث خطأ: ' . $e->getMessage()], 500);
        }
    }
}
