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
                        ->with(['primaryImage', 'sizes', 'warehouse']);

        // البحث بالكود أو الاسم فقط
        if ($request->filled('search')) {
            $search = $request->search;
            \Log::info('Search applied', ['search_term' => $search]);
            $query->where(function($q) use ($search) {
                $q->where('code', 'LIKE', "%{$search}%")
                  ->orWhere('name', 'LIKE', "%{$search}%");
            });
        }

        $products = $query->latest()->paginate(30);

        if ($request->ajax()) {
            return response()->json([
                'products' => view('delegate.products.partials.product-cards', compact('products'))->render(),
                'has_more' => $products->hasMorePages(),
                'total' => $products->total()
            ]);
        }

        return view('delegate.products.all', compact('products'));
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
}
