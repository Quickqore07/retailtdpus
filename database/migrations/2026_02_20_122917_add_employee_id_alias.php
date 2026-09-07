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
            $table->string('employee_id_1')->nullable()->after('employee_name_3');
            $table->string('employee_id_2')->nullable()->after('employee_id_1');
            $table->string('employee_id_3')->nullable()->after('employee_id_2');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employee', function (Blueprint $table) {
            $table->dropColumn('employee_id_1');
            $table->dropColumn('employee_id_2');
            $table->dropColumn('employee_id_3');
        });
    }
};
