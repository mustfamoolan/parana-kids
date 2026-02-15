<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\Product;
use App\Models\ProductSize;
use App\Models\ProductMovement;
use App\Services\ProfitCalculator;
use App\Services\SweetAlertService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AdminOrderController extends Controller
{
    protected $sweetAlertService;
    protected $profitCalculator;

    public function __construct(SweetAlertService $sweetAlertService, ProfitCalculator $profitCalculator)
    {
        $this->sweetAlertService = $sweetAlertService;
        $this->profitCalculator = $profitCalculator;
    }

    /**
     * Get a list of orders with comprehensive search, filters, and statistics.
     */
    public function index(Request $request)
    {
        $user = Auth::user();

        // Security check
        if (!$user || (!$user->isAdmin() && !$user->isSupplier())) {
            return response()->json([
                'success' => false,
                'message' => 'غير مصرح.',
            ], 403);
        }

        try {
            $query = Order::query();

            // Permissions: Suppliers only see orders with products from their warehouses
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

            // Apply Filters (Same as d:\flutter\br\parana-kids\app\Http\Controllers\Admin\OrderController.php)
            $this->applyFilters($query, $request);

            // Fetch statistics (only for Admin)
            $statistics = null;
            if ($user->isAdmin()) {
                $statistics = $this->calculateUnifiedStatistics($request);
            }

            // Pagination
            $perPage = $request->input('per_page', 15);
            $orders = $query->with(['delegate', 'items.product.warehouse', 'confirmedBy'])
                ->orderByRaw('CASE WHEN deleted_at IS NOT NULL THEN deleted_at ELSE created_at END DESC')
                ->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => [
                    'orders' => $orders->items(),
                    'statistics' => $statistics,
                    'pagination' => [
                        'total' => $orders->total(),
                        'per_page' => $orders->perPage(),
                        'current_page' => $orders->currentPage(),
                        'last_page' => $orders->lastPage(),
                    ]
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('AdminOrderController@index error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء جلب البيانات.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get options for filters (warehouses, delegates, suppliers).
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

        return response()->json([
            'success' => true,
            'data' => [
                'warehouses' => $warehouses,
                'suppliers' => $suppliers,
                'delegates' => $delegates,
            ]
        ]);
    }

    /**
     * Common filter logic.
     */
    private function applyFilters($query, Request $request)
    {
        // Status filter
        if ($request->status === 'deleted') {
            $query->onlyTrashed()->whereNotNull('deleted_by')->whereNotNull('deletion_reason');
        } elseif ($request->filled('status') && in_array($request->status, ['pending', 'confirmed', 'returned'])) {
            $query->where('status', $request->status);
        } else {
            $query->withTrashed()->where(function ($q) {
                $q->where(function ($subQ) {
                    $subQ->whereNull('deleted_at')->whereIn('status', ['pending', 'confirmed']);
                })->orWhere(function ($subQ) {
                    $subQ->whereNotNull('deleted_at')->whereNotNull('deleted_by')->whereNotNull('deletion_reason');
                });
            });
        }

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('order_number', 'like', "%{$search}%")
                    ->orWhere('customer_name', 'like', "%{$search}%")
                    ->orWhere('customer_phone', 'like', "%{$search}%")
                    ->orWhere('customer_address', 'like', "%{$search}%")
                    ->orWhereHas('delegate', function ($dq) use ($search) {
                        $dq->where('name', 'like', "%{$search}%");
                    });
            });
        }

        // Other filters
        if ($request->filled('warehouse_id')) {
            $query->whereHas('items.product', function ($q) use ($request) {
                $q->where('warehouse_id', $request->warehouse_id);
            });
        }

        if ($request->filled('confirmed_by'))
            $query->where('confirmed_by', $request->confirmed_by);
        if ($request->filled('delegate_id'))
            $query->where('delegate_id', $request->delegate_id);
        if ($request->filled('size_reviewed'))
            $query->where('size_reviewed', $request->size_reviewed);
        if ($request->filled('message_confirmed'))
            $query->where('message_confirmed', $request->message_confirmed);

        // Date range
        if ($request->filled('date_from'))
            $query->whereDate('created_at', '>=', $request->date_from);
        if ($request->filled('date_to'))
            $query->whereDate('created_at', '<=', $request->date_to);
    }

    /**
     * Calculate unified statistics across statuses.
     */
    private function calculateUnifiedStatistics(Request $request)
    {
        $user = Auth::user();
        $accessibleWarehouseIds = $user->isSupplier() ? $user->warehouses->pluck('id')->toArray() : null;

        $stats = [
            'pending' => ['count' => 0, 'total' => 0, 'profit' => 0],
            'confirmed' => ['count' => 0, 'total' => 0, 'profit' => 0],
        ];

        foreach (['pending', 'confirmed'] as $status) {
            $q = Order::where('status', $status);

            if ($accessibleWarehouseIds) {
                $q->whereHas('items.product', function ($q2) use ($accessibleWarehouseIds) {
                    $q2->whereIn('warehouse_id', $accessibleWarehouseIds);
                });
            }

            // Apply standard filters to stats too
            if ($request->filled('warehouse_id')) {
                $filterWId = $request->warehouse_id;
                $q->whereHas('items.product', function ($q2) use ($filterWId) {
                    $q2->where('warehouse_id', $filterWId);
                });
            }
            // ... (Other filters could be applied here if needed)

            $orderIds = $q->pluck('id');
            $stats[$status]['count'] = $orderIds->count();

            if ($stats[$status]['count'] > 0) {
                $stats[$status]['total'] = DB::table('order_items')
                    ->whereIn('order_id', $orderIds)
                    ->sum('subtotal') ?? 0;

                $stats[$status]['profit'] = DB::table('order_items')
                    ->join('products', 'order_items.product_id', '=', 'products.id')
                    ->whereIn('order_items.order_id', $orderIds)
                    ->selectRaw('SUM((order_items.unit_price - COALESCE(products.purchase_price, 0)) * order_items.quantity) as total_profit')
                    ->value('total_profit') ?? 0;
            }
        }

        return $stats;
    }
}
