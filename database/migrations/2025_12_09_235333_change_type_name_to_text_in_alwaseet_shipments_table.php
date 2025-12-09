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
        Schema::table('alwaseet_shipments', function (Blueprint $table) {
            $table->text('type_name')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('alwaseet_shipments', function (Blueprint $table) {
            $table->string('type_name')->change();
        });
    }
};
