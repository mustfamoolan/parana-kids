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
        Schema::table('order_items', function (Blueprint $table) {
            // إسقاط القيود الحالية التي تستخدم onDelete('cascade')
            try { $table->dropForeign(['product_id']); } catch (\Throwable $e) {}
            try { $table->dropForeign(['size_id']); } catch (\Throwable $e) {}
        });

        Schema::table('order_items', function (Blueprint $table) {
            // product_id: منع حذف المنتج إن كان مرتبطاً بعناصر طلب
            $table->foreign('product_id')->references('id')->on('products')->restrictOnDelete();

            // size_id: عند حذف القياس، لا تُحذف عناصر الطلب؛ فقط تُصبح القيمة NULL
            $table->foreign('size_id')->references('id')->on('product_sizes')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            try { $table->dropForeign(['product_id']); } catch (\Throwable $e) {}
            try { $table->dropForeign(['size_id']); } catch (\Throwable $e) {}
        });

        Schema::table('order_items', function (Blueprint $table) {
            // إرجاع الإعدادات السابقة (غير مرغوبة لكنها لازمة للـ down)
            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
            $table->foreign('size_id')->references('id')->on('product_sizes')->onDelete('cascade');
        });
    }
};


