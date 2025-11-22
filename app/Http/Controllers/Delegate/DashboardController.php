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
        // محاولة قراءة cart_id من request أولاً (من localStorage)
        $cartId = $request->input('cart_id');

        // إذا لم تكن موجودة في request، جرب session (للتوافق مع الكود القديم)
        if (!$cartId) {
            $cartId = session('current_cart_id');
        }

        $activeOrder = $cartId
            ? Cart::with('items')->find($cartId)
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
