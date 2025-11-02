<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // الحصول على القيم الحالية في enum
        $result = DB::select("SHOW COLUMNS FROM `product_movements` WHERE Field = 'movement_type'");
        $enum = $result[0]->Type;

        // استخراج القيم الحالية من enum
        preg_match("/^enum\((.*)\)$/", $enum, $matches);
        $values = str_replace("'", "", $matches[1]);
        $currentValues = explode(',', $values);

        // إضافة 'sell' إذا لم يكن موجوداً
        if (!in_array('sell', $currentValues)) {
            $currentValues[] = 'sell';
        }

        // تحديث enum
        $enumString = "'" . implode("','", $currentValues) . "'";
        DB::statement("ALTER TABLE `product_movements` MODIFY COLUMN `movement_type` ENUM($enumString) NOT NULL");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // لا يمكن حذف 'sell' إذا كانت هناك بيانات تستخدمه
        // سنتركه كما هو
    }
};

