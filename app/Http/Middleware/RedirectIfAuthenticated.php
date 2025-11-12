<?php

namespace App\Http\Middleware;

use App\Providers\RouteServiceProvider;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RedirectIfAuthenticated
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @param  string|null  ...$guards
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next, ...$guards)
    {
        $guards = empty($guards) ? [null] : $guards;

        foreach ($guards as $guard) {
            if (Auth::guard($guard)->check()) {
                $user = Auth::guard($guard)->user();

                // المندوب يذهب إلى الداشبورد
                if ($user && $user->isDelegate()) {
                    return redirect()->route('delegate.dashboard');
                }

                // المجهز يذهب إلى الداشبورد
                if ($user && $user->isSupplier()) {
                    return redirect()->route('admin.dashboard');
                }

                // المورد يذهب إلى صفحة الفواتير
                if ($user && $user->isPrivateSupplier()) {
                    return redirect()->route('admin.invoices.index');
                }

                // المدير يذهب إلى الداشبورد
                if ($user && $user->isAdmin()) {
                    return redirect()->route('admin.dashboard');
                }

                return redirect(RouteServiceProvider::HOME);
            }
        }

        return $next($request);
    }
}
