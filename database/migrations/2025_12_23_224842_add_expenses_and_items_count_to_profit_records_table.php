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
        Schema::table('profit_records', function (Blueprint $table) {
            $table->decimal('expenses_amount', 15, 2)->default(0)->after('actual_profit')->comment('إجمالي المصروفات');
            $table->integer('items_count')->default(0)->after('expenses_amount')->comment('عدد القطع المباعة');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('profit_records', function (Blueprint $table) {
            $table->dropColumn(['expenses_amount', 'items_count']);
        });
    }
};
