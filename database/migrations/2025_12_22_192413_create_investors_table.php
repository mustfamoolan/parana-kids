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
        Schema::create('investors', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('phone')->unique();
            $table->string('password');
            $table->decimal('balance', 15, 2)->default(0)->comment('الرصيد الحالي');
            $table->decimal('total_profit', 15, 2)->default(0)->comment('إجمالي الأرباح');
            $table->decimal('total_withdrawals', 15, 2)->default(0)->comment('إجمالي السحوبات');
            $table->decimal('total_deposits', 15, 2)->default(0)->comment('إجمالي الإيداعات');
            $table->text('notes')->nullable();
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('investors');
    }
};
