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
        Schema::create('alwaseet_order_status_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->onDelete('cascade');
            $table->foreignId('shipment_id')->nullable()->constrained('alwaseet_shipments')->onDelete('cascade');
            $table->string('status_id'); // من API
            $table->string('status_text'); // نص الحالة
            $table->timestamp('changed_at'); // وقت التغيير
            $table->string('changed_by')->nullable(); // نظام/مستخدم
            $table->json('metadata')->nullable(); // بيانات إضافية
            $table->timestamps();
            
            $table->index(['order_id', 'changed_at']);
            $table->index('status_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('alwaseet_order_status_history');
    }
};
