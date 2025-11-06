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
        Schema::table('warehouse_promotions', function (Blueprint $table) {
            // إضافة نوع التخفيض (مبلغ أو نسبة)
            $table->enum('discount_type', ['amount', 'percentage'])->default('amount')->after('warehouse_id');

            // جعل promotion_price nullable (لأنه لن يُستخدم عند النسبة)
            $table->decimal('promotion_price', 10, 2)->nullable()->change();

            // إضافة نسبة التخفيض
            $table->decimal('discount_percentage', 5, 2)->nullable()->after('discount_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('warehouse_promotions', function (Blueprint $table) {
            $table->dropColumn(['discount_type', 'discount_percentage']);
            $table->decimal('promotion_price', 10, 2)->nullable(false)->change();
        });
    }
};
