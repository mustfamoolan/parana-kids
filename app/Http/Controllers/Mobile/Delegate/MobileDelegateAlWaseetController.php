<?php

namespace App\Http\Controllers\Mobile\Delegate;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\AlWaseetOrderStatus;
use App\Models\AlWaseetShipment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MobileDelegateAlWaseetController extends Controller
{
    /**
     * جلب إحصائيات حالات طلبات الوسيط للمندوب
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getStatusCards(Request $request)
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

        try {
            // إنشاء مفتاح Cache فريد بناءً على الفلاتر
            $hasFilters = $request->filled('date_from') ||
                         $request->filled('date_to') ||
                         $request->filled('time_from') ||
                         $request->filled('time_to') ||
                         $request->filled('hours_ago') ||
                         $request->filled('warehouse_id');

            $filterParams = [
                'delegate_id' => $user->id,
                'date_from' => $request->date_from,
                'date_to' => $request->date_to,
                'time_from' => $request->time_from,
                'time_to' => $request->time_to,
                'hours_ago' => $request->hours_ago,
                'warehouse_id' => $request->warehouse_id,
            ];
            $cacheKey = 'delegate_status_cards_' . md5(json_encode($filterParams));
            $cacheDuration = $hasFilters ? now()->addMinutes(2) : now()->addMinutes(10);

            $result = Cache::remember($cacheKey, $cacheDuration, function () use ($user, $request, $hasFilters) {
                // جلب جميع الحالات النشطة
                $allStatuses = AlWaseetOrderStatus::getActiveStatuses();

                // بناء query base - طلبات المندوب فقط مع shipment
                $baseQuery = Order::where('delegate_id', $user->id)
                    ->whereHas('alwaseetShipment');

                // تطبيق الفلاتر
                $this->applyFilters($baseQuery, $request);

                // جلب order IDs المطابقة للفلاتر
                $orderIds = $baseQuery->pluck('id')->toArray();

                // حساب عدد الطلبات لكل حالة
                $statusCounts = [];
                $statusAmounts = [];

                // تهيئة جميع الحالات بقيمة 0
                foreach ($allStatuses as $status) {
                    $statusId = (string)$status->status_id;
                    $statusCounts[$statusId] = 0;
                    $statusAmounts[$statusId] = 0.0;
                }

                if (!empty($orderIds)) {
                    // حساب عدد الطلبات لكل حالة
                    $countsFromDb = AlWaseetShipment::whereIn('order_id', $orderIds)
                        ->whereNotNull('status_id')
                        ->selectRaw('status_id, COUNT(*) as count')
                        ->groupBy('status_id')
                        ->get()
                        ->mapWithKeys(function($item) {
                            return [(string)$item->status_id => (int)$item->count];
                        })
                        ->toArray();

                    foreach ($countsFromDb as $statusId => $count) {
                        $statusCounts[$statusId] = $count;
                    }

                    // حساب المبلغ الإجمالي لكل حالة
                    $amountsFromDb = DB::table('orders')
                        ->join('alwaseet_shipments', 'orders.id', '=', 'alwaseet_shipments.order_id')
                        ->whereIn('orders.id', $orderIds)
                        ->whereNotNull('alwaseet_shipments.status_id')
                        ->selectRaw('alwaseet_shipments.status_id, SUM(COALESCE(orders.total_amount, 0) + COALESCE(orders.delivery_fee_at_confirmation, 0)) as total_amount')
                        ->groupBy('alwaseet_shipments.status_id')
                        ->get()
                        ->mapWithKeys(function($item) {
                            return [(string)$item->status_id => (float)$item->total_amount];
                        })
                        ->toArray();

                    foreach ($amountsFromDb as $statusId => $amount) {
                        $statusAmounts[$statusId] = $amount;
                    }
                }

                // بناء status cards
                $statusCards = [];
                $totalOrders = 0;
                $totalAmount = 0.0;

                foreach ($allStatuses as $status) {
                    $statusId = (string)$status->status_id;
                    $count = $statusCounts[$statusId] ?? 0;
                    $amount = $statusAmounts[$statusId] ?? 0.0;

                    if ($count > 0 || !$hasFilters) { // عرض حتى الحالات الفارغة إذا لم تكن هناك فلاتر
                        $statusCards[] = [
                            'status_id' => $statusId,
                            'status_text' => $status->status_text,
                            'count' => $count,
                            'total_amount' => $amount,
                            'color' => $this->getStatusColor($statusId),
                        ];
                    }

                    $totalOrders += $count;
                    $totalAmount += $amount;
                }

                return [
                    'status_cards' => $statusCards,
                    'total_orders' => $totalOrders,
                    'total_amount' => $totalAmount,
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $result,
            ]);
        } catch (\Exception $e) {
            Log::error('MobileDelegateAlWaseetController: Failed to get status cards', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => $user->id,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء جلب الإحصائيات',
                'error_code' => 'STATUS_CARDS_ERROR',
            ], 500);
        }
    }

    /**
     * جلب قائمة طلبات الوسيط للمندوب
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getOrders(Request $request)
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

        // بناء query base - طلبات المندوب فقط مع shipment
        $query = Order::where('delegate_id', $user->id)
            ->whereHas('alwaseetShipment');

        // تطبيق الفلاتر
        $this->applyFilters($query, $request);

        // فلتر حسب حالة الوسيط
        if ($request->filled('status_id')) {
            $query->whereHas('alwaseetShipment', function($q) use ($request) {
                $q->where('status_id', $request->status_id);
            });
        }

        // البحث
        if ($request->filled('search')) {
            $searchTerm = $request->search;
            $query->where(function($q) use ($searchTerm) {
                $q->where('order_number', 'like', "%{$searchTerm}%")
                  ->orWhere('customer_name', 'like', "%{$searchTerm}%")
                  ->orWhere('customer_phone', 'like', "%{$searchTerm}%")
                  ->orWhere('customer_address', 'like', "%{$searchTerm}%")
                  ->orWhere('delivery_code', 'like', "%{$searchTerm}%")
                  ->orWhereHas('alwaseetShipment', function($shipmentQuery) use ($searchTerm) {
                      $shipmentQuery->where('alwaseet_order_id', 'like', "%{$searchTerm}%");
                  });
            });
        }

        // تحميل العلاقات
        $query->with([
            'items.product.primaryImage',
            'items.product.warehouse',
            'alwaseetShipment',
        ]);

        // Pagination
        $perPage = min($request->get('per_page', 20), 50);
        $orders = $query->orderBy('created_at', 'desc')->paginate($perPage);

        // تنسيق البيانات
        $formattedOrders = $orders->map(function($order) {
            return $this->formatOrderData($order);
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
    }

    /**
     * جلب تفاصيل طلب واحد مع Timeline
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function getOrderDetails($id)
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

        // جلب الطلب مع التحقق من أنه للمندوب الحالي
        $order = Order::where('id', $id)
            ->where('delegate_id', $user->id)
            ->whereHas('alwaseetShipment')
            ->with([
                'items.product.primaryImage',
                'items.product.warehouse',
                'alwaseetShipment.statusHistory.statusInfo',
                'confirmedBy',
            ])
            ->first();

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'الطلب غير موجود أو ليس لديك صلاحية للوصول إليه',
                'error_code' => 'NOT_FOUND',
            ], 404);
        }

        // تنسيق البيانات مع Timeline
        $formattedOrder = $this->formatOrderDetails($order);

        return response()->json([
            'success' => true,
            'data' => [
                'order' => $formattedOrder,
            ],
        ]);
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
            $hoursAgo = (int)$request->hours_ago;
            if ($hoursAgo > 0) {
                $query->where('created_at', '>=', now()->subHours($hoursAgo));
            }
        }

        // فلتر حسب المخزن
        if ($request->filled('warehouse_id')) {
            $query->whereIn('id', function($subQuery) use ($request) {
                $subQuery->select('order_id')
                    ->from('order_items')
                    ->join('products', 'order_items.product_id', '=', 'products.id')
                    ->where('products.warehouse_id', $request->warehouse_id)
                    ->distinct();
            });
        }
    }

    /**
     * تنسيق بيانات الطلب للإرجاع (قائمة)
     *
     * @param Order $order
     * @return array
     */
    private function formatOrderData(Order $order)
    {
        $shipment = $order->alwaseetShipment;
        $statusText = null;
        
        if ($shipment && $shipment->status_id) {
            $status = AlWaseetOrderStatus::where('status_id', $shipment->status_id)->first();
            $statusText = $status ? $status->status_text : $shipment->status;
        }

        return [
            'id' => $order->id,
            'order_number' => $order->order_number,
            'customer_name' => $order->customer_name,
            'customer_phone' => $order->customer_phone,
            'customer_address' => $order->customer_address,
            'total_amount' => (float) $order->total_amount,
            'delivery_fee' => (float) ($order->delivery_fee_at_confirmation ?? 0),
            'created_at' => $order->created_at->toIso8601String(),
            'alwaseet_shipment' => $shipment ? [
                'id' => $shipment->id,
                'alwaseet_order_id' => $shipment->alwaseet_order_id,
                'status_id' => $shipment->status_id,
                'status_text' => $statusText,
                'city_name' => $shipment->city_name,
                'region_name' => $shipment->region_name,
                'delivery_price' => (float) ($shipment->delivery_price ?? 0),
                'qr_link' => $shipment->qr_link,
                'synced_at' => $shipment->synced_at ? $shipment->synced_at->toIso8601String() : null,
            ] : null,
            'items' => $order->items->map(function($item) {
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
                    'size_name' => $item->size_name,
                    'quantity' => (int) $item->quantity,
                    'unit_price' => (float) $item->unit_price,
                ];
            }),
        ];
    }

    /**
     * تنسيق تفاصيل الطلب مع Timeline
     *
     * @param Order $order
     * @return array
     */
    private function formatOrderDetails(Order $order)
    {
        $shipment = $order->alwaseetShipment;
        $statusText = null;
        $statusTimeline = [];

        if ($shipment) {
            if ($shipment->status_id) {
                $status = AlWaseetOrderStatus::where('status_id', $shipment->status_id)->first();
                $statusText = $status ? $status->status_text : $shipment->status;
            }

            // جلب Timeline
            if ($shipment->relationLoaded('statusHistory')) {
                $statusTimeline = $shipment->statusHistory
                    ->sortBy('changed_at')
                    ->map(function($history) use ($shipment) {
                        return [
                            'status_id' => $history->status_id,
                            'status_text' => $history->status_text,
                            'changed_at' => $history->changed_at->toIso8601String(),
                            'is_current' => $history->status_id === $shipment->status_id,
                            'display_order' => $history->statusInfo ? $history->statusInfo->display_order : 999,
                        ];
                    })
                    ->values()
                    ->toArray();
            } else {
                // إذا لم تكن محملة، جلبها مباشرة
                $statusTimeline = $shipment->statusHistory()
                    ->with('statusInfo')
                    ->orderBy('changed_at', 'asc')
                    ->get()
                    ->map(function($history) use ($shipment) {
                        return [
                            'status_id' => $history->status_id,
                            'status_text' => $history->status_text,
                            'changed_at' => $history->changed_at->toIso8601String(),
                            'is_current' => $history->status_id === $shipment->status_id,
                            'display_order' => $history->statusInfo ? $history->statusInfo->display_order : 999,
                        ];
                    })
                    ->toArray();
            }
        }

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
            'total_amount' => (float) $order->total_amount,
            'delivery_fee' => (float) ($order->delivery_fee_at_confirmation ?? 0),
            'confirmed_by' => $order->confirmedBy ? [
                'id' => $order->confirmedBy->id,
                'name' => $order->confirmedBy->name,
            ] : null,
            'created_at' => $order->created_at->toIso8601String(),
            'items' => $order->items->map(function($item) {
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
                    'size_name' => $item->size_name,
                    'quantity' => (int) $item->quantity,
                    'unit_price' => (float) $item->unit_price,
                ];
            }),
            'alwaseet_shipment' => $shipment ? [
                'id' => $shipment->id,
                'alwaseet_order_id' => $shipment->alwaseet_order_id,
                'status_id' => $shipment->status_id,
                'status_text' => $statusText,
                'city_name' => $shipment->city_name,
                'region_name' => $shipment->region_name,
                'location' => $shipment->location,
                'price' => (float) ($shipment->price ?? 0),
                'delivery_price' => (float) ($shipment->delivery_price ?? 0),
                'merchant_notes' => $shipment->merchant_notes,
                'issue_notes' => $shipment->issue_notes,
                'qr_link' => $shipment->qr_link,
                'alwaseet_created_at' => $shipment->alwaseet_created_at ? $shipment->alwaseet_created_at->toIso8601String() : null,
                'synced_at' => $shipment->synced_at ? $shipment->synced_at->toIso8601String() : null,
                'status_timeline' => $statusTimeline,
            ] : null,
        ];
    }

    /**
     * الحصول على لون الحالة
     *
     * @param string $statusId
     * @return string
     */
    private function getStatusColor($statusId)
    {
        // ألوان افتراضية حسب الحالة
        $colorMap = [
            '1' => 'info',      // جديد
            '2' => 'primary',    // قيد المعالجة
            '3' => 'warning',    // جاهز للتسليم
            '4' => 'success',    // تم التسليم
            '5' => 'danger',     // ملغي
        ];

        return $colorMap[$statusId] ?? 'secondary';
    }
}

