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
        Schema::table('product_links', function (Blueprint $table) {
            // التحقق من وجود العمود قبل إضافته (للسيرفر الفعلي)
            if (!Schema::hasColumn('product_links', 'has_discount')) {
                $table->boolean('has_discount')->default(false)->after('size_name');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('product_links', function (Blueprint $table) {
            // التحقق من وجود العمود قبل حذفه
            if (Schema::hasColumn('product_links', 'has_discount')) {
                $table->dropColumn('has_discount');
            }
        });
    }
};

