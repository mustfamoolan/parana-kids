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
        Schema::table('alwaseet_shipments', function (Blueprint $table) {
            // إضافة index مركب لتحسين استعلامات COUNT حسب status_id و order_id
            if (!$this->hasIndex('alwaseet_shipments', 'alwaseet_shipments_order_id_status_id_index')) {
                $table->index(['order_id', 'status_id'], 'alwaseet_shipments_order_id_status_id_index');
            }
            
            // إضافة index على synced_at لتحسين استعلامات التحديث
            if (!$this->hasIndex('alwaseet_shipments', 'alwaseet_shipments_synced_at_index')) {
                $table->index('synced_at', 'alwaseet_shipments_synced_at_index');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('alwaseet_shipments', function (Blueprint $table) {
            $table->dropIndex('alwaseet_shipments_order_id_status_id_index');
            $table->dropIndex('alwaseet_shipments_synced_at_index');
        });
    }

    /**
     * Check if index exists
     */
    private function hasIndex($table, $indexName): bool
    {
        $connection = Schema::getConnection();
        $databaseName = $connection->getDatabaseName();
        
        $result = $connection->select(
            "SELECT COUNT(*) as count FROM information_schema.statistics 
             WHERE table_schema = ? AND table_name = ? AND index_name = ?",
            [$databaseName, $table, $indexName]
        );
        
        return $result[0]->count > 0;
    }
};
