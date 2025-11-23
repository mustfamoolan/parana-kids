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
    public function index(Request $request)
    {
        // قراءة cart_id من session
        $cartId = session('current_cart_id');

        $activeOrder = $cartId
            ? Cart::with('items')->find($cartId)
            : null;

        // تحضير customer_data من Cart إذا كان موجوداً
        $customerData = null;
        if ($activeOrder && $activeOrder->customer_name) {
            $customerData = [
                'customer_name' => $activeOrder->customer_name,
                'customer_phone' => $activeOrder->customer_phone,
                'customer_address' => $activeOrder->customer_address,
                'customer_social_link' => $activeOrder->customer_social_link,
                'notes' => $activeOrder->notes,
            ];
        }

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

        return view('delegate.dashboard', compact('activeOrder', 'stats', 'unreadMessagesCount', 'dashboardBannerEnabled', 'dashboardBannerText', 'customerData'));
    }
}
