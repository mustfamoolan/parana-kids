<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BannerController extends Controller
{
    /**
     * جلب البنر النشط
     */
    public function getActiveBanner(Request $request)
    {
        // البنر يظهر فقط للمندوبين
        $user = Auth::user();
        if (!$user || !$user->isDelegate()) {
            return response()->json([
                'success' => true,
                'active' => false,
            ]);
        }

        $enabled = Setting::getValue('floating_banner_enabled', '0') === '1';

        if (!$enabled) {
            return response()->json([
                'success' => true,
                'active' => false,
            ]);
        }

        $bannerTitle = Setting::getValue('floating_banner_title', '');
        $bannerText = Setting::getValue('floating_banner_text', '');
        $bannerImage = Setting::getValue('floating_banner_image', '');
        $bannerIcon = Setting::getValue('floating_banner_icon', 'info');

        // إذا لم يكن هناك عنوان، البنر غير نشط
        if (empty($bannerTitle)) {
            return response()->json([
                'success' => true,
                'active' => false,
            ]);
        }

        // بناء رابط الصورة إن وجدت
        $imageUrl = null;
        if ($bannerImage) {
            $imageUrl = asset('storage/' . $bannerImage);
        }

        return response()->json([
            'success' => true,
            'active' => true,
            'banner' => [
                'title' => $bannerTitle,
                'text' => $bannerText,
                'image' => $imageUrl,
                'icon' => $bannerIcon,
            ],
        ]);
    }

    /**
     * إغلاق البنر (للمندوبين)
     */
    public function dismissBanner(Request $request)
    {
        // حفظ حالة الإغلاق في localStorage على جانب العميل
        // هذا الـ endpoint موجود فقط للتوافق، لكن الحالة تُحفظ في localStorage

        return response()->json([
            'success' => true,
            'message' => 'تم إغلاق البنر',
        ]);
    }

    /**
     * جلب البنر النصي للداشبورد
     */
    public function getDashboardBanner(Request $request)
    {
        // البنر يظهر فقط للمندوبين
        $user = Auth::user();
        if (!$user || !$user->isDelegate()) {
            return response()->json([
                'success' => true,
                'active' => false,
            ]);
        }

        $enabled = Setting::getValue('dashboard_banner_enabled', '0') === '1';
        $bannerText = Setting::getValue('dashboard_banner_text', '');

        if (!$enabled || empty($bannerText)) {
            return response()->json([
                'success' => true,
                'active' => false,
            ]);
        }

        return response()->json([
            'success' => true,
            'active' => true,
            'text' => $bannerText,
        ]);
    }
}

