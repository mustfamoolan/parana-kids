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
        Schema::table('product_movements', function (Blueprint $table) {
            // إسقاط foreign key constraint الحالي
            $table->dropForeign(['size_id']);
        });

        // تعديل العمود ليكون nullable باستخدام DB::statement
        DB::statement('ALTER TABLE product_movements MODIFY size_id BIGINT UNSIGNED NULL');

        Schema::table('product_movements', function (Blueprint $table) {
            // إعادة إنشاء foreign key constraint مع nullOnDelete
            $table->foreign('size_id')->references('id')->on('product_sizes')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('product_movements', function (Blueprint $table) {
            // إسقاط foreign key constraint
            $table->dropForeign(['size_id']);
        });

        // إرجاع العمود ليكون NOT NULL
        DB::statement('ALTER TABLE product_movements MODIFY size_id BIGINT UNSIGNED NOT NULL');

        Schema::table('product_movements', function (Blueprint $table) {
            // إعادة إنشاء foreign key constraint الأصلي
            $table->foreign('size_id')->references('id')->on('product_sizes')->onDelete('cascade');
        });
    }
};
