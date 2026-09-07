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
        Schema::create('benefit_enrollments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('onboarding_list_id')->unique();

            // Use onboarding_list for: last_name, first_name, middle_initial, dob, ssn, address, city, state, zip, email (no duplicate)
            // Benefit form only: gender, salary, hire_date + benefit choices
            $table->string('gender', 20)->nullable();
            $table->decimal('salary', 12, 2)->nullable();
            $table->date('hire_date')->nullable();

            // Health care
            $table->string('medical_plan', 50)->nullable();
            $table->text('medical_waive_reason')->nullable();
            $table->string('dental', 20)->nullable();
            $table->string('vision', 20)->nullable();

            // Life/AD&D
            $table->string('life_option', 20)->nullable();
            $table->decimal('emp_life_amount', 12, 2)->nullable();
            $table->decimal('spouse_life_amount', 12, 2)->nullable();
            $table->decimal('child_life_amount', 12, 2)->nullable();
            $table->decimal('emp_life_cost', 12, 2)->nullable();
            $table->decimal('spouse_life_cost', 12, 2)->nullable();
            $table->decimal('child_life_cost', 12, 2)->nullable();

            // Pre-tax / signature
            $table->string('tax_method', 20)->nullable();
            $table->string('employee_sign')->nullable();
            $table->date('employee_date')->nullable();

            $table->timestamps();

            $table->foreign('onboarding_list_id')->references('id')->on('onboarding_list')->onDelete('cascade');
        });

        Schema::create('benefit_enrollment_dependents', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('benefit_enrollment_id');

            $table->string('last_name')->nullable();
            $table->string('first_name')->nullable();
            $table->string('gender', 20)->nullable();
            $table->date('dob')->nullable();
            $table->string('ssn', 20)->nullable();
            $table->string('relationship', 50)->nullable();
            $table->unsignedTinyInteger('medical')->default(0);
            $table->unsignedTinyInteger('dental')->default(0);
            $table->unsignedTinyInteger('vision')->default(0);

            $table->timestamps();

            $table->foreign('benefit_enrollment_id')->references('id')->on('benefit_enrollments')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('benefit_enrollment_dependents');
        Schema::dropIfExists('benefit_enrollments');
    }
};
