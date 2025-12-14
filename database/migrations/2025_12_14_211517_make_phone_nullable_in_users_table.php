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
        Schema::table('users', function (Blueprint $table) {
            // إزالة unique constraint من phone أولاً
            $table->dropUnique(['phone']);
            // جعل phone nullable
            $table->string('phone')->nullable()->change();
            // إعادة إضافة unique constraint (لكن nullable يسمح بقيم null متعددة)
            // في MySQL/MariaDB، يمكن أن يكون هناك عدة nulls مع unique constraint
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // إزالة nullable
            $table->string('phone')->nullable(false)->change();
            // إعادة إضافة unique constraint
            $table->unique('phone');
        });
    }
};
