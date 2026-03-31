<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Support\Facades\Storage;

class StoreSettingApiController extends Controller
{
    /**
     * Get Store App Settings for the mobile app (Home & Login Screens)
     */
    public function getSettings()
    {
        // 1. صور الدخول
        $loginImages = [];
        for ($i = 1; $i <= 4; $i++) {
            $imgPath = Setting::getValue('app_login_image_' . $i, '');
            if (!empty($imgPath)) {
                $loginImages[] = url(Storage::url($imgPath));
            }
        }

        // 2. سلايدر الرئيسية
        $sliderImagesJson = Setting::getValue('app_home_slider_images', '[]');
        $sliderImagesList = json_decode($sliderImagesJson, true);
        $sliderImages = [];
        if (is_array($sliderImagesList)) {
            foreach ($sliderImagesList as $img) {
                if (!empty($img)) {
                    $sliderImages[] = url(Storage::url($img));
                }
            }
        }

        // 3. بانر الأصناف
        $announcementTitle = Setting::getValue('app_home_announcement_title', '');
        $announcementSubtitle = Setting::getValue('app_home_announcement_subtitle', '');
        $announcementImagePath = Setting::getValue('app_home_announcement_image', '');
        $announcementImage = !empty($announcementImagePath) ? url(Storage::url($announcementImagePath)) : '';

        // 4. المخازن المسموحة للعملاء
        $allowedWarehousesJson = Setting::getValue('app_customer_allowed_warehouses', '[]');
        $customerAllowedWarehouses = json_decode($allowedWarehousesJson, true);
        if (!is_array($customerAllowedWarehouses)) {
            $customerAllowedWarehouses = [];
        }

        // 5. الأقسام (Categories)
        $categories = [
            ['id' => 'all', 'name' => 'الكل'],
            ['id' => 'girls', 'name' => 'بناتي'],
            ['id' => 'boys', 'name' => 'ولادي'],
            ['id' => 'boys_girls', 'name' => 'ولادي بناتي'],
            ['id' => 'accessories', 'name' => 'إكسسوارات'],
        ];

        // بناء الرد
        return response()->json([
            'success' => true,
            'data' => [
                'login_images' => $loginImages,
                'home_slider' => $sliderImages,
                'announcement' => [
                    'enabled' => !empty($announcementTitle) || !empty($announcementImage),
                    'title' => $announcementTitle,
                    'subtitle' => $announcementSubtitle,
                    'image' => $announcementImage,
                ],
                'customer_allowed_warehouses' => $customerAllowedWarehouses,
                'categories' => $categories,
            ]
        ]);
    }
}
