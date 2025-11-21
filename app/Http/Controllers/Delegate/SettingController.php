<?php

namespace App\Http\Controllers\Delegate;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SettingController extends Controller
{
    /**
     * عرض صفحة الإعدادات
     */
    public function index()
    {
        return view('delegate.settings.index');
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
            // حذف الصورة القديمة إن وجدت
            if ($user->profile_image) {
                Storage::disk('public')->delete($user->profile_image);
            }

            // حفظ الصورة الجديدة
            $image = $request->file('profile_image');
            $filename = 'profiles/' . $user->id . '_' . time() . '.' . $image->getClientOriginalExtension();

            // حفظ الصورة
            $path = $image->storeAs('profiles', $user->id . '_' . time() . '.' . $image->getClientOriginalExtension(), 'public');

            $user->profile_image = $path;
            $user->save();
        }

        return redirect()->route('delegate.settings.index')
                        ->with('success', 'تم تحديث صورة البروفايل بنجاح');
    }
}

