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
        Schema::create('exchange_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->onDelete('cascade');
            $table->foreignId('order_item_id')->constrained()->onDelete('cascade');

            // المنتج القديم (المرجع)
            $table->foreignId('old_product_id')->constrained('products');
            $table->foreignId('old_size_id')->constrained('product_sizes');
            $table->integer('old_quantity');

            // المنتج الجديد (البديل)
            $table->foreignId('new_product_id')->constrained('products');
            $table->foreignId('new_size_id')->constrained('product_sizes');
            $table->integer('new_quantity');

            $table->text('exchange_reason');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('exchange_items');
    }
};
