<?php

namespace App\Http\Controllers\Mobile\Delegate;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;

class MobileDelegateDashboardController extends Controller
{
    /**
     * Get dashboard data for delegate mobile app
     * including banners and other settings
     */
    public function index(Request $request)
    {
        // Get Dashboard Text Banner Settings
        $dashboardBannerEnabled = Setting::getValue('dashboard_banner_enabled', '0') === '1';
        $dashboardBannerText = Setting::getValue('dashboard_banner_text', '');

        // Get Floating Banner Settings
        $floatingBannerEnabled = Setting::getValue('floating_banner_enabled', '0') === '1';
        $floatingBannerTitle = Setting::getValue('floating_banner_title', '');
        $floatingBannerText = Setting::getValue('floating_banner_text', '');
        $floatingBannerImage = Setting::getValue('floating_banner_image', '');
        $floatingBannerIcon = Setting::getValue('floating_banner_icon', 'info');

        // Construct full image URL if exists
        $floatingBannerImageUrl = null;
        if ($floatingBannerImage && !empty($floatingBannerImage)) {
            $floatingBannerImageUrl = asset('storage/' . $floatingBannerImage);
        }

        return response()->json([
            'success' => true,
            'message' => 'تم جلب بيانات الداشبورد بنجاح',
            'data' => [
                'text_banner' => [
                    'enabled' => $dashboardBannerEnabled,
                    'text' => $dashboardBannerText,
                ],
                'floating_banner' => [
                    'enabled' => $floatingBannerEnabled,
                    'title' => $floatingBannerTitle,
                    'text' => $floatingBannerText,
                    'image' => $floatingBannerImage,
                    'image_url' => $floatingBannerImageUrl,
                    'icon' => $floatingBannerIcon,
                ],
            ],
        ]);
    }
}
