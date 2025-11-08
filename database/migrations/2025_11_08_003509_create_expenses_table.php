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
        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->enum('expense_type', ['rent', 'salary', 'other']);
            $table->decimal('amount', 10, 2);
            $table->date('expense_date');
            $table->string('person_name')->nullable(); // للرواتب فقط
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null'); // ربط بجدول users للمندوبين/المجهزين
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('expenses');
    }
};
