<?php

namespace App\Http\Controllers\Mobile\Admin;

use App\Http\Controllers\Controller;
use App\Models\ProductMovement;
use App\Models\Warehouse;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class MobileAdminOrderMovementController extends Controller
{
    /**
     * جلب قائمة حركات الطلبات
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getOrderMovements(Request $request)
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
            $query = ProductMovement::with(['product.primaryImage', 'size', 'warehouse', 'order', 'user', 'delegate'])
                ->whereNotNull('order_id'); // فقط الحركات المرتبطة بطلبات

            // فلتر حسب المخزن
            if ($request->filled('warehouse_id')) {
                $query->byWarehouse($request->warehouse_id);
            }

            // فلتر حسب المنتج
            if ($request->filled('product_id')) {
                $query->byProduct($request->product_id);
            }

            // فلتر حسب القياس
            if ($request->filled('size_id')) {
                $query->bySize($request->size_id);
            }

            // فلتر حسب نوع الحركة
            if ($request->filled('movement_type')) {
                $query->byMovementType($request->movement_type);
            }

            // فلتر حسب المستخدم
            if ($request->filled('user_id')) {
                $query->byUser($request->user_id);
            }

            // فلتر حسب حالة الطلب
            if ($request->filled('order_status')) {
                $query->byOrderStatus($request->order_status);
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

            // فلتر حسب المخازن المخصصة للمستخدم (للمجهز)
            if ($user->isSupplier() || $user->isPrivateSupplier()) {
                $warehouseIds = $user->warehouses->pluck('id')->toArray();
                $query->whereIn('warehouse_id', $warehouseIds);
            }

            $perPage = $request->input('per_page', 20);
            $maxPerPage = 100;
            if ($perPage > $maxPerPage) {
                $perPage = $maxPerPage;
            }

            $movements = $query->latest('created_at')
                ->paginate($perPage)
                ->appends($request->except('page'));

            // تنسيق البيانات
            $formattedMovements = $movements->map(function($movement) {
                return [
                    'id' => $movement->id,
                    'product' => $movement->product ? [
                        'id' => $movement->product->id,
                        'name' => $movement->product->name,
                        'code' => $movement->product->code,
                        'primary_image_url' => $movement->product->primaryImage ? $movement->product->primaryImage->image_url : null,
                    ] : null,
                    'size' => $movement->size ? [
                        'id' => $movement->size->id,
                        'size_name' => $movement->size->size_name,
                    ] : null,
                    'warehouse' => $movement->warehouse ? [
                        'id' => $movement->warehouse->id,
                        'name' => $movement->warehouse->name,
                    ] : null,
                    'order' => $movement->order ? [
                        'id' => $movement->order->id,
                        'order_number' => $movement->order->order_number,
                        'customer_name' => $movement->order->customer_name,
                        'status' => $movement->order->status,
                    ] : null,
                    'user' => $movement->user ? [
                        'id' => $movement->user->id,
                        'name' => $movement->user->name,
                        'code' => $movement->user->code,
                    ] : null,
                    'delegate' => $movement->delegate ? [
                        'id' => $movement->delegate->id,
                        'name' => $movement->delegate->name,
                        'code' => $movement->delegate->code,
                    ] : null,
                    'movement_type' => $movement->movement_type,
                    'movement_type_text' => $this->getMovementTypeText($movement->movement_type),
                    'quantity' => (int) $movement->quantity,
                    'balance_after' => (int) $movement->balance_after,
                    'order_status' => $movement->order_status,
                    'order_status_text' => $this->getOrderStatusText($movement->order_status),
                    'notes' => $movement->notes,
                    'created_at' => $movement->created_at->toIso8601String(),
                ];
            });

            // تجميع الحركات حسب order_id (اختياري)
            $groupedMovements = null;
            if ($request->filled('group_by_order') && $request->group_by_order == '1') {
                $groupedMovements = $movements->groupBy('order_id')->map(function ($orderMovements) {
                    $firstMovement = $orderMovements->first();
                    return [
                        'order_id' => $firstMovement->order_id,
                        'order' => $firstMovement->order ? [
                            'id' => $firstMovement->order->id,
                            'order_number' => $firstMovement->order->order_number,
                            'customer_name' => $firstMovement->order->customer_name,
                            'status' => $firstMovement->order->status,
                        ] : null,
                        'order_status' => $firstMovement->order_status,
                        'order_status_text' => $this->getOrderStatusText($firstMovement->order_status),
                        'user' => $firstMovement->user ? [
                            'id' => $firstMovement->user->id,
                            'name' => $firstMovement->user->name,
                            'code' => $firstMovement->user->code,
                        ] : null,
                        'created_at' => $firstMovement->created_at->toIso8601String(),
                        'total_quantity' => $orderMovements->sum('quantity'),
                        'movements_count' => $orderMovements->count(),
                        'movements' => $orderMovements->map(function($movement) {
                            return [
                                'id' => $movement->id,
                                'product' => $movement->product ? [
                                    'id' => $movement->product->id,
                                    'name' => $movement->product->name,
                                    'code' => $movement->product->code,
                                ] : null,
                                'size' => $movement->size ? [
                                    'id' => $movement->size->id,
                                    'size_name' => $movement->size->size_name,
                                ] : null,
                                'movement_type' => $movement->movement_type,
                                'movement_type_text' => $this->getMovementTypeText($movement->movement_type),
                                'quantity' => (int) $movement->quantity,
                                'created_at' => $movement->created_at->toIso8601String(),
                            ];
                        })->values(),
                    ];
                })->values();
            }

            return response()->json([
                'success' => true,
                'data' => $formattedMovements,
                'grouped_by_order' => $groupedMovements,
                'pagination' => [
                    'current_page' => $movements->currentPage(),
                    'per_page' => $movements->perPage(),
                    'total' => $movements->total(),
                    'last_page' => $movements->lastPage(),
                    'has_more' => $movements->hasMorePages(),
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('MobileAdminOrderMovementController: Failed to get order movements', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => $user->id,
                'request' => $request->all(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء جلب حركات الطلبات',
                'error_code' => 'FETCH_ERROR',
            ], 500);
        }
    }

    /**
     * جلب إحصائيات حركات الطلبات
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getOrderMovementsStatistics(Request $request)
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
            $query = ProductMovement::whereNotNull('order_id');

            // تطبيق نفس الفلاتر من getOrderMovements
            if ($request->filled('warehouse_id')) {
                $query->byWarehouse($request->warehouse_id);
            }

            if ($request->filled('product_id')) {
                $query->byProduct($request->product_id);
            }

            if ($request->filled('size_id')) {
                $query->bySize($request->size_id);
            }

            if ($request->filled('movement_type')) {
                $query->byMovementType($request->movement_type);
            }

            if ($request->filled('user_id')) {
                $query->byUser($request->user_id);
            }

            if ($request->filled('order_status')) {
                $query->byOrderStatus($request->order_status);
            }

            if ($request->filled('date_from')) {
                $query->whereDate('created_at', '>=', $request->date_from);
            }

            if ($request->filled('date_to')) {
                $query->whereDate('created_at', '<=', $request->date_to);
            }

            if ($request->filled('time_from')) {
                $dateFrom = $request->date_from ?? now()->format('Y-m-d');
                $query->where('created_at', '>=', $dateFrom . ' ' . $request->time_from . ':00');
            }

            if ($request->filled('time_to')) {
                $dateTo = $request->date_to ?? now()->format('Y-m-d');
                $query->where('created_at', '<=', $dateTo . ' ' . $request->time_to . ':00');
            }

            // فلتر حسب المخازن المخصصة للمستخدم (للمجهز)
            if ($user->isSupplier() || $user->isPrivateSupplier()) {
                $warehouseIds = $user->warehouses->pluck('id')->toArray();
                $query->whereIn('warehouse_id', $warehouseIds);
            }

            // إحصائيات
            $stats = [
                'total_movements' => (int) $query->count(),
                'total_additions' => (int) $query->clone()->byMovementType('add')->sum('quantity'),
                'total_sales' => (int) abs($query->clone()->byMovementType('sale')->sum('quantity')),
                'total_confirms' => (int) abs($query->clone()->byMovementType('confirm')->sum('quantity')),
                'total_returns' => (int) $query->clone()->whereIn('movement_type', ['return', 'cancel', 'delete', 'return_bulk', 'return_exchange_bulk', 'partial_return'])->sum('quantity'),
                'total_cancels' => (int) $query->clone()->byMovementType('cancel')->sum('quantity'),
                'total_deletes' => (int) $query->clone()->byMovementType('delete')->sum('quantity'),
            ];

            // إحصائيات حسب نوع الحركة
            $movementTypes = ['add', 'sale', 'confirm', 'cancel', 'return', 'delete', 'restore', 'return_bulk', 'return_exchange_bulk', 'partial_return'];
            $statsByType = [];
            foreach ($movementTypes as $type) {
                $statsByType[$type] = [
                    'count' => (int) $query->clone()->byMovementType($type)->count(),
                    'total_quantity' => (int) abs($query->clone()->byMovementType($type)->sum('quantity')),
                ];
            }

            // إحصائيات حسب حالة الطلب
            $orderStatuses = ['pending', 'confirmed', 'cancelled', 'returned', 'exchanged'];
            $statsByOrderStatus = [];
            foreach ($orderStatuses as $status) {
                $statsByOrderStatus[$status] = [
                    'count' => (int) $query->clone()->byOrderStatus($status)->count(),
                    'total_quantity' => (int) abs($query->clone()->byOrderStatus($status)->sum('quantity')),
                ];
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'summary' => $stats,
                    'by_movement_type' => $statsByType,
                    'by_order_status' => $statsByOrderStatus,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('MobileAdminOrderMovementController: Failed to get order movements statistics', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => $user->id,
                'request' => $request->all(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء جلب إحصائيات حركات الطلبات',
                'error_code' => 'FETCH_ERROR',
            ], 500);
        }
    }

    /**
     * الحصول على نص نوع الحركة
     *
     * @param string $type
     * @return string
     */
    private function getMovementTypeText($type)
    {
        $types = [
            'add' => 'إضافة',
            'sale' => 'بيع',
            'confirm' => 'تقييد',
            'cancel' => 'إلغاء',
            'return' => 'استرجاع',
            'delete' => 'حذف',
            'restore' => 'استرجاع من الحذف',
            'return_bulk' => 'إرجاع جماعي',
            'return_exchange_bulk' => 'إرجاع/استبدال جماعي',
            'partial_return' => 'إرجاع جزئي',
        ];

        return $types[$type] ?? 'غير محدد';
    }

    /**
     * الحصول على نص حالة الطلب
     *
     * @param string $status
     * @return string
     */
    private function getOrderStatusText($status)
    {
        $statuses = [
            'pending' => 'غير مقيد',
            'confirmed' => 'مقيد',
            'cancelled' => 'ملغي',
            'returned' => 'مسترجعة',
            'exchanged' => 'مستبدلة',
        ];

        return $statuses[$status] ?? 'غير محدد';
    }
}

