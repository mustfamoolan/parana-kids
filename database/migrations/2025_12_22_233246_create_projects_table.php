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
        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->string('name')->comment('اسم المشروع');
            $table->enum('project_type', ['partner', 'investors'])->comment('نوع المشروع');
            $table->foreignId('treasury_id')->nullable()->constrained('treasuries')->onDelete('cascade')->comment('خزنة فرعية للمشروع');
            $table->enum('status', ['active', 'completed', 'cancelled', 'suspended'])->default('active')->comment('حالة المشروع');
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
        Schema::dropIfExists('projects');
    }
};
