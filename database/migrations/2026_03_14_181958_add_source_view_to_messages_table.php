<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * إضافة حقل source_view لتحديد الصفحة المصدر عند مشاركة الطلب
     * هذا الحقل منفصل عن order_type ومخصص فقط للتنقل الذكي
     */
    public function up(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->string('source_view', 50)->nullable()->default(null)->after('order_type')
                  ->comment('الصفحة التي تم المشاركة منها: alwaseet, alwaseet_print, restricted, pending, deleted, partial_return');
        });
    }

    public function down(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->dropColumn('source_view');
        });
    }
};
