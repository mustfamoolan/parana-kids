<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Warehouse;
use App\Models\User;
use App\Models\WarehousePromotion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

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
        $productsQuery = $warehouse->products()
            ->withSum('sizes', 'quantity')
            ->with(['images', 'primaryImage', 'sizes', 'creator', 'warehouse.activePromotion']);

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

        // فلتر المنتجات المحجوبة
        if ($request->filled('is_hidden')) {
            $isHidden = $request->is_hidden === '1' || $request->is_hidden === 'true';
            $productsQuery->where('is_hidden', $isHidden);
        }

        // فلتر المنتجات المخفضة
        if ($request->filled('has_discount')) {
            $hasDiscount = $request->has_discount === '1' || $request->has_discount === 'true';
            if ($hasDiscount) {
                $productsQuery->where(function($q) {
                    $q->where(function($subQ) {
                        // منتجات لها تخفيض نشط
                        $subQ->whereNotNull('discount_type')
                             ->where('discount_type', '!=', 'none')
                             ->whereNotNull('discount_value')
                             ->where(function($dateQ) {
                                 $now = now();
                                 $dateQ->where(function($d) use ($now) {
                                     $d->whereNull('discount_start_date')
                                       ->orWhere('discount_start_date', '<=', $now);
                                 })
                                 ->where(function($d) use ($now) {
                                     $d->whereNull('discount_end_date')
                                       ->orWhere('discount_end_date', '>=', $now);
                                 });
                             });
                    });
                });
            } else {
                // منتجات بدون تخفيض
                $productsQuery->where(function($q) {
                    $q->whereNull('discount_type')
                      ->orWhere('discount_type', 'none')
                      ->orWhereNull('discount_value');
                });
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

        // فلتر الترتيب حسب تاريخ المواد (الأقدم / الأحدث)
        $sortDateFilter = $request->get('sort_date', '');
        if (in_array($sortDateFilter, ['asc', 'desc'])) {
            $productsQuery->orderBy('products.created_at', $sortDateFilter);
        }

        // فلتر الترتيب حسب عدد المواد / الكمية (تصاعدي / تنازلي)
        $sortQuantityFilter = $request->get('sort_quantity', '');
        if (in_array($sortQuantityFilter, ['asc', 'desc'])) {
            $productsQuery->orderBy('sizes_sum_quantity', $sortQuantityFilter);
        }

        // الترتيب الافتراضي إذا لم يتم تحديد أي ترتيب آخر
        if (!$sortDateFilter && !$sortQuantityFilter) {
            $productsQuery->latest('products.id');
        }

        // حساب الإحصائيات قبل pagination (بناءً على جميع المنتجات المفلترة)
        $allFilteredProducts = (clone $productsQuery)->get();
        $totalPieces = $allFilteredProducts->sum(function($product) {
            return $product->sizes->sum('quantity');
        });

        $totalSellingPrice = 0;
        $totalPurchasePrice = 0;

        if (auth()->user()->isAdmin()) {
            foreach ($allFilteredProducts as $product) {
                $totalQuantity = $product->sizes->sum('quantity');
                // استخدام effective_price بدلاً من selling_price
                $totalSellingPrice += $product->effective_price * $totalQuantity;

                if ($product->purchase_price) {
                    $totalPurchasePrice += $product->purchase_price * $totalQuantity;
                }
            }
        }

        // جلب المنتجات المفلترة مع pagination
        $perPage = $request->input('per_page', 24);
        $products = $productsQuery->paginate($perPage)->withQueryString();

        // تحميل العلاقات للمخزن
        $warehouse->load(['users', 'activePromotion']);

        // تمرير معاملات البحث والفلترة للـ view
        $searchTerm = $request->get('search', '');
        $genderTypeFilter = $request->get('gender_type', '');
        $isHiddenFilter = $request->get('is_hidden', '');
        $hasDiscountFilter = $request->get('has_discount', '');
        $sortDateFilter = $request->get('sort_date', '');
        $sortQuantityFilter = $request->get('sort_quantity', '');

        // جلب التخفيض النشط
        $activePromotion = $warehouse->getCurrentActivePromotion();

        // حساب إحصائيات المنتجات (من جميع منتجات المخزن وليس فقط المفلترة)
        $allProductsCount = $warehouse->products()->count();

        // حساب المنتجات مع تخفيض نشط
        $productsWithDiscount = $warehouse->products()->where(function($q) {
            $now = now();
            $q->whereNotNull('discount_type')
              ->where('discount_type', '!=', 'none')
              ->whereNotNull('discount_value')
              ->where(function($dateQ) use ($now) {
                  $dateQ->where(function($d) use ($now) {
                      $d->whereNull('discount_start_date')
                        ->orWhere('discount_start_date', '<=', $now);
                  })
                  ->where(function($d) use ($now) {
                      $d->whereNull('discount_end_date')
                        ->orWhere('discount_end_date', '>=', $now);
                  });
              });
        })->count();

        $productsWithoutDiscount = $allProductsCount - $productsWithDiscount;
        $productsHidden = $warehouse->products()->where('is_hidden', true)->count();
        $productsNotHidden = $allProductsCount - $productsHidden;

        // جلب الاستثمارات والأرباح للمخزن (للمدير فقط)
        $investments = collect();
        $warehouseProfits = collect();
        $totalWarehouseProfit = 0;
        $totalInvestorProfit = 0;
        $ownerProfit = 0;

        if (auth()->user()->isAdmin()) {
            $investments = \App\Models\Investment::where('investment_type', 'warehouse')
                ->where('warehouse_id', $warehouse->id)
                ->where('status', 'active')
                ->with('investor')
                ->get();

            // حساب إجمالي ربح المخزن من الأرباح المسجلة
            $warehouseProfits = \App\Models\InvestorProfit::where('warehouse_id', $warehouse->id)
                ->with('investor', 'investment')
                ->get();

            $totalWarehouseProfit = \App\Models\ProfitRecord::where('warehouse_id', $warehouse->id)
                ->where('status', 'confirmed')
                ->sum('actual_profit') ?? 0;

            $totalInvestorProfit = $warehouseProfits->sum('profit_amount') ?? 0;
            $ownerProfit = $totalWarehouseProfit - $totalInvestorProfit;
        }

        return view('admin.warehouses.show', compact('warehouse', 'products', 'totalSellingPrice', 'totalPurchasePrice', 'totalPieces', 'searchTerm', 'genderTypeFilter', 'isHiddenFilter', 'hasDiscountFilter', 'sortDateFilter', 'sortQuantityFilter', 'activePromotion', 'productsWithDiscount', 'productsWithoutDiscount', 'productsHidden', 'productsNotHidden', 'investments', 'warehouseProfits', 'totalWarehouseProfit', 'totalInvestorProfit', 'ownerProfit'));
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

        try {
            DB::transaction(function () use ($warehouse) {

                // جلب معرّفات المنتجات التابعة للمخزن
                $productIds = $warehouse->products()->pluck('id');

                if ($productIds->isNotEmpty()) {
                    // 1. تسجيل حركات الحذف قبل الحذف
                    foreach ($warehouse->products()->with('sizes')->get() as $product) {
                        foreach ($product->sizes as $size) {
                            \App\Models\ProductMovement::record([
                                'product_id'    => $product->id,
                                'size_id'       => $size->id,
                                'warehouse_id'  => $warehouse->id,
                                'movement_type' => 'delete',
                                'quantity'      => -$size->quantity,
                                'balance_after' => 0,
                                'notes'         => "\u062d\u0630\u0641 \u0645\u062e\u0632\u0646: {$warehouse->name} - \u0645\u0646\u062a\u062c: {$product->name} - \u0642\u064a\u0627\u0633: {$size->size_name} (\u0643\u0627\u0646 \u0627\u0644\u0631\u0635\u064a\u062f: {$size->quantity})",
                            ]);
                        }
                    }

                    // 2. جلب معرّفات الطلبات المرتبطة بمنتجات هذا المخزن
                    $affectedOrderIds = DB::table('order_items')
                        ->whereIn('product_id', $productIds)
                        ->pluck('order_id')
                        ->unique();

                    // 3. حذف order_items المرتبطة بمنتجات هذا المخزن
                    DB::table('order_items')->whereIn('product_id', $productIds)->delete();

                    // 4. حذف الطلبات التي أصبحت فارغة بعد حذف items
                    if ($affectedOrderIds->isNotEmpty()) {
                        $ordersWithRemainingItems = DB::table('order_items')
                            ->whereIn('order_id', $affectedOrderIds)
                            ->pluck('order_id')
                            ->unique();

                        $ordersToDelete = $affectedOrderIds->diff($ordersWithRemainingItems);

                        if ($ordersToDelete->isNotEmpty()) {
                            DB::table('alwaseet_shipments')->whereIn('order_id', $ordersToDelete)->delete();
                            DB::table('order_partial_returns')->whereIn('order_id', $ordersToDelete)->delete();
                            DB::table('orders')->whereIn('id', $ordersToDelete)->delete();
                        }
                    }

                    // 5. حذف حركات المخزن المرتبطة بالمنتجات
                    DB::table('product_movements')->whereIn('product_id', $productIds)->delete();

                    // 6. حذف صور المنتجات
                    DB::table('product_images')->whereIn('product_id', $productIds)->delete();

                    // 7. حذف قياسات المنتجات
                    DB::table('product_sizes')->whereIn('product_id', $productIds)->delete();

                    // 8. حذف روابط المنتجات إن وجدت
                    if (DB::getSchemaBuilder()->hasTable('product_links')) {
                        DB::table('product_links')->whereIn('product_id', $productIds)->delete();
                    }

                    // 9. حذف سجلات الأرباح المرتبطة بالمنتجات
                    if (DB::getSchemaBuilder()->hasTable('profit_records')) {
                        DB::table('profit_records')->whereIn('product_id', $productIds)->delete();
                    }

                    // 10. حذف الاستثمارات المرتبطة بالمنتجات
                    if (DB::getSchemaBuilder()->hasTable('investments')) {
                        DB::table('investments')
                            ->where('investment_type', 'product')
                            ->whereIn('product_id', $productIds)
                            ->delete();
                    }

                    // 11. حذف المنتجات نفسها
                    DB::table('products')->whereIn('id', $productIds)->delete();
                }

                // 12. حذف تخفيضات المخزن
                DB::table('warehouse_promotions')->where('warehouse_id', $warehouse->id)->delete();

                // 13. حذف استثمارات المخزن
                if (DB::getSchemaBuilder()->hasTable('investments')) {
                    DB::table('investments')
                        ->where('investment_type', 'warehouse')
                        ->where('warehouse_id', $warehouse->id)
                        ->delete();
                }

                // 14. حذف سجلات أرباح المخزن
                if (DB::getSchemaBuilder()->hasTable('profit_records')) {
                    DB::table('profit_records')->where('warehouse_id', $warehouse->id)->delete();
                }

                // 15. فصل المستخدمين المرتبطين (pivot table)
                $warehouse->users()->detach();

                // 16. حذف المخزن نفسه
                $warehouse->delete();
            });

            return redirect()->route('admin.warehouses.index')
                            ->with('success', '\u062a\u0645 \u062d\u0630\u0641 \u0627\u0644\u0645\u062e\u0632\u0646 \u0648\u062c\u0645\u064a\u0639 \u0628\u064a\u0627\u0646\u0627\u062a\u0647 \u0627\u0644\u0645\u0631\u062a\u0628\u0637\u0629 \u0628\u0646\u062c\u0627\u062d');

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Warehouse delete error', [
                'warehouse_id' => $warehouse->id,
                'error'        => $e->getMessage(),
            ]);

            return redirect()->back()
                            ->with('error', '\u062d\u062f\u062b \u062e\u0637\u0623 \u0623\u062b\u0646\u0627\u0621 \u062d\u0630\u0641 \u0627\u0644\u0645\u062e\u0632\u0646: ' . $e->getMessage());
        }
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

    /**
     * Get active promotion for warehouse (AJAX)
     */
    public function getActivePromotion(Warehouse $warehouse)
    {
        $this->authorize('view', $warehouse);

        $promotion = WarehousePromotion::active()
            ->forWarehouse($warehouse->id)
            ->first();

        if ($promotion) {
            return response()->json([
                'success' => true,
                'promotion' => [
                    'id' => $promotion->id,
                    'discount_type' => $promotion->discount_type,
                    'promotion_price' => $promotion->promotion_price,
                    'discount_percentage' => $promotion->discount_percentage,
                    'start_date' => $promotion->start_date->setTimezone('Asia/Baghdad')->format('Y-m-d\TH:i'),
                    'end_date' => $promotion->end_date->setTimezone('Asia/Baghdad')->format('Y-m-d\TH:i'),
                    'is_active' => $promotion->is_active,
                ]
            ]);
        }

        return response()->json([
            'success' => false,
            'promotion' => null
        ]);
    }

    /**
     * Store a new promotion
     */
    public function storePromotion(Request $request, Warehouse $warehouse)
    {
        $this->authorize('update', $warehouse);

        $request->validate([
            'discount_type' => 'required|in:amount,percentage',
            'promotion_price' => 'required_if:discount_type,amount|nullable|numeric|min:0',
            'discount_percentage' => 'required_if:discount_type,percentage|nullable|numeric|min:0.01|max:100',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
        ]);

        // التحقق من عدم وجود تخفيض نشط آخر
        $existingPromotion = WarehousePromotion::active()
            ->forWarehouse($warehouse->id)
            ->first();

        if ($existingPromotion) {
            return response()->json([
                'success' => false,
                'message' => 'يوجد تخفيض نشط حالياً لهذا المخزن. يرجى إيقاف التخفيض الحالي أولاً.'
            ], 422);
        }

        // تحويل التواريخ إلى توقيت العراق
        $startDate = \Carbon\Carbon::parse($request->start_date, 'Asia/Baghdad')->setTimezone('UTC');
        $endDate = \Carbon\Carbon::parse($request->end_date, 'Asia/Baghdad')->setTimezone('UTC');

        $promotion = WarehousePromotion::create([
            'warehouse_id' => $warehouse->id,
            'discount_type' => $request->discount_type,
            'promotion_price' => $request->discount_type === 'amount' ? $request->promotion_price : null,
            'discount_percentage' => $request->discount_type === 'percentage' ? $request->discount_percentage : null,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'is_active' => true,
            'created_by' => Auth::id(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'تم تفعيل التخفيض بنجاح',
            'promotion' => [
                'id' => $promotion->id,
                'discount_type' => $promotion->discount_type,
                'promotion_price' => $promotion->promotion_price,
                'discount_percentage' => $promotion->discount_percentage,
                'start_date' => $promotion->start_date->setTimezone('Asia/Baghdad')->format('Y-m-d H:i'),
                'end_date' => $promotion->end_date->setTimezone('Asia/Baghdad')->format('Y-m-d H:i'),
            ]
        ]);
    }

    /**
     * Toggle promotion (activate/deactivate)
     */
    public function togglePromotion(Request $request, Warehouse $warehouse)
    {
        $this->authorize('update', $warehouse);

        $promotion = WarehousePromotion::forWarehouse($warehouse->id)
            ->where('is_active', true)
            ->latest()
            ->first();

        if (!$promotion) {
            return response()->json([
                'success' => false,
                'message' => 'لا يوجد تخفيض نشط لهذا المخزن'
            ], 404);
        }

        $promotion->update([
            'is_active' => !$promotion->is_active
        ]);

        return response()->json([
            'success' => true,
            'message' => $promotion->is_active ? 'تم تفعيل التخفيض' : 'تم إيقاف التخفيض',
            'is_active' => $promotion->is_active
        ]);
    }

    /**
     * Update promotion
     */
    public function updatePromotion(Request $request, Warehouse $warehouse, WarehousePromotion $promotion)
    {
        $this->authorize('update', $warehouse);

        // التأكد من أن التخفيض يخص هذا المخزن
        if ($promotion->warehouse_id !== $warehouse->id) {
            abort(404);
        }

        $request->validate([
            'discount_type' => 'required|in:amount,percentage',
            'promotion_price' => 'required_if:discount_type,amount|nullable|numeric|min:0',
            'discount_percentage' => 'required_if:discount_type,percentage|nullable|numeric|min:0|max:100',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
        ]);

        // تحويل التواريخ إلى توقيت العراق
        $startDate = \Carbon\Carbon::parse($request->start_date, 'Asia/Baghdad')->setTimezone('UTC');
        $endDate = \Carbon\Carbon::parse($request->end_date, 'Asia/Baghdad')->setTimezone('UTC');

        $promotion->update([
            'discount_type' => $request->discount_type,
            'promotion_price' => $request->discount_type === 'amount' ? $request->promotion_price : null,
            'discount_percentage' => $request->discount_type === 'percentage' ? $request->discount_percentage : null,
            'start_date' => $startDate,
            'end_date' => $endDate,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'تم تحديث التخفيض بنجاح',
            'promotion' => [
                'id' => $promotion->id,
                'discount_type' => $promotion->discount_type,
                'promotion_price' => $promotion->promotion_price,
                'discount_percentage' => $promotion->discount_percentage,
                'start_date' => $promotion->start_date->setTimezone('Asia/Baghdad')->format('Y-m-d H:i'),
                'end_date' => $promotion->end_date->setTimezone('Asia/Baghdad')->format('Y-m-d H:i'),
            ]
        ]);
    }
}
