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
        Schema::create('employee_weekly_summary', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('employee_id');
            $table->unsignedBigInteger('role_id');
            $table->unsignedBigInteger('company_id');
            $table->date('eow'); // End of Week - biweek period end date
            $table->string('pay_type', 10); // HR or WK
            
            // Aggregated hours
            $table->decimal('total_hours', 10, 2)->default(0);
            $table->decimal('regular_hours', 10, 2)->default(0);
            $table->decimal('overtime_hours', 10, 2)->default(0);
            
            // Aggregated amounts
            $table->decimal('tips', 10, 2)->default(0);
            $table->decimal('mileage_excess', 10, 2)->default(0);
            $table->decimal('incentive', 10, 2)->default(0);
            $table->decimal('bonus', 10, 2)->default(0);
            $table->decimal('tips_due', 10, 2)->default(0);
            $table->decimal('mileage_due', 10, 2)->default(0);
            
            // Calculated payment methods
            $table->decimal('payroll_methods', 10, 2)->default(0);
            $table->decimal('check_methods', 10, 2)->default(0);
            $table->decimal('instant_methods', 10, 2)->default(0);
            
            // Calculated totals
            $table->decimal('gross_pay', 10, 2)->default(0);
            $table->decimal('total_earnings', 10, 2)->default(0);
            $table->decimal('hr_pay', 10, 2)->default(0);
            
            // Rate information snapshot
            $table->decimal('rate', 10, 2)->default(0);
            $table->string('rate_type', 50)->nullable();
            $table->decimal('slab_first_hours', 10, 2)->nullable();
            $table->decimal('slab_rest_rate', 10, 2)->nullable();
            
            // Minimum wage tracking
            $table->decimal('min_wage_hourly', 10, 2)->nullable();
            $table->decimal('min_wage_weekly', 10, 2)->nullable();
            $table->decimal('min_wage_due', 10, 2)->default(0);
            
            $table->timestamps();
            
            // Indexes for fast querying
            $table->index(['employee_id', 'role_id', 'eow']);
            $table->index(['company_id', 'eow']);
            $table->index('eow');
            
            // Unique constraint to prevent duplicates
            $table->unique(['employee_id', 'role_id', 'company_id', 'eow', 'pay_type'], 'employee_weekly_unique');
            
            // Foreign keys
            $table->foreign('employee_id')->references('id')->on('employee')->onDelete('cascade');
            $table->foreign('role_id')->references('id')->on('employee_roles')->onDelete('cascade');
            $table->foreign('company_id')->references('id')->on('company')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employee_weekly_summary');
    }
};
