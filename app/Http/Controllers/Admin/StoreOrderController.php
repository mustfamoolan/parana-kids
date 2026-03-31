<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Warehouse;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class StoreOrderController extends Controller
{
    /**
     * Display a listing of orders from the store app.
     */
    public function index(Request $request)
    {
        // Require admin or supplier role
        if (!Auth::user()->isAdmin() && !Auth::user()->isSupplier()) {
            abort(403);
        }

        // 1. Get filters data
        if (Auth::user()->isSupplier()) {
            $warehouses = Auth::user()->warehouses;
        } else {
            $warehouses = Warehouse::all();
        }

        $suppliers = User::whereIn('role', ['admin', 'supplier'])->get();
        $delegates = User::where('role', 'delegate')->get();

        // 2. Build Query
        $query = Order::query()->where('source', 'store');

        // Supplier restriction
        if (Auth::user()->isSupplier()) {
            $accessibleWarehouseIds = Auth::user()->warehouses->pluck('id')->toArray();
            $query->whereHas('items.product', function ($q) use ($accessibleWarehouseIds) {
                $q->whereIn('warehouse_id', $accessibleWarehouseIds);
            });
        }

        // Apply same filters as management
        $this->applyFilters($query, $request);

        // Load relations
        $query->with(['delegate', 'customer', 'items.product.warehouse', 'items.product.primaryImage', 'alwaseetShipment']);

        // Paginate
        $perPage = $request->input('per_page', 15);
        $orders = $query->latest()->paginate($perPage)->appends($request->except('page'));

        // Totals (Simplified for this view)
        $pendingTotalAmount = Order::where('source', 'store')->where('status', 'pending')->sum('total_amount');
        $confirmedTotalAmount = Order::where('source', 'store')->where('status', 'confirmed')->sum('total_amount');

        return view('admin.store_orders.index', compact(
            'orders', 'warehouses', 'suppliers', 'delegates', 
            'pendingTotalAmount', 'confirmedTotalAmount'
        ));
    }

    /**
     * Reuse filter logic
     */
    private function applyFilters($query, Request $request)
    {
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        } else {
            $query->whereIn('status', ['pending', 'confirmed']);
        }

        if ($request->filled('warehouse_id')) {
            $query->whereHas('items.product', function ($q) use ($request) {
                $q->where('warehouse_id', $request->warehouse_id);
            });
        }

        if ($request->filled('search')) {
            $searchTerm = $request->search;
            $query->where(function ($q) use ($searchTerm) {
                $q->where('order_number', 'like', "%{$searchTerm}%")
                    ->orWhere('customer_name', 'like', "%{$searchTerm}%")
                    ->orWhere('customer_phone', 'like', "%{$searchTerm}%")
                    ->orWhere('customer_address', 'like', "%{$searchTerm}%");
            });
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }
    }
}
