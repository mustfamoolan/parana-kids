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
        Schema::table('carts', function (Blueprint $table) {
            // جعل delegate_id nullable لدعم طلبات الزبائن
            $table->foreignId('delegate_id')->nullable()->change();
            // إضافة session_id للتعرف على الزبون
            $table->string('session_id')->nullable()->after('delegate_id');
            // إضافة index لـ session_id للبحث السريع
            $table->index('session_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('carts', function (Blueprint $table) {
            $table->dropIndex(['session_id']);
            $table->dropColumn('session_id');
            // إرجاع delegate_id إلى required (لكن هذا قد يفشل إذا كان هناك carts بدون delegate_id)
            // لذلك سنتركه nullable في down أيضاً
        });
    }
};
