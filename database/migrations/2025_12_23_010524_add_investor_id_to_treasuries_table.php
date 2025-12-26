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
        Schema::table('treasuries', function (Blueprint $table) {
            $table->foreignId('investor_id')->nullable()->after('created_by')->constrained('investors')->onDelete('cascade')->comment('المستثمر المرتبط بهذه الخزنة');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('treasuries', function (Blueprint $table) {
            $table->dropForeign(['investor_id']);
            $table->dropColumn('investor_id');
        });
    }
};
