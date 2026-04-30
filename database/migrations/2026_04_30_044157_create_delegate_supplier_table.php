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
        Schema::create('delegate_supplier', function (Blueprint $table) {
            $table->id();
            $table->foreignId('delegate_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('supplier_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
            
            $table->unique(['delegate_id', 'supplier_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('delegate_supplier');
    }
};
