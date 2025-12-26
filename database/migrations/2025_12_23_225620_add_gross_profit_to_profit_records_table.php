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
            $table->decimal('gross_profit', 15, 2)->default(0)->after('actual_profit')->comment('الربح الإجمالي قبل خصم المصروفات');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('profit_records', function (Blueprint $table) {
            $table->dropColumn('gross_profit');
        });
    }
};
