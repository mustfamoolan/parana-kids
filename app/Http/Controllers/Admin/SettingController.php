<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;
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

        return view('admin.settings.index', compact('deliveryFee', 'profitMargin'));
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
                // التأكد من وجود المجلد قبل الحفظ
                $profilesDir = storage_path('app/public/profiles');
                if (!is_dir($profilesDir)) {
                    File::makeDirectory($profilesDir, 0755, true);
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
}
