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
        // في MySQL، لا يمكن تعديل enum مباشرة، لذا نحذف ونعيد إنشاء
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('gender_type');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->enum('gender_type', ['boys', 'girls', 'accessories', 'boys_girls'])->nullable()->after('code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('gender_type');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->enum('gender_type', ['boys', 'girls', 'accessories'])->nullable()->after('code');
        });
    }
};
