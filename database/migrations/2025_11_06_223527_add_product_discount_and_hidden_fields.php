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
        Schema::table('products', function (Blueprint $table) {
            // حجب المنتج عن المندوبين
            $table->boolean('is_hidden')->default(false)->after('selling_price');

            // نوع التخفيض للمنتج الواحد
            $table->enum('discount_type', ['none', 'amount', 'percentage'])->nullable()->default('none')->after('is_hidden');

            // قيمة التخفيض (مبلغ أو نسبة)
            $table->decimal('discount_value', 10, 2)->nullable()->after('discount_type');

            // تاريخ بداية التخفيض
            $table->dateTime('discount_start_date')->nullable()->after('discount_value');

            // تاريخ نهاية التخفيض
            $table->dateTime('discount_end_date')->nullable()->after('discount_start_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn([
                'is_hidden',
                'discount_type',
                'discount_value',
                'discount_start_date',
                'discount_end_date'
            ]);
        });
    }
};
