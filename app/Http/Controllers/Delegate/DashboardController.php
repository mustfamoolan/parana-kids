<?php

namespace App\Http\Controllers\Delegate;

use App\Http\Controllers\Controller;
use App\Models\ArchivedOrder;
use App\Models\Cart;
use App\Models\Order;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        $activeOrder = session('current_cart_id')
            ? Cart::with('items')->find(session('current_cart_id'))
            : null;

        $stats = [
            'pending_orders' => Order::where('delegate_id', auth()->id())
                                     ->where('status', 'pending')
                                     ->count(),
            'confirmed_orders' => Order::where('delegate_id', auth()->id())
                                       ->where('status', 'confirmed')
                                       ->count(),
            'archived_orders' => ArchivedOrder::where('delegate_id', auth()->id())->count(),
        ];

        return view('delegate.dashboard', compact('activeOrder', 'stats'));
    }
}
