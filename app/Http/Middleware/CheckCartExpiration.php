<?php

namespace App\Http\Middleware;

use App\Models\Cart;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class CheckCartExpiration
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Only check for authenticated delegates
        if (auth()->check() && auth()->user()->isDelegate()) {
            // Skip cart expiration check for AJAX requests to avoid interference
            if (!$request->ajax() && !$request->wantsJson()) {
                $this->checkExpiredCarts(auth()->id());
            }
        }

        return $next($request);
    }

    /**
     * Check and expire carts for the given delegate
     */
    private function checkExpiredCarts($delegateId)
    {
        $expiredCarts = Cart::where('delegate_id', $delegateId)
            ->active()
            ->expired()
            ->with(['items.size'])
            ->get();

        if ($expiredCarts->isEmpty()) {
            return;
        }

        foreach ($expiredCarts as $cart) {
            DB::transaction(function() use ($cart) {
                // Return products to stock
                foreach ($cart->items as $item) {
                    if ($item->size) {
                        $item->size->increment('quantity', $item->quantity);
                    }

                    // Delete stock reservation if exists
                    $item->stockReservation()->delete();
                }

                // Delete cart items
                $cart->items()->delete();

                // Delete the cart
                $cart->delete();
            });
        }
    }
}
