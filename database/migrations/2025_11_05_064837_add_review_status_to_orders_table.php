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
            // حذف الحقل القديم إذا كان موجوداً
            if (Schema::hasColumn('orders', 'review_status')) {
                $table->dropColumn('review_status');
            }
            // إضافة الحقلين الجديدين
            $table->boolean('size_reviewed')->default(false)->after('status');
            $table->boolean('message_confirmed')->default(false)->after('size_reviewed');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['size_reviewed', 'message_confirmed']);
        });
    }
};
