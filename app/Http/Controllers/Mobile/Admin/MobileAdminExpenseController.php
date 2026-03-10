<?php

namespace App\Http\Controllers\Mobile\Admin;

use App\Http\Controllers\Controller;
use App\Models\Expense;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\Product;
use App\Services\InvestorExpenseCalculator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MobileAdminExpenseController extends Controller
{
    /**
     * Get a listing of expenses.
     */
    public function index(Request $request)
    {
        try {
            // بناء query للفلاتر
            $query = Expense::with(['creator', 'user', 'product', 'warehouse']);

            // فلتر المخزن
            if ($request->filled('warehouse_id')) {
                $query->where('warehouse_id', $request->warehouse_id);
            }

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
                $query->where(function ($q) use ($request) {
                    $q->where('person_name', 'LIKE', "%{$request->person_name}%")
                        ->orWhereHas('user', function ($userQuery) use ($request) {
                            $userQuery->where('name', 'LIKE', "%{$request->person_name}%");
                        });
                });
            }

            // جلب المصروفات مع Pagination
            $perPage = $request->get('per_page', 15);
            $expenses = $query->orderBy('expense_date', 'desc')
                ->orderBy('created_at', 'desc')
                ->paginate($perPage);

            // حساب الإحصائيات العامة (من جميع المصروفات حسب السياسة المتبعة)
            $allExpenses = Expense::all();

            $statistics = [
                'total_expenses' => $allExpenses->sum('amount'),
                'total_rent' => $allExpenses->where('expense_type', 'rent')->sum('amount'),
                'total_salary' => $allExpenses->where('expense_type', 'salary')->sum('amount'),
                'total_other' => $allExpenses->where('expense_type', 'other')->sum('amount'),
                'total_promotion' => $allExpenses->where('expense_type', 'promotion')->sum('amount'),
                'total_count' => $allExpenses->count(),
            ];

            return response()->json([
                'success' => true,
                'data' => [
                    'expenses' => $expenses->items(),
                    'pagination' => [
                        'current_page' => $expenses->currentPage(),
                        'last_page' => $expenses->lastPage(),
                        'total' => $expenses->total(),
                        'has_more' => $expenses->hasMorePages()
                    ],
                    'statistics' => $statistics
                ]
            ]);

        } catch (\Exception $e) {
            \Log::error('Error fetching mobile expenses: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء جلب المصروفات'
            ], 500);
        }
    }

    /**
     * Get data needed for the creation form filters.
     */
    public function getFilterOptions()
    {
        try {
            $users = User::whereIn('role', ['delegate', 'supplier'])->select('id', 'name')->get();
            $warehouses = Warehouse::select('id', 'name')->orderBy('name')->get();

            return response()->json([
                'success' => true,
                'data' => [
                    'users' => $users,
                    'warehouses' => $warehouses
                ]
            ]);
        } catch (\Exception $e) {
            \Log::error('Error fetching expense filter options: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء جلب خيارات الفلاتر'
            ], 500);
        }
    }

    /**
     * Search products for promotion expenses.
     */
    public function searchProducts(Request $request)
    {
        try {
            $search = $request->get('search', '');

            if (empty($search)) {
                return response()->json(['success' => true, 'data' => []]);
            }

            $products = Product::select('id', 'name', 'code')
                ->where(function ($query) use ($search) {
                    $query->where('name', 'LIKE', "%{$search}%")
                        ->orWhere('code', 'LIKE', "%{$search}%");
                })
                ->orderBy('name')
                ->limit(20)
                ->get();

            return response()->json([
                'success' => true,
                'data' => $products
            ]);
        } catch (\Exception $e) {
            \Log::error('Error searching products for expenses: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء البحث عن المنتجات'
            ], 500);
        }
    }

    /**
     * Store a newly created expense.
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'warehouse_id' => 'required|exists:warehouses,id',
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
                return response()->json([
                    'success' => false,
                    'message' => 'يجب إدخال اسم الشخص أو اختيار مستخدم للرواتب.',
                    'errors' => ['person_name' => ['يجب إدخال اسم الشخص أو اختيار مستخدم للرواتب.']]
                ], 422);
            }

            $expense = Expense::create([
                'warehouse_id' => $validated['warehouse_id'],
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

            // خصم المصروف من خزنة المستثمرين بناءً على حصتهم
            try {
                $expenseCalculator = app(InvestorExpenseCalculator::class);
                $expenseCalculator->deductExpenseFromInvestors($expense);
            } catch (\Exception $e) {
                \Log::error('Error deducting expense from investors: ' . $e->getMessage());
                // لا نوقف العملية إذا فشل خصم المصروف من المستثمرين
            }

            return response()->json([
                'success' => true,
                'message' => 'تم إضافة المصروف بنجاح.',
                'data' => $expense->load(['creator', 'user', 'product', 'warehouse'])
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'بيانات غير صالحة',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            \Log::error('Error storing expense: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء حفظ المصروف'
            ], 500);
        }
    }

    /**
     * Get the specified expense details.
     */
    public function show($id)
    {
        try {
            $expense = Expense::with(['creator', 'user', 'product', 'warehouse'])->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $expense
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'المصروف غير موجود'
            ], 404);
        }
    }

    /**
     * Update the specified expense.
     */
    public function update(Request $request, $id)
    {
        try {
            $expense = Expense::findOrFail($id);

            $validated = $request->validate([
                'warehouse_id' => 'required|exists:warehouses,id',
                'expense_type' => 'required|in:rent,salary,other,promotion',
                'amount' => 'required|numeric|min:0',
                'salary_amount' => 'nullable|numeric|min:0',
                'expense_date' => 'required|date',
                'person_name' => 'nullable|string|max:255',
                'user_id' => 'nullable|exists:users,id',
                'product_id' => 'nullable|exists:products,id',
                'notes' => 'nullable|string|max:1000',
            ]);

            if ($request->expense_type === 'salary' && !$request->person_name && !$request->user_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'يجب إدخال اسم الشخص أو اختيار مستخدم للرواتب.',
                    'errors' => ['person_name' => ['يجب إدخال اسم الشخص أو اختيار مستخدم للرواتب.']]
                ], 422);
            }

            // حفظ القيم القديمة للمقارنة بخوارزمية المستثمرين
            $oldAmount = $expense->amount;
            $oldProductId = $expense->product_id;
            $oldWarehouseId = $expense->warehouse_id;

            $expense->update([
                'warehouse_id' => $validated['warehouse_id'],
                'expense_type' => $validated['expense_type'],
                'amount' => $validated['amount'],
                'salary_amount' => $validated['salary_amount'] ?? null,
                'expense_date' => $validated['expense_date'],
                'person_name' => $validated['person_name'] ?? null,
                'user_id' => $validated['user_id'] ?? null,
                'product_id' => $validated['product_id'] ?? null,
                'notes' => $validated['notes'] ?? null,
            ]);

            // معالجة التغيير في المبلغ والمنتج/المخزن
            try {
                $expenseCalculator = app(InvestorExpenseCalculator::class);

                $amountChanged = ($oldAmount != $validated['amount']);
                $productChanged = ($oldProductId != $expense->product_id);
                $warehouseChanged = ($oldWarehouseId != $expense->warehouse_id);

                if ($amountChanged || $productChanged || $warehouseChanged) {
                    $oldExpense = clone $expense;
                    $oldExpense->amount = $oldAmount;
                    $oldExpense->product_id = $oldProductId;
                    $oldExpense->warehouse_id = $oldWarehouseId;

                    $expenseCalculator->returnExpenseToInvestors($oldExpense, $oldAmount);
                    $expenseCalculator->deductExpenseFromInvestors($expense);
                }
            } catch (\Exception $e) {
                \Log::error('Error updating expense investors via API: ' . $e->getMessage());
            }

            return response()->json([
                'success' => true,
                'message' => 'تم تحديث المصروف بنجاح.',
                'data' => $expense->fresh(['creator', 'user', 'product', 'warehouse'])
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'بيانات غير صالحة',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            \Log::error('Error updating expense: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء تحديث المصروف'
            ], 500);
        }
    }

    /**
     * Remove the specified expense.
     */
    public function destroy($id)
    {
        try {
            $expense = Expense::findOrFail($id);

            try {
                $expenseCalculator = app(InvestorExpenseCalculator::class);
                $expenseCalculator->returnExpenseToInvestors($expense);
            } catch (\Exception $e) {
                \Log::error('Error returning expense to investors on destroy via API: ' . $e->getMessage());
            }

            $expense->delete();

            return response()->json([
                'success' => true,
                'message' => 'تم حذف المصروف بنجاح.'
            ]);
        } catch (\Exception $e) {
            \Log::error('Error deleting expense: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء حذف المصروف'
            ], 500);
        }
    }
}
