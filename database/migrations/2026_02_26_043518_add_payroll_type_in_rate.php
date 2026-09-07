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
            $table->enum('payroll_type', ['Direct Deposit', 'Print on site'])->nullable()->after('rate_type');
        });

        Schema::table('employee_rate_requests', function (Blueprint $table) {
            $table->enum('payroll_type', ['Direct Deposit', 'Print on site'])->nullable()->after('rate_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employee_rates', function (Blueprint $table) {
            $table->dropColumn('payroll_type');       
        });
        Schema::table('employee_rate_requests', function (Blueprint $table) {
            $table->dropColumn('payroll_type');       
        });
    }
};
