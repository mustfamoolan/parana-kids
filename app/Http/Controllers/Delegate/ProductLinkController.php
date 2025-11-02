<?php

namespace App\Http\Controllers\Delegate;

use App\Http\Controllers\Controller;
use App\Models\ProductLink;
use App\Models\Warehouse;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProductLinkController extends Controller
{
    /**
     * Display a listing of delegate's product links
     */
    public function index()
    {
        // عرض روابط المندوب الحالي فقط
        $links = ProductLink::where('created_by', Auth::id())
            ->with(['warehouse', 'creator'])
            ->latest()
            ->paginate(20);

        return view('delegate.product-links.index', compact('links'));
    }

    /**
     * Show the form for creating a new product link
     */
    public function create()
    {
        // جلب جميع المخازن للمندوب
        $warehouses = Warehouse::all();

        return view('delegate.product-links.create', compact('warehouses'));
    }

    /**
     * Store a newly created product link
     */
    public function store(Request $request)
    {
        $request->validate([
            'warehouse_id' => 'nullable|exists:warehouses,id',
            'gender_type' => 'nullable|in:boys,girls,accessories,boys_girls',
            'size_name' => 'nullable|string|max:50',
        ]);

        $productLink = ProductLink::create([
            'warehouse_id' => $request->warehouse_id ?: null,
            'gender_type' => $request->gender_type,
            'size_name' => $request->size_name,
            'created_by' => Auth::id(),
        ]);

        return redirect()->route('delegate.product-links.index')
            ->with('success', 'تم إنشاء الرابط بنجاح');
    }

    /**
     * Remove the specified product link
     */
    public function destroy($id)
    {
        $productLink = ProductLink::findOrFail($id);

        // التأكد من أن الرابط للمندوب الحالي
        if ($productLink->created_by !== Auth::id()) {
            abort(403, 'ليس لديك صلاحية لحذف هذا الرابط');
        }

        $productLink->delete();

        return redirect()->route('delegate.product-links.index')
            ->with('success', 'تم حذف الرابط بنجاح');
    }

    /**
     * Get available sizes for selected warehouse and gender type
     */
    public function getSizes(Request $request)
    {
        $request->validate([
            'warehouse_id' => 'nullable|exists:warehouses,id',
            'gender_type' => 'nullable|in:boys,girls,accessories,boys_girls',
        ]);

        // جلب المنتجات
        $productsQuery = Product::whereHas('sizes', function($q) {
            $q->where('quantity', '>', 0);
        });

        // فلتر حسب المخزن (إذا كان محدداً)
        if ($request->warehouse_id) {
            $productsQuery->where('warehouse_id', $request->warehouse_id);
        }

        // فلتر حسب النوع (مع دعم boys_girls)
        if ($request->gender_type) {
            if ($request->gender_type == 'boys') {
                // عرض "ولادي" و "ولادي بناتي"
                $productsQuery->whereIn('gender_type', ['boys', 'boys_girls']);
            } elseif ($request->gender_type == 'girls') {
                // عرض "بناتي" و "ولادي بناتي"
                $productsQuery->whereIn('gender_type', ['girls', 'boys_girls']);
            } else {
                // عرض النوع المحدد فقط (boys_girls أو accessories)
                $productsQuery->where('gender_type', $request->gender_type);
            }
        }

        $products = $productsQuery->with('sizes')->get();

        // جمع القياسات المتاحة
        $sizes = [];
        foreach ($products as $product) {
            foreach ($product->sizes as $size) {
                if ($size->quantity > 0) {
                    $sizeName = $size->size_name;
                    if (!isset($sizes[$sizeName])) {
                        $sizes[$sizeName] = [
                            'name' => $sizeName,
                            'count' => 0
                        ];
                    }
                    $sizes[$sizeName]['count'] += $size->quantity;
                }
            }
        }

        // ترتيب حسب الاسم
        ksort($sizes);

        return response()->json([
            'sizes' => array_values($sizes)
        ]);
    }
}
