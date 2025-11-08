<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Expense;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ExpenseController extends Controller
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            if (!Auth::user()->isAdmin()) {
                abort(403, 'غير مصرح لك بالوصول إلى هذه الصفحة.');
            }
            return $next($request);
        });
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        // بناء query للفلاتر
        $query = Expense::with(['creator', 'user', 'product']);

        // فلتر نوع المصروف
        if ($request->filled('expense_type')) {
            $query->where('expense_type', $request->expense_type);
        }

        // فلتر من تاريخ
        if ($request->filled('date_from')) {
            $query->where('expense_date', '>=', $request->date_from);
        }

        // فلتر إلى تاريخ
        if ($request->filled('date_to')) {
            $query->where('expense_date', '<=', $request->date_to);
        }

        // فلتر اسم الشخص (للرواتب)
        if ($request->filled('person_name')) {
            $query->where(function($q) use ($request) {
                $q->where('person_name', 'LIKE', "%{$request->person_name}%")
                  ->orWhereHas('user', function($userQuery) use ($request) {
                      $userQuery->where('name', 'LIKE', "%{$request->person_name}%");
                  });
            });
        }

        // جلب المصروفات
        $expenses = $query->orderBy('expense_date', 'desc')
                         ->orderBy('created_at', 'desc')
                         ->paginate(20)
                         ->appends($request->except('page'));

        // حساب الإحصائيات (من جميع المصروفات وليس فقط المفلترة)
        $allExpenses = Expense::all();

        $totalExpenses = $allExpenses->sum('amount');
        $totalRent = $allExpenses->where('expense_type', 'rent')->sum('amount');
        $totalSalary = $allExpenses->where('expense_type', 'salary')->sum('amount');
        $totalOther = $allExpenses->where('expense_type', 'other')->sum('amount');
        $totalPromotion = $allExpenses->where('expense_type', 'promotion')->sum('amount');
        $expensesCount = $allExpenses->count();

        // حساب الإحصائيات المفلترة
        $filteredExpenses = $query->get();
        $filteredTotal = $filteredExpenses->sum('amount');

        // جلب المستخدمين للفلترة (المندوبين والمجهزين)
        $users = User::whereIn('role', ['delegate', 'supplier'])->get();

        return view('admin.expenses.index', compact(
            'expenses',
            'totalExpenses',
            'totalRent',
            'totalSalary',
            'totalOther',
            'totalPromotion',
            'expensesCount',
            'filteredTotal',
            'users'
        ));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        // جلب المستخدمين (المندوبين والمجهزين) للاختيار في الرواتب
        $users = User::whereIn('role', ['delegate', 'supplier'])->get();

        // جلب المنتجات للبحث في الترويج
        $products = \App\Models\Product::select('id', 'name', 'code')->orderBy('name')->get();

        return view('admin.expenses.create', compact('users', 'products'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'expense_type' => 'required|in:rent,salary,other,promotion',
            'amount' => 'required|numeric|min:0',
            'salary_amount' => 'nullable|numeric|min:0',
            'expense_date' => 'required|date',
            'person_name' => 'nullable|string|max:255',
            'user_id' => 'nullable|exists:users,id',
            'product_id' => 'nullable|exists:products,id',
            'notes' => 'nullable|string|max:1000',
        ]);

        // للرواتب: يجب إدخال اسم الشخص أو اختيار مستخدم
        if ($request->expense_type === 'salary' && !$request->person_name && !$request->user_id) {
            return back()->withErrors(['person_name' => 'يجب إدخال اسم الشخص أو اختيار مستخدم للرواتب.'])->withInput();
        }

        $expense = Expense::create([
            'expense_type' => $validated['expense_type'],
            'amount' => $validated['amount'],
            'salary_amount' => $validated['salary_amount'] ?? null,
            'expense_date' => $validated['expense_date'],
            'person_name' => $validated['person_name'] ?? null,
            'user_id' => $validated['user_id'] ?? null,
            'product_id' => $validated['product_id'] ?? null,
            'notes' => $validated['notes'] ?? null,
            'created_by' => Auth::id(),
        ]);

        return redirect()->route('admin.expenses.index')
                        ->with('success', 'تم إضافة المصروف بنجاح.');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Expense $expense)
    {
        // جلب المستخدمين (المندوبين والمجهزين) للاختيار في الرواتب
        $users = User::whereIn('role', ['delegate', 'supplier'])->get();

        // جلب المنتجات للبحث في الترويج
        $products = \App\Models\Product::select('id', 'name', 'code')->orderBy('name')->get();

        return view('admin.expenses.edit', compact('expense', 'users', 'products'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Expense $expense)
    {
        $validated = $request->validate([
            'expense_type' => 'required|in:rent,salary,other,promotion',
            'amount' => 'required|numeric|min:0',
            'salary_amount' => 'nullable|numeric|min:0',
            'expense_date' => 'required|date',
            'person_name' => 'nullable|string|max:255',
            'user_id' => 'nullable|exists:users,id',
            'product_id' => 'nullable|exists:products,id',
            'notes' => 'nullable|string|max:1000',
        ]);

        // للرواتب: يجب إدخال اسم الشخص أو اختيار مستخدم
        if ($request->expense_type === 'salary' && !$request->person_name && !$request->user_id) {
            return back()->withErrors(['person_name' => 'يجب إدخال اسم الشخص أو اختيار مستخدم للرواتب.'])->withInput();
        }

        $expense->update([
            'expense_type' => $validated['expense_type'],
            'amount' => $validated['amount'],
            'salary_amount' => $validated['salary_amount'] ?? null,
            'expense_date' => $validated['expense_date'],
            'person_name' => $validated['person_name'] ?? null,
            'user_id' => $validated['user_id'] ?? null,
            'product_id' => $validated['product_id'] ?? null,
            'notes' => $validated['notes'] ?? null,
        ]);

        return redirect()->route('admin.expenses.index')
                        ->with('success', 'تم تحديث المصروف بنجاح.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Expense $expense)
    {
        $expense->delete();

        return redirect()->route('admin.expenses.index')
                        ->with('success', 'تم حذف المصروف بنجاح.');
    }

    /**
     * Search products for promotion expenses (AJAX)
     */
    public function searchProducts(Request $request)
    {
        try {
            $search = $request->get('search', '');

            if (empty($search)) {
                return response()->json([]);
            }

            $products = \App\Models\Product::select('id', 'name', 'code')
                ->where(function($query) use ($search) {
                    $query->where('name', 'LIKE', "%{$search}%")
                          ->orWhere('code', 'LIKE', "%{$search}%");
                })
                ->orderBy('name')
                ->limit(20)
                ->get();

            return response()->json($products);
        } catch (\Exception $e) {
            \Log::error('Error searching products: ' . $e->getMessage());
            return response()->json(['error' => 'حدث خطأ في البحث'], 500);
        }
    }
}
