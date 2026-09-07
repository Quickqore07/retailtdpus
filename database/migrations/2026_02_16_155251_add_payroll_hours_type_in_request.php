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
        Schema::table('employee_rate_requests', function (Blueprint $table) {
            $table->enum('payroll_hours_type', ['hours', 'percentage'])->default('hours')->after('payroll_hours');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employee_rate_requests', function (Blueprint $table) {
            //
        });
    }
};
