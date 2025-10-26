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
            $table->string('delivery_code')->nullable()->after('notes');
            $table->timestamp('confirmed_at')->nullable()->after('delivery_code');
            $table->foreignId('confirmed_by')->nullable()->constrained('users')->after('confirmed_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['confirmed_by']);
            $table->dropColumn(['delivery_code', 'confirmed_at', 'confirmed_by']);
        });
    }
};
