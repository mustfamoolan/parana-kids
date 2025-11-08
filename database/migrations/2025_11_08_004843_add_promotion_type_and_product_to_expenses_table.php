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
        Schema::table('expenses', function (Blueprint $table) {
            $table->foreignId('product_id')->nullable()->after('user_id')->constrained('products')->onDelete('set null');
        });

        // تحديث enum لإضافة 'promotion'
        \DB::statement("ALTER TABLE expenses MODIFY COLUMN expense_type ENUM('rent', 'salary', 'other', 'promotion')");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            $table->dropForeign(['product_id']);
            $table->dropColumn('product_id');
        });

        // إرجاع enum إلى الحالة السابقة
        \DB::statement("ALTER TABLE expenses MODIFY COLUMN expense_type ENUM('rent', 'salary', 'other')");
    }
};
