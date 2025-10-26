<?php

namespace App\Console\Commands;

use App\Models\Cart;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ExpireCartsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'carts:expire';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Expire carts that have passed their expiration time and return products to stock';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting cart expiration process...');

        // Find expired active carts
        $expiredCarts = Cart::active()
            ->expired()
            ->with(['items.size'])
            ->get();

        if ($expiredCarts->isEmpty()) {
            $this->info('No expired carts found.');
            return;
        }

        $this->info("Found {$expiredCarts->count()} expired carts.");

        $expiredCount = 0;

        foreach ($expiredCarts as $cart) {
            DB::transaction(function() use ($cart, &$expiredCount) {
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

                $expiredCount++;
            });

            $this->line("Expired cart: {$cart->cart_name} (ID: {$cart->id})");
        }

        $this->info("Successfully expired {$expiredCount} carts and returned products to stock.");
    }
}
