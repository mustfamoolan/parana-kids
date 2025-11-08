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
        Schema::create('sales_reports', function (Blueprint $table) {
            $table->id();
            $table->date('report_date');
            $table->date('date_from')->nullable();
            $table->date('date_to')->nullable();
            $table->json('filters')->nullable()->comment('الفلاتر المطبقة');

            // المبالغ
            $table->decimal('total_amount_with_delivery', 15, 2)->default(0);
            $table->decimal('total_amount_without_delivery', 15, 2)->default(0);
            $table->decimal('total_profit_without_margin', 15, 2)->default(0);
            $table->decimal('total_profit_with_margin', 15, 2)->default(0);
            $table->decimal('total_margin_amount', 15, 2)->default(0);

            // الإحصائيات
            $table->integer('orders_count')->default(0);
            $table->integer('items_count')->default(0);
            $table->foreignId('most_sold_product_id')->nullable()->constrained('products')->onDelete('set null');
            $table->foreignId('least_sold_product_id')->nullable()->constrained('products')->onDelete('set null');

            // بيانات الجارتات
            $table->json('chart_data')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sales_reports');
    }
};
