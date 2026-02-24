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
