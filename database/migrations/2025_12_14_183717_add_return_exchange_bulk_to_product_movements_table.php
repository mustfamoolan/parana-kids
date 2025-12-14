<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // تحديث enum لإضافة return_exchange_bulk
        DB::statement("ALTER TABLE product_movements MODIFY COLUMN movement_type ENUM(
            'add', 'sale', 'sell', 'confirm', 'cancel', 'return', 'delete', 'restore',
            'transfer_out', 'transfer_in', 'return_bulk', 'increase', 'decrease',
            'order_edit_add', 'order_edit_remove', 'order_edit_increase', 'order_edit_decrease',
            'partial_return', 'adjustment', 'return_exchange_bulk'
        )");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // إرجاع enum للحالة السابقة (إزالة return_exchange_bulk)
        DB::statement("ALTER TABLE product_movements MODIFY COLUMN movement_type ENUM(
            'add', 'sale', 'sell', 'confirm', 'cancel', 'return', 'delete', 'restore',
            'transfer_out', 'transfer_in', 'return_bulk', 'increase', 'decrease',
            'order_edit_add', 'order_edit_remove', 'order_edit_increase', 'order_edit_decrease',
            'partial_return', 'adjustment'
        )");
    }
};
