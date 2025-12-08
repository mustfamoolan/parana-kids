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
        Schema::create('alwaseet_sync_logs', function (Blueprint $table) {
            $table->id();
            $table->enum('type', ['manual', 'automatic'])->default('automatic');
            $table->enum('status', ['success', 'failed', 'partial'])->default('success');
            $table->integer('orders_synced')->default(0);
            $table->integer('orders_updated')->default(0);
            $table->integer('orders_created')->default(0);
            $table->integer('orders_failed')->default(0);
            $table->text('error_message')->nullable();
            $table->json('filters')->nullable(); // status_id, date_from, date_to
            $table->timestamp('started_at');
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index('type');
            $table->index('status');
            $table->index('started_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('alwaseet_sync_logs');
    }
};
