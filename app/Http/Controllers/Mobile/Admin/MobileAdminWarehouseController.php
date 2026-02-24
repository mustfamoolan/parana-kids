<?php

namespace App\Http\Controllers\Mobile\Admin;

use App\Http\Controllers\Controller;
use App\Models\Warehouse;
use App\Models\Product;
use App\Models\ProductSize;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MobileAdminWarehouseController extends Controller
{
    /**
     * Display a listing of the warehouses with statistics.
     */
    public function index(Request $request)
    {
        $user = Auth::user();

        try {
            // Determine accessible warehouses
            if ($user->isAdmin()) {
                $query = Warehouse::with('creator');
            } else {
                $query = $user->warehouses()->with('creator');
            }

            // Global Statistics
            if ($user->isAdmin()) {
                $totalWarehouses = Warehouse::count();
                $totalProducts = Product::count();
                $totalPieces = ProductSize::sum('quantity');
            } else {
                $accessibleWarehouseIds = $user->warehouses()->pluck('warehouses.id');
                $totalWarehouses = $accessibleWarehouseIds->count();
                $totalProducts = Product::whereIn('warehouse_id', $accessibleWarehouseIds)->count();
                $totalPieces = ProductSize::whereIn(
                    'product_id',
                    Product::whereIn('warehouse_id', $accessibleWarehouseIds)->pluck('id')
                )->sum('quantity');
            }

            $perPage = $request->input('per_page', 15);
            $warehouses = $query->withCount('products')->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => [
                    'statistics' => [
                        'total_warehouses' => $totalWarehouses,
                        'total_products' => $totalProducts,
                        'total_pieces' => $totalPieces,
                    ],
                    'warehouses' => $warehouses
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء جلب قائمة المخازن: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified warehouse with filtered products.
     */
    public function show(Request $request, $id)
    {
        $user = Auth::user();

        try {
            $warehouse = Warehouse::with(['users', 'activePromotion'])->find($id);

            if (!$warehouse) {
                return response()->json([
                    'success' => false,
                    'message' => 'المخزن غير موجود'
                ], 404);
            }

            // Check authorization
            if (!$user->isAdmin() && !$user->warehouses->contains($id)) {
                return response()->json([
                    'success' => false,
                    'message' => 'غير مصرح لك بالوصول لهذا المخزن'
                ], 403);
            }

            // Build products query
            $productsQuery = $warehouse->products()->with(['images', 'primaryImage', 'sizes', 'creator', 'warehouse.activePromotion']);

            // Filtering
            $this->applyFilters($productsQuery, $request);

            // Statistics (for the specific warehouse)
            $allProductsCount = $warehouse->products()->count();
            $productsHidden = $warehouse->products()->where('is_hidden', true)->count();
            $productsDiscounted = $warehouse->products()->where(function ($q) {
                $now = now();
                $q->whereNotNull('discount_type')
                    ->where('discount_type', '!=', 'none')
                    ->whereNotNull('discount_value')
                    ->where(function ($dateQ) use ($now) {
                        $dateQ->where(function ($d) use ($now) {
                            $d->whereNull('discount_start_date')->orWhere('discount_start_date', '<=', $now);
                        })->where(function ($d) use ($now) {
                            $d->whereNull('discount_end_date')->orWhere('discount_end_date', '>=', $now);
                        });
                    });
            })->count();

            $perPage = $request->input('per_page', 24);
            $products = $productsQuery->paginate($perPage);

            // Map products to include effective_price
            $products->getCollection()->transform(function ($product) {
                $product->effective_price = $product->effective_price; // Uses accessor
                return $product;
            });

            return response()->json([
                'success' => true,
                'data' => [
                    'warehouse' => $warehouse,
                    'statistics' => [
                        'total_products' => $allProductsCount,
                        'hidden_products' => $productsHidden,
                        'discounted_products' => $productsDiscounted,
                        'visible_products' => $allProductsCount - $productsHidden,
                    ],
                    'products' => $products
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء جلب بيانات المخزن: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created warehouse.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'location' => 'nullable|string|max:255',
        ]);

        try {
            $warehouse = Warehouse::create([
                'name' => $request->name,
                'location' => $request->location,
                'created_by' => Auth::id(),
            ]);

            // Auto-assign the creator with manage permissions
            $warehouse->users()->attach(Auth::id(), ['can_manage' => true]);

            return response()->json([
                'success' => true,
                'message' => 'تم إنشاء المخزن بنجاح',
                'data' => $warehouse
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء إنشاء المخزن: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update the specified warehouse.
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'location' => 'nullable|string|max:255',
        ]);

        try {
            $warehouse = Warehouse::find($id);
            if (!$warehouse)
                return response()->json(['success' => false, 'message' => 'المخزن غير موجود'], 404);

            $warehouse->update($request->only(['name', 'location']));

            return response()->json([
                'success' => true,
                'message' => 'تم تحديث المخزن بنجاح',
                'data' => $warehouse
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء تحديث المخزن: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Sync users for a warehouse (permissions).
     */
    public function syncUsers(Request $request, $id)
    {
        $request->validate([
            'users' => 'required|array',
            'users.*.id' => 'required|exists:users,id',
            'users.*.can_manage' => 'boolean',
        ]);

        try {
            $warehouse = Warehouse::find($id);
            if (!$warehouse)
                return response()->json(['success' => false, 'message' => 'المخزن غير موجود'], 404);

            $syncData = [];
            foreach ($request->users as $userData) {
                $syncData[$userData['id']] = ['can_manage' => $userData['can_manage'] ?? false];
            }

            $warehouse->users()->sync($syncData);

            return response()->json([
                'success' => true,
                'message' => 'تم تحديث صلاحيات الوصول بنجاح'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء تحديث الصلاحيات: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created product in the warehouse.
     */
    public function storeProduct(Request $request, $id)
    {
        $warehouse = Warehouse::find($id);
        if (!$warehouse)
            return response()->json(['success' => false, 'message' => 'المخزن غير موجود'], 404);

        $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|unique:products,code',
            'purchase_price' => 'required|numeric',
            'selling_price' => 'required|numeric',
            'gender_type' => 'required|in:boys,girls,boys_girls,accessories',
            'description' => 'nullable|string',
            'sizes' => 'required|array',
            'sizes.*.size_name' => 'required|string',
            'sizes.*.quantity' => 'required|integer|min:0',
            'images' => 'nullable|array',
            'images.*' => 'image|max:2048',
        ]);

        try {
            \DB::beginTransaction();

            $product = $warehouse->products()->create([
                'name' => $request->name,
                'code' => $request->code,
                'purchase_price' => $request->purchase_price,
                'selling_price' => $request->selling_price,
                'gender_type' => $request->gender_type,
                'description' => $request->description,
                'created_by' => Auth::id(),
            ]);

            // Sizes
            foreach ($request->sizes as $size) {
                $product->sizes()->create($size);
            }

            // Images
            if ($request->hasFile('images')) {
                foreach ($request->file('images') as $index => $file) {
                    $path = $file->store('products', 'public');
                    $product->images()->create([
                        'image_path' => $path,
                        'is_primary' => $index === 0,
                        'order' => $index,
                    ]);
                }
            }

            \DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'تم إضافة المنتج بنجاح',
                'data' => $product->load('sizes', 'images')
            ]);
        } catch (\Exception $e) {
            \DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء إضافة المنتج: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update an existing product.
     */
    public function updateProduct(Request $request, $id, $productId)
    {
        $product = Product::where('warehouse_id', $id)->find($productId);
        if (!$product)
            return response()->json(['success' => false, 'message' => 'المنتج غير موجود'], 404);

        $request->validate([
            'name' => 'required|string|max:255',
            'purchase_price' => 'required|numeric',
            'selling_price' => 'required|numeric',
            'gender_type' => 'required|in:boys,girls,boys_girls,accessories',
            'description' => 'nullable|string',
            'sizes' => 'required|array',
        ]);

        try {
            \DB::beginTransaction();

            $product->update($request->only(['name', 'purchase_price', 'selling_price', 'gender_type', 'description']));

            // Update Sizes (Delete and Recreate or match by ID)
            $product->sizes()->delete();
            foreach ($request->sizes as $size) {
                $product->sizes()->create([
                    'size_name' => $size['size_name'],
                    'quantity' => $size['quantity'],
                ]);
            }

            \DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'تم تحديث المنتج بنجاح'
            ]);
        } catch (\Exception $e) {
            \DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء تحديث المنتج: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Toggle product visibility (Hide/Unhide).
     */
    public function toggleVisibility($id, $productId)
    {
        try {
            $product = Product::where('warehouse_id', $id)->find($productId);
            if (!$product)
                return response()->json(['success' => false, 'message' => 'المنتج غير موجود'], 404);

            $product->update(['is_hidden' => !$product->is_hidden]);

            return response()->json([
                'success' => true,
                'message' => $product->is_hidden ? 'تم حجب المنتج' : 'تم إلغاء حجب المنتج',
                'is_hidden' => $product->is_hidden
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Apply discount to product.
     */
    public function applyDiscount(Request $request, $id, $productId)
    {
        $request->validate([
            'type' => 'required|in:percentage,amount,none',
            'value' => 'nullable|numeric|min:0',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
        ]);

        try {
            $product = Product::where('warehouse_id', $id)->find($productId);
            if (!$product)
                return response()->json(['success' => false, 'message' => 'المنتج غير موجود'], 404);

            $product->update([
                'discount_type' => $request->type,
                'discount_value' => $request->type === 'none' ? null : $request->value,
                'discount_start_date' => $request->start_date,
                'discount_end_date' => $request->end_date,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'تم تحديث الخصم بنجاح'
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Delete a product.
     */
    public function destroyProduct($id, $productId)
    {
        try {
            $product = Product::where('warehouse_id', $id)->find($productId);
            if (!$product)
                return response()->json(['success' => false, 'message' => 'المنتج غير موجود'], 404);

            $product->delete();

            return response()->json([
                'success' => true,
                'message' => 'تم حذف المنتج بنجاح'
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Get all users for permissions assignment.
     */
    public function getUsers()
    {
        try {
            $users = \App\Models\User::select('id', 'name', 'role')->get();
            return response()->json([
                'success' => true,
                'data' => $users
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Apply filters to the products query.
     */
    private function applyFilters($query, Request $request)
    {
        // Search
        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('code', 'LIKE', "%{$search}%")
                    ->orWhere('name', 'LIKE', "%{$search}%")
                    ->orWhereHas('sizes', function ($sizeQuery) use ($search) {
                        $sizeQuery->where('size_name', 'LIKE', "%{$search}%");
                    });
            });
        }

        // Gender Type
        if ($request->filled('gender_type')) {
            $genderType = $request->gender_type;
            if ($genderType == 'boys') {
                $query->whereIn('gender_type', ['boys', 'boys_girls']);
            } elseif ($genderType == 'girls') {
                $query->whereIn('gender_type', ['girls', 'boys_girls']);
            } else {
                $query->where('gender_type', $genderType);
            }
        }

        // Visibility
        if ($request->filled('is_hidden')) {
            $isHidden = $request->is_hidden === '1' || $request->is_hidden === 'true';
            $query->where('is_hidden', $isHidden);
        }

        // Discounts
        if ($request->filled('has_discount')) {
            $hasDiscount = $request->has_discount === '1' || $request->has_discount === 'true';
            if ($hasDiscount) {
                $query->where(function ($q) {
                    $q->whereNotNull('discount_type')
                        ->where('discount_type', '!=', 'none')
                        ->whereNotNull('discount_value')
                        ->where(function ($dateQ) {
                            $now = now();
                            $dateQ->where(function ($d) use ($now) {
                                $d->whereNull('discount_start_date')->orWhere('discount_start_date', '<=', $now);
                            })->where(function ($d) use ($now) {
                                $d->whereNull('discount_end_date')->orWhere('discount_end_date', '>=', $now);
                            });
                        });
                });
            } else {
                $query->where(function ($q) {
                    $q->whereNull('discount_type')
                        ->orWhere('discount_type', 'none')
                        ->orWhereNull('discount_value');
                });
            }
        }
    }
}
