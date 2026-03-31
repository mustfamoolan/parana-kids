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
        Schema::table('users', function (Blueprint $table) {
            $table->string('google_id')->nullable()->index()->after('id');
        });
        
        // تحديث enum role لإضافة customer
        DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('admin', 'supplier', 'delegate', 'private_supplier', 'customer') DEFAULT 'delegate'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // إرجاع enum role إلى الحالة السابقة
        DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('admin', 'supplier', 'delegate', 'private_supplier') DEFAULT 'delegate'");
        
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('google_id');
        });
    }
};
