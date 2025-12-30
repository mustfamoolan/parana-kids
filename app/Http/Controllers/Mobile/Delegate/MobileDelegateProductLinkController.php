<?php

namespace App\Http\Controllers\Mobile\Delegate;

use App\Http\Controllers\Controller;
use App\Models\ProductLink;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class MobileDelegateProductLinkController extends Controller
{
    /**
     * جلب قائمة روابط المندوب
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

        // بناء الاستعلام
        $query = ProductLink::where('created_by', $user->id)
            ->with(['warehouse', 'creator']);

        // فلتر حسب المخزن
        if ($request->filled('warehouse_id')) {
            $query->where('warehouse_id', $request->warehouse_id);
        }

        // فلتر حسب النوع
        if ($request->filled('gender_type')) {
            $query->where('gender_type', $request->gender_type);
        }

        // Pagination
        $perPage = min($request->get('per_page', 20), 50);
        $links = $query->latest()->paginate($perPage);

        // تنسيق البيانات
        $formattedLinks = $links->map(function($link) {
            return $this->formatLinkData($link);
        });

        return response()->json([
            'success' => true,
            'data' => $formattedLinks,
            'pagination' => [
                'current_page' => $links->currentPage(),
                'per_page' => $links->perPage(),
                'total' => $links->total(),
                'last_page' => $links->lastPage(),
                'has_more' => $links->hasMorePages(),
            ],
        ]);
    }

    /**
     * إنشاء رابط جديد
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
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

        // التحقق من البيانات
        $validator = Validator::make($request->all(), [
            'warehouse_id' => 'nullable|exists:warehouses,id',
            'gender_type' => 'nullable|in:boys,girls,accessories,boys_girls',
            'size_name' => 'nullable|string|max:50',
            'has_discount' => 'nullable|boolean',
        ], [
            'warehouse_id.exists' => 'المخزن المحدد غير موجود',
            'gender_type.in' => 'نوع المنتج غير صحيح',
            'size_name.max' => 'اسم القياس يجب أن يكون أقل من 50 حرف',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'خطأ في التحقق من البيانات',
                'errors' => $validator->errors(),
                'error_code' => 'VALIDATION_ERROR',
            ], 422);
        }

        // التحقق من أن المندوب لديه صلاحية الوصول للمخزن المحدد
        if ($request->warehouse_id && !$user->canAccessWarehouse($request->warehouse_id)) {
            return response()->json([
                'success' => false,
                'message' => 'ليس لديك صلاحية للوصول إلى هذا المخزن',
                'error_code' => 'FORBIDDEN_WAREHOUSE',
            ], 403);
        }

        // إنشاء الرابط
        $productLink = ProductLink::create([
            'warehouse_id' => $request->warehouse_id ?: null,
            'gender_type' => $request->gender_type,
            'size_name' => $request->size_name,
            'has_discount' => $request->has('has_discount') ? (bool)$request->has_discount : false,
            'created_by' => $user->id,
        ]);

        // تحميل العلاقات
        $productLink->load(['warehouse', 'creator']);

        return response()->json([
            'success' => true,
            'message' => 'تم إنشاء الرابط بنجاح',
            'data' => $this->formatLinkData($productLink),
        ], 201);
    }

    /**
     * حذف رابط
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id)
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

        $productLink = ProductLink::find($id);

        if (!$productLink) {
            return response()->json([
                'success' => false,
                'message' => 'الرابط غير موجود',
                'error_code' => 'NOT_FOUND',
            ], 404);
        }

        // التأكد من أن الرابط للمندوب الحالي
        if ($productLink->created_by !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'ليس لديك صلاحية لحذف هذا الرابط',
                'error_code' => 'FORBIDDEN',
            ], 403);
        }

        $productLink->delete();

        return response()->json([
            'success' => true,
            'message' => 'تم حذف الرابط بنجاح',
        ]);
    }

    /**
     * جلب القياسات المتاحة بناءً على المخزن والنوع
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getSizes(Request $request)
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

        // التحقق من البيانات
        $validator = Validator::make($request->all(), [
            'warehouse_id' => 'nullable|exists:warehouses,id',
            'gender_type' => 'nullable|in:boys,girls,accessories,boys_girls',
        ], [
            'warehouse_id.exists' => 'المخزن المحدد غير موجود',
            'gender_type.in' => 'نوع المنتج غير صحيح',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'خطأ في التحقق من البيانات',
                'errors' => $validator->errors(),
                'error_code' => 'VALIDATION_ERROR',
            ], 422);
        }

        // التحقق من أن المندوب لديه صلاحية الوصول للمخزن المحدد
        if ($request->warehouse_id && !$user->canAccessWarehouse($request->warehouse_id)) {
            return response()->json([
                'success' => false,
                'message' => 'ليس لديك صلاحية للوصول إلى هذا المخزن',
                'error_code' => 'FORBIDDEN_WAREHOUSE',
            ], 403);
        }

        // جلب المنتجات
        $productsQuery = Product::whereHas('sizes', function($q) {
            $q->where('quantity', '>', 0);
        });

        // الحصول على معرفات المخازن المصرح بها للمندوب
        $userWarehouseIds = $user->warehouses()->pluck('warehouse_id');

        // فلتر حسب المخزن (إذا كان محدداً)
        if ($request->warehouse_id) {
            $productsQuery->where('warehouse_id', $request->warehouse_id);
        } else {
            // إذا لم يكن هناك مخزن محدد، عرض المنتجات من المخازن المخصصة للمندوب فقط
            $productsQuery->whereIn('warehouse_id', $userWarehouseIds);
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
            'success' => true,
            'data' => [
                'sizes' => array_values($sizes)
            ],
        ]);
    }

    /**
     * جلب المخازن المتاحة للمندوب
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getWarehouses()
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

        $warehouses = $user->warehouses;

        $formattedWarehouses = $warehouses->map(function($warehouse) {
            return [
                'id' => $warehouse->id,
                'name' => $warehouse->name,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => [
                'warehouses' => $formattedWarehouses,
            ],
        ]);
    }

    /**
     * تنسيق بيانات الرابط للإرجاع
     *
     * @param ProductLink $link
     * @return array
     */
    private function formatLinkData(ProductLink $link)
    {
        // حساب وقت الانتهاء (2 ساعة من تاريخ الإنشاء)
        $expiresAt = $link->created_at->copy()->addHours(2);
        $now = now();
        $remainingSeconds = max(0, $now->diffInSeconds($expiresAt, false));

        return [
            'id' => $link->id,
            'token' => $link->token,
            'full_url' => $link->full_url,
            'warehouse' => $link->warehouse ? [
                'id' => $link->warehouse->id,
                'name' => $link->warehouse->name,
            ] : null,
            'gender_type' => $link->gender_type,
            'size_name' => $link->size_name,
            'has_discount' => (bool) $link->has_discount,
            'expires_at' => $expiresAt->toIso8601String(),
            'remaining_seconds' => $remainingSeconds,
            'created_at' => $link->created_at->toIso8601String(),
            'created_by' => $link->creator ? [
                'id' => $link->creator->id,
                'name' => $link->creator->name,
            ] : null,
        ];
    }
}

