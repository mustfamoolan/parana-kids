<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class CustomerProductApiController extends Controller
{
    /**
     * Get a list of products for the customer app
     */
    public function index(Request $request)
    {
        // 1. Get allowed warehouses
        $allowedWarehousesJson = Setting::getValue('app_customer_allowed_warehouses', '[]');
        $allowedWarehouses = json_decode($allowedWarehousesJson, true);
        
        if (empty($allowedWarehouses)) {
            return response()->json([
                'success' => true,
                'data' => [],
                'message' => 'لم يتم تحديد مخازن متاحة للعرض حالياً'
            ]);
        }

        // 2. Build Query
        $query = Product::with(['primaryImage', 'warehouse.activePromotion'])
            ->whereIn('warehouse_id', $allowedWarehouses)
            ->where('is_hidden', false);

        // a. Filter by Categories (gender_type)
        if ($request->filled('category') && $request->category !== 'all') {
            $category = $request->category;
            if ($category === 'girls') {
                $query->whereIn('gender_type', ['girls', 'boys_girls']);
            } elseif ($category === 'boys') {
                $query->whereIn('gender_type', ['boys', 'boys_girls']);
            } else {
                $query->where('gender_type', $category);
            }
        }

        // b. Search by Name or Code
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                  ->orWhere('code', 'LIKE', "%{$search}%");
            });
        }

        // c. Check availability (Total stock > 0)
        $query->whereHas('sizes', function($q) {
            $q->where('quantity', '>', 0);
        });

        // 3. Execution
        $products = $query->latest()->paginate($request->per_page ?? 20);

        return response()->json([
            'success' => true,
            'data' => $products->map(function ($product) {
                return $this->formatProductItem($product);
            }),
            'meta' => [
                'current_page' => $products->currentPage(),
                'last_page' => $products->lastPage(),
                'total' => $products->total(),
            ]
        ]);
    }

    /**
     * Get smart search suggestions
     */
    public function searchSuggestions(Request $request)
    {
        $q = $request->get('q', '');
        if (strlen($q) < 2) {
            return response()->json(['success' => true, 'data' => []]);
        }

        $allowedWarehousesJson = Setting::getValue('app_customer_allowed_warehouses', '[]');
        $allowedWarehouses = json_decode($allowedWarehousesJson, true);

        $suggestions = Product::whereIn('warehouse_id', $allowedWarehouses)
            ->where('is_hidden', false)
            ->where('name', 'LIKE', "%{$q}%")
            ->whereHas('sizes', function($qu) {
                $qu->where('quantity', '>', 0);
            })
            ->limit(10)
            ->get(['id', 'name']);

        return response()->json([
            'success' => true,
            'data' => $suggestions
        ]);
    }

    /**
     * Get single product details
     */
    public function show($id)
    {
        $product = Product::with(['images', 'sizes', 'warehouse.activePromotion'])
            ->where('is_hidden', false)
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $this->formatProductDetails($product)
        ]);
    }

    /**
     * Get suggestions (Complete the Look) from the same warehouse
     */
    public function suggestions($id)
    {
        $currentProduct = Product::findOrFail($id);

        $suggestions = Product::with(['primaryImage', 'warehouse.activePromotion'])
            ->where('warehouse_id', $currentProduct->warehouse_id)
            ->where('id', '!=', $id)
            ->where('is_hidden', false)
            ->whereHas('sizes', function($q) {
                $q->where('quantity', '>', 0);
            })
            ->inRandomOrder()
            ->limit(4)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $suggestions->map(function ($product) {
                return $this->formatProductItem($product);
            })
        ]);
    }

    /**
     * Helper to format generic product card data
     */
    private function formatProductItem(Product $product)
    {
        $discountInfo = $product->getDiscountInfo();
        
        return [
            'id' => $product->id,
            'name' => $product->name,
            'code' => $product->code,
            'selling_price' => (float)$product->selling_price,
            'effective_price' => (float)$product->effective_price,
            'has_discount' => $product->hasActiveDiscount(),
            'discount_percentage' => $discountInfo ? (float)$discountInfo['percentage'] : 0,
            'primary_image' => $product->primary_image_url ?? '',
            'warehouse_name' => $product->warehouse ? $product->warehouse->name : '',
        ];
    }

    /**
     * Helper to format detailed product page data
     */
    private function formatProductDetails(Product $product)
    {
        $discountInfo = $product->getDiscountInfo();
        
        return [
            'id' => $product->id,
            'name' => $product->name,
            'code' => $product->code,
            'description' => $product->description ?? 'لا يوجد وصف متاح للمنتج',
            'selling_price' => (float)$product->selling_price,
            'effective_price' => (float)$product->effective_price,
            'has_discount' => $product->hasActiveDiscount(),
            'discount_info' => $discountInfo ? [
                'type' => $discountInfo['type'],
                'value' => (float)$discountInfo['value'],
                'original_price' => (float)$discountInfo['original_price'],
                'discount_price' => (float)$discountInfo['discount_price'],
                'percentage' => (float)$discountInfo['percentage']
            ] : null,
            'images' => $product->images->map(function($img) {
                return $img->image_url;
            }),
            'sizes' => $product->sizes->map(function($size) {
                return [
                    'id' => $size->id,
                    'size_name' => $size->size_name,
                    'quantity' => (int)$size->quantity,
                    'available' => (int)$size->quantity > 0,
                ];
            })->values(),
            'warehouse_id' => $product->warehouse_id,
            'warehouse_name' => $product->warehouse ? $product->warehouse->name : '',
        ];
    }
}
