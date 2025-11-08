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
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->text('description')->nullable();
            $table->timestamps();
        });

        // إضافة سجل افتراضي لسعر التوصيل
        \DB::table('settings')->insert([
            'key' => 'delivery_fee',
            'value' => '5000',
            'description' => 'سعر التوصيل بالدينار العراقي',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // إضافة سجل افتراضي لربح الفروقات
        \DB::table('settings')->insert([
            'key' => 'profit_margin',
            'value' => '0',
            'description' => 'ربح الفروقات بالدينار العراقي',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
