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
            // تحويل size_reviewed من boolean إلى enum
            if (Schema::hasColumn('orders', 'size_reviewed')) {
                $table->dropColumn('size_reviewed');
            }
            $table->enum('size_reviewed', ['not_reviewed', 'reviewed'])
                  ->default('not_reviewed')
                  ->after('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // إعادة boolean
            if (Schema::hasColumn('orders', 'size_reviewed')) {
                $table->dropColumn('size_reviewed');
            }
            $table->boolean('size_reviewed')->default(false)->after('status');
        });
    }
};
