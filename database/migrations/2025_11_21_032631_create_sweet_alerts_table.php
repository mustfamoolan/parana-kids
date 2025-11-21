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
        Schema::create('sweet_alerts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('type'); // message, order_created, order_confirmed, order_deleted, etc.
            $table->string('title');
            $table->text('message');
            $table->string('icon')->default('info'); // success, error, warning, info
            $table->json('data')->nullable(); // لحفظ بيانات إضافية مثل order_id, conversation_id
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            // Indexes للبحث السريع
            $table->index('user_id');
            $table->index('read_at');
            $table->index(['user_id', 'read_at']);
            $table->index('type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sweet_alerts');
    }
};
