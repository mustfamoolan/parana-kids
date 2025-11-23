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
            $table->string('customer_name')->nullable()->after('cart_name');
            $table->string('customer_phone', 20)->nullable()->after('customer_name');
            $table->text('customer_address')->nullable()->after('customer_phone');
            $table->string('customer_social_link')->nullable()->after('customer_address');
            $table->text('notes')->nullable()->after('customer_social_link');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('carts', function (Blueprint $table) {
            $table->dropColumn([
                'customer_name',
                'customer_phone',
                'customer_address',
                'customer_social_link',
                'notes'
            ]);
        });
    }
};
