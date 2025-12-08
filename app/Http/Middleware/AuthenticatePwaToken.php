<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\PwaTokenController;
use Symfony\Component\HttpFoundation\Response;

class AuthenticatePwaToken
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handle(Request $request, Closure $next): Response
    {
        // محاولة الحصول على token من header
        $token = $request->header('Authorization');

        // إزالة "Bearer " إذا كان موجوداً
        if ($token && str_starts_with($token, 'Bearer ')) {
            $token = substr($token, 7);
        }

        // إذا لم يكن موجود في header، جرب X-PWA-Token
        if (!$token) {
            $token = $request->header('X-PWA-Token');
        }

        // إذا لم يكن موجود، جرب query parameter
        if (!$token) {
            $token = $request->query('token');
        }

        if (!$token) {
            return response()->json([
                'success' => false,
                'message' => 'غير مصرح. يرجى توفير token.',
            ], 401);
        }

        $user = PwaTokenController::validateToken($token);

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Token غير صحيح أو منتهي الصلاحية.',
            ], 401);
        }

        // تسجيل دخول المستخدم مؤقتاً للطلب الحالي
        Auth::setUser($user);

        return $next($request);
    }
}

