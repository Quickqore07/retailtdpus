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
        Schema::create('employee_roles', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('code');
            $table->boolean('active')->default(true);
            $table->timestamps();
        });
        Schema::create('employee', function (Blueprint $table) {
            $table->id();
            $table->date('hire_date')->nullable();
            $table->foreignId('company_id')->constrained('company');
            $table->string('employee_id')->nullable();
            $table->string('pos_name');
            $table->string('ssn')->nullable();

            $table->string('employee_name_1')->nullable();
            $table->string('employee_name_2')->nullable();
            $table->string('employee_name_3')->nullable();
            $table->string('check_name')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->string('street')->nullable();
            $table->string('city')->nullable();
            $table->string('zip')->nullable();
            $table->string('state')->nullable();
            $table->date('dob')->nullable();

            $table->date('termination_date')->nullable();

            $table->enum('employee_type', ['New', 'Existing','Completed'])->default('New');

       

            $table->string('emergency_contact_name')->nullable();
            $table->string('emergency_contact_phone')->nullable();
            $table->string('emergency_contact_relationship')->nullable();

            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        Schema::create('employee_rates', function (Blueprint $table) {
            $table->id();
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

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employee_roles');
        Schema::dropIfExists('employee');
        Schema::dropIfExists('employee_rates');
    }
};
