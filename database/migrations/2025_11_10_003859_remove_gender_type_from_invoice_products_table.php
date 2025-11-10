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
        if (Schema::hasColumn('invoice_products', 'gender_type')) {
            Schema::table('invoice_products', function (Blueprint $table) {
                $table->dropColumn('gender_type');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasColumn('invoice_products', 'gender_type')) {
            Schema::table('invoice_products', function (Blueprint $table) {
                $table->enum('gender_type', ['boys', 'girls', 'boys_girls', 'accessories'])->after('price_yuan');
            });
        }
    }
};
