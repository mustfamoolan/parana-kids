<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class SettingController extends Controller
{
    /**
     * عرض صفحة الإعدادات
     */
    public function index()
    {
        $deliveryFee = Setting::getDeliveryFee();
        $profitMargin = Setting::getProfitMargin();

        // جلب بيانات البنر
        $bannerEnabled = Setting::getValue('floating_banner_enabled', '0') === '1';
        $bannerTitle = Setting::getValue('floating_banner_title', '');
        $bannerText = Setting::getValue('floating_banner_text', '');
        $bannerImage = Setting::getValue('floating_banner_image', '');
        $bannerIcon = Setting::getValue('floating_banner_icon', 'info');

        // جلب بيانات البنر النصي للداشبورد
        $dashboardBannerEnabled = Setting::getValue('dashboard_banner_enabled', '0') === '1';
        $dashboardBannerText = Setting::getValue('dashboard_banner_text', '');

        return view('admin.settings.index', compact('deliveryFee', 'profitMargin', 'bannerEnabled', 'bannerTitle', 'bannerText', 'bannerImage', 'bannerIcon', 'dashboardBannerEnabled', 'dashboardBannerText'));
    }

    /**
     * تحديث الإعدادات
     */
    public function update(Request $request)
    {
        $request->validate([
            'delivery_fee' => 'required|numeric|min:0',
            'profit_margin' => 'nullable|numeric|min:0',
        ]);

        Setting::setValue('delivery_fee', $request->delivery_fee, 'سعر التوصيل بالدينار العراقي');

        if ($request->filled('profit_margin')) {
            Setting::setValue('profit_margin', $request->profit_margin, 'ربح الفروقات بالدينار العراقي');
        } else {
            Setting::setValue('profit_margin', 0, 'ربح الفروقات بالدينار العراقي');
        }

        return redirect()->route('admin.settings.index')
                        ->with('success', 'تم تحديث الإعدادات بنجاح');
    }

    /**
     * تحديث صورة البروفايل
     */
    public function updateProfile(Request $request)
    {
        $request->validate([
            'profile_image' => 'nullable|image|mimes:jpeg,jpg,png|max:2048',
        ]);

        $user = auth()->user();

        if ($request->hasFile('profile_image')) {
            try {
                // التأكد من وجود المجلد قبل الحفظ باستخدام Storage facade
                if (!Storage::disk('public')->exists('profiles')) {
                    Storage::disk('public')->makeDirectory('profiles');
                }

                // حذف الصورة القديمة إن وجدت
                if ($user->profile_image) {
                    Storage::disk('public')->delete($user->profile_image);
                }

                // حفظ الصورة الجديدة
                $image = $request->file('profile_image');
                $path = $image->storeAs('profiles', $user->id . '_' . time() . '.' . $image->getClientOriginalExtension(), 'public');

                $user->profile_image = $path;
                $user->save();
            } catch (\Exception $e) {
                Log::error('Failed to upload profile image: ' . $e->getMessage());
                return back()->withErrors(['profile_image' => 'فشل رفع الصورة: ' . $e->getMessage()])->withInput();
            }
        }

        return redirect()->route('admin.settings.index')
                        ->with('success', 'تم تحديث صورة البروفايل بنجاح');
    }

    /**
     * تحديث البنر الإعلاني
     */
    public function updateBanner(Request $request)
    {
        $request->validate([
            'banner_title' => 'required|string|max:255',
            'banner_text' => 'nullable|string|max:1000',
            'banner_image' => 'nullable|image|mimes:jpeg,jpg,png|max:2048',
            'banner_icon' => 'required|in:success,error,warning,info,question',
        ]);

        try {
            // حفظ العنوان
            Setting::setValue('floating_banner_title', $request->banner_title, 'عنوان البنر الإعلاني');

            // حفظ النص
            Setting::setValue('floating_banner_text', $request->banner_text ?? '', 'نص البنر الإعلاني');

            // حفظ الأيقونة
            Setting::setValue('floating_banner_icon', $request->banner_icon, 'أيقونة البنر الإعلاني');

            // رفع الصورة إن وجدت
            if ($request->hasFile('banner_image')) {
                if (!Storage::disk('public')->exists('banners')) {
                    Storage::disk('public')->makeDirectory('banners');
                }

                // حذف الصورة القديمة إن وجدت
                $oldImage = Setting::getValue('floating_banner_image', '');
                if ($oldImage && Storage::disk('public')->exists($oldImage)) {
                    Storage::disk('public')->delete($oldImage);
                }

                // حفظ الصورة الجديدة
                $image = $request->file('banner_image');
                $path = $image->storeAs('banners', 'banner_' . time() . '.' . $image->getClientOriginalExtension(), 'public');
                Setting::setValue('floating_banner_image', $path, 'صورة البنر الإعلاني');
            }

            return response()->json([
                'success' => true,
                'message' => 'تم حفظ البنر بنجاح',
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to update banner: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'فشل حفظ البنر: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * تفعيل/إلغاء تفعيل البنر
     */
    public function toggleBanner(Request $request)
    {
        $enabled = $request->input('enabled', false);
        Setting::setValue('floating_banner_enabled', $enabled ? '1' : '0', 'حالة تفعيل البنر الإعلاني');

        return response()->json([
            'success' => true,
            'message' => $enabled ? 'تم تفعيل البنر' : 'تم إلغاء تفعيل البنر',
            'enabled' => $enabled,
        ]);
    }

    /**
     * تحديث البنر النصي للداشبورد
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
                'message' => 'تم حفظ البنر النصي بنجاح',
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to update dashboard banner: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'فشل حفظ البنر النصي: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * تفعيل/إلغاء تفعيل البنر النصي للداشبورد
     */
    public function toggleDashboardBanner(Request $request)
    {
        $enabled = $request->input('enabled', false);
        Setting::setValue('dashboard_banner_enabled', $enabled ? '1' : '0', 'حالة تفعيل البنر النصي في الداشبورد');

        return response()->json([
            'success' => true,
            'message' => $enabled ? 'تم تفعيل البنر النصي' : 'تم إلغاء تفعيل البنر النصي',
            'enabled' => $enabled,
        ]);
    }
}
