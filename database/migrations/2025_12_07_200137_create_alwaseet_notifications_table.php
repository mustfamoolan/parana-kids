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
        Schema::create('alwaseet_notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('alwaseet_shipment_id')->constrained('alwaseet_shipments')->onDelete('cascade');
            $table->string('type'); // status_changed, invoice_received, etc.
            $table->string('title');
            $table->text('message');
            $table->string('old_status')->nullable();
            $table->string('new_status')->nullable();
            $table->boolean('is_read')->default(false);
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->index('alwaseet_shipment_id');
            $table->index('type');
            $table->index('is_read');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('alwaseet_notifications');
    }
};
