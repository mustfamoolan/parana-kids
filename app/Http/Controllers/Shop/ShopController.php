<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductSize;
use App\Models\Warehouse;
use Illuminate\Http\Request;

class ShopController extends Controller
{
    /**
     * عرض جميع المنتجات (الصفحة الرئيسية)
     */
    public function index(Request $request)
    {
        // جلب جميع المنتجات المتاحة (غير محجوبة) من جميع المخازن
        $query = Product::where('is_hidden', false)
                        ->with(['primaryImage', 'images', 'sizes.reservations', 'warehouse.activePromotion']);

        $searchedSize = null;

        // فلتر النوع
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

        // فلتر التخفيض
        if ($request->filled('has_discount') && $request->has_discount == '1') {
            $query->where(function($q) {
                $q->whereNotNull('discount_type')
                  ->where('discount_type', '!=', 'none')
                  ->whereNotNull('discount_value')
                  ->where(function($dateQ) {
                      $dateQ->where(function($noDates) {
                          $noDates->whereNull('discount_start_date')
                                  ->whereNull('discount_end_date');
                      })->orWhere(function($withDates) {
                          $withDates->where(function($startDate) {
                              $startDate->whereNull('discount_start_date')
                                        ->orWhere('discount_start_date', '<=', now());
                          })->where(function($endDate) {
                              $endDate->whereNull('discount_end_date')
                                      ->orWhere('discount_end_date', '>=', now());
                          });
                      });
                  });
            });
        }

        // البحث
        if ($request->filled('search')) {
            $search = trim($request->search);

            // البحث في القياسات
            $sizeMatches = ProductSize::whereHas('product', function($q) {
                $q->where('is_hidden', false);
            })
            ->where('size_name', 'LIKE', "%{$search}%")
            ->whereRaw('quantity > (
                SELECT COALESCE(SUM(quantity_reserved), 0)
                FROM stock_reservations
                WHERE product_size_id = product_sizes.id
            )')
            ->exists();

            if ($sizeMatches) {
                $query->whereHas('sizes', function($q) use ($search) {
                    $q->where('size_name', 'LIKE', "%{$search}%")
                      ->whereRaw('quantity > (
                          SELECT COALESCE(SUM(quantity_reserved), 0)
                          FROM stock_reservations
                          WHERE product_size_id = product_sizes.id
                      )');
                });
                $searchedSize = $search;
            } else {
                // البحث في كود المنتج
                $codeMatches = Product::where('code', 'LIKE', "%{$search}%")->exists();
                if ($codeMatches) {
                    $query->where('code', 'LIKE', "%{$search}%");
                } else {
                    // البحث في النوع
                    $genderTypeMap = [
                        'ولادي' => ['boys', 'boys_girls'],
                        'بناتي' => ['girls', 'boys_girls'],
                        'ولادي بناتي' => ['boys_girls'],
                        'اكسسوار' => ['accessories'],
                    ];

                    $lowerSearch = mb_strtolower($search);
                    $foundGenderType = false;

                    foreach ($genderTypeMap as $key => $types) {
                        if (mb_strtolower($key) == $lowerSearch || stripos($key, $search) !== false || stripos($search, $key) !== false) {
                            $query->whereIn('gender_type', $types);
                            $foundGenderType = true;
                            break;
                        }
                    }

                    // البحث في اسم المنتج
                    if (!$foundGenderType) {
                        $query->where('name', 'LIKE', "%{$search}%");
                    }
                }
            }
        }

        $products = $query->latest()->paginate(30);

        // جلب بيانات الكارت النشط إذا كان موجوداً
        $activeCart = null;
        $cartId = session('shop_cart_id');
        if ($cartId) {
            $activeCart = \App\Models\Cart::with('items')->find($cartId);
        }

        if ($request->ajax()) {
            return response()->json([
                'products' => view('shop.partials.product-cards', compact('products', 'searchedSize'))->render(),
                'has_more' => $products->hasMorePages(),
                'total' => $products->total(),
            ]);
        }

        return view('shop.index', compact('products', 'searchedSize', 'activeCart'));
    }

    /**
     * عرض صفحة المنتج الواحد
     */
    public function show(Product $product)
    {
        // التحقق من أن المنتج متاح
        if ($product->is_hidden) {
            abort(404, 'المنتج غير متاح');
        }

        // تحميل العلاقات
        $product->load(['warehouse.activePromotion', 'images', 'sizes', 'creator']);

        return view('shop.product', compact('product'));
    }

    /**
     * Get product data for modal (API endpoint)
     */
    public function getProductData($id)
    {
        $product = Product::with(['sizes', 'primaryImage', 'warehouse'])
                         ->where('is_hidden', false)
                         ->findOrFail($id);

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
