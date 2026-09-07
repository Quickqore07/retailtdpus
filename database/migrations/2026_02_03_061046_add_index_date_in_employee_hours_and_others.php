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
        Schema::table('employee_hours', function (Blueprint $table) {
            $table->index('date');
            $table->index(['date', 'employee_id']);
            $table->index(['date', 'company_id']);
        });
        Schema::table('payroll_check_amounts', function (Blueprint $table) {
            $table->index('eow');
            $table->index(['eow', 'employee_id']);
            $table->index(['eow', 'company_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employee_hours', function (Blueprint $table) {
            $table->dropIndex('date');
            $table->dropIndex(['date', 'employee_id']);
            $table->dropIndex(['date', 'company_id']);
        });
        Schema::table('payroll_check_amounts', function (Blueprint $table) {
            $table->dropIndex('eow');
            $table->dropIndex(['eow', 'employee_id']);
            $table->dropIndex(['eow', 'company_id']);
        });
    }
};
