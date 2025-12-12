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
            // إضافة حقول إضافية من API
            if (!Schema::hasColumn('alwaseet_shipments', 'pickup_id')) {
                $table->string('pickup_id')->nullable()->after('qr_id');
            }
            if (!Schema::hasColumn('alwaseet_shipments', 'merchant_invoice_id')) {
                $table->string('merchant_invoice_id')->nullable()->after('pickup_id');
            }
            // حفظ جميع بيانات API الكاملة كـ JSON
            if (!Schema::hasColumn('alwaseet_shipments', 'api_data')) {
                $table->json('api_data')->nullable()->after('merchant_invoice_id');
            }
        });
        
        // إضافة index بشكل منفصل لتجنب مشاكل
        if (Schema::hasColumn('alwaseet_shipments', 'pickup_id')) {
            Schema::table('alwaseet_shipments', function (Blueprint $table) {
                $table->index('pickup_id');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('alwaseet_shipments', function (Blueprint $table) {
            // حذف index أولاً
            $sm = Schema::getConnection()->getDoctrineSchemaManager();
            $indexesFound = $sm->listTableIndexes('alwaseet_shipments');
            if (array_key_exists('alwaseet_shipments_pickup_id_index', $indexesFound)) {
                $table->dropIndex('alwaseet_shipments_pickup_id_index');
            }
            
            // حذف الأعمدة
            if (Schema::hasColumn('alwaseet_shipments', 'pickup_id')) {
                $table->dropColumn('pickup_id');
            }
            if (Schema::hasColumn('alwaseet_shipments', 'merchant_invoice_id')) {
                $table->dropColumn('merchant_invoice_id');
            }
            if (Schema::hasColumn('alwaseet_shipments', 'api_data')) {
                $table->dropColumn('api_data');
            }
        });
    }
};
