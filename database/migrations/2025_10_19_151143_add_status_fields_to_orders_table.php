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
        Schema::table('orders', function (Blueprint $table) {
            $table->string('cancellation_reason')->nullable()->after('delivery_code');
            $table->text('return_notes')->nullable()->after('cancellation_reason');
            $table->timestamp('cancelled_at')->nullable()->after('return_notes');
            $table->timestamp('returned_at')->nullable()->after('cancelled_at');
            $table->timestamp('exchanged_at')->nullable()->after('returned_at');
            $table->foreignId('processed_by')->nullable()->constrained('users')->after('exchanged_at');
            $table->boolean('is_partial_return')->default(false)->after('processed_by');
            $table->boolean('is_partial_exchange')->default(false)->after('is_partial_return');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['processed_by']);
            $table->dropColumn([
                'cancellation_reason',
                'return_notes',
                'cancelled_at',
                'returned_at',
                'exchanged_at',
                'processed_by',
                'is_partial_return',
                'is_partial_exchange'
            ]);
        });
    }
};
