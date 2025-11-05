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
        Schema::table('orders', function (Blueprint $table) {
            // تحديث enum message_confirmed لإضافة الحالة الرابعة
            if (Schema::hasColumn('orders', 'message_confirmed')) {
                $table->dropColumn('message_confirmed');
            }
            $table->enum('message_confirmed', ['not_sent', 'waiting_response', 'not_confirmed', 'confirmed'])
                  ->default('not_sent')
                  ->after('size_reviewed');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // إزالة الحالة الرابعة
            if (Schema::hasColumn('orders', 'message_confirmed')) {
                $table->dropColumn('message_confirmed');
            }
            $table->enum('message_confirmed', ['not_sent', 'not_confirmed', 'confirmed'])
                  ->default('not_sent')
                  ->after('size_reviewed');
        });
    }
};
