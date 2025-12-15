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
        Schema::table('alwaseet_order_status_history', function (Blueprint $table) {
            // حذف foreign key constraint القديم
            $table->dropForeign(['order_id']);
            
            // تعديل order_id ليصبح nullable
            $table->foreignId('order_id')->nullable()->change()->constrained('orders')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('alwaseet_order_status_history', function (Blueprint $table) {
            // إعادة order_id ليصبح NOT NULL
            $table->dropForeign(['order_id']);
            $table->foreignId('order_id')->change()->constrained('orders')->onDelete('cascade');
        });
    }
};
