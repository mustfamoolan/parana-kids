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
        Schema::create('product_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->foreignId('size_id')->constrained('product_sizes')->onDelete('cascade');
            $table->foreignId('warehouse_id')->constrained()->onDelete('cascade');
            $table->foreignId('order_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->enum('movement_type', ['add', 'sale', 'confirm', 'cancel', 'return', 'delete', 'restore']);
            $table->integer('quantity'); // موجب للإضافة، سالب للخصم
            $table->integer('balance_after'); // الرصيد بعد الحركة
            $table->string('order_status')->nullable(); // حالة الطلب وقت الحركة
            $table->text('notes')->nullable();
            $table->timestamps();

            // Indexes للفلترة السريعة
            $table->index(['product_id', 'created_at']);
            $table->index(['warehouse_id', 'created_at']);
            $table->index(['movement_type', 'created_at']);
            $table->index(['user_id', 'created_at']);
            $table->index(['order_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_movements');
    }
};
