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
        Schema::create('profit_records', function (Blueprint $table) {
            $table->id();

            // Foreign keys
            $table->foreignId('warehouse_id')->nullable()->constrained('warehouses')->onDelete('cascade');
            $table->foreignId('product_id')->nullable()->constrained('products')->onDelete('cascade');
            $table->foreignId('order_id')->nullable()->constrained('orders')->onDelete('cascade');
            $table->foreignId('delegate_id')->nullable()->constrained('users')->onDelete('cascade');
            $table->foreignId('return_item_id')->nullable()->constrained('return_items')->onDelete('cascade');

            // التاريخ للفلترة بين تاريخين
            $table->date('record_date');

            // القيم المالية
            $table->decimal('warehouse_value', 15, 2)->default(0)->comment('قيمة المخزن');
            $table->decimal('product_value', 15, 2)->default(0)->comment('قيمة المنتج');
            $table->decimal('expected_profit', 15, 2)->default(0)->comment('الربح المتوقع (للطلبات pending)');
            $table->decimal('actual_profit', 15, 2)->default(0)->comment('الربح الحالي (للطلبات confirmed)');
            $table->decimal('return_amount', 15, 2)->default(0)->comment('مبلغ الاسترجاع');
            $table->decimal('total_amount', 15, 2)->default(0)->comment('المبلغ الإجمالي للطلب/المنتج/المخزن');

            // نوع السجل وحالة الطلب
            $table->enum('record_type', ['warehouse', 'product', 'order'])->comment('نوع السجل');
            $table->enum('status', ['pending', 'confirmed', 'returned', 'cancelled'])->nullable()->comment('حالة الطلب عند التسجيل');

            $table->timestamps();

            // Indexes للكشوفات المستقبلية
            $table->index('record_date'); // للفلترة بين تاريخين
            $table->index('delegate_id'); // لكشف ربح المندوب
            $table->index('warehouse_id'); // لكشف قيمة المخزن
            $table->index('product_id'); // لكشف قيمة المنتج
            $table->index('order_id'); // للبحث حسب الطلب
            $table->index(['record_date', 'record_type']); // للبحث المركب
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('profit_records');
    }
};
