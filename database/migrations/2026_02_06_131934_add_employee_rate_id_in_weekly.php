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
        Schema::table('employee_weekly_summary', function (Blueprint $table) {
            $table->unsignedBigInteger('employee_rate_id')->nullable()->after('employee_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employee_weekly_summary', function (Blueprint $table) {
            $table->dropColumn('employee_rate_id');
        });
    }
};
