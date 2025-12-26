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
        Schema::create('treasuries', function (Blueprint $table) {
            $table->id();
            $table->string('name')->comment('اسم الخزنة');
            $table->decimal('initial_capital', 15, 2)->default(0)->comment('رأس المال');
            $table->decimal('current_balance', 15, 2)->default(0)->comment('الرصيد الحالي');
            $table->text('notes')->nullable()->comment('ملاحظات');
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('treasuries');
    }
};
