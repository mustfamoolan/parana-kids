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
        Schema::create('investment_investors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('investment_id')->constrained('investments')->onDelete('cascade');
            $table->foreignId('investor_id')->constrained('investors')->onDelete('cascade');
            $table->decimal('profit_percentage', 5, 2)->comment('نسبة الربح');
            $table->decimal('investment_amount', 15, 2)->comment('مبلغ الاستثمار');
            $table->text('notes')->nullable()->comment('ملاحظات');
            $table->timestamps();

            $table->unique(['investment_id', 'investor_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('investment_investors');
    }
};
