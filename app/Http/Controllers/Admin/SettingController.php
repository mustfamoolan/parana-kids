<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;

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
}
