<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\AlWaseetShipment;
use App\Models\Order;
use App\Models\Warehouse;
use App\Models\User;
use App\Models\AlWaseetOrderStatus;
use App\Services\AlWaseetService;
use App\Models\Setting;
use App\Models\ProductMovement;
use App\Services\ProfitCalculator;
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
     * Get orders for the Print & Upload workflow (pending only).
     */
    public function getPrintUploadOrders(Request $request)
    {
        $user = Auth::user();

        if (!$user || (!$user->isAdmin() && !$user->isSupplier())) {
            return response()->json(['success' => false, 'message' => 'غير مصرح.'], 403);
        }

        try {
            $query = Order::where('status', 'pending');

            // Security Filters (Suppliers)
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

            // Apply Filters (Same as web printAndUploadOrders)
            $this->applyPrintFilters($query, $request);

            $perPage = $request->input('per_page', 20);
            $orders = $query->with([
                'delegate',
                'items.product.primaryImage',
                'items.product.warehouse',
                'confirmedBy',
                'processedBy',
                'alwaseetShipment'
            ])->orderBy('created_at', 'desc')->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => [
                    'orders' => $orders->getCollection(),
                    'pagination' => [
                        'total' => $orders->total(),
                        'per_page' => $orders->perPage(),
                        'current_page' => $orders->currentPage(),
                        'last_page' => $orders->lastPage(),
                    ]
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('AlWaseetApiController@getPrintUploadOrders error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'فشل جلب الطلبات', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Send order to AlWaseet carrier.
     */
    public function sendToAlWaseet(Request $request, $id)
    {
        try {
            $order = Order::with('items.product')->findOrFail($id);
            if (!Auth::user()->isAdmin()) {
                return response()->json(['success' => false, 'message' => 'غير مصرح.'], 403);
            }

            // التحقق من وجود المحافظة والمنطقة
            if (empty($order->alwaseet_city_id) || empty($order->alwaseet_region_id)) {
                return response()->json([
                    'success' => false,
                    'message' => 'يجب تحديد المحافظة والمنطقة أولاً',
                ], 400);
            }

            // جلب حجم الطرد "عادي"
            $packageSizes = $this->alWaseetService->getPackageSizes();
            $normalPackageSize = collect($packageSizes)->firstWhere('package_size_name', 'عادي');

            if (!$normalPackageSize) {
                $normalPackageSize = $packageSizes[0] ?? null;
            }

            if (!$normalPackageSize) {
                return response()->json(['success' => false, 'message' => 'فشل جلب أحجام الطرود من الواسط'], 500);
            }

            $deliveryFee = Setting::getDeliveryFee();
            $totalPrice = $order->total_amount + $deliveryFee;

            $productParts = $order->items->map(function ($item) {
                $rawName = optional($item->product)->name ?? $item->product_name ?? '';
                $name = trim($rawName);
                if (strpos($name, '(') !== false) {
                    $name = trim(substr($name, 0, strpos($name, '(')));
                }
                if (empty($name))
                    return '';
                $unitPrice = $item->unit_price ?? 0;
                $quantity = $item->quantity ?? 0;
                return "{$name} سعر {$unitPrice} العدد {$quantity}";
            })->filter();

            $totalQuantity = $order->items->sum('quantity');
            $goodsType = $productParts->implode(' - ') ?: 'بضاعة متنوعة';

            $alwaseetData = [
                'customer_name' => $order->customer_name,
                'customer_phone' => $order->customer_phone,
                'city_id' => $order->alwaseet_city_id,
                'region_id' => $order->alwaseet_region_id,
                'merchant_notes' => $order->notes,
                'location' => $order->customer_address,
                'price' => $totalPrice, // السعر شامل التوصيل
                'goods_type' => $goodsType,
                'quantity' => $totalQuantity,
                'package_size_id' => $normalPackageSize['id'],
            ];

            $response = $this->alWaseetService->createOrder($alwaseetData);

            if (isset($response['id'])) {
                $shipment = AlWaseetShipment::updateOrCreate(
                    ['order_id' => $order->id],
                    [
                        'alwaseet_order_id' => $response['id'],
                        'pickup_id' => $response['pickup_id'] ?? null,
                        'qr_id' => $response['qr_id'] ?? null,
                        'qr_link' => $response['qr_link'] ?? null,
                        'status' => 'جديد',
                        'status_id' => '1',
                        'synced_at' => now(),
                    ]
                );

                return response()->json([
                    'success' => true,
                    'message' => 'تم إرسال الطلب للواسط بنجاح',
                    'data' => $shipment
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'فشل إرسال الطلب للواسط: ' . ($response['message'] ?? 'خطأ غير معروف'),
                'response' => $response
            ], 500);

        } catch (\Exception $e) {
            Log::error('AlWaseetApiController@sendToAlWaseet error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'حدث خطأ أثناء الإرسال', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Confirm order (Muqayyad).
     */
    public function confirmOrder(Request $request, Order $order)
    {
        if (!Auth::user()->isAdmin()) {
            return response()->json(['success' => false, 'message' => 'غير مصرح.'], 403);
        }

        if ($order->status !== 'pending') {
            return response()->json(['success' => false, 'message' => 'لا يمكن تقييد الطلبات المقيدة بالأساس'], 400);
        }

        try {
            DB::transaction(function () use ($order) {
                $deliveryFee = Setting::getDeliveryFee();
                $profitMargin = Setting::getProfitMargin();

                $deliveryCode = $order->delivery_code;
                $shipment = $order->alwaseetShipment;

                if ($shipment && isset($shipment->qr_id) && !empty($shipment->qr_id) && trim((string) $shipment->qr_id) !== '') {
                    $deliveryCode = (string) $shipment->qr_id;
                } elseif (!$deliveryCode && $shipment && isset($shipment->alwaseet_order_id) && !empty($shipment->alwaseet_order_id)) {
                    $deliveryCode = (string) $shipment->alwaseet_order_id;
                }

                $order->update([
                    'status' => 'confirmed',
                    'confirmed_at' => now(),
                    'confirmed_by' => auth()->id(),
                    'delivery_code' => $deliveryCode,
                    'delivery_fee_at_confirmation' => $deliveryFee,
                    'profit_margin_at_confirmation' => $profitMargin,
                ]);

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
                        'notes' => "تقييد طلب #{$order->order_number} (موبايل)"
                    ]);
                }

                $profitCalculator = new ProfitCalculator();
                $profitCalculator->recordOrderProfit($order);
            });

            return response()->json(['success' => true, 'message' => 'تم تقييد الطلب بنجاح']);
        } catch (\Exception $e) {
            Log::error('AlWaseetApiController@confirmOrder error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'فشل تقييد الطلب', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Get materials list for prepare-orders page.
     */
    public function getMaterialsList(Request $request)
    {
        $user = Auth::user();
        if (!$user || (!$user->isAdmin() && !$user->isSupplier())) {
            return response()->json(['success' => false, 'message' => 'غير مصرح.'], 403);
        }

        try {
            $query = Order::where('status', 'pending');

            if ($user->isSupplier()) {
                $accessibleWarehouseIds = $user->warehouses->pluck('id')->toArray();
                $query->whereIn('id', function ($subQuery) use ($accessibleWarehouseIds) {
                    $subQuery->select('order_id')->from('order_items')->join('products', 'order_items.product_id', '=', 'products.id')->whereIn('products.warehouse_id', $accessibleWarehouseIds);
                });
            }

            $this->applyPrintFilters($query, $request);

            $orders = $query->with([
                'delegate',
                'items.product.primaryImage',
                'items.product.warehouse',
                'alwaseetShipment'
            ])->get();

            // Filter items by warehouse permissions
            foreach ($orders as $order) {
                $order->items = $order->items->filter(function ($item) use ($request, $user) {
                    if (!$item->product)
                        return false;
                    if ($request->filled('warehouse_id') && $item->product->warehouse_id != $request->warehouse_id)
                        return false;
                    if ($user->isSupplier()) {
                        $accessibleWarehouseIds = $user->warehouses->pluck('id')->toArray();
                        if (!in_array($item->product->warehouse_id, $accessibleWarehouseIds))
                            return false;
                    }
                    return true;
                });
            }

            $orders = $orders->filter(fn($o) => $o->items->count() > 0)->values();

            return response()->json(['success' => true, 'data' => $orders]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'فشل جلب قائمة المواد', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Get materials list grouped by product code (Picking List).
     */
    public function getMaterialsListGrouped(Request $request)
    {
        $user = Auth::user();
        if (!$user || (!$user->isAdmin() && !$user->isSupplier())) {
            return response()->json(['success' => false, 'message' => 'غير مصرح.'], 403);
        }

        try {
            $query = Order::where('status', 'pending');
            if ($user->isSupplier()) {
                $accessibleWarehouseIds = $user->warehouses->pluck('id')->toArray();
                $query->whereIn('id', function ($subQuery) use ($accessibleWarehouseIds) {
                    $subQuery->select('order_id')->from('order_items')->join('products', 'order_items.product_id', '=', 'products.id')->whereIn('products.warehouse_id', $accessibleWarehouseIds);
                });
            }

            $this->applyPrintFilters($query, $request);

            $orders = $query->with(['items.product.primaryImage', 'items.product.warehouse'])->get();

            $materialsGrouped = [];
            foreach ($orders as $order) {
                foreach ($order->items as $item) {
                    if (!$item->product)
                        continue;
                    if ($request->filled('warehouse_id') && $item->product->warehouse_id != $request->warehouse_id)
                        continue;
                    if ($user->isSupplier()) {
                        $accessibleWarehouseIds = $user->warehouses->pluck('id')->toArray();
                        if (!in_array($item->product->warehouse_id, $accessibleWarehouseIds))
                            continue;
                    }

                    $productCode = $item->product->code;
                    $sizeKey = $item->size_name ?? 'no_size';

                    if (!isset($materialsGrouped[$productCode])) {
                        $materialsGrouped[$productCode] = [
                            'product' => $item->product,
                            'sizes' => []
                        ];
                    }

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

            $materials = [];
            ksort($materialsGrouped);
            foreach ($materialsGrouped as $productCode => $group) {
                ksort($group['sizes']);
                foreach ($group['sizes'] as $sizeData) {
                    $materials[] = [
                        'product' => $group['product'],
                        'product_code' => $productCode,
                        'size_name' => $sizeData['size_name'],
                        'total_quantity' => $sizeData['total_quantity'],
                        'orders' => $sizeData['orders']
                    ];
                }
            }

            return response()->json(['success' => true, 'data' => $materials]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'فشل جلب قائمة المواد المجمعة', 'error' => $e->getMessage()], 500);
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

    private function applyPrintFilters($query, Request $request)
    {
        // Reuse basic filters from track logic
        $this->applyTrackFilters($query, $request);

        // Additional Print & Upload specific filters
        if ($request->filled('size_reviewed')) {
            $query->where('size_reviewed', $request->size_reviewed);
        }

        if ($request->filled('message_confirmed')) {
            $query->where('message_confirmed', $request->message_confirmed);
        }

        if ($request->filled('alwaseet_sent')) {
            if ($request->alwaseet_sent === 'sent') {
                $query->whereHas('alwaseetShipment');
            } elseif ($request->alwaseet_sent === 'not_sent') {
                $query->whereDoesntHave('alwaseetShipment');
            }
        }

        if ($request->filled('alwaseet_complete')) {
            if ($request->alwaseet_complete === 'complete') {
                $query->whereNotNull('alwaseet_city_id')
                    ->whereNotNull('alwaseet_region_id')
                    ->where('alwaseet_city_id', '!=', '')
                    ->where('alwaseet_region_id', '!=', '');
            } elseif ($request->alwaseet_complete === 'incomplete') {
                $query->where(function ($q) {
                    $q->whereNull('alwaseet_city_id')
                        ->orWhere('alwaseet_city_id', '=', '')
                        ->orWhereNull('alwaseet_region_id')
                        ->orWhere('alwaseet_region_id', '=', '');
                });
            }
        }

        // Time filters
        if ($request->filled('time_from')) {
            $dateFrom = $request->date_from ?? now()->format('Y-m-d');
            $query->where('created_at', '>=', $dateFrom . ' ' . $request->time_from . ':00');
        }

        if ($request->filled('time_to')) {
            $dateTo = $request->date_to ?? now()->format('Y-m-d');
            $query->where('created_at', '<=', $dateTo . ' ' . $request->time_to . ':00');
        }
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
