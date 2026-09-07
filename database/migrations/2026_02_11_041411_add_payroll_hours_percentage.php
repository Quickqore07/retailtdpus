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
        Schema::table('employee_rates', function (Blueprint $table) {
            $table->enum('payroll_hours_type', ['percentage', 'fixed'])->default('fixed')->before('payroll_hours');
        });
        Schema::table('employee_weekly_summary', function (Blueprint $table) {
            $table->decimal('payroll_hours', 10, 2)->nullable()->after('total_hours');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employee_rates', function (Blueprint $table) {
            $table->dropColumn('payroll_hours_type');
        });
        Schema::table('employee_weekly_summary', function (Blueprint $table) {
            $table->dropColumn('payroll_hours');
        });
    }
};
