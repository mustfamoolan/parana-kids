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
        Schema::create('investor_profits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('investor_id')->constrained('investors')->onDelete('cascade');
            $table->foreignId('investment_id')->constrained('investments')->onDelete('cascade');
            $table->foreignId('profit_record_id')->constrained('profit_records')->onDelete('cascade');
            $table->foreignId('order_id')->nullable()->constrained('orders')->onDelete('cascade');
            $table->foreignId('product_id')->nullable()->constrained('products')->onDelete('cascade');
            $table->foreignId('warehouse_id')->nullable()->constrained('warehouses')->onDelete('cascade');
            $table->foreignId('private_warehouse_id')->nullable()->constrained('private_warehouses')->onDelete('cascade');
            $table->decimal('profit_amount', 15, 2)->comment('مبلغ الربح للمستثمر');
            $table->decimal('base_profit', 15, 2)->comment('الربح الأساسي قبل التوزيع');
            $table->decimal('profit_percentage', 5, 2)->comment('النسبة المستخدمة');
            $table->date('profit_date');
            $table->enum('status', ['pending', 'paid', 'cancelled'])->default('pending');
            $table->date('payment_date')->nullable();
            $table->text('payment_notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('investor_profits');
    }
};
