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
        Schema::create('alwaseet_shipments', function (Blueprint $table) {
            $table->id();
            $table->string('alwaseet_order_id')->unique();
            $table->string('client_name');
            $table->string('client_mobile');
            $table->string('client_mobile2')->nullable();
            $table->string('city_id');
            $table->string('city_name');
            $table->string('region_id');
            $table->string('region_name');
            $table->text('location');
            $table->decimal('price', 10, 2);
            $table->decimal('delivery_price', 10, 2)->default(0);
            $table->string('package_size');
            $table->string('type_name');
            $table->string('status_id');
            $table->string('status');
            $table->string('items_number')->default('1');
            $table->text('merchant_notes')->nullable();
            $table->text('issue_notes')->nullable();
            $table->boolean('replacement')->default(false);
            $table->string('qr_id')->nullable();
            $table->string('qr_link')->nullable();
            $table->timestamp('alwaseet_created_at')->nullable();
            $table->timestamp('alwaseet_updated_at')->nullable();
            $table->timestamp('synced_at')->nullable();
            $table->foreignId('order_id')->nullable()->constrained('orders')->onDelete('set null');
            $table->timestamps();

            $table->index('status_id');
            $table->index('order_id');
            $table->index('synced_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('alwaseet_shipments');
    }
};
