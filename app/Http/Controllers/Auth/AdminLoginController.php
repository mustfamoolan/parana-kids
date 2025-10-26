<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class AdminLoginController extends Controller
{
    public function showLoginForm()
    {
        return view('auth.admin-login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'login_field' => 'required|string',
            'password' => 'required|string',
        ]);

        $loginField = $request->login_field;
        $password = $request->password;

        // Try to find user by phone (for admin) or code (for supplier)
        $user = User::where('phone', $loginField)
                   ->orWhere('code', $loginField)
                   ->first();

        if ($user && Hash::check($password, $user->password)) {
            // Check if user is admin or supplier
            if ($user->isAdminOrSupplier()) {
                Auth::login($user);
                return redirect()->intended('/admin/dashboard');
            }
        }

        return back()->withErrors([
            'login_field' => 'بيانات الدخول غير صحيحة.',
        ])->withInput($request->only('login_field'));
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/admin/login');
    }
}
