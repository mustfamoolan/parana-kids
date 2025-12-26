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
        Schema::create('investment_targets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('investment_id')->constrained('investments')->onDelete('cascade');
            $table->string('target_type')->comment('نوع الهدف (product/warehouse/private_warehouse)');
            $table->unsignedBigInteger('target_id')->comment('معرف الهدف');
            $table->decimal('value', 15, 2)->default(0)->comment('قيمة هذا الهدف');
            $table->timestamps();

            $table->index(['target_type', 'target_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('investment_targets');
    }
};
