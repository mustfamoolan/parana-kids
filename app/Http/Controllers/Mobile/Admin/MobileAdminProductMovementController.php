<?php

namespace App\Http\Controllers\Mobile\Admin;

use App\Http\Controllers\Controller;
use App\Models\ProductMovement;
use App\Models\Warehouse;
use App\Models\User;
use Illuminate\Http\Request;

class MobileAdminProductMovementController extends Controller
{
    /**
     * Fetch all product movements (orders + manual warehouse ops) with filters.
     */
    public function index(Request $request)
    {
        $query = ProductMovement::with(['product', 'size', 'warehouse', 'order', 'user']);

        // Warehouse filter
        if ($request->filled('warehouse_id')) {
            $query->byWarehouse($request->warehouse_id);
        }

        // Product filter
        if ($request->filled('product_id')) {
            $query->byProduct($request->product_id);
        }

        // Size filter
        if ($request->filled('size_id')) {
            $query->bySize($request->size_id);
        }

        // Product search
        if ($request->filled('product_search')) {
            $productSearch = $request->product_search;
            $query->whereHas('product', function ($q) use ($productSearch) {
                $q->where(function ($subQ) use ($productSearch) {
                    $subQ->where('name', 'like', '%' . $productSearch . '%')
                        ->orWhere('code', 'like', '%' . $productSearch . '%');
                });
            });
        }

        // Movement type filter
        if ($request->filled('movement_type')) {
            $movementType = $request->movement_type;
            if ($movementType === 'sale') {
                $query->whereIn('movement_type', ['sale', 'sell']);
            } else {
                $query->byMovementType($movementType);
            }
        }

        // Returns only filter
        if ($request->filled('show_returns_only') && $request->show_returns_only == '1') {
            $query->whereIn('movement_type', ['return', 'cancel', 'delete', 'return_bulk', 'return_exchange_bulk', 'partial_return']);
        }

        // User filter
        if ($request->filled('user_id')) {
            $query->byUser($request->user_id);
        }

        // Source type filter
        if ($request->filled('source_type')) {
            if ($request->source_type === 'order') {
                $query->whereNotNull('order_id');
            } elseif ($request->source_type === 'manual') {
                $query->whereNull('order_id');
            }
        }

        // Date filter
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        // Time filter
        if ($request->filled('time_from')) {
            $dateFrom = $request->date_from ?? ($request->date_to ?? now()->format('Y-m-d'));
            $query->where('created_at', '>=', $dateFrom . ' ' . $request->time_from . ':00');
        }

        if ($request->filled('time_to')) {
            $dateTo = $request->date_to ?? ($request->date_from ?? now()->format('Y-m-d'));
            $query->where('created_at', '<=', $dateTo . ' ' . $request->time_to . ':00');
        }

        // Supplier specific warehouses
        if (auth()->user()->isSupplier()) {
            $warehouseIds = auth()->user()->warehouses->pluck('id')->toArray();
            $query->whereIn('warehouse_id', $warehouseIds);
        }

        $perPage = $request->input('per_page', 20);
        $movements = $query->latest()->paginate($perPage);

        // Fast statistics
        $stats = [
            'total_additions' => ProductMovement::byMovementType('add')->sum('quantity'),
            'total_sales' => abs(ProductMovement::byMovementType('sale')->sum('quantity')),
            'total_returns' => ProductMovement::whereIn('movement_type', ['return', 'cancel', 'delete'])->sum('quantity'),
            'order_movements' => ProductMovement::whereNotNull('order_id')->count(),
            'manual_movements' => ProductMovement::whereNull('order_id')->count(),
        ];

        // Filter metadata
        if (auth()->user()->isAdmin()) {
            $warehouses = Warehouse::select(['id', 'name'])->get();
        } else {
            $warehouses = auth()->user()->warehouses()->select(['warehouses.id', 'warehouses.name'])->get();
        }

        $users = User::select(['id', 'name'])->get();

        $movementTypes = [
            ['id' => 'add', 'name' => 'إضافة'],
            ['id' => 'sale', 'name' => 'بيع'],
            ['id' => 'confirm', 'name' => 'تقييد'],
            ['id' => 'delete', 'name' => 'حذف'],
        ];

        $sourceTypes = [
            ['id' => 'order', 'name' => 'طلب'],
            ['id' => 'manual', 'name' => 'إدارة المخزن'],
        ];

        return response()->json([
            'status' => 'success',
            'data' => [
                'movements' => $movements,
                'stats' => $stats,
                'filters' => [
                    'warehouses' => $warehouses,
                    'users' => $users,
                    'movement_types' => $movementTypes,
                    'source_types' => $sourceTypes,
                ]
            ]
        ]);
    }
}
