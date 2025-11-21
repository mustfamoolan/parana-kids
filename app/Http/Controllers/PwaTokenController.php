<?php

namespace App\Http\Controllers;

use App\Models\PwaToken;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class PwaTokenController extends Controller
{
    /**
     * Generate a new PWA token for the authenticated user
     */
    public function generateToken(Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['error' => 'غير مصرح'], 401);
        }

        try {
            $token = PwaToken::generateToken($user->id);

            Log::info('PwaTokenController: Token generated', [
                'user_id' => $user->id,
                'token_id' => $token->id,
            ]);

            return response()->json([
                'success' => true,
                'token' => $token->token,
                'expires_at' => $token->expires_at->toIso8601String(),
            ]);
        } catch (\Exception $e) {
            Log::error('PwaTokenController: Error generating token', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json(['error' => 'فشل في إنشاء token'], 500);
        }
    }

    /**
     * Revoke (delete) PWA token for the authenticated user
     */
    public function revokeToken(Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['error' => 'غير مصرح'], 401);
        }

        try {
            $deleted = PwaToken::where('user_id', $user->id)->delete();

            Log::info('PwaTokenController: Token revoked', [
                'user_id' => $user->id,
                'deleted' => $deleted,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'تم إلغاء token بنجاح',
            ]);
        } catch (\Exception $e) {
            Log::error('PwaTokenController: Error revoking token', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json(['error' => 'فشل في إلغاء token'], 500);
        }
    }

    /**
     * Validate PWA token and return user
     */
    public static function validateToken($token)
    {
        $pwaToken = PwaToken::findByToken($token);

        if (!$pwaToken || !$pwaToken->isValid()) {
            return null;
        }

        return $pwaToken->user;
    }
}
