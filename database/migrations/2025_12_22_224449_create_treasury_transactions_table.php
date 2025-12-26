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
        Schema::create('treasury_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('treasury_id')->constrained('treasuries')->onDelete('cascade');
            $table->enum('transaction_type', ['deposit', 'withdrawal'])->comment('نوع العملية');
            $table->decimal('amount', 15, 2)->comment('المبلغ');
            $table->string('reference_type')->nullable()->comment('نوع المرجع (order/profit_record/manual)');
            $table->unsignedBigInteger('reference_id')->nullable()->comment('معرف المرجع');
            $table->text('description')->nullable()->comment('الوصف');
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->timestamps();

            $table->index(['reference_type', 'reference_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('treasury_transactions');
    }
};
