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
        Schema::table('investments', function (Blueprint $table) {
            // جعل profit_percentage و investment_amount nullable للبنية الجديدة
            $table->decimal('profit_percentage', 5, 2)->nullable()->change();
            $table->decimal('investment_amount', 15, 2)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('investments', function (Blueprint $table) {
            // إرجاع الحقول إلى required (لكن يجب إعطاء قيم افتراضية)
            $table->decimal('profit_percentage', 5, 2)->default(0)->nullable(false)->change();
            $table->decimal('investment_amount', 15, 2)->default(0)->nullable(false)->change();
        });
    }
};
