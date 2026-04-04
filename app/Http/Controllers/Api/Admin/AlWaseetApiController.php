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

                // Create the base array from the model
                $orderData = $order->toArray();

                // Add calculated fields for easier mobile consumption
                $orderData['current_api_status'] = $apiStatus['status'] ?? ($shipment->status ?? 'غير معروف');
                $orderData['current_api_status_id'] = $apiStatus['status_id'] ?? ($shipment->status_id ?? null);

                // Construct pickup code
                $orderData['alwaseet_code'] = $apiStatus['pickup_id'] ?? ($shipment->pickup_id ?? ($shipment->qr_id ?? ($order->delivery_code ?? null)));

                // Add Timeline (Movements Log)
                if ($shipment) {
                    $statusTimeline = $shipment->statusHistory
                        ->sortBy('changed_at')
                        ->map(function ($history) use ($shipment) {
                            return [
                                'status_id' => $history->status_id,
                                'status_text' => $history->status_text,
                                'changed_at' => $history->changed_at ? $history->changed_at->toIso8601String() : null,
                                'is_current' => (string) $history->status_id === (string) $shipment->status_id,
                                'display_order' => $history->statusInfo ? $history->statusInfo->display_order : 999,
                            ];
                        })
                        ->values()
                        ->toArray();

                    // Attach to the shipment object in the order data
                    if (isset($orderData['alwaseet_shipment'])) {
                        $orderData['alwaseet_shipment']['status_timeline'] = $statusTimeline;
                    }
                }

                return $orderData;
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

            // 5. Calculate Statistics (Pending only)
            $statsQuery = Order::where('status', 'pending');
            // Apply same security filters to stats
            if ($user->isSupplier()) {
                $statsQuery->whereIn('id', function ($subQuery) use ($accessibleWarehouseIds) {
                    $subQuery->select('order_id')
                        ->from('order_items')
                        ->join('products', 'order_items.product_id', '=', 'products.id')
                        ->whereIn('products.warehouse_id', $accessibleWarehouseIds)
                        ->distinct();
                });
            }
            $this->applyPrintFilters($statsQuery, $request);

            $pendingCount = (clone $statsQuery)->count();

            // Calculate Total Pieces (Sum of quantities) and Total Items (Unique Products)
            $itemsStats = DB::table('order_items')
                ->whereIn('order_id', (clone $statsQuery)->select('id'))
                ->selectRaw('SUM(quantity) as total_pieces, COUNT(DISTINCT product_id) as total_items')
                ->first();

            $totalPiecesCount = (int) ($itemsStats->total_pieces ?? 0);
            $totalItemsCount = (int) ($itemsStats->total_items ?? 0);

            $sentOrdersCount = (clone $statsQuery)->whereHas('alwaseetShipment')->count();

            return response()->json([
                'success' => true,
                'data' => [
                    'orders' => $orders->getCollection(),
                    'pendingCount' => $pendingCount,
                    'totalPiecesCount' => $totalPiecesCount,
                    'totalItemsCount' => $totalItemsCount,
                    'sentOrdersCount' => $sentOrdersCount,
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
            $user = Auth::user();
            if (!$user || (!$user->isAdmin() && !$user->isSupplier())) {
                return response()->json(['success' => false, 'message' => 'غير مصرح.'], 403);
            }

            // Security Check for Suppliers
            if ($user->isSupplier()) {
                $accessibleWarehouseIds = $user->warehouses->pluck('id')->toArray();
                $orderHasAccessibleItems = DB::table('order_items')
                    ->join('products', 'order_items.product_id', '=', 'products.id')
                    ->where('order_items.order_id', $id)
                    ->whereIn('products.warehouse_id', $accessibleWarehouseIds)
                    ->exists();

                if (!$orderHasAccessibleItems) {
                    return response()->json(['success' => false, 'message' => 'غير مصرح لك بالتحكم في هذا الطلب.'], 403);
                }
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
                'client_name' => $order->customer_name,
                'client_mobile' => AlWaseetService::formatPhone($order->customer_phone),
                'city_id' => $order->alwaseet_city_id,
                'region_id' => $order->alwaseet_region_id,
                'merchant_notes' => $order->notes,
                'location' => $order->customer_address,
                'price' => $totalPrice, // السعر شامل التوصيل
                'type_name' => $goodsType,
                'items_number' => $totalQuantity,
                'package_size' => $normalPackageSize['id'],
            ];

            $response = $this->alWaseetService->createOrder($alwaseetData);

            if (isset($response['id'])) {
                // جلب بيانات الطلب الكاملة لضمان توفر كافة الحقول لقاعدة البيانات
                $fetchedOrders = $this->alWaseetService->getOrdersByIds([$response['id']]);
                $alwaseetOrder = !empty($fetchedOrders) ? $fetchedOrders[0] : null;

                $shipmentData = [
                    'alwaseet_order_id' => $response['id'],
                    'pickup_id' => $response['pickup_id'] ?? ($alwaseetOrder['pickup_id'] ?? null),
                    'qr_id' => $response['qr_id'] ?? ($alwaseetOrder['qr_id'] ?? null),
                    'qr_link' => $response['qr_link'] ?? ($alwaseetOrder['qr_link'] ?? null),
                    'client_name' => $alwaseetOrder['client_name'] ?? $order->customer_name,
                    'client_mobile' => $alwaseetOrder['client_mobile'] ?? AlWaseetService::formatPhone($order->customer_phone),
                    'client_mobile2' => $alwaseetOrder['client_mobile2'] ?? null,
                    'city_id' => $alwaseetOrder['city_id'] ?? $order->alwaseet_city_id,
                    'city_name' => $alwaseetOrder['city_name'] ?? '',
                    'region_id' => $alwaseetOrder['region_id'] ?? $order->alwaseet_region_id,
                    'region_name' => $alwaseetOrder['region_name'] ?? '',
                    'location' => $alwaseetOrder['location'] ?? $order->customer_address,
                    'price' => $alwaseetOrder['price'] ?? $totalPrice,
                    'delivery_price' => $alwaseetOrder['delivery_price'] ?? 0,
                    'package_size' => $alwaseetOrder['package_size'] ?? $normalPackageSize['id'],
                    'type_name' => $alwaseetOrder['type_name'] ?? $goodsType,
                    'items_number' => $alwaseetOrder['items_number'] ?? $totalQuantity,
                    'merchant_notes' => $alwaseetOrder['merchant_notes'] ?? $order->notes,
                    'status' => $alwaseetOrder['status'] ?? 'جديد',
                    'status_id' => $alwaseetOrder['status_id'] ?? '1',
                    'alwaseet_created_at' => isset($alwaseetOrder['created_at']) ? \Carbon\Carbon::parse($alwaseetOrder['created_at']) : now(),
                    'alwaseet_updated_at' => isset($alwaseetOrder['updated_at']) ? \Carbon\Carbon::parse($alwaseetOrder['updated_at']) : now(),
                    'synced_at' => now(),
                ];

                $shipment = AlWaseetShipment::updateOrCreate(
                    ['order_id' => $order->id],
                    $shipmentData
                );

                // حفظ كود التوصيل في الطلب نفسه لضمان ظهوره في كافة القوائم
                // نفضل qr_id ثم pickup_id
                $trackingCode = $shipment->qr_id ?: $shipment->pickup_id;
                if ($trackingCode) {
                    $order->update(['delivery_code' => $trackingCode]);
                }

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
        $user = Auth::user();
        if (!$user || (!$user->isAdmin() && !$user->isSupplier())) {
            return response()->json(['success' => false, 'message' => 'غير مصرح.'], 403);
        }

        // Security Check for Suppliers
        if ($user->isSupplier()) {
            $accessibleWarehouseIds = $user->warehouses->pluck('id')->toArray();
            $orderHasAccessibleItems = DB::table('order_items')
                ->join('products', 'order_items.product_id', '=', 'products.id')
                ->where('order_items.order_id', $order->id)
                ->whereIn('products.warehouse_id', $accessibleWarehouseIds)
                ->exists();

            if (!$orderHasAccessibleItems) {
                return response()->json(['success' => false, 'message' => 'غير مصرح لك بالتحكم في هذا الطلب.'], 403);
            }
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

                if ($shipment && isset($shipment->pickup_id) && !empty($shipment->pickup_id)) {
                    $deliveryCode = (string) $shipment->pickup_id;
                } elseif ($shipment && isset($shipment->qr_id) && !empty($shipment->qr_id)) {
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
     * Print all sent orders in a single PDF (Mobile API).
     */
    public function printAllOrders(Request $request)
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
                $query->whereIn('id', function ($subQuery) use ($accessibleWarehouseIds) {
                    $subQuery->select('order_id')->from('order_items')->join('products', 'order_items.product_id', '=', 'products.id')->whereIn('products.warehouse_id', $accessibleWarehouseIds);
                });
            }

            // Apply Filters (Same as listing)
            $this->applyPrintFilters($query, $request);

            // Get orders with shipments that have PDF links
            $orders = $query->whereHas('alwaseetShipment', function ($q) {
                $q->whereNotNull('qr_link')->where('qr_link', '!=', '');
            })->with('alwaseetShipment')->get();

            $qrLinks = $orders->pluck('alwaseetShipment.qr_link')->toArray();

            if (empty($qrLinks)) {
                return response()->json([
                    'success' => false,
                    'message' => 'لا توجد طلبات مرسلة لديها روابط طباعة للدمج',
                ], 400);
            }

            // Merge PDFs
            $mergedPdf = $this->alWaseetService->mergePdfs($qrLinks);

            // Return the PDF content
            return response($mergedPdf, 200)
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', 'attachment; filename="alwaseet-all-' . date('Y-m-d') . '.pdf"')
                ->header('Content-Length', strlen($mergedPdf));

        } catch (\Exception $e) {
            Log::error('AlWaseetApiController@printAllOrders error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'فشل دمج الملفات: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete an order (soft-delete) from tracking.
     */
    public function deleteOrder(Request $request, Order $order)
    {
        $user = Auth::user();
        if (!$user || (!$user->isAdmin() && !$user->isSupplier() && !$user->isPrivateSupplier())) {
            return response()->json(['success' => false, 'message' => 'غير مصرح.'], 403);
        }

        // Security Check for Suppliers
        if ($user->isSupplier() || $user->isPrivateSupplier()) {
            $accessibleWarehouseIds = $user->warehouses->pluck('id')->toArray();
            $orderHasAccessibleItems = DB::table('order_items')
                ->join('products', 'order_items.product_id', '=', 'products.id')
                ->where('order_items.order_id', $order->id)
                ->whereIn('products.warehouse_id', $accessibleWarehouseIds)
                ->exists();

            if (!$orderHasAccessibleItems) {
                return response()->json(['success' => false, 'message' => 'غير مصرح لك بالتحكم في هذا الطلب.'], 403);
            }
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

        if ($user->isSupplier() || $user->isPrivateSupplier()) {
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

    private function applyTrackFilters($query, Request $request, $excludeStatusAndSearch = false)
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
        if (!$excludeStatusAndSearch && $request->filled('api_status_id')) {
            $query->whereHas('alwaseetShipment', function ($q) use ($request) {
                $q->where('status_id', $request->api_status_id);
            });
        }

        if (!$excludeStatusAndSearch && $request->filled('search')) {
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
            if ($user->isSupplier() || $user->isPrivateSupplier()) {
                $accessibleWarehouseIds = $user->warehouses->pluck('id')->toArray();
                if (!empty($accessibleWarehouseIds)) {
                    $query->whereIn('id', function ($subQuery) use ($accessibleWarehouseIds) {
                        $subQuery->select('order_id')->from('order_items')->join('products', 'order_items.product_id', '=', 'products.id')->whereIn('products.warehouse_id', $accessibleWarehouseIds);
                    });
                }
            }

            $this->applyTrackFilters($query, $request, true);
            $orderIds = $query->pluck('id')->toArray();

            if (empty($orderIds))
                return [];

            $countsFromDb = AlWaseetShipment::whereIn('order_id', $orderIds)
                ->whereNotNull('status_id')
                ->selectRaw('status_id, COUNT(*) as count')
                ->groupBy('status_id')
                ->get()
                ->pluck('count', 'status_id')
                ->toArray();

            // Ensure all active statuses are included, even if count is 0
            $allStatuses = \App\Models\AlWaseetOrderStatus::where('is_active', true)->get();
            $resultCounts = [];
            foreach ($allStatuses as $status) {
                $sid = (string)$status->status_id;
                $resultCounts[$sid] = $countsFromDb[$sid] ?? $countsFromDb[(int)$sid] ?? 0;
            }

            return $resultCounts;
        });
    }

    /**
     * Get AlWaseet city options for mobile.
     */
    public function getCityOptions()
    {
        try {
            $cities = $this->alWaseetService->getCities();
            return response()->json(['success' => true, 'data' => $cities]);
        } catch (\Exception $e) {
            Log::error('AlWaseetApiController@getCityOptions error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'فشل جلب المحافظات', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Get AlWaseet region options for a given city.
     */
    public function getRegionOptions(Request $request)
    {
        $request->validate(['city_id' => 'required|string']);
        try {
            $regions = $this->alWaseetService->getRegions($request->city_id);
            return response()->json(['success' => true, 'data' => $regions]);
        } catch (\Exception $e) {
            Log::error('AlWaseetApiController@getRegionOptions error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'فشل جلب المناطق', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Update AlWaseet-specific order fields (city, region, statuses, time note).
     */
    public function updateOrderFields(Request $request, $id)
    {
        $user = Auth::user();
        if (!$user || (!$user->isAdmin() && !$user->isSupplier() && !$user->isPrivateSupplier())) {
            return response()->json(['success' => false, 'message' => 'غير مصرح.'], 403);
        }

        $order = Order::findOrFail($id);

        // Security Check for Suppliers
        if ($user->isSupplier() || $user->isPrivateSupplier()) {
            $accessibleWarehouseIds = $user->warehouses->pluck('id')->toArray();
            $orderHasAccessibleItems = DB::table('order_items')
                ->join('products', 'order_items.product_id', '=', 'products.id')
                ->where('order_items.order_id', $id)
                ->whereIn('products.warehouse_id', $accessibleWarehouseIds)
                ->exists();

            if (!$orderHasAccessibleItems) {
                return response()->json(['success' => false, 'message' => 'غير مصرح لك بتعديل هذا الطلب.'], 403);
            }
        }

        $request->validate([
            'alwaseet_city_id' => 'nullable|string',
            'alwaseet_region_id' => 'nullable|string',
            'alwaseet_delivery_time_note' => 'nullable|string|in:morning,noon,evening,urgent',
            'size_reviewed' => 'nullable|boolean',
            'message_confirmed' => 'nullable|boolean',
            'customer_social_link' => 'nullable|string|max:500',
        ]);

        try {
            $data = $request->only([
                'alwaseet_city_id',
                'alwaseet_region_id',
                'alwaseet_delivery_time_note',
                'size_reviewed',
                'message_confirmed',
                'customer_social_link',
            ]);

            // Only update provided fields
            $order->update(array_filter($data, fn($v) => !is_null($v)));

            return response()->json([
                'success' => true,
                'message' => 'تم تحديث البيانات بنجاح',
                'data' => $order->fresh()->only([
                    'alwaseet_city_id',
                    'alwaseet_region_id',
                    'alwaseet_delivery_time_note',
                    'size_reviewed',
                    'message_confirmed',
                    'customer_social_link',
                ]),
            ]);
        } catch (\Exception $e) {
            Log::error('AlWaseetApiController@updateOrderFields error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'فشل التحديث: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Get AlWaseet settings and connection status.
     */
    public function getSettings()
    {
        $user = Auth::user();
        if (!$user->isAdmin()) {
            return response()->json(['success' => false, 'message' => 'غير مصرح.'], 403);
        }

        $username = Setting::getValue('alwaseet_username');
        $password = Setting::getValue('alwaseet_password');
        $isConfigured = !empty($username) && !empty($password);

        $connectionStatus = ['success' => false, 'message' => 'غير متصل'];
        $accountType = null;

        if ($isConfigured) {
            try {
                $connectionStatus = $this->alWaseetService->testConnection();
                $accountType = $this->alWaseetService->getAccountType();
            } catch (\Exception $e) {
                $connectionStatus = ['success' => false, 'message' => $e->getMessage()];
            }
        }

        return response()->json([
            'success' => true,
            'data' => [
                'username' => $username,
                'password_set' => !empty($password),
                'is_configured' => $isConfigured,
                'connection_status' => $connectionStatus,
                'account_type' => $accountType,
            ]
        ]);
    }

    /**
     * Update AlWaseet credentials.
     */
    public function updateSettings(Request $request)
    {
        if (!Auth::user()->isAdmin()) {
            return response()->json(['success' => false, 'message' => 'غير مصرح.'], 403);
        }

        $request->validate([
            'username' => 'required|string',
            'password' => 'nullable|string',
        ]);

        try {
            Setting::setValue('alwaseet_username', $request->username);
            if ($request->filled('password')) {
                Setting::setValue('alwaseet_password', $request->password);
                $this->alWaseetService->clearToken();
            }

            $test = $this->alWaseetService->testConnection();
            $accountType = null;
            if ($test['success']) {
                $accountType = $this->alWaseetService->getAccountType();
            }

            return response()->json([
                'success' => true,
                'message' => $test['success'] ? 'تم التحديث والاتصال بنجاح' : 'تم حفظ الإعدادات ولكن فشل الاتصال: ' . $test['message'],
                'data' => [
                    'connection_status' => $test,
                    'account_type' => $accountType,
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'فشل التحديث: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Reconnect to AlWaseet (refresh token).
     */
    public function reconnect()
    {
        if (!Auth::user()->isAdmin()) {
            return response()->json(['success' => false, 'message' => 'غير مصرح.'], 403);
        }

        try {
            $this->alWaseetService->clearToken();
            $test = $this->alWaseetService->testConnection();
            $accountType = null;
            if ($test['success']) {
                $accountType = $this->alWaseetService->getAccountType();
            }

            return response()->json([
                'success' => true,
                'message' => $test['success'] ? 'تم إعادة الاتصال بنجاح' : 'فشل إعادة الاتصال: ' . $test['message'],
                'data' => [
                    'connection_status' => $test,
                    'account_type' => $accountType,
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'فشل إعادة الاتصال: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Logout from AlWaseet (clear token).
     */
    public function logoutAlWaseet()
    {
        if (!Auth::user()->isAdmin()) {
            return response()->json(['success' => false, 'message' => 'غير مصرح.'], 403);
        }

        try {
            $this->alWaseetService->clearToken();
            return response()->json(['success' => true, 'message' => 'تم تسجيل الخروج بنجاح']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'فشل تسجيل الخروج: ' . $e->getMessage()], 500);
        }
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
