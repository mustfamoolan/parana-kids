<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Models\Warehouse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class StoreAppSettingController extends Controller
{
    /**
     * عرض صفحة إعدادات تطبيق المتجر
     */
    public function index()
    {
        // صور الدخول
        $loginImages = [
            '1' => Setting::getValue('app_login_image_1', ''),
            '2' => Setting::getValue('app_login_image_2', ''),
            '3' => Setting::getValue('app_login_image_3', ''),
            '4' => Setting::getValue('app_login_image_4', ''),
        ];

        // سلايدر الرئيسية
        $sliderImagesJson = Setting::getValue('app_home_slider_images', '[]');
        $sliderImages = json_decode($sliderImagesJson, true);
        if (!is_array($sliderImages)) {
            $sliderImages = [];
        }

        // الإعلان تحت الأصناف
        $announcementTitle = Setting::getValue('app_home_announcement_title', '');
        $announcementSubtitle = Setting::getValue('app_home_announcement_subtitle', '');
        $announcementImage = Setting::getValue('app_home_announcement_image', '');

        // المخازن المسموحة للعملاء
        $customerAllowedWarehousesJson = Setting::getValue('app_customer_allowed_warehouses', '[]');
        $customerAllowedWarehouses = json_decode($customerAllowedWarehousesJson, true);
        if (!is_array($customerAllowedWarehouses)) {
            $customerAllowedWarehouses = [];
        }

        // مدة صلاحية روابط المنتجات
        $productLinkDuration = Setting::getValue('app_product_link_duration', 2);

        // جلب جميع المخازن
        $warehouses = Warehouse::all();

        return view('admin.store-settings.index', compact(
            'loginImages',
            'sliderImages',
            'announcementTitle',
            'announcementSubtitle',
            'announcementImage',
            'customerAllowedWarehouses',
            'productLinkDuration',
            'warehouses'
        ));
    }

    /**
     * تحديث الإعدادات والصور
     */
    public function update(Request $request)
    {
        $request->validate([
            // Login Images
            'login_image_1' => 'nullable|image|mimes:jpeg,jpg,png,webp|max:4096',
            'login_image_2' => 'nullable|image|mimes:jpeg,jpg,png,webp|max:4096',
            'login_image_3' => 'nullable|image|mimes:jpeg,jpg,png,webp|max:4096',
            'login_image_4' => 'nullable|image|mimes:jpeg,jpg,png,webp|max:4096',
            
            // Slider Images (Array of files)
            'slider_images' => 'nullable|array|max:4',
            'slider_images.*' => 'image|mimes:jpeg,jpg,png,webp|max:4096',
            
            // Announcement
            'announcement_title' => 'nullable|string|max:255',
            'announcement_subtitle' => 'nullable|string|max:255',
            'announcement_image' => 'nullable|image|mimes:jpeg,jpg,png,webp|max:4096',

            // Allow customers to see specific warehouses
            'customer_allowed_warehouses' => 'nullable|array',
            'customer_allowed_warehouses.*' => 'exists:warehouses,id',

            // Product Link Duration
            'product_link_duration' => 'required|integer|min:1|max:168', // Max 1 week
        ]);

        try {
            // مسار الحفظ
            $storageFolder = 'store-settings';
            if (!Storage::disk('public')->exists($storageFolder)) {
                Storage::disk('public')->makeDirectory($storageFolder);
            }

            // 1. معالجة صور شاشات الدخول
            for ($i = 1; $i <= 4; $i++) {
                $fileKey = 'login_image_' . $i;
                $settingKey = 'app_login_image_' . $i;

                if ($request->hasFile($fileKey)) {
                    $this->replaceImageSetting($settingKey, $request->file($fileKey), $storageFolder, "صورة صفحة تسجيل الدخول رقم $i");
                }
            }

            // 2. معالجة صور السلايدر المتعددة
            if ($request->hasFile('slider_images')) {
                // Remove old slider images from storage
                $oldSliderJson = Setting::getValue('app_home_slider_images', '[]');
                $oldSliderArr = json_decode($oldSliderJson, true);
                if (is_array($oldSliderArr)) {
                    foreach ($oldSliderArr as $oldImg) {
                        if (Storage::disk('public')->exists($oldImg)) {
                            Storage::disk('public')->delete($oldImg);
                        }
                    }
                }

                // Upload new ones
                $newSliderImages = [];
                foreach ($request->file('slider_images') as $index => $sliderFile) {
                    $path = $sliderFile->storeAs(
                        $storageFolder, 
                        'slider_' . time() . '_' . $index . '.' . $sliderFile->getClientOriginalExtension(), 
                        'public'
                    );
                    $newSliderImages[] = $path;
                }
                
                Setting::setValue('app_home_slider_images', json_encode($newSliderImages), 'صور سلايدر الصفحة الرئيسية للتطبيق');
            }

            // 3. معالجة الإعلان
            Setting::setValue('app_home_announcement_title', $request->announcement_title ?? '', 'عنوان الإعلان في التطبيق');
            Setting::setValue('app_home_announcement_subtitle', $request->announcement_subtitle ?? '', 'الوصف الفرعي للإعلان في التطبيق');
            
            if ($request->hasFile('announcement_image')) {
                $this->replaceImageSetting('app_home_announcement_image', $request->file('announcement_image'), $storageFolder, "صورة بانر الإعلان في التطبيق");
            }

            // 4. حفظ المخازن المسموحة للعملاء
            // Save allowed warehouses array as JSON
            $allowedWarehouses = $request->customer_allowed_warehouses ?? [];
            Setting::setValue('app_customer_allowed_warehouses', json_encode($allowedWarehouses), 'المخازن المسموحة لكل عملاء التطبيق');

            // 5. حفظ مدة صلاحية روابط المنتجات
            Setting::setValue('app_product_link_duration', $request->product_link_duration, 'مدة صلاحية روابط المنتجات بالساعات');

            return redirect()->route('admin.store-settings.index')->with('success', 'تم حفظ إعدادات التطبيق وتحديث الصور بنجاح!');

        } catch (\Exception $e) {
            Log::error('Failed to update Store App Settings: ' . $e->getMessage());
            return back()->withErrors(['error' => 'حدث خطأ أثناء حفظ التغييرات: ' . $e->getMessage()])->withInput();
        }
    }

    /**
     * دالة مساعدة لاستبدال صورة في الإعدادات وحذف القديمة
     */
    private function replaceImageSetting($settingKey, $file, $folder, $description)
    {
        $oldImage = Setting::getValue($settingKey, '');
        if ($oldImage && Storage::disk('public')->exists($oldImage)) {
            Storage::disk('public')->delete($oldImage);
        }

        $path = $file->storeAs($folder, $settingKey . '_' . time() . '.' . $file->getClientOriginalExtension(), 'public');
        Setting::setValue($settingKey, $path, $description);
    }
}
