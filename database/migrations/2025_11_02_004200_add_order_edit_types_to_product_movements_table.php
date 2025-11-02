<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('product_movements', function (Blueprint $table) {
            $table->enum('movement_type', [
                'add', 'sale', 'sell', 'confirm', 'cancel', 'return', 'delete', 'restore',
                'transfer_out', 'transfer_in', 'return_bulk', 'increase', 'decrease',
                'order_edit_add', 'order_edit_remove', 'order_edit_increase', 'order_edit_decrease'
            ])->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('product_movements', function (Blueprint $table) {
            $table->enum('movement_type', [
                'add', 'sale', 'sell', 'confirm', 'cancel', 'return', 'delete', 'restore',
                'transfer_out', 'transfer_in', 'return_bulk', 'increase', 'decrease'
            ])->change();
        });
    }
};

