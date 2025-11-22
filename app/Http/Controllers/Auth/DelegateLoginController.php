<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class DelegateLoginController extends Controller
{
    public function showLoginForm()
    {
        return view('auth.delegate-login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'code' => 'required|string',
            'password' => 'required|string',
        ]);

        $code = $request->code;
        $password = $request->password;

        // Find user by code
        $user = User::where('code', $code)->first();

        if ($user && Hash::check($password, $user->password)) {
            // Check if user is delegate
            if ($user->isDelegate()) {
                // تفعيل Remember Me لحفظ حالة تسجيل الدخول في PWA
                Auth::login($user, true);
                return redirect()->intended('/delegate/dashboard');
            }
        }

        return back()->withErrors([
            'code' => 'بيانات الدخول غير صحيحة.',
        ])->withInput($request->only('code'));
    }

    public function logout(Request $request)
    {
        // تنظيف جميع البيانات من session قبل logout
        $request->session()->flush();

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/delegate/login');
    }
}
