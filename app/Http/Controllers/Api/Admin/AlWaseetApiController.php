<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\AlWaseetShipment;
use App\Models\Order;
use App\Models\Warehouse;
use App\Models\User;
use App\Models\AlWaseetOrderStatus;
use App\Services\AlWaseetService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AlWaseetApiController extends Controller
{
    protected $alWaseetService;

    public function __construct(AlWaseetService $alWaseetService)
    {
        $this->alWaseetService = $alWaseetService;
    }

    /**
     * Get tracked orders with API status and statistics for mobile.
     */
    public function index(Request $request)
    {
        $user = Auth::user();

        // 1. Authorization
        if (!$user || (!$user->isAdmin() && !$user->isSupplier())) {
            return response()->json([
                'success' => false,
                'message' => 'غير مصرح.',
            ], 403);
        }

        try {
            // 2. Base Query
            $query = Order::query();

            // 3. Security Filters (Suppliers)
            if ($user->isSupplier()) {
                $accessibleWarehouseIds = $user->warehouses->pluck('id')->toArray();
                if (!empty($accessibleWarehouseIds)) {
                    $query->whereIn('id', function ($subQuery) use ($accessibleWarehouseIds) {
                        $subQuery->select('order_id')
                            ->from('order_items')
                            ->join('products', 'order_items.product_id', '=', 'products.id')
                            ->whereIn('products.warehouse_id', $accessibleWarehouseIds)
                            ->distinct();
                    });
                } else {
                    $query->whereRaw('1 = 0');
                }
            }

            // 4. Apply Filters (Reused logic from Track Orders Web)
            $this->applyTrackFilters($query, $request);

            // 5. Fetch Status Counts (Statistics)
            $statusCounts = $this->getStatusCounts($request, $user);

            // 6. Pagination & Data Retrieval
            $perPage = $request->input('per_page', 20);
            $orders = $query->with([
                'delegate',
                'items.product.primaryImage',
                'items.product.warehouse',
                'alwaseetShipment.statusHistory.statusInfo'
            ])->orderBy('created_at', 'desc')->paginate($perPage);

            // 7. Enhance with Real-time API Data (Chunked)
            $alwaseetOrderIds = $orders->getCollection()->pluck('alwaseetShipment.alwaseet_order_id')->filter()->unique()->toArray();
            $apiData = [];
            if (!empty($alwaseetOrderIds)) {
                $apiData = $this->fetchRealTimeStatus($alwaseetOrderIds);
            }

            // 8. Prepare Final Response
            $transformedOrders = $orders->getCollection()->map(function ($order) use ($apiData) {
                $shipment = $order->alwaseetShipment;
                $apiStatus = null;
                if ($shipment && isset($apiData[$shipment->alwaseet_order_id])) {
                    $apiStatus = $apiData[$shipment->alwaseet_order_id];
                }

                // Add calculated fields to order object for easier mobile consumption
                $order->current_api_status = $apiStatus['status'] ?? ($shipment->status ?? 'غير معروف');
                $order->current_api_status_id = $apiStatus['status_id'] ?? ($shipment->status_id ?? null);

                // Construct pickup code
                $order->alwaseet_code = $apiStatus['pickup_id'] ?? ($shipment->pickup_id ?? ($shipment->qr_id ?? ($order->delivery_code ?? null)));

                return $order;
            });

            return response()->json([
                'success' => true,
                'data' => [
                    'orders' => $transformedOrders,
                    'status_counts' => $statusCounts,
                    'pagination' => [
                        'total' => $orders->total(),
                        'per_page' => $orders->perPage(),
                        'current_page' => $orders->currentPage(),
                        'last_page' => $orders->lastPage(),
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('AlWaseetApiController@index error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء جلب الطلبات.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete an order (soft-delete) from tracking.
     */
    public function deleteOrder(Request $request, Order $order)
    {
        if (!Auth::user()->isAdmin()) {
            return response()->json(['success' => false, 'message' => 'غير مصرح.'], 403);
        }

        try {
            $order->deleted_by = Auth::id();
            $order->deletion_reason = 'تم الحذف من تطبيق الموبايل (تتبع الطلبات)';
            $order->save();
            $order->delete();

            return response()->json(['success' => true, 'message' => 'تم حذف الطلب بنجاح']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'فشل الحذف', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Get options for filters (warehouses, delegates, suppliers, statuses).
     */
    public function getFilters()
    {
        $user = Auth::user();

        if ($user->isSupplier()) {
            $warehouses = $user->warehouses;
        } else {
            $warehouses = Warehouse::all();
        }

        $suppliers = User::whereIn('role', ['admin', 'supplier'])->get(['id', 'name', 'code', 'role']);
        $delegates = User::where('role', 'delegate')->get(['id', 'name', 'code']);

        // Fetch AlWaseet Statuses
        $statuses = AlWaseetOrderStatus::orderBy('display_order')->get(['status_id', 'status_text']);

        return response()->json([
            'success' => true,
            'data' => [
                'warehouses' => $warehouses,
                'suppliers' => $suppliers,
                'delegates' => $delegates,
                'statuses' => $statuses,
            ]
        ]);
    }

    private function applyTrackFilters($query, Request $request)
    {
        if ($request->filled('warehouse_id')) {
            $query->whereHas('items.product', function ($q) use ($request) {
                $q->where('warehouse_id', $request->warehouse_id);
            });
        }

        if ($request->filled('confirmed_by'))
            $query->where('confirmed_by', $request->confirmed_by);
        if ($request->filled('delegate_id'))
            $query->where('delegate_id', $request->delegate_id);
        if ($request->filled('api_status_id')) {
            $query->whereHas('alwaseetShipment', function ($q) use ($request) {
                $q->where('status_id', $request->api_status_id);
            });
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('order_number', 'like', "%{$search}%")
                    ->orWhere('customer_name', 'like', "%{$search}%")
                    ->orWhere('customer_phone', 'like', "%{$search}%")
                    ->orWhere('customer_address', 'like', "%{$search}%")
                    ->orWhere('delivery_code', 'like', "%{$search}%")
                    ->orWhereHas('alwaseetShipment', function ($sq) use ($search) {
                        $sq->where('alwaseet_order_id', 'like', "%{$search}%");
                    });
            });
        }

        if ($request->filled('date_from'))
            $query->whereDate('created_at', '>=', $request->date_from);
        if ($request->filled('date_to'))
            $query->whereDate('created_at', '<=', $request->date_to);
    }

    private function getStatusCounts(Request $request, $user)
    {
        // For mobile, we recalculate or fetch from cache similarly to web
        $filterParams = [
            'warehouse_id' => $request->warehouse_id,
            'confirmed_by' => $request->confirmed_by,
            'delegate_id' => $request->delegate_id,
            'date_from' => $request->date_from,
            'date_to' => $request->date_to,
            'user_id' => $user->id
        ];

        $cacheKey = 'mobile_alwaseet_status_counts_' . md5(json_encode($filterParams));

        return Cache::remember($cacheKey, now()->addMinutes(5), function () use ($request, $user) {
            $query = Order::query();

            // Re-apply same base security/filters for counts
            if ($user->isSupplier()) {
                $accessibleWarehouseIds = $user->warehouses->pluck('id')->toArray();
                if (!empty($accessibleWarehouseIds)) {
                    $query->whereIn('id', function ($subQuery) use ($accessibleWarehouseIds) {
                        $subQuery->select('order_id')->from('order_items')->join('products', 'order_items.product_id', '=', 'products.id')->whereIn('products.warehouse_id', $accessibleWarehouseIds);
                    });
                }
            }

            $this->applyTrackFilters($query, $request);
            $orderIds = $query->pluck('id')->toArray();

            if (empty($orderIds))
                return [];

            return AlWaseetShipment::whereIn('order_id', $orderIds)
                ->whereNotNull('status_id')
                ->selectRaw('status_id, COUNT(*) as count')
                ->groupBy('status_id')
                ->get()
                ->pluck('count', 'status_id');
        });
    }

    private function fetchRealTimeStatus($ids)
    {
        $cacheKey = 'mobile_alwaseet_api_batch_' . md5(implode(',', $ids));

        return Cache::remember($cacheKey, now()->addSeconds(30), function () use ($ids) {
            try {
                $batchSize = 10;
                $batches = array_chunk($ids, $batchSize);
                $allData = [];
                foreach ($batches as $batch) {
                    $apiOrders = $this->alWaseetService->getOrdersByIds($batch);
                    foreach ($apiOrders as $apiOrder) {
                        if (isset($apiOrder['id'])) {
                            $allData[$apiOrder['id']] = $apiOrder;
                        }
                    }
                }
                return $allData;
            } catch (\Exception $e) {
                Log::error('AlWaseetApiController fetchRealTimeStatus fail: ' . $e->getMessage());
                return [];
            }
        });
    }
}
