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
            $table->text('alwaseet_address')->nullable()->after('customer_address');
            $table->string('alwaseet_city_id')->nullable()->after('alwaseet_address');
            $table->string('alwaseet_region_id')->nullable()->after('alwaseet_city_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['alwaseet_address', 'alwaseet_city_id', 'alwaseet_region_id']);
        });
    }
};
