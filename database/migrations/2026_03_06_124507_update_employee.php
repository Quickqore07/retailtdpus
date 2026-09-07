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
        Schema::table('employee', function (Blueprint $table) {
            $table->string('first_name')->nullable()->after('employee_id');
            $table->string('last_name')->nullable()->after('first_name');
            $table->string('middle_name')->nullable()->after('last_name');
            $table->integer('workgroup_id')->after('middle_name');
        });
        Schema::table('check_master', function (Blueprint $table) {
            $table->boolean('is_1099')->default(false)->after('check_amount');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employee', function (Blueprint $table) {
            $table->dropColumn('first_name');
            $table->dropColumn('last_name');
            $table->dropColumn('middle_name');
            $table->dropColumn('workgroup_id');
        });
        Schema::table('check_master', function (Blueprint $table) {
            $table->dropColumn('is_1099');
        });
    }
};
