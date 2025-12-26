<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Investment;
use App\Models\Investor;
use App\Models\Product;
use App\Models\Warehouse;
use App\Models\PrivateWarehouse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class InvestmentController extends Controller
{
    /**
     * عرض قائمة الاستثمارات
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', Investment::class);

        $query = Investment::with(['investor', 'product', 'warehouse', 'privateWarehouse']);

        // فلترة حسب النوع
        if ($request->filled('investment_type')) {
            $query->where('investment_type', $request->investment_type);
        }

        // فلترة حسب الحالة
        if ($request->filled('status')) {
            $query->where('status', $request->status);
    }

        // فلترة حسب المستثمر
        if ($request->filled('investor_id')) {
            $query->where('investor_id', $request->investor_id);
        }

        // بحث
        if ($request->filled('search')) {
            $query->whereHas('investor', function($q) use ($request) {
                $q->where('name', 'LIKE', "%{$request->search}%")
                  ->orWhere('phone', 'LIKE', "%{$request->search}%");
            });
        }

        $investments = $query->latest()->paginate(20);
        $investors = Investor::where('status', 'active')->get();

        return view('admin.investments.index', compact('investments', 'investors'));
    }

    /**
     * عرض صفحة إضافة استثمار
     */
    public function create(Request $request)
    {
        $this->authorize('create', Investment::class);
        
        $investors = Investor::where('status', 'active')->get();
        $products = Product::all();
        $warehouses = Warehouse::all();
        $privateWarehouses = PrivateWarehouse::all();

        // دعم query parameters لتحديد المنتج/المخزن/المستثمر تلقائياً
        $productId = $request->query('product_id');
        $warehouseId = $request->query('warehouse_id');
        $privateWarehouseId = $request->query('private_warehouse_id');
        $investorId = $request->query('investor_id');
        $backUrl = $request->query('back_url');

        return view('admin.investments.create', compact(
            'investors', 
            'products', 
            'warehouses', 
            'privateWarehouses',
            'productId',
            'warehouseId',
            'privateWarehouseId',
            'investorId',
            'backUrl'
        ));
    }

    /**
     * حفظ استثمار جديد
     */
    public function store(Request $request)
    {
        $this->authorize('create', Investment::class);

        $validated = $request->validate([
            'investor_id' => 'required|exists:investors,id',
            'investment_type' => 'required|in:product,warehouse,private_warehouse',
            'product_id' => 'required_if:investment_type,product|exists:products,id',
            'product_warehouse_id' => 'required_if:investment_type,product|exists:warehouses,id',
            'warehouse_id' => 'required_if:investment_type,warehouse|exists:warehouses,id',
            'private_warehouse_id' => 'required_if:investment_type,private_warehouse|exists:private_warehouses,id',
            'profit_percentage' => 'required|numeric|min:0|max:100',
            'investment_amount' => 'required|numeric|min:0',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after:start_date',
            'notes' => 'nullable|string',
        ]);

        // التحقق من أن المنتج ينتمي للمخزن المختار
        if ($validated['investment_type'] === 'product' && isset($validated['product_id']) && isset($validated['product_warehouse_id'])) {
            $product = Product::find($validated['product_id']);
            if ($product && $product->warehouse_id != $validated['product_warehouse_id']) {
                return back()->withErrors([
                    'product_id' => 'المنتج المختار لا ينتمي للمخزن المحدد'
                ])->withInput();
            }
        }

        // إزالة product_warehouse_id لأنه ليس حقل في جدول investments
        unset($validated['product_warehouse_id']);

        // التحقق من النسب
        $targetId = $validated['product_id'] ?? $validated['warehouse_id'] ?? $validated['private_warehouse_id'];
        $validation = Investment::validatePercentages(
            $validated['investment_type'],
            $targetId,
            $validated['profit_percentage']
        );

        if (!$validation['is_valid']) {
            return back()->withErrors([
                'profit_percentage' => "مجموع النسب الحالية: {$validation['total']}%. الحد الأقصى: 100%"
            ])->withInput();
        }

        $validated['created_by'] = Auth::id();
        $validated['status'] = 'active';

        Investment::create($validated);

        // إذا كان هناك back_url، العودة إليه
        $backUrl = $request->input('back_url');
        if ($backUrl) {
            return redirect($backUrl)->with('success', 'تم إنشاء الاستثمار بنجاح');
        }

        return redirect()->route('admin.investments.index')
            ->with('success', 'تم إنشاء الاستثمار بنجاح');
    }

    /**
     * عرض صفحة تعديل استثمار
     */
    public function edit(Investment $investment)
    {
        $this->authorize('update', $investment);
        
        $investors = Investor::where('status', 'active')->get();
        $products = Product::all();
        $warehouses = Warehouse::all();
        $privateWarehouses = PrivateWarehouse::all();

        return view('admin.investments.edit', compact('investment', 'investors', 'products', 'warehouses', 'privateWarehouses'));
    }

    /**
     * تحديث استثمار
     */
    public function update(Request $request, Investment $investment)
    {
        $this->authorize('update', $investment);

        $validated = $request->validate([
            'investor_id' => 'required|exists:investors,id',
            'investment_type' => 'required|in:product,warehouse,private_warehouse',
            'product_id' => 'required_if:investment_type,product|exists:products,id',
            'warehouse_id' => 'required_if:investment_type,warehouse|exists:warehouses,id',
            'private_warehouse_id' => 'required_if:investment_type,private_warehouse|exists:private_warehouses,id',
            'profit_percentage' => 'required|numeric|min:0|max:100',
            'investment_amount' => 'required|numeric|min:0',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after:start_date',
            'status' => 'required|in:active,completed,cancelled,suspended',
            'notes' => 'nullable|string',
        ]);

        // التحقق من النسب (استثناء الاستثمار الحالي)
        $targetId = $validated['product_id'] ?? $validated['warehouse_id'] ?? $validated['private_warehouse_id'];
        $validation = Investment::validatePercentages(
            $validated['investment_type'],
            $targetId,
            $validated['profit_percentage'],
            $investment->id
        );

        if (!$validation['is_valid']) {
            return back()->withErrors([
                'profit_percentage' => "مجموع النسب الحالية: {$validation['total']}%. الحد الأقصى: 100%"
            ])->withInput();
        }

        $investment->update($validated);

        return redirect()->route('admin.investments.index')
            ->with('success', 'تم تحديث الاستثمار بنجاح');
    }

    /**
     * حذف/إلغاء استثمار
     */
    public function destroy(Investment $investment)
    {
        $this->authorize('delete', $investment);

        // التحقق من وجود أرباح مرتبطة
        if ($investment->profits()->exists()) {
            return back()->withErrors(['error' => 'لا يمكن حذف استثمار لديه أرباح مرتبطة']);
        }

        $investment->delete();

        return redirect()->route('admin.investments.index')
            ->with('success', 'تم حذف الاستثمار بنجاح');
    }
}
