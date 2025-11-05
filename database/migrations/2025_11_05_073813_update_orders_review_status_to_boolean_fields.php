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
            // إضافة الحقلين الجديدين إذا لم يكونا موجودين
            if (!Schema::hasColumn('orders', 'size_reviewed')) {
                $table->boolean('size_reviewed')->default(false)->after('status');
            }
            if (!Schema::hasColumn('orders', 'message_confirmed')) {
                $table->boolean('message_confirmed')->default(false)->after('size_reviewed');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (Schema::hasColumn('orders', 'size_reviewed')) {
                $table->dropColumn('size_reviewed');
            }
            if (Schema::hasColumn('orders', 'message_confirmed')) {
                $table->dropColumn('message_confirmed');
            }
            // إعادة الحقل القديم
            $table->enum('review_status', ['not_reviewed', 'size_reviewed', 'message_not_confirmed', 'message_confirmed'])
                  ->default('not_reviewed')
                  ->after('status');
        });
    }
};
