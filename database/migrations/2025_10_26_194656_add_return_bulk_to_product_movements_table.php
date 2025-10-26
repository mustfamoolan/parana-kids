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
            // تحديث enum لإضافة إرجاع طلبات
            $table->enum('movement_type', [
                'add',
                'sale',
                'confirm',
                'cancel',
                'return',
                'delete',
                'restore',
                'transfer_out',
                'transfer_in',
                'return_bulk'
            ])->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('product_movements', function (Blueprint $table) {
            // إرجاع enum للحالة السابقة
            $table->enum('movement_type', [
                'add',
                'sale',
                'confirm',
                'cancel',
                'return',
                'delete',
                'restore',
                'transfer_out',
                'transfer_in'
            ])->change();
        });
    }
};
