<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Warehouse;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class WarehouseController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $user = Auth::user();

        // تحديد المخازن المتاحة أولاً
        if ($user->isAdmin()) {
            $query = Warehouse::with('creator');
        } else {
            // للمجهزين: جميع المخازن المصرح لهم بها
            $query = $user->warehouses()->with('creator');
        }

        // الفلترة
        $warehouseId = $request->get('warehouse_id');
        $productSearch = $request->get('product_search');

        if ($warehouseId) {
            $query->where('id', $warehouseId);
        }

        if ($productSearch) {
            $query->whereHas('products', function($q) use ($productSearch) {
                $q->where('name', 'LIKE', "%{$productSearch}%")
                  ->orWhere('code', 'LIKE', "%{$productSearch}%");
            });
        }

        $perPage = $request->input('per_page', 15);
        $warehouses = $query->withCount('products')->paginate($perPage)->appends($request->except('page'));

        // حساب الإحصائيات الكلية
        if ($user->isAdmin()) {
            $totalWarehouses = Warehouse::count();
            $totalProducts = \App\Models\Product::count();
            $totalPieces = \App\Models\ProductSize::sum('quantity');
        } else {
            $totalWarehouses = $user->warehouses()->count();
            $totalProducts = \App\Models\Product::whereIn('warehouse_id',
                            $user->warehouses()->pluck('warehouses.id'))->count();
            $totalPieces = \App\Models\ProductSize::whereIn('product_id',
                          \App\Models\Product::whereIn('warehouse_id',
                          $user->warehouses()->pluck('warehouses.id'))->pluck('id'))->sum('quantity');
        }

        // قائمة المخازن للفلترة - فقط المخازن المصرح بها
        if ($user->isAdmin()) {
            $warehousesList = Warehouse::select('id', 'name')->get();
        } else {
            $warehousesList = $user->warehouses()->select('warehouses.id', 'warehouses.name')->get();
        }

        return view('admin.warehouses.index', compact(
            'warehouses',
            'totalWarehouses',
            'totalProducts',
            'totalPieces',
            'warehousesList'
        ));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $this->authorize('create', Warehouse::class);

        return view('admin.warehouses.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $this->authorize('create', Warehouse::class);

        $request->validate([
            'name' => 'required|string|max:255',
            'location' => 'required|string|max:255',
        ]);

        $warehouse = Warehouse::create([
            'name' => $request->name,
            'location' => $request->location,
            'created_by' => Auth::id(),
        ]);

        return redirect()->route('admin.warehouses.index')
                        ->with('success', 'تم إنشاء المخزن بنجاح');
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, Warehouse $warehouse)
    {
        $this->authorize('view', $warehouse);

        // بناء الاستعلام للمنتجات
        $productsQuery = $warehouse->products()->with(['images', 'primaryImage', 'sizes', 'creator']);

        // فلتر حسب النوع (gender_type)
        if ($request->filled('gender_type')) {
            $genderType = $request->gender_type;
            if ($genderType == 'boys') {
                // عرض "ولادي" و "ولادي بناتي"
                $productsQuery->whereIn('gender_type', ['boys', 'boys_girls']);
            } elseif ($genderType == 'girls') {
                // عرض "بناتي" و "ولادي بناتي"
                $productsQuery->whereIn('gender_type', ['girls', 'boys_girls']);
            } else {
                // عرض النوع المحدد فقط (boys_girls أو accessories)
                $productsQuery->where('gender_type', $genderType);
            }
        }

        // البحث بكود المنتج أو اسم المنتج أو القياس
        if ($request->filled('search')) {
            $search = trim($request->search);
            $productsQuery->where(function($q) use ($search) {
                $q->where('code', 'LIKE', "%{$search}%")
                  ->orWhere('name', 'LIKE', "%{$search}%")
                  ->orWhereHas('sizes', function($sizeQuery) use ($search) {
                      $sizeQuery->where('size_name', 'LIKE', "%{$search}%");
                  });
            });
        }

        // جلب المنتجات المفلترة
        $products = $productsQuery->get();

        // تحميل العلاقات للمخزن
        $warehouse->load(['users']);

        // حساب السعر الكلي للبيع والشراء (للمدير فقط) - بناءً على المنتجات المفلترة
        $totalSellingPrice = 0;
        $totalPurchasePrice = 0;

        // حساب إجمالي القطع - بناءً على المنتجات المفلترة
        $totalPieces = $products->sum(function($product) {
            return $product->sizes->sum('quantity');
        });

        if (auth()->user()->isAdmin()) {
            foreach ($products as $product) {
                $totalQuantity = $product->sizes->sum('quantity');
                $totalSellingPrice += $product->selling_price * $totalQuantity;

                if ($product->purchase_price) {
                    $totalPurchasePrice += $product->purchase_price * $totalQuantity;
                }
            }
        }

        // تمرير معاملات البحث والفلترة للـ view
        $searchTerm = $request->get('search', '');
        $genderTypeFilter = $request->get('gender_type', '');

        // تعيين المنتجات المفلترة للمخزن لعرضها في الـ view
        $warehouse->setRelation('products', $products);

        return view('admin.warehouses.show', compact('warehouse', 'totalSellingPrice', 'totalPurchasePrice', 'totalPieces', 'searchTerm', 'genderTypeFilter'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Warehouse $warehouse)
    {
        $this->authorize('update', $warehouse);

        return view('admin.warehouses.edit', compact('warehouse'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Warehouse $warehouse)
    {
        $this->authorize('update', $warehouse);

        $request->validate([
            'name' => 'required|string|max:255',
            'location' => 'required|string|max:255',
        ]);

        $warehouse->update($request->only(['name', 'location']));

        return redirect()->route('admin.warehouses.index')
                        ->with('success', 'تم تحديث المخزن بنجاح');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Warehouse $warehouse)
    {
        $this->authorize('delete', $warehouse);

        // تسجيل حركات الحذف لجميع المنتجات والقياسات قبل الحذف
        foreach ($warehouse->products as $product) {
            foreach ($product->sizes as $size) {
                \App\Models\ProductMovement::record([
                    'product_id' => $product->id,
                    'size_id' => $size->id,
                    'warehouse_id' => $warehouse->id,
                    'movement_type' => 'delete',
                    'quantity' => -$size->quantity,
                    'balance_after' => 0,
                    'notes' => "حذف مخزن: {$warehouse->name} - منتج: {$product->name} - قياس: {$size->size_name} (كان الرصيد: {$size->quantity})",
                ]);
            }
        }

        $warehouse->delete();

        return redirect()->route('admin.warehouses.index')
                        ->with('success', 'تم حذف المخزن بنجاح');
    }

    /**
     * Show the form for assigning users to warehouse
     */
    public function assignUsers(Warehouse $warehouse)
    {
        $this->authorize('manage', $warehouse);

        $users = User::whereIn('role', ['supplier', 'delegate'])->get();
        $assignedUsers = $warehouse->users()->get();

        return view('admin.warehouses.assign-users', compact('warehouse', 'users', 'assignedUsers'));
    }

    /**
     * Update warehouse users
     */
    public function updateUsers(Request $request, Warehouse $warehouse)
    {
        $this->authorize('manage', $warehouse);

        $request->validate([
            'users' => 'array',
            'users.*.user_id' => 'required|exists:users,id',
            'users.*.can_manage' => 'boolean',
        ]);

        // Clear existing assignments
        $warehouse->users()->detach();

        // Add new assignments
        if ($request->has('users')) {
            foreach ($request->users as $userData) {
                $warehouse->users()->attach($userData['user_id'], [
                    'can_manage' => $userData['can_manage'] ?? false
                ]);
            }
        }

        return redirect()->route('admin.warehouses.show', $warehouse)
                        ->with('success', 'تم تحديث صلاحيات المستخدمين بنجاح');
    }
}
