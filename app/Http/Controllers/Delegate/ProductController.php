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

        // بناء الاستعلام الأساسي (استبعاد المنتجات المحجوبة)
        $query = Product::whereIn('warehouse_id', $warehouseIds)
                        ->where('is_hidden', false)
                        ->with(['primaryImage', 'images', 'sizes.reservations', 'warehouse.activePromotion']);

        $searchedSize = null; // لتمرير القياس المبحوث للـ view

        // فلتر النوع
        if ($request->filled('gender_type')) {
            $genderType = $request->gender_type;
            if ($genderType == 'boys') {
                // عرض "ولادي" و "ولادي بناتي"
                $query->whereIn('gender_type', ['boys', 'boys_girls']);
            } elseif ($genderType == 'girls') {
                // عرض "بناتي" و "ولادي بناتي"
                $query->whereIn('gender_type', ['girls', 'boys_girls']);
            } else {
                // عرض النوع المحدد فقط (boys_girls أو accessories)
                $query->where('gender_type', $genderType);
            }
        }

        // فلتر التخفيض
        if ($request->filled('has_discount') && $request->has_discount == '1') {
            $query->where(function($q) {
                // تخفيض المنتج الواحد
                $q->whereNotNull('discount_type')
                  ->where('discount_type', '!=', 'none')
                  ->whereNotNull('discount_value')
                  ->where(function($dateQ) {
                      // إذا لم تكن هناك تواريخ محددة، يعتبر التخفيض دائماً نشطاً
                      // أو إذا كانت التواريخ ضمن النطاق الصحيح
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

        // البحث بالقياس أولاً، ثم الكود، ثم النوع، ثم الاسم
        if ($request->filled('search')) {
            $search = trim($request->search);
            \Log::info('Search applied', ['search_term' => $search]);

            // أولاً: البحث في القياسات (أولوية)
            $sizeMatches = ProductSize::whereHas('product', function($q) use ($warehouseIds) {
                $q->whereIn('warehouse_id', $warehouseIds)
                  ->where('is_hidden', false);
            })
            ->where('size_name', 'LIKE', "%{$search}%")
            ->whereRaw('quantity > (
                SELECT COALESCE(SUM(quantity_reserved), 0)
                FROM stock_reservations
                WHERE product_size_id = product_sizes.id
            )')
            ->exists();

            if ($sizeMatches) {
                // إذا كان البحث عن قياس، اعرض المنتجات التي تحتوي على هذا القياس
                $query->whereHas('sizes', function($q) use ($search) {
                    $q->where('size_name', 'LIKE', "%{$search}%")
                      ->whereRaw('quantity > (
                          SELECT COALESCE(SUM(quantity_reserved), 0)
                          FROM stock_reservations
                          WHERE product_size_id = product_sizes.id
                      )');
                });
                $searchedSize = $search; // حفظ القياس المبحوث
            } else {
                // ثانياً: البحث في كود المنتج
                $codeMatches = Product::whereIn('warehouse_id', $warehouseIds)
                                      ->where('code', 'LIKE', "%{$search}%")
                                      ->exists();

                if ($codeMatches) {
                    // إذا كان البحث عن كود منتج، أظهر المنتج بكل قياساته
                    $query->where('code', 'LIKE', "%{$search}%");
                } else {
                    // ثالثاً: البحث في النوع
                    $genderTypeMap = [
                        'ولادي' => ['boys', 'boys_girls'],
                        'بناتي' => ['girls', 'boys_girls'],
                        'ولادي بناتي' => ['boys_girls'],
                        'اكسسوار' => ['accessories'],
                        'boys' => ['boys', 'boys_girls'],
                        'girls' => ['girls', 'boys_girls'],
                        'boys_girls' => ['boys_girls'],
                        'accessories' => ['accessories'],
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

                    // رابعاً: إذا لم يكن البحث عن النوع، ابحث في اسم المنتج
                    if (!$foundGenderType) {
                        $query->where('name', 'LIKE', "%{$search}%");
                    }
                }
            }
        }

        $products = $query->latest()->paginate(30);

        $searchedSize = $searchedSize ?? null;

        // جلب بيانات الكارت النشط إذا كان موجوداً
        $activeCart = null;
        $customerData = null;
        $cartId = session('current_cart_id');
        if ($cartId) {
            $activeCart = \App\Models\Cart::with('items')->find($cartId);
            if ($activeCart && $activeCart->delegate_id === auth()->id() && $activeCart->customer_name) {
                $customerData = [
                    'customer_name' => $activeCart->customer_name,
                    'customer_phone' => $activeCart->customer_phone,
                    'customer_address' => $activeCart->customer_address,
                    'customer_social_link' => $activeCart->customer_social_link,
                    'notes' => $activeCart->notes,
                ];
            }
        }

        if ($request->ajax()) {
            return response()->json([
                'products' => view('delegate.products.partials.product-cards', compact('products', 'searchedSize'))->render(),
                'has_more' => $products->hasMorePages(),
                'total' => $products->total()
            ]);
        }

        return view('delegate.products.all', compact('products', 'searchedSize', 'activeCart', 'customerData'));
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
                              ->where('is_hidden', false)
                              ->with(['images', 'sizes', 'warehouse.activePromotion'])
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
        $product->load(['warehouse.activePromotion', 'images', 'sizes', 'creator']);

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
                         ->where('is_hidden', false)
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
