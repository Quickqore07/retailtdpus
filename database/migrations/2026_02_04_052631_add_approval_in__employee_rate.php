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
        Schema::create('employee_rate_requests', function (Blueprint $table) {
            $table->id();
            $table->integer('employee_rate_id')->nullable();
            $table->integer('employee_id');
            $table->integer('role_id');
            $table->enum('pay_type', ['HR', 'WK'])->default('HR');
            $table->enum('rate_type', ['Payroll Regular', 'Payroll Slab' ,'1099 Regular','1099 Slab'])->default('Payroll Regular');
            $table->enum('check_payment_type', ['percentage', 'fixed'])->default('fixed');
            $table->decimal('check_payment_amount', 10, 2)->default(0);
            $table->decimal('rate', 10, 2)->default(0);
            $table->integer('slab_first_hours')->default(0);
            $table->decimal('slab_rest_rate', 10, 2)->default(0);
            $table->integer('payroll_hours')->default(0);
            $table->date('effective_date');
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->text('notes')->nullable();
            $table->integer('approved_by')->nullable();
            $table->datetime('approved_at')->nullable();
            $table->integer('rejected_by')->nullable();
            $table->datetime('rejected_at')->nullable();
            $table->timestamps();
        });

        Schema::create('employee_hours_requests', function (Blueprint $table) {
            $table->id();
            $table->integer('employee_hours_id')->nullable();
            $table->date('date')->nullable();
            $table->foreignId('employee_id')->constrained('employee');
            $table->string('employee_name')->nullable();
            $table->foreignId('company_id')->constrained('company');
            $table->string('dev_id')->nullable();
            $table->string('ssn')->nullable();
            $table->string('pay_id')->nullable();
            $table->enum('pay_type', ['HR', 'WK'])->default('HR');
            $table->decimal('total_hours', 10, 2)->default(0);
            $table->decimal('tips', 10, 2)->default(0);
            $table->decimal('mileage_excess', 10, 2)->default(0);
            $table->decimal('incentive', 10, 2)->default(0);
            $table->decimal('bonus', 10, 2)->default(0);
            $table->string('home_store')->nullable();
            $table->foreignId('role_id')->constrained('employee_roles');
            $table->decimal('pay_rate', 10, 2)->default(0);
            $table->decimal('tips_due', 10, 2)->default(0);
            $table->decimal('mileage_due', 10, 2)->default(0);
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->text('notes')->nullable();
            $table->integer('approved_by')->nullable();
            $table->datetime('approved_at')->nullable();
            $table->integer('rejected_by')->nullable();
            $table->datetime('rejected_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employee_rate_requests');
    }
};
