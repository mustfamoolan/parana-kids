<?php

namespace App\Http\Controllers\Mobile\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductSize;
use App\Models\ProductMovement;
use App\Models\OrderItem;
use App\Services\ProfitCalculator;
use App\Services\SweetAlertService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MobileAdminOrderController extends Controller
{
    protected $sweetAlertService;
    protected $profitCalculator;

    public function __construct(SweetAlertService $sweetAlertService, ProfitCalculator $profitCalculator)
    {
        $this->sweetAlertService = $sweetAlertService;
        $this->profitCalculator = $profitCalculator;
    }

    /**
     * جلب قوائم الفلاتر (المخازن، المجهزين، المندوبين)
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
                $warehouses = \App\Models\Warehouse::all();
            }

            // جلب قائمة المجهزين (المديرين والمجهزين) للفلترة
            $suppliers = \App\Models\User::whereIn('role', ['admin', 'supplier'])->get();

            // جلب قائمة المندوبين
            $delegates = \App\Models\User::where('role', 'delegate')->get();

            // جلب قائمة منشئي الطلبات (للفلتر delegate_id - يشمل delegate, admin, supplier)
            $orderCreators = \App\Models\User::whereIn('role', ['delegate', 'admin', 'supplier'])
                ->orderBy('role')
                ->orderBy('name')
                ->get();

            return response()->json([
                'success' => true,
                'data' => [
                    'warehouses' => $warehouses->map(function ($warehouse) {
                        return [
                            'id' => $warehouse->id,
                            'name' => $warehouse->name,
                        ];
                    }),
                    'suppliers' => $suppliers->map(function ($supplier) {
                        return [
                            'id' => $supplier->id,
                            'name' => $supplier->name,
                            'code' => $supplier->code,
                            'role' => $supplier->role,
                            'role_text' => $supplier->role === 'admin' ? 'مدير' : 'مجهز',
                        ];
                    }),
                    'delegates' => $delegates->map(function ($delegate) {
                        return [
                            'id' => $delegate->id,
                            'name' => $delegate->name,
                            'code' => $delegate->code,
                            'role' => $delegate->role,
                        ];
                    }),
                    'order_creators' => $orderCreators->map(function ($creator) {
                        return [
                            'id' => $creator->id,
                            'name' => $creator->name,
                            'code' => $creator->code,
                            'role' => $creator->role,
                            'role_text' => $creator->role === 'admin' ? 'مدير' : ($creator->role === 'supplier' ? 'مجهز' : 'مندوب'),
                        ];
                    }),
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('MobileAdminOrderController: Failed to get filter options', [
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
     * جلب قائمة الطلبات غير المقيدة (Pending)
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getPendingOrders(Request $request)
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
            // Base query - فرض حالة pending دائماً
            $query = Order::where('status', 'pending');

            // للمجهز: عرض الطلبات التي تحتوي على منتجات من مخازن له صلاحية الوصول إليها
            if ($user->isSupplier()) {
                $accessibleWarehouseIds = $user->warehouses->pluck('id')->toArray();
                if (!empty($accessibleWarehouseIds)) {
                    $query->whereHas('items.product', function ($q) use ($accessibleWarehouseIds) {
                        $q->whereIn('warehouse_id', $accessibleWarehouseIds);
                    });
                } else {
                    $query->whereRaw('1 = 0');
                }
            }

            // تطبيق الفلاتر
            $this->applyFilters($query, $request);

            // تحميل العلاقات
            $query->with([
                'delegate',
                'items.product.primaryImage',
                'items.product.warehouse',
                'confirmedBy',
            ]);

            // Pagination
            $perPage = min($request->get('per_page', 15), 50);
            $orders = $query->orderBy('created_at', 'desc')->paginate($perPage);

            // حساب الإحصائيات (للمدير فقط)
            $statistics = null;
            if ($user->isAdmin()) {
                $statistics = $this->calculatePendingStatistics($request);
            }

            // تنسيق البيانات
            $formattedOrders = $orders->map(function ($order) {
                return $this->formatOrderListItem($order);
            });

            return response()->json([
                'success' => true,
                'data' => $formattedOrders,
                'statistics' => $statistics,
                'pagination' => [
                    'current_page' => $orders->currentPage(),
                    'per_page' => $orders->perPage(),
                    'total' => $orders->total(),
                    'last_page' => $orders->lastPage(),
                    'has_more' => $orders->hasMorePages(),
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('MobileAdminOrderController: Failed to get pending orders', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => $user->id,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء جلب الطلبات',
                'error_code' => 'FETCH_ERROR',
            ], 500);
        }
    }

    /**
     * جلب قائمة الطلبات المقيدة (Confirmed)
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getConfirmedOrders(Request $request)
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
            // Base query - فرض حالة confirmed دائماً
            $query = Order::where('status', 'confirmed');

            // للمجهز: عرض الطلبات التي تحتوي على منتجات من مخازن له صلاحية الوصول إليها
            if ($user->isSupplier()) {
                $accessibleWarehouseIds = $user->warehouses->pluck('id')->toArray();
                if (!empty($accessibleWarehouseIds)) {
                    $query->whereHas('items.product', function ($q) use ($accessibleWarehouseIds) {
                        $q->whereIn('warehouse_id', $accessibleWarehouseIds);
                    });
                } else {
                    $query->whereRaw('1 = 0');
                }
            }

            // تطبيق الفلاتر (على confirmed_at للطلبات المقيدة)
            $this->applyFiltersForConfirmed($query, $request);

            // تحميل العلاقات
            $query->with([
                'delegate',
                'items.product.primaryImage',
                'items.product.warehouse',
                'confirmedBy',
                'processedBy',
            ]);

            // Pagination
            $perPage = min($request->get('per_page', 15), 50);
            $orders = $query->latest('confirmed_at')->paginate($perPage);

            // حساب الإحصائيات (للمدير فقط)
            $statistics = null;
            if ($user->isAdmin()) {
                $statistics = $this->calculateConfirmedStatistics($request);
            }

            // تنسيق البيانات
            $formattedOrders = $orders->map(function ($order) {
                return $this->formatOrderListItem($order);
            });

            return response()->json([
                'success' => true,
                'data' => $formattedOrders,
                'statistics' => $statistics,
                'pagination' => [
                    'current_page' => $orders->currentPage(),
                    'per_page' => $orders->perPage(),
                    'total' => $orders->total(),
                    'last_page' => $orders->lastPage(),
                    'has_more' => $orders->hasMorePages(),
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('MobileAdminOrderController: Failed to get confirmed orders', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => $user->id,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء جلب الطلبات',
                'error_code' => 'FETCH_ERROR',
            ], 500);
        }
    }

    /**
     * جلب قائمة موحدة للطلبات (Management)
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getOrders(Request $request)
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
            // Base query
            $query = Order::query();

            // للمجهز: عرض الطلبات التي تحتوي على منتجات من مخازن له صلاحية الوصول إليها
            if ($user->isSupplier()) {
                $accessibleWarehouseIds = $user->warehouses->pluck('id')->toArray();
                if (!empty($accessibleWarehouseIds)) {
                    $query->whereHas('items.product', function ($q) use ($accessibleWarehouseIds) {
                        $q->whereIn('warehouse_id', $accessibleWarehouseIds);
                    });
                } else {
                    $query->whereRaw('1 = 0');
                }
            }

            // فلتر الحالة
            if ($request->status === 'deleted') {
                $query->onlyTrashed()
                    ->whereNotNull('deleted_by')
                    ->whereNotNull('deletion_reason')
                    ->with(['deletedByUser']);
            } elseif ($request->filled('status') && in_array($request->status, ['pending', 'confirmed'])) {
                $query->where('status', $request->status);
            } else {
                // افتراضياً: pending و confirmed
                $query->whereIn('status', ['pending', 'confirmed']);
            }

            // تطبيق الفلاتر
            $this->applyFilters($query, $request);

            // تحميل العلاقات
            $query->with([
                'delegate',
                'items.product.primaryImage',
                'items.product.warehouse',
                'confirmedBy',
                'processedBy',
            ]);

            // Pagination
            $perPage = min($request->get('per_page', 15), 50);
            $orders = $query->orderBy('created_at', 'desc')->paginate($perPage);

            // تنسيق البيانات
            $formattedOrders = $orders->map(function ($order) {
                return $this->formatOrderListItem($order);
            });

            return response()->json([
                'success' => true,
                'data' => $formattedOrders,
                'pagination' => [
                    'current_page' => $orders->currentPage(),
                    'per_page' => $orders->perPage(),
                    'total' => $orders->total(),
                    'last_page' => $orders->lastPage(),
                    'has_more' => $orders->hasMorePages(),
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('MobileAdminOrderController: Failed to get orders', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => $user->id,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء جلب الطلبات',
                'error_code' => 'FETCH_ERROR',
            ], 500);
        }
    }

    /**
     * جلب تفاصيل طلب واحد
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function getOrderDetails($id)
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
            // جلب الطلب
            $query = Order::where('id', $id);

            // للمجهز: التحقق من أن الطلب يحتوي على منتجات من مخازن له صلاحية الوصول إليها
            if ($user->isSupplier()) {
                $accessibleWarehouseIds = $user->warehouses->pluck('id')->toArray();
                if (!empty($accessibleWarehouseIds)) {
                    $query->whereHas('items.product', function ($q) use ($accessibleWarehouseIds) {
                        $q->whereIn('warehouse_id', $accessibleWarehouseIds);
                    });
                } else {
                    $query->whereRaw('1 = 0');
                }
            }

            $order = $query->with([
                'delegate',
                'items.product.primaryImage',
                'items.product.warehouse',
                'items.size',
                'cart',
                'confirmedBy',
                'processedBy',
            ])->first();

            if (!$order) {
                return response()->json([
                    'success' => false,
                    'message' => 'الطلب غير موجود أو ليس لديك صلاحية للوصول إليه',
                    'error_code' => 'NOT_FOUND',
                ], 404);
            }

            // حذف إشعارات الطلب عند فتحه
            try {
                $this->sweetAlertService->deleteOrderAlerts($order->id, $user->id);
            } catch (\Exception $e) {
                Log::error('MobileAdminOrderController: Error deleting order alerts', [
                    'error' => $e->getMessage(),
                    'order_id' => $order->id,
                ]);
            }

            // تنسيق البيانات
            $formattedOrder = $this->formatOrderDetails($order);

            return response()->json([
                'success' => true,
                'data' => [
                    'order' => $formattedOrder,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('MobileAdminOrderController: Failed to get order details', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'order_id' => $id,
                'user_id' => $user->id,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء جلب تفاصيل الطلب',
                'error_code' => 'FETCH_ERROR',
            ], 500);
        }
    }

    /**
     * جلب بيانات التعديل (المنتجات المتاحة)
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function getOrderEditData($id)
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
            // جلب الطلب
            $query = Order::where('id', $id);

            // للمجهز: التحقق من أن الطلب يحتوي على منتجات من مخازن له صلاحية الوصول إليها
            if ($user->isSupplier()) {
                $accessibleWarehouseIds = $user->warehouses->pluck('id')->toArray();
                if (!empty($accessibleWarehouseIds)) {
                    $query->whereHas('items.product', function ($q) use ($accessibleWarehouseIds) {
                        $q->whereIn('warehouse_id', $accessibleWarehouseIds);
                    });
                } else {
                    $query->whereRaw('1 = 0');
                }
            }

            $order = $query->with([
                'delegate',
                'items.product.primaryImage',
                'items.product.warehouse',
                'items.size',
                'cart',
            ])->first();

            if (!$order) {
                return response()->json([
                    'success' => false,
                    'message' => 'الطلب غير موجود أو ليس لديك صلاحية للوصول إليه',
                    'error_code' => 'NOT_FOUND',
                ], 404);
            }

            // التحقق من إمكانية التعديل
            if ($order->status !== 'pending' && !$order->canBeEdited()) {
                return response()->json([
                    'success' => false,
                    'message' => 'لا يمكن تعديل هذا الطلب (مر أكثر من 5 ساعات على التقييد)',
                    'error_code' => 'CANNOT_EDIT',
                ], 400);
            }

            // جلب المنتجات المتاحة
            $productsQuery = Product::with(['sizes', 'primaryImage']);

            // للمجهز: فقط منتجات المخازن المسموح له بها
            if ($user->isSupplier()) {
                $warehouseIds = $user->warehouses()->pluck('warehouses.id');
                $productsQuery->whereIn('warehouse_id', $warehouseIds);
            }

            $products = $productsQuery->get();

            // تنسيق البيانات
            $formattedOrder = $this->formatOrderDetails($order);
            $formattedProducts = $products->map(function ($product) {
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
                    'sizes' => $product->sizes->map(function ($size) {
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
                'data' => [
                    'order' => $formattedOrder,
                    'products' => $formattedProducts,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('MobileAdminOrderController: Failed to get order edit data', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'order_id' => $id,
                'user_id' => $user->id,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء جلب بيانات التعديل',
                'error_code' => 'FETCH_ERROR',
            ], 500);
        }
    }

    /**
     * تعديل الطلب
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateOrder(Request $request, $id)
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

        $request->validate([
            'delivery_code' => 'nullable|string|max:255',
            'customer_name' => 'required|string|max:255',
            'customer_phone' => 'required|string|max:20',
            'customer_phone2' => 'nullable|string|max:20',
            'customer_address' => 'required|string',
            'customer_social_link' => 'required|string|max:255',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.size_id' => 'required|exists:product_sizes,id',
            'items.*.quantity' => 'required|integer|min:1',
            'alwaseet_city_id' => 'nullable|string',
            'alwaseet_region_id' => 'nullable|string',
            'alwaseet_delivery_time_note' => 'nullable|string',
            'size_reviewed' => 'nullable|string',
            'message_confirmed' => 'nullable|string',
        ]);

        try {
            // جلب الطلب
            $query = Order::where('id', $id);

            // للمجهز: التحقق من أن الطلب يحتوي على منتجات من مخازن له صلاحية الوصول إليها
            if ($user->isSupplier()) {
                $accessibleWarehouseIds = $user->warehouses->pluck('id')->toArray();
                if (!empty($accessibleWarehouseIds)) {
                    $query->whereHas('items.product', function ($q) use ($accessibleWarehouseIds) {
                        $q->whereIn('warehouse_id', $accessibleWarehouseIds);
                    });
                } else {
                    $query->whereRaw('1 = 0');
                }
            }

            $order = $query->with(['items.product', 'items.size'])->first();

            if (!$order) {
                return response()->json([
                    'success' => false,
                    'message' => 'الطلب غير موجود أو ليس لديك صلاحية للوصول إليه',
                    'error_code' => 'NOT_FOUND',
                ], 404);
            }

            // التحقق من إمكانية التعديل
            if ($order->status !== 'pending' && !$order->canBeEdited()) {
                return response()->json([
                    'success' => false,
                    'message' => 'لا يمكن تعديل هذا الطلب (مر أكثر من 5 ساعات على التقييد)',
                    'error_code' => 'CANNOT_EDIT',
                ], 400);
            }

            DB::transaction(function () use ($request, $order, $user) {
                // تحميل العناصر القديمة مع العلاقات
                $oldItems = $order->items()->with(['size', 'product'])->get();

                // تحديث معلومات الطلب
                $order->update($request->only([
                    'delivery_code',
                    'customer_name',
                    'customer_phone',
                    'customer_phone2',
                    'customer_address',
                    'customer_social_link',
                    'notes',
                    'alwaseet_city_id',
                    'alwaseet_region_id',
                    'alwaseet_delivery_time_note',
                    'size_reviewed',
                    'message_confirmed',
                ]));

                // للطلبات غير المقيدة (pending) أو المقيدة التي لم يمر عليها 5 ساعات: معالجة المنتجات
                if ($order->status === 'pending' || ($order->status === 'confirmed' && $order->canBeEdited())) {
                    // إنشاء خريطة للعناصر القديمة
                    $oldItemsMap = [];
                    foreach ($oldItems as $oldItem) {
                        $key = $oldItem->product_id . '_' . $oldItem->size_id;
                        $oldItemsMap[$key] = $oldItem;
                    }

                    // معالجة العناصر القديمة
                    foreach ($oldItemsMap as $key => $oldItem) {
                        if (!$oldItem->size)
                            continue;

                        // البحث عن العنصر في الطلب الجديد
                        $foundNewItem = null;
                        foreach ($request->items as $newItem) {
                            $newKey = $newItem['product_id'] . '_' . $newItem['size_id'];
                            if ($newKey === $key) {
                                $foundNewItem = $newItem;
                                break;
                            }
                        }

                        if ($foundNewItem === null) {
                            // المنتج محذوف → إرجاع كامل للمخزن
                            $oldItem->size->increment('quantity', $oldItem->quantity);
                            ProductMovement::record([
                                'product_id' => $oldItem->product_id,
                                'size_id' => $oldItem->size_id,
                                'warehouse_id' => $oldItem->product->warehouse_id,
                                'order_id' => $order->id,
                                'movement_type' => 'order_edit_remove',
                                'quantity' => $oldItem->quantity,
                                'balance_after' => $oldItem->size->refresh()->quantity,
                                'order_status' => $order->status,
                                'notes' => "تعديل طلب #{$order->order_number} - إرجاع منتج: {$oldItem->product_name} ({$oldItem->size_name})"
                            ]);
                        } else {
                            // المنتج موجود → مقارنة الكميات
                            $quantityDiff = $foundNewItem['quantity'] - $oldItem->quantity;

                            if ($quantityDiff > 0) {
                                // زيادة الكمية → خصم الفرق من المخزن
                                $availableQuantity = $oldItem->size->quantity;

                                if ($availableQuantity < $quantityDiff) {
                                    throw new \Exception("الكمية المتوفرة من {$oldItem->product->name} - {$oldItem->size->size_name} غير كافية. المطلوب: {$quantityDiff}، المتوفر: {$availableQuantity}");
                                }
                                $oldItem->size->decrement('quantity', $quantityDiff);
                                ProductMovement::record([
                                    'product_id' => $oldItem->product_id,
                                    'size_id' => $oldItem->size_id,
                                    'warehouse_id' => $oldItem->product->warehouse_id,
                                    'order_id' => $order->id,
                                    'movement_type' => 'order_edit_increase',
                                    'quantity' => -$quantityDiff,
                                    'balance_after' => $oldItem->size->refresh()->quantity,
                                    'order_status' => $order->status,
                                    'notes' => "تعديل طلب #{$order->order_number} - زيادة كمية: {$oldItem->product_name} ({$oldItem->size_name}) من {$oldItem->quantity} إلى {$foundNewItem['quantity']}"
                                ]);
                            } elseif ($quantityDiff < 0) {
                                // إنقاص الكمية → إرجاع الفرق للمخزن
                                $oldItem->size->increment('quantity', abs($quantityDiff));
                                ProductMovement::record([
                                    'product_id' => $oldItem->product_id,
                                    'size_id' => $oldItem->size_id,
                                    'warehouse_id' => $oldItem->product->warehouse_id,
                                    'order_id' => $order->id,
                                    'movement_type' => 'order_edit_decrease',
                                    'quantity' => abs($quantityDiff),
                                    'balance_after' => $oldItem->size->refresh()->quantity,
                                    'order_status' => $order->status,
                                    'notes' => "تعديل طلب #{$order->order_number} - إنقاص كمية: {$oldItem->product_name} ({$oldItem->size_name}) من {$oldItem->quantity} إلى {$foundNewItem['quantity']}"
                                ]);
                            }
                        }
                    }

                    // معالجة المنتجات الجديدة
                    foreach ($request->items as $newItem) {
                        $newKey = $newItem['product_id'] . '_' . $newItem['size_id'];
                        if (!isset($oldItemsMap[$newKey])) {
                            // منتج جديد → خصم الكمية من المخزن
                            $product = Product::findOrFail($newItem['product_id']);
                            $size = ProductSize::findOrFail($newItem['size_id']);

                            // التحقق من توفر الكمية
                            $availableQuantity = $size->quantity;

                            if ($availableQuantity < $newItem['quantity']) {
                                throw new \Exception("الكمية المتوفرة من {$product->name} - {$size->size_name} غير كافية. المتوفر: {$availableQuantity}");
                            }

                            $size->decrement('quantity', $newItem['quantity']);
                            ProductMovement::record([
                                'product_id' => $newItem['product_id'],
                                'size_id' => $newItem['size_id'],
                                'warehouse_id' => $product->warehouse_id,
                                'order_id' => $order->id,
                                'user_id' => $user->id,
                                'movement_type' => 'order_edit_add',
                                'quantity' => -$newItem['quantity'],
                                'balance_after' => $size->refresh()->quantity,
                                'order_status' => $order->status,
                                'notes' => "تعديل طلب #{$order->order_number} - إضافة منتج: {$product->name} ({$size->size_name})"
                            ]);
                        }
                    }
                }

                // حذف العناصر القديمة وإعادة إنشائها
                $order->items()->delete();

                // إنشاء العناصر الجديدة
                $totalAmount = 0;
                foreach ($request->items as $itemData) {
                    $product = Product::findOrFail($itemData['product_id']);
                    $subtotal = $product->selling_price * $itemData['quantity'];
                    $totalAmount += $subtotal;

                    OrderItem::create([
                        'order_id' => $order->id,
                        'product_id' => $itemData['product_id'],
                        'size_id' => $itemData['size_id'],
                        'size_name' => ProductSize::find($itemData['size_id'])->size_name ?? null,
                        'quantity' => $itemData['quantity'],
                        'unit_price' => $product->selling_price,
                        'subtotal' => $subtotal,
                        'product_name' => $product->name,
                        'product_code' => $product->code,
                    ]);
                }

                // تحديث المبلغ الإجمالي
                $order->update(['total_amount' => $totalAmount]);
            });

            // إعادة تحميل الطلب
            $order->refresh();
            $order->load([
                'delegate',
                'items.product.primaryImage',
                'items.product.warehouse',
                'items.size',
                'confirmedBy',
            ]);

            return response()->json([
                'success' => true,
                'message' => 'تم تعديل الطلب بنجاح',
                'data' => [
                    'order' => $this->formatOrderDetails($order),
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('MobileAdminOrderController: Failed to update order', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'order_id' => $id,
                'user_id' => $user->id,
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage() ?: 'حدث خطأ أثناء تعديل الطلب',
                'error_code' => 'UPDATE_ERROR',
            ], 500);
        }
    }

    /**
     * جلب قائمة الطلبات المقيدة للإرجاع الجزئي
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getPartialReturns(Request $request)
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
            $query = Order::where('status', 'confirmed')
                // إخفاء الطلبات التي تم إرجاع جميع منتجاتها (لا تحتوي على منتجات قابلة للإرجاع)
                ->whereHas('items', function ($itemsQuery) {
                    $itemsQuery->where('quantity', '>', 0);
                });

            // تطبيق فلاتر الصلاحيات
            $warehouses = $this->getAccessibleWarehouses();
            if ($warehouses->isNotEmpty()) {
                $query->whereHas('items.product', function ($q) use ($warehouses) {
                    $q->whereIn('warehouse_id', $warehouses->pluck('id'));
                });
            }

            // فلتر المندوب
            if ($request->filled('delegate_id')) {
                $query->where('delegate_id', $request->delegate_id);
            }

            // فلتر المجهز
            if ($request->filled('confirmed_by')) {
                $query->where('confirmed_by', $request->confirmed_by);
            }

            // البحث الذكي (مطابقة تامة)
            if ($request->filled('search')) {
                $searchTerm = $request->search;
                $query->where(function ($q) use ($searchTerm) {
                    $q->where('order_number', '=', $searchTerm)
                        ->orWhere('customer_name', '=', $searchTerm)
                        ->orWhere('customer_phone', '=', $searchTerm)
                        ->orWhere('customer_social_link', '=', $searchTerm)
                        ->orWhere('customer_address', '=', $searchTerm)
                        ->orWhere('delivery_code', '=', $searchTerm)
                        ->orWhereHas('delegate', function ($delegateQuery) use ($searchTerm) {
                            $delegateQuery->where('name', '=', $searchTerm)
                                ->orWhere('code', '=', $searchTerm);
                        })
                        ->orWhereHas('confirmedBy', function ($confirmedQuery) use ($searchTerm) {
                            $confirmedQuery->where('name', '=', $searchTerm);
                        });
                });
            }

            // فلتر حسب التاريخ
            if ($request->filled('date_from')) {
                $query->whereDate('created_at', '>=', $request->date_from);
            }

            if ($request->filled('date_to')) {
                $query->whereDate('created_at', '<=', $request->date_to);
            }

            $perPage = $request->input('per_page', 15);
            $maxPerPage = 50;
            if ($perPage > $maxPerPage) {
                $perPage = $maxPerPage;
            }

            // تحميل العلاقات
            $query->with(['delegate', 'items.product.primaryImage', 'items.size', 'items.returnItems', 'confirmedBy']);

            $orders = $query->latest('created_at')
                ->paginate($perPage)
                ->appends($request->except('page'));

            // تنسيق البيانات
            $formattedOrders = $orders->map(function ($order) {
                return [
                    'id' => $order->id,
                    'order_number' => $order->order_number,
                    'customer_name' => $order->customer_name,
                    'customer_phone' => $order->customer_phone,
                    'delivery_code' => $order->delivery_code,
                    'status' => $order->status,
                    'total_amount' => (float) $order->total_amount,
                    'delegate' => $order->delegate ? [
                        'id' => $order->delegate->id,
                        'name' => $order->delegate->name,
                        'code' => $order->delegate->code,
                    ] : null,
                    'confirmed_by' => $order->confirmedBy ? [
                        'id' => $order->confirmedBy->id,
                        'name' => $order->confirmedBy->name,
                    ] : null,
                    'created_at' => $order->created_at->toIso8601String(),
                    'items_count' => $order->items->count(),
                    'items_with_remaining' => $order->items->filter(function ($item) {
                        return $item->remaining_quantity > 0;
                    })->count(),
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $formattedOrders,
                'pagination' => [
                    'current_page' => $orders->currentPage(),
                    'per_page' => $orders->perPage(),
                    'total' => $orders->total(),
                    'last_page' => $orders->lastPage(),
                    'has_more' => $orders->hasMorePages(),
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('MobileAdminOrderController: Failed to get partial returns', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => $user->id,
                'request' => $request->all(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء جلب قائمة الإرجاعات الجزئية',
                'error_code' => 'FETCH_ERROR',
            ], 500);
        }
    }

    /**
     * جلب تفاصيل طلب للإرجاع الجزئي
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function getPartialReturnOrder($id)
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
            $order = Order::with([
                'items.product.primaryImage',
                'items.size',
                'items.returnItems',
                'delegate',
                'confirmedBy',
            ])->find($id);

            if (!$order) {
                return response()->json([
                    'success' => false,
                    'message' => 'الطلب غير موجود',
                    'error_code' => 'NOT_FOUND',
                ], 404);
            }

            // التحقق من الصلاحيات
            $warehouses = $this->getAccessibleWarehouses();
            if ($warehouses->isNotEmpty()) {
                $hasAccess = $order->items()->whereHas('product', function ($q) use ($warehouses) {
                    $q->whereIn('warehouse_id', $warehouses->pluck('id'));
                })->exists();

                if (!$hasAccess) {
                    return response()->json([
                        'success' => false,
                        'message' => 'ليس لديك صلاحية للوصول إلى هذا الطلب',
                        'error_code' => 'FORBIDDEN',
                    ], 403);
                }
            }

            if ($order->status !== 'confirmed') {
                return response()->json([
                    'success' => false,
                    'message' => 'لا يمكن إرجاع منتجات من طلب غير مقيد',
                    'error_code' => 'INVALID_STATUS',
                ], 400);
            }

            // تنسيق البيانات
            $formattedOrder = $this->formatOrderDetails($order);
            $formattedOrder['items'] = $order->items->map(function ($item) {
                $returnedQuantity = $item->returnItems->sum('quantity_returned');
                return [
                    'id' => $item->id,
                    'product' => $item->product ? [
                        'id' => $item->product->id,
                        'name' => $item->product->name,
                        'code' => $item->product->code,
                        'primary_image_url' => $item->product->primaryImage ? $item->product->primaryImage->image_url : null,
                    ] : [
                        'id' => null,
                        'name' => $item->product_name,
                        'code' => $item->product_code,
                        'primary_image_url' => null,
                    ],
                    'size' => $item->size ? [
                        'id' => $item->size->id,
                        'size_name' => $item->size->size_name,
                    ] : [
                        'id' => null,
                        'size_name' => $item->size_name,
                    ],
                    'size_id' => $item->size_id,
                    'size_name' => $item->size_name,
                    'quantity' => (int) $item->quantity,
                    'original_quantity' => (int) $item->original_quantity,
                    'remaining_quantity' => (int) $item->remaining_quantity,
                    'returned_quantity' => (int) $returnedQuantity,
                    'unit_price' => (float) $item->unit_price,
                    'subtotal' => (float) $item->subtotal,
                    'return_items' => $item->returnItems->map(function ($returnItem) {
                        return [
                            'id' => $returnItem->id,
                            'quantity_returned' => (int) $returnItem->quantity_returned,
                            'return_reason' => $returnItem->return_reason,
                            'created_at' => $returnItem->created_at->toIso8601String(),
                        ];
                    }),
                ];
            });

            return response()->json([
                'success' => true,
                'data' => [
                    'order' => $formattedOrder,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('MobileAdminOrderController: Failed to get partial return order', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'order_id' => $id,
                'user_id' => $user->id,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء جلب تفاصيل الطلب',
                'error_code' => 'FETCH_ERROR',
            ], 500);
        }
    }

    /**
     * معالجة الإرجاع الجزئي
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function processPartialReturn(Request $request, $id)
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
            $order = Order::with(['items.product', 'items.size', 'items.returnItems'])->find($id);

            if (!$order) {
                return response()->json([
                    'success' => false,
                    'message' => 'الطلب غير موجود',
                    'error_code' => 'NOT_FOUND',
                ], 404);
            }

            // التحقق من الصلاحيات
            $warehouses = $this->getAccessibleWarehouses();
            if ($warehouses->isNotEmpty()) {
                $hasAccess = $order->items()->whereHas('product', function ($q) use ($warehouses) {
                    $q->whereIn('warehouse_id', $warehouses->pluck('id'));
                })->exists();

                if (!$hasAccess) {
                    return response()->json([
                        'success' => false,
                        'message' => 'ليس لديك صلاحية للوصول إلى هذا الطلب',
                        'error_code' => 'FORBIDDEN',
                    ], 403);
                }
            }

            if ($order->status !== 'confirmed') {
                return response()->json([
                    'success' => false,
                    'message' => 'لا يمكن إرجاع منتجات من طلب غير مقيد. حالة الطلب: ' . $order->status,
                    'error_code' => 'INVALID_STATUS',
                ], 400);
            }

            $validated = $request->validate([
                'return_items' => 'required|array|min:1',
                'return_items.*.order_item_id' => 'required|exists:order_items,id',
                'return_items.*.product_id' => 'required|exists:products,id',
                'return_items.*.size_id' => 'nullable',
                'return_items.*.quantity' => 'required|integer|min:1',
                'notes' => 'nullable|string|max:1000',
            ]);

            $result = DB::transaction(function () use ($validated, $order, $request, $user) {
                $totalAmountReduction = 0;

                foreach ($validated['return_items'] as $returnItem) {
                    $orderItem = OrderItem::find($returnItem['order_item_id']);

                    if (!$orderItem || $orderItem->order_id !== $order->id) {
                        throw new \Exception("عنصر الطلب غير موجود أو لا ينتمي لهذا الطلب");
                    }

                    // استخدام size_id من order_item مباشرة
                    $sizeIdToUse = $orderItem->size_id ?? $returnItem['size_id'] ?? null;
                    $size = null;

                    // البحث عن القياس
                    if (!empty($sizeIdToUse) && $sizeIdToUse != 0) {
                        $size = ProductSize::find($sizeIdToUse);
                    }

                    if (!$size && $orderItem->size_name) {
                        $size = ProductSize::where('product_id', $orderItem->product_id)
                            ->where('size_name', $orderItem->size_name)
                            ->first();

                        if ($size) {
                            $orderItem->size_id = $size->id;
                            $orderItem->save();
                        }
                    }

                    if (!$size) {
                        throw new \Exception("القياس غير موجود للمنتج: {$orderItem->product_name}");
                    }

                    // التحقق من الكمية المتبقية
                    $remainingQuantity = $orderItem->remaining_quantity;
                    if ($returnItem['quantity'] > $remainingQuantity) {
                        throw new \Exception("الكمية المراد إرجاعها ({$returnItem['quantity']}) أكبر من الكمية المتبقية ({$remainingQuantity}) للمنتج: {$orderItem->product_name}");
                    }

                    // تقليل كمية order_item
                    $orderItem->quantity -= $returnItem['quantity'];
                    $orderItem->subtotal = $orderItem->quantity * $orderItem->unit_price;
                    $orderItem->save();

                    // إرجاع الكمية للمخزن
                    $size->increment('quantity', $returnItem['quantity']);
                    $size->refresh();

                    // تسجيل حركة المادة
                    ProductMovement::record([
                        'product_id' => $orderItem->product_id,
                        'size_id' => $size->id,
                        'warehouse_id' => $orderItem->product->warehouse_id,
                        'order_id' => $order->id,
                        'user_id' => $user->id,
                        'movement_type' => 'partial_return',
                        'quantity' => $returnItem['quantity'],
                        'balance_after' => $size->quantity,
                        'order_status' => $order->status,
                        'notes' => "إرجاع جزئي من طلب #{$order->order_number} - منتج: {$orderItem->product_name} ({$orderItem->size_name})",
                    ]);

                    // تسجيل ReturnItem
                    \App\Models\ReturnItem::create([
                        'order_id' => $order->id,
                        'order_item_id' => $returnItem['order_item_id'],
                        'product_id' => $orderItem->product_id,
                        'size_id' => $size->id,
                        'quantity_returned' => $returnItem['quantity'],
                        'return_reason' => $request->notes ?? 'إرجاع جزئي',
                    ]);

                    // حساب المبلغ المخصوم
                    $totalAmountReduction += $returnItem['quantity'] * $orderItem->unit_price;
                }

                // معالجة تأثير الإرجاع الجزئي على المستثمرين
                if (config('features.investor_profits_enabled', true)) {
                    try {
                        $returnCalculator = app(\App\Services\InvestorReturnCalculator::class);
                        $returnCalculator->processPartialReturnForInvestors($order, $validated['return_items']);
                    } catch (\Exception $e) {
                        Log::warning('InvestorReturnCalculator failed', [
                            'error' => $e->getMessage(),
                            'order_id' => $order->id,
                        ]);
                    }
                }

                // تحديث المبلغ الإجمالي للطلب
                $order->total_amount -= $totalAmountReduction;
                $order->save();

                // التحقق من أن جميع منتجات الطلب تم إرجاعها
                $order->refresh();
                $allItemsReturned = $order->items()->where('quantity', '>', 0)->count() === 0;

                if ($allItemsReturned) {
                    // حذف الطلب تلقائياً (soft delete) مع السبب
                    $order->deleted_by = $user->id;
                    $order->deletion_reason = 'إرجاع الطلب بالكامل';
                    $order->deleted_at = now();
                    $order->save();
                }

                return [
                    'total_amount_reduction' => $totalAmountReduction,
                    'all_items_returned' => $allItemsReturned,
                ];
            });

            $order->refresh();
            $formattedOrder = $this->formatOrderDetails($order);

            return response()->json([
                'success' => true,
                'message' => $result['all_items_returned']
                    ? 'تم إرجاع جميع المنتجات بنجاح وتم حذف الطلب تلقائياً'
                    : 'تم إرجاع المنتجات بنجاح',
                'data' => [
                    'order' => $formattedOrder,
                    'total_amount_reduction' => $result['total_amount_reduction'],
                    'all_items_returned' => $result['all_items_returned'],
                ],
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'خطأ في البيانات المرسلة',
                'errors' => $e->errors(),
                'error_code' => 'VALIDATION_ERROR',
            ], 422);
        } catch (\Exception $e) {
            Log::error('MobileAdminOrderController: Failed to process partial return', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'order_id' => $id,
                'user_id' => $user->id,
                'request' => $request->all(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء معالجة الإرجاع: ' . $e->getMessage(),
                'error_code' => 'PROCESS_ERROR',
            ], 500);
        }
    }

    /**
     * جلب بيانات تجهيز الطلب
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function getOrderProcessData($id)
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
            // جلب الطلب
            $query = Order::where('id', $id);

            // للمجهز: التحقق من أن الطلب يحتوي على منتجات من مخازن له صلاحية الوصول إليها
            if ($user->isSupplier()) {
                $accessibleWarehouseIds = $user->warehouses->pluck('id')->toArray();
                if (!empty($accessibleWarehouseIds)) {
                    $query->whereHas('items.product', function ($q) use ($accessibleWarehouseIds) {
                        $q->whereIn('warehouse_id', $accessibleWarehouseIds);
                    });
                } else {
                    $query->whereRaw('1 = 0');
                }
            }

            $order = $query->with([
                'delegate',
                'items.product.primaryImage',
                'items.product.warehouse',
                'items.size',
                'cart',
            ])->first();

            if (!$order) {
                return response()->json([
                    'success' => false,
                    'message' => 'الطلب غير موجود أو ليس لديك صلاحية للوصول إليه',
                    'error_code' => 'NOT_FOUND',
                ], 404);
            }

            // التحقق من أن الطلب غير مقيد
            if ($order->status !== 'pending') {
                return response()->json([
                    'success' => false,
                    'message' => 'لا يمكن تجهيز الطلبات المقيدة',
                    'error_code' => 'CANNOT_PROCESS',
                ], 400);
            }

            // جلب المنتجات المتوفرة
            $warehouses = $this->getAccessibleWarehouses();
            $products = Product::whereIn('warehouse_id', $warehouses->pluck('id'))
                ->with([
                    'primaryImage',
                    'sizes' => function ($q) {
                        $q->where('quantity', '>', 0);
                    }
                ])
                ->get();

            // تنسيق البيانات
            $formattedOrder = $this->formatOrderDetails($order);
            $formattedProducts = $products->map(function ($product) {
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
                    'sizes' => $product->sizes->map(function ($size) {
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
                'data' => [
                    'order' => $formattedOrder,
                    'products' => $formattedProducts,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('MobileAdminOrderController: Failed to get order process data', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'order_id' => $id,
                'user_id' => $user->id,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء جلب بيانات التجهيز',
                'error_code' => 'FETCH_ERROR',
            ], 500);
        }
    }

    /**
     * تجهيز وتقييد الطلب
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function processOrder(Request $request, $id)
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

        $request->validate([
            'customer_name' => 'required|string|max:255',
            'customer_phone' => 'required|string|max:20',
            'customer_phone2' => 'nullable|string|max:20',
            'customer_address' => 'required|string',
            'customer_social_link' => 'required|string|max:255',
            'delivery_code' => 'required|string|max:100',
            'notes' => 'nullable|string',
        ]);

        try {
            // جلب الطلب
            $query = Order::where('id', $id);

            // للمجهز: التحقق من أن الطلب يحتوي على منتجات من مخازن له صلاحية الوصول إليها
            if ($user->isSupplier()) {
                $accessibleWarehouseIds = $user->warehouses->pluck('id')->toArray();
                if (!empty($accessibleWarehouseIds)) {
                    $query->whereHas('items.product', function ($q) use ($accessibleWarehouseIds) {
                        $q->whereIn('warehouse_id', $accessibleWarehouseIds);
                    });
                } else {
                    $query->whereRaw('1 = 0');
                }
            }

            $order = $query->with(['items.product', 'items.size'])->first();

            if (!$order) {
                return response()->json([
                    'success' => false,
                    'message' => 'الطلب غير موجود أو ليس لديك صلاحية للوصول إليه',
                    'error_code' => 'NOT_FOUND',
                ], 404);
            }

            if ($order->status !== 'pending') {
                return response()->json([
                    'success' => false,
                    'message' => 'لا يمكن تجهيز الطلبات المقيدة',
                    'error_code' => 'CANNOT_PROCESS',
                ], 400);
            }

            DB::transaction(function () use ($order, $request, $user) {
                // حفظ القيم الحالية من الإعدادات وقت التقييد
                $deliveryFee = \App\Models\Setting::getDeliveryFee();
                $profitMargin = \App\Models\Setting::getProfitMargin();

                // تحديث معلومات الطلب
                $order->update([
                    'customer_name' => $request->customer_name,
                    'customer_phone' => $request->customer_phone,
                    'customer_phone2' => $request->customer_phone2,
                    'customer_address' => $request->customer_address,
                    'customer_social_link' => $request->customer_social_link,
                    'delivery_code' => $request->delivery_code,
                    'notes' => $request->notes,
                    'status' => 'confirmed',
                    'confirmed_at' => now(),
                    'confirmed_by' => $user->id,
                    'delivery_fee_at_confirmation' => $deliveryFee,
                    'profit_margin_at_confirmation' => $profitMargin,
                ]);

                // إرسال SweetAlert للمندوب
                try {
                    $this->sweetAlertService->notifyOrderConfirmed($order);
                } catch (\Exception $e) {
                    Log::error('MobileAdminOrderController: Error sending SweetAlert for order_confirmed', [
                        'error' => $e->getMessage(),
                        'order_id' => $order->id,
                    ]);
                }

                // تسجيل حركة التقييد/التجهيز لكل منتج في الطلب
                $order->load('items.product', 'items.size');
                foreach ($order->items as $item) {
                    $balanceAfter = 0;
                    if ($item->size_id && $item->size) {
                        $balanceAfter = $item->size->quantity;
                    }

                    ProductMovement::record([
                        'product_id' => $item->product_id,
                        'size_id' => $item->size_id,
                        'warehouse_id' => $item->product->warehouse_id,
                        'order_id' => $order->id,
                        'movement_type' => 'confirm',
                        'quantity' => 0,
                        'balance_after' => $balanceAfter,
                        'order_status' => 'confirmed',
                        'notes' => "تقييد/تجهيز طلب #{$order->order_number}"
                    ]);
                }

                // تسجيل الربح عند التقييد
                $this->profitCalculator->recordOrderProfit($order);
            });

            // إعادة تحميل الطلب
            $order->refresh();
            $order->load([
                'delegate',
                'items.product.primaryImage',
                'items.product.warehouse',
                'items.size',
                'confirmedBy',
                'processedBy',
            ]);

            return response()->json([
                'success' => true,
                'message' => 'تم تجهيز وتقييد الطلب بنجاح',
                'data' => [
                    'order' => $this->formatOrderDetails($order),
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('MobileAdminOrderController: Failed to process order', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'order_id' => $id,
                'user_id' => $user->id,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء تجهيز الطلب',
                'error_code' => 'PROCESS_ERROR',
            ], 500);
        }
    }

    /**
     * جلب قائمة المواد (غير مجمعة)
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getMaterialsList(Request $request)
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
            // Base query
            $query = Order::query();

            // فلتر الصلاحيات
            if ($user->isSupplier()) {
                $accessibleWarehouseIds = $user->warehouses->pluck('id')->toArray();
                $query->whereHas('items.product', function ($q) use ($accessibleWarehouseIds) {
                    $q->whereIn('warehouse_id', $accessibleWarehouseIds);
                });
            }

            // فلتر الحالة (pending بشكل افتراضي)
            if ($request->filled('status')) {
                if ($request->status === 'deleted') {
                    $query->onlyTrashed()
                        ->whereNotNull('deleted_by')
                        ->whereNotNull('deletion_reason');
                } else {
                    $query->where('status', $request->status);
                }
            } else {
                $query->where('status', 'pending');
            }

            // تطبيق الفلاتر
            $this->applyFilters($query, $request);

            $orders = $query->with([
                'delegate',
                'items.product.primaryImage',
                'items.product.warehouse'
            ])->get();

            // فلترة items حسب المخزن والصلاحيات
            foreach ($orders as $order) {
                $order->items = $order->items->filter(function ($item) use ($request, $user) {
                    if (!$item->product)
                        return false;

                    // فلتر المخزن
                    if ($request->filled('warehouse_id')) {
                        if ($item->product->warehouse_id != $request->warehouse_id) {
                            return false;
                        }
                    }

                    // فلتر صلاحيات المجهز
                    if ($user->isSupplier()) {
                        $accessibleWarehouseIds = $user->warehouses->pluck('id')->toArray();
                        if (!in_array($item->product->warehouse_id, $accessibleWarehouseIds)) {
                            return false;
                        }
                    }

                    return true;
                });
            }

            // إزالة الطلبات التي لا تحتوي على items بعد الفلترة
            $orders = $orders->filter(function ($order) {
                return $order->items->count() > 0;
            });

            // تجميع المواد
            $materials = [];
            foreach ($orders as $order) {
                foreach ($order->items as $item) {
                    if (!$item->product)
                        continue;

                    $key = $item->product_id . '_' . $item->size_name;

                    if (!isset($materials[$key])) {
                        $materials[$key] = [
                            'product' => [
                                'id' => $item->product->id,
                                'name' => $item->product->name,
                                'code' => $item->product->code,
                                'primary_image_url' => $item->product->primaryImage ? $item->product->primaryImage->image_url : null,
                                'warehouse' => $item->product->warehouse ? [
                                    'id' => $item->product->warehouse->id,
                                    'name' => $item->product->warehouse->name,
                                ] : null,
                            ],
                            'size_name' => $item->size_name,
                            'total_quantity' => 0,
                            'orders' => []
                        ];
                    }

                    $materials[$key]['total_quantity'] += $item->quantity;
                    $materials[$key]['orders'][] = [
                        'order_number' => $order->order_number,
                        'quantity' => $item->quantity,
                        'order_id' => $order->id
                    ];
                }
            }

            // تحويل إلى مصفوفة مسطحة
            $materialsList = array_values($materials);

            return response()->json([
                'success' => true,
                'data' => $materialsList,
            ]);
        } catch (\Exception $e) {
            Log::error('MobileAdminOrderController: Failed to get materials list', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => $user->id,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء جلب قائمة المواد',
                'error_code' => 'FETCH_ERROR',
            ], 500);
        }
    }

    /**
     * جلب قائمة المواد (مجمعة حسب كود المنتج)
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getMaterialsListGrouped(Request $request)
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
            // Base query
            $query = Order::query();

            // فلتر الصلاحيات
            if ($user->isSupplier()) {
                $accessibleWarehouseIds = $user->warehouses->pluck('id')->toArray();
                $query->whereHas('items.product', function ($q) use ($accessibleWarehouseIds) {
                    $q->whereIn('warehouse_id', $accessibleWarehouseIds);
                });
            }

            // فلتر الحالة (pending بشكل افتراضي)
            if ($request->filled('status')) {
                if ($request->status === 'deleted') {
                    $query->onlyTrashed()
                        ->whereNotNull('deleted_by')
                        ->whereNotNull('deletion_reason');
                } else {
                    $query->where('status', $request->status);
                }
            } else {
                $query->where('status', 'pending');
            }

            // تطبيق الفلاتر
            $this->applyFilters($query, $request);

            $orders = $query->with([
                'delegate',
                'items.product.primaryImage',
                'items.product.warehouse'
            ])->get();

            // تجميع المواد حسب كود المنتج
            $materialsGrouped = [];
            foreach ($orders as $order) {
                foreach ($order->items as $item) {
                    if (!$item->product)
                        continue;

                    // فلتر المخزن
                    if ($request->filled('warehouse_id')) {
                        if ($item->product->warehouse_id != $request->warehouse_id) {
                            continue;
                        }
                    }

                    // فلتر صلاحيات المجهز
                    if ($user->isSupplier()) {
                        $accessibleWarehouseIds = $user->warehouses->pluck('id')->toArray();
                        if (!in_array($item->product->warehouse_id, $accessibleWarehouseIds)) {
                            continue;
                        }
                    }

                    // استخدام كود المنتج كمفتاح للتجميع
                    $productCode = $item->product->code;
                    $sizeKey = $item->size_name ?? 'no_size';

                    if (!isset($materialsGrouped[$productCode])) {
                        $materialsGrouped[$productCode] = [
                            'product' => [
                                'id' => $item->product->id,
                                'name' => $item->product->name,
                                'code' => $item->product->code,
                                'primary_image_url' => $item->product->primaryImage ? $item->product->primaryImage->image_url : null,
                                'warehouse' => $item->product->warehouse ? [
                                    'id' => $item->product->warehouse->id,
                                    'name' => $item->product->warehouse->name,
                                ] : null,
                            ],
                            'sizes' => []
                        ];
                    }

                    // إضافة الحجم إذا لم يكن موجوداً
                    if (!isset($materialsGrouped[$productCode]['sizes'][$sizeKey])) {
                        $materialsGrouped[$productCode]['sizes'][$sizeKey] = [
                            'size_name' => $item->size_name,
                            'total_quantity' => 0,
                            'orders' => []
                        ];
                    }

                    $materialsGrouped[$productCode]['sizes'][$sizeKey]['total_quantity'] += $item->quantity;
                    $materialsGrouped[$productCode]['sizes'][$sizeKey]['orders'][] = [
                        'order_number' => $order->order_number,
                        'quantity' => $item->quantity,
                        'order_id' => $order->id
                    ];
                }
            }

            // تحويل إلى مصفوفة مسطحة مع ترتيب حسب الكود
            $materials = [];
            ksort($materialsGrouped);

            foreach ($materialsGrouped as $productCode => $group) {
                // ترتيب الأحجام داخل كل منتج
                ksort($group['sizes']);

                foreach ($group['sizes'] as $sizeKey => $sizeData) {
                    $materials[] = [
                        'product' => $group['product'],
                        'product_code' => $productCode,
                        'size_name' => $sizeData['size_name'],
                        'total_quantity' => $sizeData['total_quantity'],
                        'orders' => $sizeData['orders']
                    ];
                }
            }

            return response()->json([
                'success' => true,
                'data' => $materials,
            ]);
        } catch (\Exception $e) {
            Log::error('MobileAdminOrderController: Failed to get materials list grouped', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => $user->id,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء جلب قائمة المواد',
                'error_code' => 'FETCH_ERROR',
            ], 500);
        }
    }

    /**
     * تطبيق الفلاتر على Query
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param Request $request
     * @return void
     */
    private function applyFilters($query, Request $request)
    {
        // فلتر المخزن
        if ($request->filled('warehouse_id')) {
            $query->whereHas('items.product', function ($q) use ($request) {
                $q->where('warehouse_id', $request->warehouse_id);
            });
        }

        // فلتر المجهز
        if ($request->filled('confirmed_by')) {
            $query->where('confirmed_by', $request->confirmed_by);
        }

        // فلتر المندوب
        if ($request->filled('delegate_id')) {
            $query->where('delegate_id', $request->delegate_id);
        }

        // فلتر حالة التدقيق
        if ($request->filled('size_reviewed')) {
            $query->where('size_reviewed', $request->size_reviewed);
        }

        // فلتر حالة تأكيد الرسالة
        if ($request->filled('message_confirmed')) {
            $query->where('message_confirmed', $request->message_confirmed);
        }

        // البحث في الطلبات
        if ($request->filled('search')) {
            $searchTerm = $request->search;
            $query->where(function ($q) use ($searchTerm) {
                $q->where('order_number', 'like', "%{$searchTerm}%")
                    ->orWhere('customer_name', 'like', "%{$searchTerm}%")
                    ->orWhere('customer_phone', 'like', "%{$searchTerm}%")
                    ->orWhere('customer_phone2', 'like', "%{$searchTerm}%")
                    ->orWhere('customer_social_link', 'like', "%{$searchTerm}%")
                    ->orWhere('customer_address', 'like', "%{$searchTerm}%")
                    ->orWhere('delivery_code', 'like', "%{$searchTerm}%")
                    ->orWhereHas('delegate', function ($delegateQuery) use ($searchTerm) {
                        $delegateQuery->where('name', 'like', "%{$searchTerm}%");
                    })
                    ->orWhereHas('items.product', function ($productQuery) use ($searchTerm) {
                        $productQuery->where('name', 'like', "%{$searchTerm}%")
                            ->orWhere('code', 'like', "%{$searchTerm}%");
                    });
            });
        }

        // فلتر حسب التاريخ
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        // فلتر حسب الوقت
        if ($request->filled('time_from')) {
            $dateFrom = $request->date_from ?? now()->format('Y-m-d');
            $query->where('created_at', '>=', $dateFrom . ' ' . $request->time_from . ':00');
        }

        if ($request->filled('time_to')) {
            $dateTo = $request->date_to ?? now()->format('Y-m-d');
            $query->where('created_at', '<=', $dateTo . ' ' . $request->time_to . ':00');
        }

        // فلتر حسب الساعات الماضية
        if ($request->filled('hours_ago')) {
            $hoursAgo = (int) $request->hours_ago;
            if ($hoursAgo > 0) {
                $query->where('created_at', '>=', now()->subHours($hoursAgo));
            }
        }

        // فلتر حسب الساعات (hours_filter - للتوافق مع pendingOrders)
        if ($request->filled('hours_filter')) {
            $hoursAgo = now()->subHours($request->hours_filter);
            $query->where('created_at', '>=', $hoursAgo);
        }

        // فلتر حسب تاريخ التقييد (للطلبات المقيدة - في management)
        if ($request->filled('confirmed_from')) {
            $query->whereDate('confirmed_at', '>=', $request->confirmed_from);
        }

        if ($request->filled('confirmed_to')) {
            $query->whereDate('confirmed_at', '<=', $request->confirmed_to);
        }

        // فلتر حسب تاريخ الإرجاع (للطلبات المسترجعة - في management)
        if ($request->filled('returned_from')) {
            $query->whereDate('returned_at', '>=', $request->returned_from);
        }

        if ($request->filled('returned_to')) {
            $query->whereDate('returned_at', '<=', $request->returned_to);
        }
    }

    /**
     * تطبيق الفلاتر على Query للطلبات المقيدة (يعمل على confirmed_at)
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param Request $request
     * @return void
     */
    private function applyFiltersForConfirmed($query, Request $request)
    {
        // تطبيق الفلاتر الأساسية فقط (بدون فلاتر التاريخ والوقت)
        // فلتر المخزن
        if ($request->filled('warehouse_id')) {
            $query->whereHas('items.product', function ($q) use ($request) {
                $q->where('warehouse_id', $request->warehouse_id);
            });
        }

        // فلتر المجهز
        if ($request->filled('confirmed_by')) {
            $query->where('confirmed_by', $request->confirmed_by);
        }

        // فلتر المندوب
        if ($request->filled('delegate_id')) {
            $query->where('delegate_id', $request->delegate_id);
        }

        // فلتر حالة التدقيق
        if ($request->filled('size_reviewed')) {
            $query->where('size_reviewed', $request->size_reviewed);
        }

        // فلتر حالة تأكيد الرسالة
        if ($request->filled('message_confirmed')) {
            $query->where('message_confirmed', $request->message_confirmed);
        }

        // البحث في الطلبات
        if ($request->filled('search')) {
            $searchTerm = $request->search;
            $query->where(function ($q) use ($searchTerm) {
                $q->where('order_number', 'like', "%{$searchTerm}%")
                    ->orWhere('customer_name', 'like', "%{$searchTerm}%")
                    ->orWhere('customer_phone', 'like', "%{$searchTerm}%")
                    ->orWhere('customer_phone2', 'like', "%{$searchTerm}%")
                    ->orWhere('customer_social_link', 'like', "%{$searchTerm}%")
                    ->orWhere('customer_address', 'like', "%{$searchTerm}%")
                    ->orWhere('delivery_code', 'like', "%{$searchTerm}%")
                    ->orWhereHas('delegate', function ($delegateQuery) use ($searchTerm) {
                        $delegateQuery->where('name', 'like', "%{$searchTerm}%");
                    })
                    ->orWhereHas('items.product', function ($productQuery) use ($searchTerm) {
                        $productQuery->where('name', 'like', "%{$searchTerm}%")
                            ->orWhere('code', 'like', "%{$searchTerm}%");
                    });
            });
        }

        // فلتر حسب التاريخ (على confirmed_at)
        if ($request->filled('date_from')) {
            $query->whereDate('confirmed_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('confirmed_at', '<=', $request->date_to);
        }

        // فلتر حسب الوقت (على confirmed_at)
        if ($request->filled('time_from')) {
            $dateFrom = $request->date_from ?? now()->format('Y-m-d');
            $query->where('confirmed_at', '>=', $dateFrom . ' ' . $request->time_from . ':00');
        }

        if ($request->filled('time_to')) {
            $dateTo = $request->date_to ?? now()->format('Y-m-d');
            $query->where('confirmed_at', '<=', $dateTo . ' ' . $request->time_to . ':00');
        }

        // فلتر حسب الساعات الماضية (على confirmed_at)
        if ($request->filled('hours_ago')) {
            $hoursAgo = (int) $request->hours_ago;
            if ($hoursAgo > 0) {
                $query->where('confirmed_at', '>=', now()->subHours($hoursAgo));
            }
        }

        // فلتر حسب تاريخ التقييد (confirmed_from/confirmed_to)
        if ($request->filled('confirmed_from')) {
            $query->whereDate('confirmed_at', '>=', $request->confirmed_from);
        }

        if ($request->filled('confirmed_to')) {
            $query->whereDate('confirmed_at', '<=', $request->confirmed_to);
        }
    }

    /**
     * حساب إحصائيات الطلبات غير المقيدة (للمدير فقط)
     *
     * @param Request $request
     * @return array
     */
    private function calculatePendingStatistics(Request $request)
    {
        $query = Order::where('status', 'pending');
        $this->applyFilters($query, $request);

        $orderIds = $query->pluck('id')->toArray();

        $totalAmount = 0;
        $totalProfit = 0;

        if (!empty($orderIds)) {
            $totalAmount = DB::table('order_items')
                ->whereIn('order_id', $orderIds)
                ->sum('subtotal') ?? 0;

            $totalProfit = DB::table('order_items')
                ->join('products', 'order_items.product_id', '=', 'products.id')
                ->whereIn('order_items.order_id', $orderIds)
                ->selectRaw('SUM((order_items.unit_price - COALESCE(products.purchase_price, 0)) * order_items.quantity) as total_profit')
                ->value('total_profit') ?? 0;
        }

        return [
            'total_orders' => count($orderIds),
            'total_amount' => (float) $totalAmount,
            'total_profit' => (float) $totalProfit,
        ];
    }

    /**
     * حساب إحصائيات الطلبات المقيدة (للمدير فقط)
     *
     * @param Request $request
     * @return array
     */
    private function calculateConfirmedStatistics(Request $request)
    {
        $query = Order::where('status', 'confirmed');
        $this->applyFiltersForConfirmed($query, $request);

        $orderIds = $query->pluck('id')->toArray();

        $totalAmount = 0;
        $totalProfit = 0;

        if (!empty($orderIds)) {
            $totalAmount = DB::table('order_items')
                ->whereIn('order_id', $orderIds)
                ->sum('subtotal') ?? 0;

            $totalProfit = DB::table('order_items')
                ->join('products', 'order_items.product_id', '=', 'products.id')
                ->whereIn('order_items.order_id', $orderIds)
                ->whereNotNull('products.purchase_price')
                ->where('products.purchase_price', '>', 0)
                ->selectRaw('SUM((order_items.unit_price - products.purchase_price) * order_items.quantity) as total_profit')
                ->value('total_profit') ?? 0;
        }

        return [
            'total_orders' => count($orderIds),
            'total_amount' => (float) $totalAmount,
            'total_profit' => (float) $totalProfit,
        ];
    }

    /**
     * الحصول على المخازن المتاحة للمستخدم
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    private function getAccessibleWarehouses()
    {
        $user = Auth::user();

        if ($user->isAdmin()) {
            return \App\Models\Warehouse::all();
        }

        if ($user->isSupplier() || $user->isPrivateSupplier()) {
            return $user->warehouses;
        }

        return collect();
    }

    /**
     * تنسيق بيانات الطلب للإرجاع (قائمة)
     *
     * @param Order $order
     * @return array
     */
    private function formatOrderListItem(Order $order)
    {
        return [
            'id' => $order->id,
            'order_number' => $order->order_number,
            'customer_name' => $order->customer_name,
            'customer_phone' => $order->customer_phone,
            'customer_address' => $order->customer_address,
            'customer_social_link' => $order->customer_social_link,
            'status' => $order->status,
            'total_amount' => (float) $order->total_amount,
            'delivery_fee' => (float) ($order->delivery_fee_at_confirmation ?? 0),
            'size_reviewed' => $order->size_reviewed,
            'message_confirmed' => $order->message_confirmed,
            'delegate' => $order->delegate ? [
                'id' => $order->delegate->id,
                'name' => $order->delegate->name,
                'code' => $order->delegate->code,
            ] : null,
            'confirmed_by' => $order->confirmedBy ? [
                'id' => $order->confirmedBy->id,
                'name' => $order->confirmedBy->name,
            ] : null,
            'created_at' => $order->created_at->toIso8601String(),
            'confirmed_at' => $order->confirmed_at ? $order->confirmed_at->toIso8601String() : null,
            'items_count' => $order->items->count(),
            'items' => $order->items->map(function ($item) {
                return [
                    'id' => $item->id,
                    'product' => [
                        'id' => $item->product_id, // Safety if product relation is missing
                        'name' => $item->product ? $item->product->name : $item->product_name,
                        'code' => $item->product ? $item->product->code : $item->product_code,
                        'primary_image_url' => ($item->product && $item->product->primaryImage) ? $item->product->primaryImage->image_url : null,
                        'warehouse' => ($item->product && $item->product->warehouse) ? [
                            'id' => $item->product->warehouse->id,
                            'name' => $item->product->warehouse->name,
                        ] : null,
                    ],
                    'size_id' => $item->size_id,
                    'size_name' => $item->size_name,
                    'quantity' => (int) $item->quantity,
                    'unit_price' => (float) $item->unit_price,
                    'subtotal' => (float) $item->subtotal,
                ];
            }),
        ];
    }

    /**
     * تحديث سريع لحالة التدقيق أو الرسالة
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateQuickStatus(Request $request, $id)
    {
        $user = Auth::user();

        // التحقق من أن المستخدم مدير أو مجهز
        if (!$user || (!$user->isAdmin() && !$user->isSupplier() && !$user->isPrivateSupplier())) {
            return response()->json([
                'success' => false,
                'message' => 'غير مصرح للقيام بهذا الإجراء.',
                'error_code' => 'FORBIDDEN',
            ], 403);
        }

        try {
            $order = Order::find($id);

            if (!$order) {
                return response()->json([
                    'success' => false,
                    'message' => 'الطلب غير موجود.',
                    'error_code' => 'NOT_FOUND',
                ], 404);
            }

            // للمجهز: التحقق من الصلاحيات
            if ($user->isSupplier()) {
                $accessibleWarehouseIds = $user->warehouses->pluck('id')->toArray();
                $hasAccess = $order->items()->whereHas('product', function ($q) use ($accessibleWarehouseIds) {
                    $q->whereIn('warehouse_id', $accessibleWarehouseIds);
                })->exists();

                if (!$hasAccess) {
                    return response()->json([
                        'success' => false,
                        'message' => 'ليس لديك صلاحية لتعديل هذا الطلب.',
                        'error_code' => 'FORBIDDEN',
                    ], 403);
                }
            }

            // التحديث بناءً على البيانات المرسلة
            $updateData = [];
            if ($request->has('size_reviewed')) {
                $updateData['size_reviewed'] = (bool) $request->size_reviewed;
            }
            if ($request->has('message_confirmed')) {
                $updateData['message_confirmed'] = (bool) $request->message_confirmed;
            }

            if (empty($updateData)) {
                return response()->json([
                    'success' => false,
                    'message' => 'لا توجد بيانات للتحديث.',
                    'error_code' => 'NO_DATA',
                ], 400);
            }

            $order->update($updateData);

            // إرسال إشعار للمندوب إذا تغيرت الحالات
            try {
                // يمكن إضافة منطق إرسال إشعارات هنا إذا رغب المستخدم
            } catch (\Exception $e) {
                Log::error('Error sending notification for quick status update');
            }

            return response()->json([
                'success' => true,
                'message' => 'تم تحديث الحالة بنجاح.',
                'data' => [
                    'order' => $this->formatOrderListItem($order), // Return list item format to update local state
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('MobileAdminOrderController: Failed to update quick status', [
                'error' => $e->getMessage(),
                'order_id' => $id,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء تحديث الحالة.',
                'error_code' => 'UPDATE_ERROR',
            ], 500);
        }
    }

    /**
     * تنسيق تفاصيل الطلب
     *
     * @param Order $order
     * @return array
     */
    private function formatOrderDetails(Order $order)
    {
        return [
            'id' => $order->id,
            'order_number' => $order->order_number,
            'customer_name' => $order->customer_name,
            'customer_phone' => $order->customer_phone,
            'customer_phone2' => $order->customer_phone2,
            'customer_address' => $order->customer_address,
            'customer_social_link' => $order->customer_social_link,
            'delivery_code' => $order->delivery_code,
            'notes' => $order->notes,
            'status' => $order->status,
            'total_amount' => (float) $order->total_amount,
            'delivery_fee' => (float) ($order->delivery_fee_at_confirmation ?? 0),
            'size_reviewed' => $order->size_reviewed,
            'message_confirmed' => $order->message_confirmed,
            'delegate' => $order->delegate ? [
                'id' => $order->delegate->id,
                'name' => $order->delegate->name,
                'code' => $order->delegate->code,
            ] : null,
            'confirmed_by' => $order->confirmedBy ? [
                'id' => $order->confirmedBy->id,
                'name' => $order->confirmedBy->name,
            ] : null,
            'processed_by' => $order->processedBy ? [
                'id' => $order->processedBy->id,
                'name' => $order->processedBy->name,
            ] : null,
            'created_at' => $order->created_at->toIso8601String(),
            'confirmed_at' => $order->confirmed_at ? $order->confirmed_at->toIso8601String() : null,
            'can_be_edited' => $order->status === 'pending' || $order->canBeEdited(),
            'items' => $order->items->map(function ($item) {
                return [
                    'id' => $item->id,
                    'product' => [
                        'id' => $item->product->id,
                        'name' => $item->product->name,
                        'code' => $item->product->code,
                        'primary_image_url' => $item->product->primaryImage ? $item->product->primaryImage->image_url : null,
                        'warehouse' => $item->product->warehouse ? [
                            'id' => $item->product->warehouse->id,
                            'name' => $item->product->warehouse->name,
                        ] : null,
                    ],
                    'size_id' => $item->size_id,
                    'size_name' => $item->size_name,
                    'quantity' => (int) $item->quantity,
                    'unit_price' => (float) $item->unit_price,
                    'subtotal' => (float) $item->subtotal,
                ];
            }),
        ];
    }
}

