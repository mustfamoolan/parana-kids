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
        Schema::create('investments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('investor_id')->constrained('investors')->onDelete('cascade');
            $table->enum('investment_type', ['product', 'warehouse', 'private_warehouse']);
            $table->foreignId('product_id')->nullable()->constrained('products')->onDelete('cascade');
            $table->foreignId('warehouse_id')->nullable()->constrained('warehouses')->onDelete('cascade');
            $table->foreignId('private_warehouse_id')->nullable()->constrained('private_warehouses')->onDelete('cascade');
            $table->decimal('profit_percentage', 5, 2)->comment('نسبة الربح');
            $table->decimal('investment_amount', 15, 2)->comment('مبلغ الاستثمار');
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->enum('status', ['active', 'completed', 'cancelled', 'suspended'])->default('active');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('investments');
    }
};
