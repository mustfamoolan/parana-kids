<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use App\Models\Warehouse;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // إضافة الحقل كـ nullable أولاً
        Schema::table('expenses', function (Blueprint $table) {
            $table->foreignId('warehouse_id')->nullable()->after('id')->constrained('warehouses')->onDelete('restrict');
        });

        // معالجة المصروفات الموجودة: ربطها بأول مخزن متاح
        $defaultWarehouse = Warehouse::first();
        if ($defaultWarehouse) {
            DB::table('expenses')
                ->whereNull('warehouse_id')
                ->update(['warehouse_id' => $defaultWarehouse->id]);
        }

        // جعل الحقل required بعد ملء البيانات
        Schema::table('expenses', function (Blueprint $table) {
            $table->foreignId('warehouse_id')->nullable(false)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            $table->dropForeign(['warehouse_id']);
            $table->dropColumn('warehouse_id');
        });
    }
};
