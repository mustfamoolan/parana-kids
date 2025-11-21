<?php

namespace App\Http\Controllers\Delegate;

use App\Http\Controllers\Controller;
use App\Models\ArchivedOrder;
use App\Models\Cart;
use App\Models\Order;
use App\Models\Setting;
use App\Services\NotificationService;
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

        // عدد الرسائل غير المقروءة
        $notificationService = new NotificationService();
        $unreadMessagesCount = $notificationService->getUnreadCount(auth()->id(), 'message');

        // جلب بيانات البنر النصي للداشبورد
        $dashboardBannerEnabled = Setting::getValue('dashboard_banner_enabled', '0') === '1';
        $dashboardBannerText = Setting::getValue('dashboard_banner_text', '');

        return view('delegate.dashboard', compact('activeOrder', 'stats', 'unreadMessagesCount', 'dashboardBannerEnabled', 'dashboardBannerText'));
    }
}
