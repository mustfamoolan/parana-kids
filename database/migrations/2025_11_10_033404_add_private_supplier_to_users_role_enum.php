<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // تحديث enum role لإضافة private_supplier
        DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('admin', 'supplier', 'delegate', 'private_supplier') DEFAULT 'delegate'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // إرجاع enum role إلى الحالة السابقة
        // ملاحظة: يجب التأكد من عدم وجود مستخدمين بـ private_supplier قبل الرجوع
        DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('admin', 'supplier', 'delegate') DEFAULT 'delegate'");
    }
};
