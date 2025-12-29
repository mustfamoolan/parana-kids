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
        Schema::table('fcm_tokens', function (Blueprint $table) {
            $table->string('app_type')->nullable()->after('device_type'); // delegate_mobile, web, etc.
            $table->boolean('is_active')->default(true)->after('app_type');
            
            $table->index('app_type');
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('fcm_tokens', function (Blueprint $table) {
            $table->dropIndex(['app_type']);
            $table->dropIndex(['is_active']);
            $table->dropColumn(['app_type', 'is_active']);
        });
    }
};
