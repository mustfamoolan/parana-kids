<?php

namespace App\Http\Controllers\Mobile\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductSize;
use App\Models\ProductMovement;
use App\Models\Warehouse;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MobileAdminBulkReturnController extends Controller
{
    /**
     * جلب قوائم الفلاتر (المخازن، المندوبين)
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getFilterOptions()
    {
        $user = Auth::user();

        // التحقق من أن المستخدم مدير أو مجهز
        if (!$user || (!$user->isAdmin() && !$user->isSupplier() && !$user->isPrivateSupplier())) {
            return response()->json([
                'success' => false,
                'message' => 'غير مصرح. يجب أن تكون مديراً أو مجهزاً للوصول إلى هذه البيانات.',
                'error_code' => 'FORBIDDEN',
            ], 403);
        }

        try {
            // جلب قائمة المخازن حسب الصلاحيات
            if ($user->isSupplier() || $user->isPrivateSupplier()) {
                $warehouses = $user->warehouses;
            } else {
                $warehouses = Warehouse::all();
            }

            // جلب قائمة المندوبين
            $delegates = User::where('role', 'delegate')->get();

            return response()->json([
                'success' => true,
                'data' => [
                    'warehouses' => $warehouses->map(function($warehouse) {
                        return [
                            'id' => $warehouse->id,
                            'name' => $warehouse->name,
                        ];
                    }),
                    'delegates' => $delegates->map(function($delegate) {
                        return [
                            'id' => $delegate->id,
                            'name' => $delegate->name,
                            'code' => $delegate->code,
                        ];
                    }),
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('MobileAdminBulkReturnController: Failed to get filter options', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => $user->id,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء جلب قوائم الفلاتر',
                'error_code' => 'FETCH_ERROR',
            ], 500);
        }
    }

    /**
     * البحث عن المنتجات
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function searchProducts(Request $request)
    {
        $user = Auth::user();

        // التحقق من أن المستخدم مدير أو مجهز
        if (!$user || (!$user->isAdmin() && !$user->isSupplier() && !$user->isPrivateSupplier())) {
            return response()->json([
                'success' => false,
                'message' => 'غير مصرح. يجب أن تكون مديراً أو مجهزاً للوصول إلى هذه البيانات.',
                'error_code' => 'FORBIDDEN',
            ], 403);
        }

        try {
            $query = Product::with(['sizes', 'primaryImage', 'warehouse']);

            // فلتر حسب صلاحيات المجهز
            if ($user->isSupplier() || $user->isPrivateSupplier()) {
                $warehouseIds = $user->warehouses()->pluck('warehouses.id');
                $query->whereIn('warehouse_id', $warehouseIds);
            }

            // فلتر حسب المخزن (اختياري)
            if ($request->filled('warehouse_id')) {
                $query->where('warehouse_id', $request->warehouse_id);
            }

            // البحث بالاسم أو الكود
            if ($request->filled('search')) {
                $query->where(function($q) use ($request) {
                    $q->where('name', 'like', '%' . $request->search . '%')
                      ->orWhere('code', 'like', '%' . $request->search . '%');
                });
            }

            $limit = $request->input('limit', 10);
            $maxLimit = 50;
            if ($limit > $maxLimit) {
                $limit = $maxLimit;
            }

            $products = $query->limit($limit)->get();

            // تنسيق البيانات
            $formattedProducts = $products->map(function($product) {
                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'code' => $product->code,
                    'purchase_price' => (float) $product->purchase_price,
                    'selling_price' => (float) $product->selling_price,
                    'warehouse_id' => $product->warehouse_id,
                    'warehouse' => $product->warehouse ? [
                        'id' => $product->warehouse->id,
                        'name' => $product->warehouse->name,
                    ] : null,
                    'primary_image_url' => $product->primaryImage ? $product->primaryImage->image_url : null,
                    'sizes' => $product->sizes->map(function($size) {
                        return [
                            'id' => $size->id,
                            'size_name' => $size->size_name,
                            'quantity' => (int) $size->quantity,
                        ];
                    }),
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $formattedProducts,
            ]);
        } catch (\Exception $e) {
            Log::error('MobileAdminBulkReturnController: Failed to search products', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => $user->id,
                'request' => $request->all(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء البحث عن المنتجات',
                'error_code' => 'FETCH_ERROR',
            ], 500);
        }
    }

    /**
     * إرجاع المنتجات بشكل جماعي
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function returnProducts(Request $request)
    {
        $user = Auth::user();

        // التحقق من أن المستخدم مدير أو مجهز
        if (!$user || (!$user->isAdmin() && !$user->isSupplier() && !$user->isPrivateSupplier())) {
            return response()->json([
                'success' => false,
                'message' => 'غير مصرح. يجب أن تكون مديراً أو مجهزاً للوصول إلى هذه البيانات.',
                'error_code' => 'FORBIDDEN',
            ], 403);
        }

        $validated = $request->validate([
            'delegate_id' => 'required|exists:users,id',
            'warehouse_id' => 'required|exists:warehouses,id',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.size_id' => 'required|exists:product_sizes,id',
            'items.*.quantity' => 'required|integer|min:1',
        ]);

        // التحقق من صلاحيات المخزن
        $warehouses = $user->isAdmin() 
            ? Warehouse::all() 
            : $user->warehouses;

        $warehouseIds = $warehouses->pluck('id')->toArray();
        if (!in_array($validated['warehouse_id'], $warehouseIds)) {
            return response()->json([
                'success' => false,
                'message' => 'ليس لديك صلاحية للوصول إلى هذا المخزن',
                'error_code' => 'FORBIDDEN',
            ], 403);
        }

        try {
            $result = DB::transaction(function() use ($validated, $user) {
                $delegate = User::find($validated['delegate_id']);
                $returnedItems = [];

                foreach ($validated['items'] as $item) {
                    $size = ProductSize::find($item['size_id']);

                    if (!$size) {
                        throw new \Exception("القياس غير موجود (size_id: {$item['size_id']})");
                    }

                    // التحقق من أن المنتج يخص المخزن المحدد
                    $product = Product::find($item['product_id']);
                    if (!$product || $product->warehouse_id != $validated['warehouse_id']) {
                        throw new \Exception("المنتج لا يخص المخزن المحدد");
                    }

                    // التحقق من أن القياس يخص المنتج
                    if ($size->product_id != $item['product_id']) {
                        throw new \Exception("القياس لا يخص المنتج المحدد");
                    }

                    // إضافة الكمية للمخزن
                    $oldQuantity = $size->quantity;
                    $size->increment('quantity', $item['quantity']);
                    $size->refresh();

                    // تحديث warehouse_id للمنتج
                    $product->warehouse_id = $validated['warehouse_id'];
                    $product->save();

                    // تسجيل الحركة
                    ProductMovement::record([
                        'product_id' => $item['product_id'],
                        'size_id' => $item['size_id'],
                        'warehouse_id' => $validated['warehouse_id'],
                        'delegate_id' => $validated['delegate_id'],
                        'user_id' => $user->id,
                        'movement_type' => 'return_bulk',
                        'quantity' => $item['quantity'],
                        'balance_after' => $size->quantity,
                        'notes' => 'إرجاع طلبات - مندوب: ' . $delegate->name,
                    ]);

                    $returnedItems[] = [
                        'product_id' => $item['product_id'],
                        'product_name' => $product->name,
                        'size_id' => $item['size_id'],
                        'size_name' => $size->size_name,
                        'quantity' => $item['quantity'],
                        'old_quantity' => $oldQuantity,
                        'new_quantity' => $size->quantity,
                    ];
                }

                return [
                    'delegate' => [
                        'id' => $delegate->id,
                        'name' => $delegate->name,
                        'code' => $delegate->code,
                    ],
                    'warehouse' => [
                        'id' => $validated['warehouse_id'],
                        'name' => $warehouses->firstWhere('id', $validated['warehouse_id'])->name,
                    ],
                    'items' => $returnedItems,
                    'total_items' => count($returnedItems),
                ];
            });

            return response()->json([
                'success' => true,
                'message' => 'تم إرجاع المواد بنجاح',
                'data' => $result,
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'خطأ في البيانات المرسلة',
                'errors' => $e->errors(),
                'error_code' => 'VALIDATION_ERROR',
            ], 422);
        } catch (\Exception $e) {
            Log::error('MobileAdminBulkReturnController: Failed to return products', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => $user->id,
                'request' => $request->all(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء الإرجاع: ' . $e->getMessage(),
                'error_code' => 'PROCESS_ERROR',
            ], 500);
        }
    }
}

