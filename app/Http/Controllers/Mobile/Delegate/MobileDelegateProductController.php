<?php

namespace App\Http\Controllers\Mobile\Delegate;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductSize;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MobileDelegateProductController extends Controller
{
    /**
     * جلب جميع المنتجات من المخازن المصرح بها للمندوب
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $user = Auth::user();

        // التحقق من أن المستخدم مندوب
        if (!$user || !$user->isDelegate()) {
            return response()->json([
                'success' => false,
                'message' => 'غير مصرح. يجب أن تكون مندوباً للوصول إلى هذه البيانات.',
                'error_code' => 'FORBIDDEN',
            ], 403);
        }

        // الحصول على معرفات المخازن المصرح بها للمندوب
        $warehouseIds = $user->warehouses()->pluck('warehouse_id');

        if ($warehouseIds->isEmpty()) {
            return response()->json([
                'success' => true,
                'data' => [],
                'pagination' => [
                    'current_page' => 1,
                    'per_page' => $request->get('per_page', 30),
                    'total' => 0,
                    'last_page' => 1,
                    'has_more' => false,
                ],
            ]);
        }

        // بناء الاستعلام الأساسي (استبعاد المنتجات المحجوبة)
        $query = Product::whereIn('warehouse_id', $warehouseIds)
                        ->where('is_hidden', false)
                        ->with(['primaryImage', 'images', 'sizes.reservations', 'warehouse.activePromotion']);

        // فلتر المخزن
        if ($request->filled('warehouse_id')) {
            $warehouseId = $request->warehouse_id;
            // التحقق من أن المندوب لديه صلاحية الوصول لهذا المخزن
            if (in_array($warehouseId, $warehouseIds->toArray())) {
                $query->where('warehouse_id', $warehouseId);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'ليس لديك صلاحية للوصول إلى هذا المخزن',
                    'error_code' => 'FORBIDDEN_WAREHOUSE',
                ], 403);
            }
        }

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

        // إخفاء المنتجات التي جميع قياساتها غير متوفرة (available_quantity = 0)
        $query->whereHas('sizes', function($q) {
            $q->whereRaw('quantity > (
                SELECT COALESCE(SUM(quantity_reserved), 0)
                FROM stock_reservations
                WHERE product_size_id = product_sizes.id
            )');
        });

        // Pagination
        $perPage = min($request->get('per_page', 30), 100); // حد أقصى 100 منتج في الصفحة
        $products = $query->latest()->paginate($perPage);

        // تنسيق البيانات للإرجاع
        $formattedProducts = $products->map(function($product) {
            return $this->formatProductData($product);
        });

        return response()->json([
            'success' => true,
            'data' => $formattedProducts,
            'pagination' => [
                'current_page' => $products->currentPage(),
                'per_page' => $products->perPage(),
                'total' => $products->total(),
                'last_page' => $products->lastPage(),
                'has_more' => $products->hasMorePages(),
            ],
        ]);
    }

    /**
     * جلب منتج واحد بالتفاصيل الكاملة
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        $user = Auth::user();

        // التحقق من أن المستخدم مندوب
        if (!$user || !$user->isDelegate()) {
            return response()->json([
                'success' => false,
                'message' => 'غير مصرح. يجب أن تكون مندوباً للوصول إلى هذه البيانات.',
                'error_code' => 'FORBIDDEN',
            ], 403);
        }

        // الحصول على معرفات المخازن المصرح بها للمندوب
        $warehouseIds = $user->warehouses()->pluck('warehouse_id');

        if ($warehouseIds->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'ليس لديك صلاحية للوصول إلى أي مخازن',
                'error_code' => 'NO_WAREHOUSES',
            ], 403);
        }

        // جلب المنتج مع العلاقات
        $product = Product::with(['primaryImage', 'images', 'sizes.reservations', 'warehouse.activePromotion'])
                          ->where('id', $id)
                          ->where('is_hidden', false)
                          ->first();

        if (!$product) {
            return response()->json([
                'success' => false,
                'message' => 'المنتج غير موجود',
                'error_code' => 'PRODUCT_NOT_FOUND',
            ], 404);
        }

        // التحقق من أن المنتج ينتمي لمخزن المندوب
        if (!in_array($product->warehouse_id, $warehouseIds->toArray())) {
            return response()->json([
                'success' => false,
                'message' => 'ليس لديك صلاحية للوصول إلى هذا المنتج',
                'error_code' => 'FORBIDDEN_PRODUCT',
            ], 403);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'product' => $this->formatProductData($product),
            ],
        ]);
    }

    /**
     * تنسيق بيانات المنتج للإرجاع
     *
     * @param Product $product
     * @return array
     */
    private function formatProductData(Product $product)
    {
        // التأكد من تحميل العلاقات إذا لم تكن محملة
        if (!$product->relationLoaded('sizes')) {
            $product->load('sizes.reservations');
        }

        // حساب الكميات المتاحة للقياسات
        $sizes = $product->sizes->map(function($size) {
            // التأكد من تحميل العلاقة إذا لم تكن محملة
            if (!$size->relationLoaded('reservations')) {
                $size->load('reservations');
            }

            // حساب الكمية المحجوزة
            $reserved = $size->reservations->sum('quantity_reserved');
            
            return [
                'id' => $size->id,
                'size_name' => $size->size_name,
                'quantity' => (int) $size->quantity,
                'available_quantity' => max(0, (int) $size->quantity - (int) $reserved),
                'reserved_quantity' => (int) $reserved,
            ];
        });

        // معلومات التخفيض
        $discountInfo = $product->getDiscountInfo();
        $discount = null;
        if ($discountInfo) {
            $discount = [
                'has_discount' => true,
                'type' => $discountInfo['type'],
                'value' => $discountInfo['value'],
                'original_price' => $discountInfo['original_price'],
                'discount_price' => $discountInfo['discount_price'],
                'discount_amount' => $discountInfo['discount_amount'],
                'percentage' => round($discountInfo['percentage'], 2),
                'start_date' => $discountInfo['start_date'] ? $discountInfo['start_date']->toIso8601String() : null,
                'end_date' => $discountInfo['end_date'] ? $discountInfo['end_date']->toIso8601String() : null,
            ];
        } else {
            $discount = [
                'has_discount' => false,
            ];
        }

        // معلومات التخفيض العام للمخزن
        $warehousePromotion = ['has_promotion' => false];
        if ($product->warehouse) {
            // محاولة الحصول على activePromotion من العلاقة المحملة
            if ($product->warehouse->relationLoaded('activePromotion')) {
                $activePromotion = $product->warehouse->activePromotion;
            } else {
                // إذا لم تكن محملة، جلبها مباشرة
                $activePromotion = $product->warehouse->getCurrentActivePromotion();
            }

            if ($activePromotion && $activePromotion->isActive()) {
                $warehousePromotion = [
                    'has_promotion' => true,
                    'discount_type' => $activePromotion->discount_type,
                    'discount_percentage' => $activePromotion->discount_percentage ? (float) $activePromotion->discount_percentage : null,
                    'promotion_price' => $activePromotion->promotion_price ? (float) $activePromotion->promotion_price : null,
                    'start_date' => $activePromotion->start_date ? $activePromotion->start_date->toIso8601String() : null,
                    'end_date' => $activePromotion->end_date ? $activePromotion->end_date->toIso8601String() : null,
                ];
            }
        }

        // حساب السعر الفعلي
        $effectivePrice = $product->effective_price;

        return [
            'id' => $product->id,
            'name' => $product->name,
            'code' => $product->code,
            'gender_type' => $product->gender_type,
            'selling_price' => (float) $product->selling_price,
            'effective_price' => (float) $effectivePrice,
            'purchase_price' => (float) $product->purchase_price,
            'description' => $product->description,
            'warehouse' => [
                'id' => $product->warehouse->id,
                'name' => $product->warehouse->name,
            ],
            'primary_image' => $product->primaryImage ? $product->primaryImage->image_url : null,
            'images' => $product->images->map(function($image) {
                return $image->image_url;
            })->toArray(),
            'sizes' => $sizes,
            'discount' => $discount,
            'warehouse_promotion' => $warehousePromotion,
            'created_at' => $product->created_at->toIso8601String(),
            'updated_at' => $product->updated_at->toIso8601String(),
        ];
    }
}

