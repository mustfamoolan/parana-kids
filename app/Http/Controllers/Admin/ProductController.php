<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Warehouse;
use App\Models\ProductImage;
use App\Models\ProductSize;
use App\Models\ProductMovement;
use App\Models\Investment;
use App\Models\InvestmentTarget;
use App\Models\InvestmentInvestor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class ProductController extends Controller
{
    /**
     * Display all products from accessible warehouses (for order creation)
     */
    public function allProducts(Request $request)
    {
        // التأكد من أن المستخدم مدير أو مجهز
        if (!Auth::user()->isAdmin() && !Auth::user()->isSupplier()) {
            abort(403, 'غير مصرح لك بالوصول إلى هذه الصفحة.');
        }

        // للمدير: جميع المخازن
        // للمجهز: فقط المخازن المصرح بها
        if (Auth::user()->isAdmin()) {
            $warehouseIds = \App\Models\Warehouse::pluck('id')->toArray();
            $warehouses = \App\Models\Warehouse::orderBy('name')->get();
        } else {
            $warehouseIds = Auth::user()->warehouses->pluck('id')->toArray();
            $warehouses = Auth::user()->warehouses()->orderBy('name')->get();
        }

        // بناء الاستعلام الأساسي (استبعاد المنتجات المحجوبة)
        $query = Product::whereIn('warehouse_id', $warehouseIds)
                        ->where('is_hidden', false)
                        ->with(['primaryImage', 'images', 'sizes', 'warehouse.activePromotion']);

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

        // فلتر المخزن
        if ($request->filled('warehouse_id')) {
            $warehouseId = $request->warehouse_id;
            if (in_array($warehouseId, $warehouseIds)) {
                $query->where('warehouse_id', $warehouseId);
            }
        }

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

        // البحث بالقياس أولاً، ثم الكود، ثم النوع، ثم الاسم
        $searchedSize = null;
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

        $products = $query->latest()->paginate(30)->appends($request->except('page'));

        $searchedSize = $searchedSize ?? null;

        // جلب بيانات الكارت النشط إذا كان موجوداً
        $activeCart = null;
        $customerData = null;
        $cartId = session('current_cart_id');
        if ($cartId) {
            $activeCart = \App\Models\Cart::with('items')->find($cartId);
            if ($activeCart && $activeCart->created_by === auth()->id() && $activeCart->status === 'active') {
                $customerData = [
                    'customer_name' => $activeCart->customer_name,
                    'customer_phone' => $activeCart->customer_phone,
                    'customer_address' => $activeCart->customer_address,
                    'customer_social_link' => $activeCart->customer_social_link,
                    'notes' => $activeCart->notes,
                ];
            } else {
                $activeCart = null;
            }
        }

        // إذا كان الطلب AJAX، إرجاع JSON
        if ($request->expectsJson() || $request->ajax()) {
            $productsHtml = view('admin.products.partials.product-cards', [
                'products' => $products,
                'searchedSize' => $searchedSize
            ])->render();

            return response()->json([
                'products' => $productsHtml,
                'has_more' => $products->hasMorePages(),
                'total' => $products->total(),
                'current_page' => $products->currentPage(),
            ]);
        }

        return view('admin.products.all', compact('products', 'warehouses', 'activeCart', 'customerData', 'searchedSize'));
    }

    /**
     * Get product data for modal (API endpoint)
     */
    public function getProductData($id)
    {
        // التأكد من أن المستخدم مدير أو مجهز
        if (!Auth::user()->isAdmin() && !Auth::user()->isSupplier()) {
            return response()->json(['error' => 'غير مصرح لك بالوصول'], 403);
        }

        $product = Product::with(['sizes', 'primaryImage', 'warehouse'])
                         ->where('is_hidden', false)
                         ->findOrFail($id);

        // للمدير: جميع المخازن
        // للمجهز: فقط المخازن المصرح بها
        if (Auth::user()->isAdmin()) {
            $warehouseIds = \App\Models\Warehouse::pluck('id')->toArray();
        } else {
            $warehouseIds = Auth::user()->warehouses->pluck('id')->toArray();
        }

        // التحقق من صلاحية الوصول للمخزن
        if (!in_array($product->warehouse_id, $warehouseIds)) {
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

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request, Warehouse $warehouse)
    {
        $this->authorize('view', $warehouse);

        $perPage = $request->input('per_page', 15);
        $products = $warehouse->products()
                              ->with(['images', 'sizes', 'creator', 'warehouse.activePromotion'])
                              ->latest()
                              ->paginate($perPage)
                              ->appends($request->except('page'));

        return view('admin.products.index', compact('warehouse', 'products'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Warehouse $warehouse)
    {
        $this->authorize('view', $warehouse);
        $this->authorize('create', Product::class);

        return view('admin.products.create', compact('warehouse'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request, Warehouse $warehouse)
    {
        $this->authorize('view', $warehouse);
        $this->authorize('create', Product::class);

        $request->validate([
            'name' => 'nullable|string|max:255',
            'code' => 'required|string|max:255',
            'gender_type' => 'required|in:boys,girls,accessories,boys_girls',
            'purchase_price' => 'nullable|numeric|min:0',
            'selling_price' => 'required|numeric|min:0',
            'description' => 'nullable|string',
            'link_1688' => 'nullable|url|max:500',
            'is_hidden' => 'nullable|boolean',
            'discount_type' => 'nullable|in:none,amount,percentage',
            'discount_value' => 'nullable|numeric|min:0|required_if:discount_type,amount,percentage',
            'discount_start_date' => 'nullable|date|required_with:discount_end_date',
            'discount_end_date' => 'nullable|date|after_or_equal:discount_start_date|required_with:discount_start_date',
            'image_urls' => 'nullable|array',
            'image_urls.*' => 'url|max:1000',
            'sizes' => 'required|array|min:1',
            'sizes.*.size_name' => 'required|string|max:50',
            'sizes.*.quantity' => 'required|integer|min:0',
            'images' => 'nullable|array',
            'images.*' => 'image|mimes:jpeg,png,jpg,gif,webp|max:5120',
        ]);

        // استخدام الكود كاسم افتراضي إذا لم يتم إدخال اسم
        $productName = $request->name ?: $request->code;

        // تحويل تواريخ التخفيض إلى UTC إذا كانت موجودة
        $discountStartDate = $request->discount_start_date
            ? \Carbon\Carbon::parse($request->discount_start_date, 'Asia/Baghdad')->setTimezone('UTC')
            : null;
        $discountEndDate = $request->discount_end_date
            ? \Carbon\Carbon::parse($request->discount_end_date, 'Asia/Baghdad')->setTimezone('UTC')
            : null;

        $product = Product::create([
            'warehouse_id' => $warehouse->id,
            'name' => $productName,
            'code' => $request->code,
            'gender_type' => $request->gender_type,
            'purchase_price' => $request->purchase_price,
            'selling_price' => $request->selling_price,
            'description' => $request->description,
            'link_1688' => $request->link_1688,
            'is_hidden' => $request->has('is_hidden') && auth()->user()->isAdmin() ? (bool)$request->is_hidden : false,
            'discount_type' => $request->discount_type ?? 'none',
            'discount_value' => $request->discount_value,
            'discount_start_date' => $discountStartDate,
            'discount_end_date' => $discountEndDate,
            'created_by' => Auth::id(),
        ]);

        // Add sizes
        foreach ($request->sizes as $sizeData) {
            $size = ProductSize::create([
                'product_id' => $product->id,
                'size_name' => $sizeData['size_name'],
                'quantity' => $sizeData['quantity'],
            ]);

            // تسجيل حركة الإضافة
            ProductMovement::record([
                'product_id' => $product->id,
                'size_id' => $size->id,
                'warehouse_id' => $product->warehouse_id,
                'movement_type' => 'add',
                'quantity' => $sizeData['quantity'],
                'balance_after' => $sizeData['quantity'],
                'notes' => "إضافة منتج جديد: {$product->name}",
            ]);
        }

        // رفع الصور إن وجدت - دعم صور متعددة
        $imageIndex = 0;
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $image) {
                try {
                    // التأكد من وجود المجلد قبل الحفظ باستخدام Storage facade
                    if (!Storage::disk('public')->exists('products')) {
                        Storage::disk('public')->makeDirectory('products');
                    }

                    $path = $image->store('products', 'public');

                    ProductImage::create([
                        'product_id' => $product->id,
                        'image_path' => $path,
                        'is_primary' => $imageIndex === 0, // أول صورة = primary
                    ]);
                    $imageIndex++;
                } catch (\Exception $e) {
                    Log::error('Failed to upload product image: ' . $e->getMessage());
                    return back()->withErrors(['images' => 'فشل رفع الصورة: ' . $e->getMessage()])->withInput();
                }
            }
        }

        // تحميل الصور من URLs
        if ($request->filled('image_urls')) {
            foreach ($request->image_urls as $imageUrl) {
                if (empty($imageUrl)) continue;

                try {
                    $imageContent = file_get_contents($imageUrl);

                    if ($imageContent !== false) {
                        $extension = pathinfo(parse_url($imageUrl, PHP_URL_PATH), PATHINFO_EXTENSION);
                        if (empty($extension) || !in_array(strtolower($extension), ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
                            $extension = 'jpg';
                        }

                        $filename = 'product_' . time() . '_' . uniqid() . '.' . $extension;
                        $path = 'products/' . $filename;

                        Storage::disk('public')->put($path, $imageContent);

                        ProductImage::create([
                            'product_id' => $product->id,
                            'image_path' => $path,
                            'is_primary' => $imageIndex === 0,
                        ]);
                        $imageIndex++;
                    }
                } catch (\Exception $e) {
                    // تجاهل الخطأ والاستمرار مع الصور الأخرى
                }
            }
        }

        // خصم تكلفة المنتج من المستثمرين إذا كان المخزن مرتبط بمشروع استثمار
        if ($product->purchase_price && $product->purchase_price > 0) {
            $this->deductProductCostFromInvestors($product, $warehouse);
        }

        return redirect()->route('admin.warehouses.products.index', $warehouse)
                        ->with('success', 'تم إنشاء المنتج بنجاح');
    }

    /**
     * خصم تكلفة المنتج من المستثمرين
     */
    private function deductProductCostFromInvestors(Product $product, Warehouse $warehouse)
    {
        // حساب تكلفة المنتج: purchase_price × إجمالي الكمية (جميع المقاسات)
        $totalQuantity = $product->sizes()->sum('quantity');
        if ($totalQuantity <= 0 || !$product->purchase_price) {
            return; // لا يوجد كمية أو سعر شراء
        }

        $totalCost = $product->purchase_price * $totalQuantity;

        // جلب جميع الاستثمارات النشطة للمخزن
        $investmentIds = InvestmentTarget::where('target_type', 'warehouse')
            ->where('target_id', $warehouse->id)
            ->pluck('investment_id');

        $investments = Investment::whereIn('id', $investmentIds)
            ->where('status', 'active')
            ->where(function($q) {
                $q->whereNull('end_date')
                  ->orWhere('end_date', '>=', now());
            })
            ->where('start_date', '<=', now())
            ->with('investors.investor.treasury')
            ->get();

        if ($investments->isEmpty()) {
            return; // لا توجد استثمارات نشطة
        }

        DB::transaction(function () use ($investments, $totalCost, $product, $warehouse) {
            foreach ($investments as $investment) {
                foreach ($investment->investors as $investmentInvestor) {
                    $investor = $investmentInvestor->investor;
                    $treasury = $investor->treasury;

                    if (!$treasury) {
                        Log::warning("Investor {$investor->id} ({$investor->name}) does not have a treasury. Cannot deduct product cost.");
                        continue;
                    }

                    // استخدام cost_percentage المحفوظ مباشرة
                    $costPercentage = $investmentInvestor->cost_percentage ?? 0;
                    if ($costPercentage <= 0) {
                        continue;
                    }

                    // حساب حصة المستثمر من التكلفة
                    $investorCost = ($totalCost * $costPercentage) / 100;

                    // خصم التكلفة من رصيد المستثمر (ما عدا المدير)
                    // السماح بالذهاب للسالب
                    if (!$investor->is_admin) {
                        $treasury->withdraw(
                            $investorCost,
                            "تكلفة منتج: {$product->name} - مخزن #{$warehouse->id}",
                            Auth::id()
                        );
                    }

                    // تحديث investment_amount للمستثمر
                    $investmentInvestor->increment('investment_amount', $investorCost);
                }

                // تحديث total_value للاستثمار
                $investment->increment('total_value', $totalCost);
            }
        });
    }

    /**
     * Display the specified resource.
     */
    public function show(Warehouse $warehouse, Product $product)
    {
        $this->authorize('view', $warehouse);
        $this->authorize('view', $product);

        $product->load(['warehouse.activePromotion', 'images', 'sizes', 'creator']);

        // حساب السعر الكلي للبيع والشراء (للمدير فقط)
        $totalQuantity = $product->sizes->sum('quantity');
        $totalSellingPrice = $product->effective_price * $totalQuantity;
        $totalPurchasePrice = 0;

        if (auth()->user()->isAdmin() && $product->purchase_price) {
            $totalPurchasePrice = $product->purchase_price * $totalQuantity;
        }

        // جلب الاستثمارات والأرباح للمنتج (للمدير فقط)
        $investments = collect();
        $productProfits = collect();
        $totalProductProfit = 0;
        $totalInvestorProfit = 0;
        $ownerProfit = 0;

        if (auth()->user()->isAdmin()) {
            $investments = \App\Models\Investment::where('investment_type', 'product')
                ->where('product_id', $product->id)
                ->where('status', 'active')
                ->with('investor')
                ->get();

            // حساب إجمالي ربح المنتج من الأرباح المسجلة
            $productProfits = \App\Models\InvestorProfit::where('product_id', $product->id)
                ->with('investor', 'investment')
                ->get();

            $totalProductProfit = \App\Models\ProfitRecord::where('product_id', $product->id)
                ->where('status', 'confirmed')
                ->sum('actual_profit') ?? 0;

            $totalInvestorProfit = $productProfits->sum('profit_amount') ?? 0;
            $ownerProfit = $totalProductProfit - $totalInvestorProfit;
        }

        // التحقق من وجود cart نشط للمدير/المجهز
        $activeCart = null;
        $customerData = null;
        $cartId = session('current_cart_id');
        if ($cartId) {
            $activeCart = \App\Models\Cart::with('items')->find($cartId);
            if ($activeCart && $activeCart->created_by === auth()->id() && $activeCart->status === 'active') {
                $customerData = [
                    'customer_name' => $activeCart->customer_name,
                    'customer_phone' => $activeCart->customer_phone,
                    'customer_address' => $activeCart->customer_address,
                ];
            } else {
                $activeCart = null;
            }
        }

        return view('admin.products.show', compact('product', 'totalQuantity', 'totalSellingPrice', 'totalPurchasePrice', 'activeCart', 'customerData', 'investments', 'productProfits', 'totalProductProfit', 'totalInvestorProfit', 'ownerProfit'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Warehouse $warehouse, Product $product)
    {
        $this->authorize('view', $warehouse);
        $this->authorize('update', $product);

        $product->load(['sizes']);

        return view('admin.products.edit', compact('product'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Warehouse $warehouse, Product $product)
    {
        $this->authorize('view', $warehouse);
        $this->authorize('update', $product);

        $request->validate([
            'name' => 'nullable|string|max:255',
            'code' => 'required|string|max:255',
            'gender_type' => 'nullable|in:boys,girls,accessories,boys_girls',
            'purchase_price' => 'nullable|numeric|min:0',
            'selling_price' => 'required|numeric|min:0',
            'description' => 'nullable|string',
            'link_1688' => 'nullable|url|max:500',
            'is_hidden' => 'nullable|boolean',
            'discount_type' => 'nullable|in:none,amount,percentage',
            'discount_value' => 'nullable|numeric|min:0|required_if:discount_type,amount,percentage',
            'discount_start_date' => 'nullable|date|required_with:discount_end_date',
            'discount_end_date' => 'nullable|date|after_or_equal:discount_start_date|required_with:discount_start_date',
            'image_urls' => 'nullable|array',
            'image_urls.*' => 'url|max:1000',
            'keep_images' => 'nullable|array',
            'keep_images.*' => 'exists:product_images,id',
            'sizes' => 'required|array|min:1',
            'sizes.*.size_name' => 'required|string|max:50',
            'sizes.*.quantity' => 'required|integer|min:0',
            'images' => 'nullable|array',
            'images.*' => 'image|mimes:jpeg,png,jpg,gif,webp|max:5120',
        ]);

        // استخدام الكود كاسم افتراضي إذا لم يتم إدخال اسم
        $productName = $request->name ?: $request->code;

        // تحويل تواريخ التخفيض إلى UTC إذا كانت موجودة
        $discountStartDate = $request->discount_start_date
            ? \Carbon\Carbon::parse($request->discount_start_date, 'Asia/Baghdad')->setTimezone('UTC')
            : null;
        $discountEndDate = $request->discount_end_date
            ? \Carbon\Carbon::parse($request->discount_end_date, 'Asia/Baghdad')->setTimezone('UTC')
            : null;

        $updateData = [
            'name' => $productName,
            'code' => $request->code,
            'gender_type' => $request->gender_type,
            'selling_price' => $request->selling_price,
            'description' => $request->description,
            'link_1688' => $request->link_1688,
        ];

        // فقط المدير يمكنه تعديل سعر الشراء والحجب والتخفيض
        if (auth()->user()->isAdmin()) {
            $updateData['purchase_price'] = $request->purchase_price;
            $updateData['is_hidden'] = $request->has('is_hidden') ? (bool)$request->is_hidden : false;
            $updateData['discount_type'] = $request->discount_type ?? 'none';
            $updateData['discount_value'] = $request->discount_value;
            $updateData['discount_start_date'] = $discountStartDate;
            $updateData['discount_end_date'] = $discountEndDate;
        }

        $product->update($updateData);

        // حفظ القياسات القديمة لمقارنتها
        $oldSizes = $product->sizes->keyBy('size_name');
        $newSizes = collect($request->sizes)->keyBy('size_name');

        // حفظ القياسات الموجودة قبل أي تعديل (للاستخدام لاحقاً)
        $existingSizes = $product->sizes->keyBy('size_name');

        // تسجيل القياسات المحذوفة قبل الحذف (فقط إذا لم تعد موجودة في القياسات الجديدة)
        foreach ($oldSizes as $sizeName => $oldSize) {
            if (!isset($newSizes[$sizeName])) {
                // حذف القياس فقط إذا لم يعد موجوداً في القائمة الجديدة
                ProductMovement::record([
                    'product_id' => $product->id,
                    'size_id' => $oldSize->id,
                    'warehouse_id' => $product->warehouse_id,
                    'movement_type' => 'delete',
                    'quantity' => -$oldSize->quantity,
                    'balance_after' => 0,
                    'notes' => "حذف القياس - المنتج: {$product->name} - القياس: {$sizeName} (كان الرصيد: {$oldSize->quantity})",
                ]);
                // حذف القياس فقط إذا لم يعد موجوداً في القائمة الجديدة
                $oldSize->delete();
                // إزالة من existingSizes أيضاً
                unset($existingSizes[$sizeName]);
            }
        }

        // Update sizes - تحديث القياسات الموجودة بدلاً من حذفها وإنشاء قياسات جديدة
        // هذا يحافظ على size_id في order_items
        foreach ($request->sizes as $sizeData) {
            $oldQuantity = null;

            if (isset($existingSizes[$sizeData['size_name']])) {
                // تحديث القياس الموجود (حتى لو كانت الكمية 0، نحافظ على size_id)
                $size = $existingSizes[$sizeData['size_name']];
                $oldQuantity = $size->quantity; // حفظ الكمية القديمة قبل التحديث
                $size->quantity = $sizeData['quantity'];
                $size->save();
            } else {
                // إنشاء قياس جديد
                $size = ProductSize::create([
                    'product_id' => $product->id,
                    'size_name' => $sizeData['size_name'],
                    'quantity' => $sizeData['quantity'],
                ]);
            }

            // تسجيل حركة التعديل عند تغيير الكمية
            if ($oldQuantity !== null) {
                // القياس موجود، حساب الفرق من الكمية القديمة المحفوظة
                $quantityDifference = $sizeData['quantity'] - $oldQuantity;

                // تسجيل الحركة فقط إذا تغيرت الكمية
                if ($quantityDifference > 0) {
                    // زيادة الكمية
                    ProductMovement::record([
                        'product_id' => $product->id,
                        'size_id' => $size->id,
                        'warehouse_id' => $product->warehouse_id,
                        'movement_type' => 'increase',
                        'quantity' => $quantityDifference,
                        'balance_after' => $sizeData['quantity'],
                        'notes' => "زيادة كمية - المنتج: {$product->name} - القياس: {$sizeData['size_name']} (+{$quantityDifference})",
                    ]);
                } elseif ($quantityDifference < 0) {
                    // نقص الكمية
                    ProductMovement::record([
                        'product_id' => $product->id,
                        'size_id' => $size->id,
                        'warehouse_id' => $product->warehouse_id,
                        'movement_type' => 'decrease',
                        'quantity' => $quantityDifference,
                        'balance_after' => $sizeData['quantity'],
                        'notes' => "نقص كمية - المنتج: {$product->name} - القياس: {$sizeData['size_name']} ({$quantityDifference})",
                    ]);
                }
            } else {
                // إذا كان القياس جديد، سجله كإضافة
                ProductMovement::record([
                    'product_id' => $product->id,
                    'size_id' => $size->id,
                    'warehouse_id' => $product->warehouse_id,
                    'movement_type' => 'add',
                    'quantity' => $sizeData['quantity'],
                    'balance_after' => $sizeData['quantity'],
                    'notes' => "إضافة قياس جديد - المنتج: {$product->name} - القياس: {$sizeData['size_name']}",
                ]);
            }
        }

        // معالجة الصور
        // حذف الصور التي لم يتم الاحتفاظ بها
        $keepImageIds = $request->keep_images ?? [];
        foreach ($product->images as $oldImage) {
            if (!in_array($oldImage->id, $keepImageIds)) {
                Storage::disk('public')->delete($oldImage->image_path);
                $oldImage->delete();
            }
        }

        // إعادة تعيين primary للصورة الأولى المتبقية
        $remainingImages = $product->images()->get();
        if ($remainingImages->count() > 0) {
            $remainingImages->each(function($img) { $img->update(['is_primary' => false]); });
            $remainingImages->first()->update(['is_primary' => true]);
        }

        // حساب عدد الصور الحالية
        $imageIndex = $remainingImages->count();

        // رفع صور جديدة من الملفات
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $image) {
                try {
                    // التأكد من وجود المجلد قبل الحفظ باستخدام Storage facade
                    if (!Storage::disk('public')->exists('products')) {
                        Storage::disk('public')->makeDirectory('products');
                    }

                    $path = $image->store('products', 'public');

                    ProductImage::create([
                        'product_id' => $product->id,
                        'image_path' => $path,
                        'is_primary' => $imageIndex === 0,
                    ]);
                    $imageIndex++;
                } catch (\Exception $e) {
                    Log::error('Failed to upload product image: ' . $e->getMessage());
                    return back()->withErrors(['images' => 'فشل رفع الصورة: ' . $e->getMessage()])->withInput();
                }
            }
        }

        // تحميل صور من URLs
        if ($request->filled('image_urls')) {
            foreach ($request->image_urls as $imageUrl) {
                if (empty($imageUrl)) continue;

                try {
                    $imageContent = file_get_contents($imageUrl);

                    if ($imageContent !== false) {
                        $extension = pathinfo(parse_url($imageUrl, PHP_URL_PATH), PATHINFO_EXTENSION);
                        if (empty($extension) || !in_array(strtolower($extension), ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
                            $extension = 'jpg';
                        }

                        $filename = 'product_' . time() . '_' . uniqid() . '.' . $extension;
                        $path = 'products/' . $filename;

                        Storage::disk('public')->put($path, $imageContent);

                        ProductImage::create([
                            'product_id' => $product->id,
                            'image_path' => $path,
                            'is_primary' => $imageIndex === 0,
                        ]);
                        $imageIndex++;
                    }
                } catch (\Exception $e) {
                    // تجاهل الخطأ والاستمرار
                }
            }
        }

        // التحقق من وجود back_url وإذا كان موجوداً، نعيد التوجيه إليه (نفس منطق OrderController)
        $backUrl = $request->input('back_url');
        if ($backUrl) {
            $backUrl = urldecode($backUrl);
            $parsed = parse_url($backUrl);
            $currentHost = $request->getHost();

            // التحقق من أن back_url من نفس النطاق (Security check)
            if (isset($parsed['host']) && $parsed['host'] !== $currentHost) {
                $backUrl = null;
            }
        }

        // إذا كان back_url موجوداً وصحيحاً، نعيد التوجيه إليه مع إضافة #product-{id} للانتقال مباشرة إلى المنتج
        if ($backUrl) {
            // إضافة #product-{id} إلى URL للانتقال مباشرة إلى المنتج
            // إزالة أي hash موجود مسبقاً وإضافة hash جديد
            $backUrl = preg_replace('/#.*$/', '', $backUrl);
            $backUrl .= '#product-' . $product->id;
            return redirect($backUrl)->with('success', 'تم تحديث المنتج بنجاح');
        }

        return redirect()->route('admin.warehouses.products.show', [$warehouse, $product])
                        ->with('success', 'تم تحديث المنتج بنجاح');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Warehouse $warehouse, Product $product)
    {
        $this->authorize('view', $warehouse);
        $this->authorize('delete', $product);

        // تسجيل حركات الحذف لجميع القياسات قبل الحذف
        foreach ($product->sizes as $size) {
            ProductMovement::record([
                'product_id' => $product->id,
                'size_id' => $size->id,
                'warehouse_id' => $product->warehouse_id,
                'movement_type' => 'delete',
                'quantity' => -$size->quantity,
                'balance_after' => 0,
                'notes' => "حذف منتج: {$product->name} - القياس: {$size->size_name} (كان الرصيد: {$size->quantity})",
            ]);
        }

        // Delete images from storage
        foreach ($product->images as $image) {
            Storage::disk('public')->delete($image->image_path);
        }

        $warehouse = $product->warehouse;
        $product->delete();

        return redirect()->route('admin.warehouses.products.index', $warehouse)
                        ->with('success', 'تم حذف المنتج بنجاح');
    }

    /**
     * Upload images for product - DISABLED (using single image upload in store/update methods)
     */
    /*
    public function uploadImages(Request $request, Product $product)
    {
        $this->authorize('update', $product);

        $request->validate([
            'images' => 'required|array|max:5',
            'images.*' => 'image|mimes:jpeg,png,jpg|max:2048',
        ]);

        foreach ($request->file('images') as $index => $image) {
            $path = $image->store('products', 'public');

            ProductImage::create([
                'product_id' => $product->id,
                'image_path' => $path,
                'is_primary' => $index === 0 && $product->images()->count() === 0,
                'order' => $product->images()->count() + $index,
            ]);
        }

        return redirect()->back()->with('success', 'تم رفع الصور بنجاح');
    }
    */

    /**
     * Delete product image - DISABLED (using single image replacement in update method)
     */
    /*
    public function deleteImage(ProductImage $image)
    {
        $this->authorize('update', $image->product);

        Storage::disk('public')->delete($image->image_path);
        $image->delete();

        return redirect()->back()->with('success', 'تم حذف الصورة بنجاح');
    }
    */

    /**
     * Toggle product hidden status
     */
    public function toggleHidden(Request $request, Warehouse $warehouse, Product $product)
    {
        $this->authorize('view', $warehouse);
        $this->authorize('update', $product);

        if (!auth()->user()->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'غير مصرح لك بتنفيذ هذه العملية'
            ], 403);
        }

        $request->validate([
            'is_hidden' => 'required|boolean',
        ]);

        $product->update([
            'is_hidden' => $request->is_hidden,
        ]);

        return response()->json([
            'success' => true,
            'message' => $request->is_hidden ? 'تم حجب المنتج بنجاح' : 'تم إلغاء حجب المنتج بنجاح',
        ]);
    }

    /**
     * Update product discount
     */
    public function updateDiscount(Request $request, Warehouse $warehouse, Product $product)
    {
        $this->authorize('view', $warehouse);
        $this->authorize('update', $product);

        if (!auth()->user()->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'غير مصرح لك بتنفيذ هذه العملية'
            ], 403);
        }

        $request->validate([
            'discount_type' => 'required|in:none,amount,percentage',
            'discount_value' => 'nullable|numeric|min:0|required_if:discount_type,amount,percentage',
            'discount_start_date' => 'nullable|date|required_with:discount_end_date',
            'discount_end_date' => 'nullable|date|after_or_equal:discount_start_date|required_with:discount_start_date',
        ]);

        $discountStartDate = $request->discount_start_date
            ? \Carbon\Carbon::parse($request->discount_start_date, 'Asia/Baghdad')->setTimezone('UTC')
            : null;
        $discountEndDate = $request->discount_end_date
            ? \Carbon\Carbon::parse($request->discount_end_date, 'Asia/Baghdad')->setTimezone('UTC')
            : null;

        $product->update([
            'discount_type' => $request->discount_type,
            'discount_value' => $request->discount_type !== 'none' ? $request->discount_value : null,
            'discount_start_date' => $discountStartDate,
            'discount_end_date' => $discountEndDate,
        ]);

        return response()->json([
            'success' => true,
            'message' => $request->discount_type === 'none' ? 'تم إلغاء التخفيض بنجاح' : 'تم تحديث التخفيض بنجاح',
        ]);
    }

    /**
     * جلب المنتجات من مخزن معين (API)
     */
    public function getProductsByWarehouse(Warehouse $warehouse)
    {
        $this->authorize('view', $warehouse);

        $products = $warehouse->products()
            ->where('is_hidden', false)
            ->select('id', 'name', 'code')
            ->orderBy('name')
            ->get();

        return response()->json([
            'success' => true,
            'products' => $products
        ]);
    }
}
