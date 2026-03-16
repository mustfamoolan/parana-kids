<?php

namespace App\Http\Controllers\Mobile\Admin;

use App\Http\Controllers\Controller;
use App\Models\ProductLink;
use App\Models\Product;
use App\Models\Warehouse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class MobileAdminProductLinkController extends Controller
{
    /**
     * جلب قائمة روابط المنتجات للمدير أو المجهز
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $user = Auth::user();

        // بناء الاستعلام
        $query = ProductLink::with(['warehouse', 'creator']);

        // للمجهز: عرض روابطه فقط
        if ($user->isSupplier()) {
            $query->where('created_by', $user->id);
        }

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
        $formattedLinks = $links->map(function ($link) {
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

        // التحقق من صلاحية الوصول للمخزن للمجهز
        if ($request->warehouse_id && $user->isSupplier()) {
            if (!$user->canAccessWarehouse($request->warehouse_id)) {
                return response()->json([
                    'success' => false,
                    'message' => 'ليس لديك صلاحية للوصول إلى هذا المخزن',
                    'error_code' => 'FORBIDDEN_WAREHOUSE',
                ], 403);
            }
        }

        // إنشاء الرابط
        $productLink = ProductLink::create([
            'warehouse_id' => $request->warehouse_id ?: null,
            'gender_type' => $request->gender_type,
            'size_name' => $request->size_name,
            'has_discount' => $request->has('has_discount') ? (bool) $request->has_discount : false,
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
        $productLink = ProductLink::find($id);

        if (!$productLink) {
            return response()->json([
                'success' => false,
                'message' => 'الرابط غير موجود',
                'error_code' => 'NOT_FOUND',
            ], 404);
        }

        // Authorization: المدير يمكنه حذف أي رابط، المجهز فقط روابطه
        if ($user->isSupplier() && $productLink->created_by !== $user->id) {
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

        // التحقق من البيانات
        $validator = Validator::make($request->all(), [
            'warehouse_id' => 'nullable|exists:warehouses,id',
            'gender_type' => 'nullable|in:boys,girls,accessories,boys_girls',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'خطأ في التحقق من البيانات',
                'errors' => $validator->errors(),
                'error_code' => 'VALIDATION_ERROR',
            ], 422);
        }

        // التحقق من صلاحية الوصول للمخزن للمجهز
        if ($request->warehouse_id && $user->isSupplier()) {
            if (!$user->canAccessWarehouse($request->warehouse_id)) {
                return response()->json([
                    'success' => false,
                    'message' => 'ليس لديك صلاحية للوصول إلى هذا المخزن',
                    'error_code' => 'FORBIDDEN_WAREHOUSE',
                ], 403);
            }
        }

        // جلب المنتجات
        $productsQuery = Product::where('is_hidden', false)
            ->whereHas('sizes', function ($q) {
                $q->where('quantity', '>', 0);
            });

        // فلتر حسب المخزن (إذا كان محدداً)
        if ($request->warehouse_id) {
            $productsQuery->where('warehouse_id', $request->warehouse_id);
        } elseif ($user->isSupplier()) {
            // للمجهز: إذا لم يحدد مخزناً، نجلب فقط من مخازنه
            $productsQuery->whereIn('warehouse_id', $user->warehouses()->pluck('warehouse_id'));
        }

        // فلتر حسب النوع (مع دعم boys_girls)
        if ($request->gender_type) {
            if ($request->gender_type == 'boys') {
                $productsQuery->whereIn('gender_type', ['boys', 'boys_girls']);
            } elseif ($request->gender_type == 'girls') {
                $productsQuery->whereIn('gender_type', ['girls', 'boys_girls']);
            } else {
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

        ksort($sizes);

        return response()->json([
            'success' => true,
            'data' => [
                'sizes' => array_values($sizes)
            ],
        ]);
    }

    /**
     * جلب المخازن المتاحة
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getWarehouses()
    {
        $user = Auth::user();

        if ($user->isSupplier()) {
            $warehouses = $user->warehouses;
        } else {
            $warehouses = Warehouse::all();
        }

        $formattedWarehouses = $warehouses->map(function ($warehouse) {
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
