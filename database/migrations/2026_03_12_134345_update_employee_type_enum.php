<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement("ALTER TABLE employee MODIFY COLUMN employee_type ENUM('New', 'Completed','Existing','Rate Approval') DEFAULT 'New'");
        Schema::table('employee', function (Blueprint $table) {
            $table->boolean('mail_sent')->default(false)->after('employee_type');
        });
        Schema::table('direct_deposits', function (Blueprint $table) {
            $table->string('deposit_type_1', 20)->default('percentage')->after('deposit_amount_1');
            $table->string('deposit_type_2', 20)->nullable()->after('deposit_amount_2');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("ALTER TABLE employee MODIFY COLUMN employee_type ENUM('New', 'Completed','Existing') DEFAULT 'New'");
        Schema::table('employee', function (Blueprint $table) {
            $table->dropColumn('mail_sent');
        });
        Schema::table('direct_deposits', function (Blueprint $table) {
            $table->dropColumn(['deposit_type_1', 'deposit_type_2']);
        });
    }
};
