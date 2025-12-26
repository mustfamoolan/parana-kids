<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Investor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Session;

class InvestorLoginController extends Controller
{
    /**
     * عرض صفحة تسجيل الدخول
     */
    public function showLoginForm()
    {
        return view('auth.investor-login');
    }

    /**
     * تسجيل الدخول
     */
    public function login(Request $request)
    {
        $request->validate([
            'login_field' => 'required|string',
            'password' => 'required|string',
        ]);

        $loginField = $request->login_field;
        $password = $request->password;

        // البحث عن المستثمر بالاسم أو الرقم
        $investor = Investor::where(function($q) use ($loginField) {
                $q->where('name', $loginField)
                  ->orWhere('phone', $loginField);
            })
            ->first();

        if (!$investor) {
            \Log::info('Investor login failed: Investor not found', [
                'login_field' => $loginField
            ]);
            return back()->withErrors([
                'login_field' => 'بيانات الدخول غير صحيحة.',
            ])->withInput($request->only('login_field'));
        }

        // التحقق من كلمة المرور
        if (!$investor->verifyPassword($password)) {
            \Log::info('Investor login failed: Password incorrect', [
                'investor_id' => $investor->id,
                'investor_name' => $investor->name
            ]);
            return back()->withErrors([
                'login_field' => 'بيانات الدخول غير صحيحة.',
            ])->withInput($request->only('login_field'));
        }

        // التحقق من حالة المستثمر
        if ($investor->status !== 'active') {
            \Log::info('Investor login failed: Account inactive', [
                'investor_id' => $investor->id,
                'investor_name' => $investor->name,
                'status' => $investor->status
            ]);
            return back()->withErrors([
                'login_field' => 'حسابك غير نشط.',
            ])->withInput($request->only('login_field'));
        }

        // حفظ المستثمر في الجلسة
        Session::put('investor_id', $investor->id);
        Session::put('investor_name', $investor->name);

        \Log::info('Investor login successful', [
            'investor_id' => $investor->id,
            'investor_name' => $investor->name
        ]);

        return redirect()->intended(route('investor.dashboard'));
    }

    /**
     * تسجيل الخروج
     */
    public function logout(Request $request)
    {
        Session::forget('investor_id');
        Session::forget('investor_name');

        return redirect()->route('investor.login');
    }
}
