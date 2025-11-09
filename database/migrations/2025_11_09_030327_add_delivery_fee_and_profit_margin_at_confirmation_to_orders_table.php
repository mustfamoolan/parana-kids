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
        Schema::table('orders', function (Blueprint $table) {
            $table->decimal('delivery_fee_at_confirmation', 10, 2)->nullable()->after('total_amount');
            $table->decimal('profit_margin_at_confirmation', 10, 2)->nullable()->after('delivery_fee_at_confirmation');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['delivery_fee_at_confirmation', 'profit_margin_at_confirmation']);
        });
    }
};
