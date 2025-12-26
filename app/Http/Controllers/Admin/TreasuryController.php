<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Treasury;
use App\Models\TreasuryTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class TreasuryController extends Controller
{
    /**
     * عرض قائمة الخزن
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', Treasury::class);

        $query = Treasury::query();

        // بحث
        if ($request->filled('search')) {
            $query->where('name', 'LIKE', "%{$request->search}%");
        }

        $treasuries = $query->withCount('transactions')
            ->with(['creator', 'investor'])
            ->latest()
            ->paginate(20);

        return view('admin.treasuries.index', compact('treasuries'));
    }

    /**
     * عرض صفحة إضافة خزنة
     */
    public function create()
    {
        $this->authorize('create', Treasury::class);
        return view('admin.treasuries.create');
    }

    /**
     * حفظ خزنة جديدة
     */
    public function store(Request $request)
    {
        $this->authorize('create', Treasury::class);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'initial_capital' => 'required|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        $validated['current_balance'] = $validated['initial_capital'];
        $validated['created_by'] = Auth::id();

        $treasury = Treasury::create($validated);

        return redirect()->route('admin.treasuries.show', $treasury)
            ->with('success', 'تم إنشاء الخزنة بنجاح');
    }

    /**
     * عرض تفاصيل الخزنة
     */
    public function show(Request $request, Treasury $treasury)
    {
        $this->authorize('view', $treasury);

        $treasury->load(['creator', 'investor']);

        // إحصائيات
        $totalDeposits = $treasury->transactions()->where('transaction_type', 'deposit')->sum('amount');
        $totalWithdrawals = $treasury->transactions()->where('transaction_type', 'withdrawal')->sum('amount');

        // قوائم
        $transactions = $treasury->transactions()
            ->with('creator')
            ->latest()
            ->paginate(20);

        // تحديد صفحة العودة بناءً على referrer أو investor_id
        $backUrl = null;
        $referrer = $request->headers->get('referer');
        
        // إذا كانت الخزنة مرتبطة بمستثمر، العودة إلى صفحة المستثمر
        if ($treasury->investor_id) {
            $backUrl = route('admin.investors.show', $treasury->investor_id);
        } elseif ($referrer) {
            // استخدام referrer إذا كان من نفس الموقع
            $parsedReferrer = parse_url($referrer);
            $currentHost = $request->getHost();
            
            if (!isset($parsedReferrer['host']) || $parsedReferrer['host'] === $currentHost) {
                $backUrl = $referrer;
            }
        }
        
        // إذا لم يكن هناك referrer صحيح، العودة إلى قائمة الخزن
        if (!$backUrl) {
            $backUrl = route('admin.treasuries.index');
        }

        return view('admin.treasuries.show', compact(
            'treasury',
            'totalDeposits',
            'totalWithdrawals',
            'transactions',
            'backUrl'
        ));
    }

    /**
     * عرض صفحة تعديل خزنة
     */
    public function edit(Treasury $treasury)
    {
        $this->authorize('update', $treasury);
        return view('admin.treasuries.edit', compact('treasury'));
    }

    /**
     * تحديث بيانات الخزنة
     */
    public function update(Request $request, Treasury $treasury)
    {
        $this->authorize('update', $treasury);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'notes' => 'nullable|string',
        ]);

        $treasury->update($validated);

        return redirect()->route('admin.treasuries.show', $treasury)
            ->with('success', 'تم تحديث بيانات الخزنة بنجاح');
    }

    /**
     * إيداع في الخزنة
     */
    public function deposit(Request $request, Treasury $treasury)
    {
        $this->authorize('update', $treasury);

        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'description' => 'nullable|string',
        ]);

        try {
            $treasury->deposit(
                $validated['amount'],
                'manual',
                null,
                $validated['description'] ?? 'إيداع يدوي',
                Auth::id()
            );
            return back()->with('success', 'تم الإيداع بنجاح');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    /**
     * سحب من الخزنة
     */
    public function withdraw(Request $request, Treasury $treasury)
    {
        $this->authorize('update', $treasury);

        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'description' => 'nullable|string',
        ]);

        try {
            $treasury->withdraw(
                $validated['amount'],
                'manual',  // reference_type
                null,      // reference_id
                $validated['description'] ?? 'سحب يدوي',
                Auth::id()
            );
            return back()->with('success', 'تم السحب بنجاح');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    /**
     * حذف خزنة
     */
    public function destroy(Treasury $treasury)
    {
        $this->authorize('delete', $treasury);

        // التحقق من أن الخزنة مرتبطة بمستثمر (لا يمكن حذف خزنات المستثمرين من هنا)
        if ($treasury->investor_id) {
            return back()->withErrors(['error' => 'لا يمكن حذف خزنة مرتبطة بمستثمر. يرجى حذف المستثمر أولاً']);
        }

        return DB::transaction(function () use ($treasury) {
            // حذف جميع معاملات الخزنة
            $treasury->transactions()->delete();

            // حذف الخزنة
            $treasury->delete();

            return redirect()->route('admin.treasuries.index')
                ->with('success', 'تم حذف الخزنة بنجاح');
        });
    }

    /**
     * تصفير رصيد الخزنة
     */
    public function resetBalance(Request $request, Treasury $treasury)
    {
        $this->authorize('update', $treasury);

        return DB::transaction(function () use ($treasury) {
            // حفظ الرصيد الحالي
            $currentBalance = $treasury->current_balance;

            // تسجيل معاملة withdrawal لتوثيق التصفير (قبل تصفير الرصيد)
            if ($currentBalance > 0) {
                TreasuryTransaction::create([
                    'treasury_id' => $treasury->id,
                    'transaction_type' => 'withdrawal',
                    'amount' => $currentBalance,
                    'reference_type' => 'manual',
                    'reference_id' => null,
                    'description' => 'تصفير رصيد الخزنة - ' . $treasury->name,
                    'created_by' => auth()->id(),
                ]);
            }

            // تصفير الرصيد
            $treasury->update([
                'current_balance' => 0,
            ]);

            return redirect()->route('admin.treasuries.show', $treasury)
                ->with('success', 'تم تصفير رصيد الخزنة بنجاح');
        });
    }
}
