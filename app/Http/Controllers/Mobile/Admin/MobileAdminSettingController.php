<?php

namespace App\Http\Controllers\Mobile\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class MobileAdminSettingController extends Controller
{
    /**
     * Get all settings
     */
    public function index()
    {
        try {
            $settings = [
                'delivery_fee' => Setting::getDeliveryFee(),
                'profit_margin' => Setting::getProfitMargin(),
                'alwaseet_merchant_notes' => Setting::getValue('alwaseet_merchant_notes', ''),

                // Floating Banner
                'floating_banner_enabled' => Setting::getValue('floating_banner_enabled', '0') === '1',
                'floating_banner_title' => Setting::getValue('floating_banner_title', ''),
                'floating_banner_text' => Setting::getValue('floating_banner_text', ''),
                'floating_banner_image' => Setting::getValue('floating_banner_image', ''),
                'floating_banner_image_url' => Setting::getValue('floating_banner_image', '') ? url('storage/' . Setting::getValue('floating_banner_image')) : null,
                'floating_banner_icon' => Setting::getValue('floating_banner_icon', 'info'),

                // Dashboard Banner
                'dashboard_banner_enabled' => Setting::getValue('dashboard_banner_enabled', '0') === '1',
                'dashboard_banner_text' => Setting::getValue('dashboard_banner_text', ''),
            ];

            return response()->json([
                'success' => true,
                'data' => $settings
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch settings: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update general settings
     */
    public function update(Request $request)
    {
        $request->validate([
            'delivery_fee' => 'required|numeric|min:0',
            'profit_margin' => 'nullable|numeric|min:0',
            'alwaseet_merchant_notes' => 'nullable|string|max:1000',
        ]);

        try {
            Setting::setValue('delivery_fee', $request->delivery_fee, 'سعر التوصيل بالدينار العراقي');
            Setting::setValue('profit_margin', $request->profit_margin ?? 0, 'ربح الفروقات بالدينار العراقي');
            Setting::setValue('alwaseet_merchant_notes', $request->alwaseet_merchant_notes ?? '', 'ملاحظة التاجر للواسط');

            return response()->json([
                'success' => true,
                'message' => 'Settings updated successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update settings: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update promotional banner
     */
    public function updateBanner(Request $request)
    {
        $request->validate([
            'banner_title' => 'required|string|max:255',
            'banner_text' => 'nullable|string|max:1000',
            'banner_image' => 'nullable|image|mimes:jpeg,jpg,png|max:2048',
            'banner_icon' => 'required|in:success,error,warning,info,question,campaign',
        ]);

        try {
            Setting::setValue('floating_banner_title', $request->banner_title, 'عنوان البنر الإعلاني');
            Setting::setValue('floating_banner_text', $request->banner_text ?? '', 'نص البنر الإعلاني');
            Setting::setValue('floating_banner_icon', $request->banner_icon, 'أيقونة البنر الإعلاني');

            if ($request->hasFile('banner_image')) {
                if (!Storage::disk('public')->exists('banners')) {
                    Storage::disk('public')->makeDirectory('banners');
                }

                $oldImage = Setting::getValue('floating_banner_image', '');
                if ($oldImage && Storage::disk('public')->exists($oldImage)) {
                    Storage::disk('public')->delete($oldImage);
                }

                $image = $request->file('banner_image');
                $path = $image->storeAs('banners', 'banner_' . time() . '.' . $image->getClientOriginalExtension(), 'public');
                Setting::setValue('floating_banner_image', $path, 'صورة البنر الإعلاني');
            }

            return response()->json([
                'success' => true,
                'message' => 'Banner updated successfully',
                'data' => [
                    'floating_banner_title' => Setting::getValue('floating_banner_title', ''),
                    'floating_banner_text' => Setting::getValue('floating_banner_text', ''),
                    'floating_banner_image' => Setting::getValue('floating_banner_image', ''),
                    'floating_banner_image_url' => Setting::getValue('floating_banner_image', '') ? url('storage/' . Setting::getValue('floating_banner_image')) : null,
                    'floating_banner_icon' => Setting::getValue('floating_banner_icon', 'info'),
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update banner: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Toggle floating banner
     */
    public function toggleBanner(Request $request)
    {
        $enabled = $request->input('enabled', false);
        try {
            Setting::setValue('floating_banner_enabled', $enabled ? '1' : '0', 'حالة تفعيل البنر الإعلاني');
            return response()->json([
                'success' => true,
                'message' => $enabled ? 'Banner enabled' : 'Banner disabled',
                'enabled' => $enabled
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to toggle banner: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update dashboard text banner
     */
    public function updateDashboardBanner(Request $request)
    {
        $request->validate([
            'dashboard_banner_text' => 'required|string|max:500',
        ]);

        try {
            Setting::setValue('dashboard_banner_text', $request->dashboard_banner_text, 'نص البنر النصي في الداشبورد');
            return response()->json([
                'success' => true,
                'message' => 'Dashboard banner updated successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update dashboard banner: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Toggle dashboard banner
     */
    public function toggleDashboardBanner(Request $request)
    {
        $enabled = $request->input('enabled', false);
        try {
            Setting::setValue('dashboard_banner_enabled', $enabled ? '1' : '0', 'حالة تفعيل البنر النصي في الداشبورد');
            return response()->json([
                'success' => true,
                'message' => $enabled ? 'Dashboard banner enabled' : 'Dashboard banner disabled',
                'enabled' => $enabled
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to toggle dashboard banner: ' . $e->getMessage()
            ], 500);
        }
    }
}
