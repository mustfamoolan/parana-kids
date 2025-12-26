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
        Schema::table('investment_investors', function (Blueprint $table) {
            $table->decimal('cost_percentage', 5, 2)->nullable()->after('profit_percentage')->comment('نسبة التكلفة');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('investment_investors', function (Blueprint $table) {
            $table->dropColumn('cost_percentage');
        });
    }
};
