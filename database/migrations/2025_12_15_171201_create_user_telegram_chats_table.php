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
        Schema::create('user_telegram_chats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('chat_id')->index();
            $table->string('device_name')->nullable(); // اسم الجهاز (اختياري)
            $table->timestamp('linked_at')->useCurrent();
            $table->timestamps();

            // منع تكرار نفس chat_id لنفس المستخدم
            $table->unique(['user_id', 'chat_id']);
        });

        // نقل البيانات الموجودة من العمود القديم telegram_chat_id إلى الجدول الجديد
        DB::statement("
            INSERT INTO user_telegram_chats (user_id, chat_id, linked_at, created_at, updated_at)
            SELECT id, telegram_chat_id, NOW(), NOW(), NOW()
            FROM users
            WHERE telegram_chat_id IS NOT NULL
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_telegram_chats');
    }
};
