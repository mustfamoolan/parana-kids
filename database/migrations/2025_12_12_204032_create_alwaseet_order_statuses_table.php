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
        Schema::create('alwaseet_order_statuses', function (Blueprint $table) {
            $table->id();
            $table->string('status_id')->unique()->comment('معرف الحالة من API');
            $table->string('status_text')->comment('نص الحالة');
            $table->boolean('is_active')->default(true)->comment('هل الحالة نشطة');
            $table->integer('display_order')->default(0)->comment('ترتيب العرض');
            $table->timestamps();
            
            $table->index('status_id');
            $table->index('is_active');
            $table->index('display_order');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('alwaseet_order_statuses');
    }
};
