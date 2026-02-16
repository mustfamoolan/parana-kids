<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class CheckTokenExpiration
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user) {
            // Check if using Sanctum token
            $token = $user->currentAccessToken();

            if ($token) {
                // Get token from database with expires_at
                $tokenRecord = DB::table('personal_access_tokens')
                    ->where('id', $token->id)
                    ->first();

                if ($tokenRecord && $tokenRecord->expires_at) {
                    $expiresAt = \Carbon\Carbon::parse($tokenRecord->expires_at);

                    if ($expiresAt->isPast()) {
                        // Token has expired
                        return response()->json([
                            'success' => false,
                            'message' => 'انتهت صلاحية الجلسة. يرجى تسجيل الدخول مرة أخرى',
                            'error_code' => 'TOKEN_EXPIRED'
                        ], 401);
                    }
                }
            }
        }

        return $next($request);
    }
}
