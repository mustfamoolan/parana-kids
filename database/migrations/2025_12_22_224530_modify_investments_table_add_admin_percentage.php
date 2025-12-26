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
            // جعل investor_id nullable لأنه لن يكون مطلوباً في البنية الجديدة
            $table->foreignId('investor_id')->nullable()->change();
            
            // إضافة الحقول الجديدة
            $table->decimal('admin_profit_percentage', 5, 2)->default(0)->after('investment_type')->comment('نسبة المدير الثابتة');
            $table->decimal('total_value', 15, 2)->default(0)->after('admin_profit_percentage')->comment('القيمة الإجمالية للاستثمار');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('investments', function (Blueprint $table) {
            $table->dropColumn(['admin_profit_percentage', 'total_value']);
            $table->foreignId('investor_id')->nullable(false)->change();
        });
    }
};
