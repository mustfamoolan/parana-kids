<?php

namespace App\Http\Controllers\Delegate;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductSize;
use App\Models\Warehouse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProductController extends Controller
{
    /**
     * Display all products from accessible warehouses
     */
    public function allProducts(Request $request)
    {
        // الحصول على معرفات المخازن المصرح بها للمندوب
        $warehouseIds = Auth::user()->warehouses()->pluck('warehouse_id');

        // بناء الاستعلام الأساسي
        $query = Product::whereIn('warehouse_id', $warehouseIds)
                        ->with(['primaryImage', 'images', 'sizes.reservations', 'warehouse']);

        $searchedSize = null; // لتمرير القياس المبحوث للـ view

        // البحث بالكود أو القياس
        if ($request->filled('search')) {
            $search = trim($request->search);
            \Log::info('Search applied', ['search_term' => $search]);

            // البحث في كود المنتج أولاً
            $codeMatches = Product::whereIn('warehouse_id', $warehouseIds)
                                  ->where('code', 'LIKE', "%{$search}%")
                                  ->exists();

            if ($codeMatches) {
                // إذا كان البحث عن كود منتج، أظهر المنتج بكل قياساته
                $query->where('code', 'LIKE', "%{$search}%");
            } else {
                // إذا لم يكن كود منتج، ابحث في القياسات المتوفرة فقط
                // نستخدم whereRaw لحساب available_quantity في SQL مباشرة
                $query->whereHas('sizes', function($q) use ($search) {
                    $q->where('size_name', 'LIKE', "%{$search}%")
                      ->whereRaw('quantity > (
                          SELECT COALESCE(SUM(quantity_reserved), 0)
                          FROM stock_reservations
                          WHERE product_size_id = product_sizes.id
                      )');
                });
                $searchedSize = $search; // حفظ القياس المبحوث
            }
        }

        $products = $query->latest()->paginate(30);

        if ($request->ajax()) {
            return response()->json([
                'products' => view('delegate.products.partials.product-cards', compact('products', 'searchedSize'))->render(),
                'has_more' => $products->hasMorePages(),
                'total' => $products->total()
            ]);
        }

        return view('delegate.products.all', compact('products', 'searchedSize'));
    }

    /**
     * Display a listing of products in a warehouse
     */
    public function index(Warehouse $warehouse)
    {
        // Check if delegate has access to this warehouse
        if (!Auth::user()->canAccessWarehouse($warehouse->id)) {
            abort(403, 'ليس لديك صلاحية للوصول إلى هذا المخزن');
        }

        $products = $warehouse->products()
                              ->with(['images', 'sizes'])
                              ->paginate(10);

        return view('delegate.products.index', compact('warehouse', 'products'));
    }

    /**
     * Display the specified product
     */
    public function show(Warehouse $warehouse, Product $product)
    {
        // Check if delegate has access to this warehouse
        if (!Auth::user()->canAccessWarehouse($warehouse->id)) {
            abort(403, 'ليس لديك صلاحية للوصول إلى هذا المخزن');
        }

        // Verify the product belongs to this warehouse
        if ($product->warehouse_id !== $warehouse->id) {
            abort(404, 'المنتج غير موجود في هذا المخزن');
        }

        // تحميل العلاقات بشكل صريح
        $product->load(['warehouse', 'images', 'sizes', 'creator']);

        // جلب السلال النشطة للمندوب
        $activeCarts = Auth::user()->carts()
            ->where('status', 'active')
            ->where(function($q) {
                $q->whereNull('expires_at')
                  ->orWhere('expires_at', '>', now());
            })
            ->with(['items'])
            ->get();

        // تحديد السلة المحددة من الـ query parameter
        $selectedCart = null;
        if (request('cart_id')) {
            $selectedCart = $activeCarts->where('id', request('cart_id'))->first();
        }

        return view('delegate.products.show', compact('product', 'activeCarts', 'selectedCart'));
    }

    /**
     * Get product data for modal (API endpoint)
     */
    public function getProductData($id)
    {
        $product = Product::with(['sizes', 'primaryImage', 'warehouse'])
                         ->findOrFail($id);

        // التحقق من صلاحية الوصول للمخزن
        if (!Auth::user()->canAccessWarehouse($product->warehouse_id)) {
            return response()->json(['error' => 'ليس لديك صلاحية للوصول إلى هذا المنتج'], 403);
        }

        return response()->json([
            'id' => $product->id,
            'name' => $product->name,
            'code' => $product->code,
            'selling_price' => $product->selling_price,
            'image' => $product->primaryImage ? $product->primaryImage->image_url : null,
            'sizes' => $product->sizes->map(function($size) {
                return [
                    'id' => $size->id,
                    'size_name' => $size->size_name,
                    'available_quantity' => $size->available_quantity,
                    'quantity' => $size->quantity,
                ];
            })
        ]);
    }
}
