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

        Schema::table('employee_rate_requests', function (Blueprint $table) {
            $table->boolean('hr_approved')->default(false)->after('approved_by');
            $table->timestamp('hr_approved_at')->nullable()->after('hr_approved');
            $table->integer('hr_approved_by')->nullable()->after('hr_approved_at');
        });

        Schema::table('employee_rate_requests', function (Blueprint $table) {
            $table->dropColumn(['approved_by', 'approved_at']);
        });

        Schema::table('employee_rate_requests', function (Blueprint $table) {
            $table->boolean('do_approved')->default(false)->after('hr_approved_by');
            $table->timestamp('do_approved_at')->nullable()->after('do_approved');
            $table->integer('do_approved_by')->nullable()->after('do_approved_at');
        });

        Schema::table('employee_rate_requests', function (Blueprint $table) {
            $table->boolean('admin_approved')->default(false)->after('do_approved_by');
            $table->timestamp('admin_approved_at')->nullable()->after('admin_approved');
            $table->integer('admin_approved_by')->nullable()->after('admin_approved_at');
        });


        Schema::table('employee_rate_requests', function (Blueprint $table) {
            $table->dropColumn('payroll_hours_type');
        });

        Schema::table('employee_rate_requests', function (Blueprint $table) {
            $table->enum('payroll_hours_type', ['fixed', 'percentage'])
                ->default('fixed')
                ->after('payroll_hours');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employee_rate_requests', function (Blueprint $table) {
            $table->integer('approved_by')->nullable()->after('status');
            $table->timestamp('approved_at')->nullable()->after('approved_by');
            
            $table->dropColumn('hr_approved');
            $table->dropColumn('hr_approved_at');
            $table->dropColumn('hr_approved_by');
            $table->dropColumn('do_approved');
            $table->dropColumn('do_approved_at');
            $table->dropColumn('do_approved_by');
            $table->dropColumn('admin_approved');
            $table->dropColumn('admin_approved_at');
            $table->dropColumn('admin_approved_by');

        });
        Schema::table('employee_rate_requests', function (Blueprint $table) {
            $table->dropColumn('payroll_hours_type');
        });
    
        Schema::table('employee_rate_requests', function (Blueprint $table) {
            $table->enum('payroll_hours_type', ['fixed', 'percentage'])
                  ->default('fixed')
                  ->notNullable();
        });
    }
};
