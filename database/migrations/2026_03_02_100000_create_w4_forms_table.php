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
        Schema::create('w4_forms', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('employee_id')->nullable()->index();
            $table->unsignedBigInteger('onboarding_list_id')->nullable()->index();

            // Name, address, SSN live in onboarding_list only; W-4 reads/updates them there (no duplication)

            // Step 1(c) - Filing status: 1=Single/MFS, 2=Married joint/surviving, 3=Head of household
            $table->unsignedTinyInteger('single_or_married')->nullable();
            $table->unsignedTinyInteger('married_filing')->nullable();
            $table->unsignedTinyInteger('head_of_household')->nullable();

            // Step 2 - Multiple jobs: 1=estimator, 2=worksheet, 3=two jobs box
            $table->unsignedTinyInteger('multiple_jobs_and_spouse_works')->nullable();

            // Step 3 - Dependents and credits
            $table->string('qualifying_children', 50)->nullable();
            $table->string('dependents', 50)->nullable();
            $table->string('total_amount', 50)->nullable();

            // Step 4 - Other adjustments
            $table->string('other_income', 50)->nullable();
            $table->string('deductions', 50)->nullable();
            $table->string('extra_withholding', 50)->nullable();
            

            // Page 3 - Step 2(b) Multiple Jobs Worksheet
            $table->string('worksheet_line1', 50)->nullable();
            $table->string('worksheet_line2a', 50)->nullable();
            $table->string('worksheet_line2b', 50)->nullable();
            $table->string('worksheet_line2c', 50)->nullable();
            $table->string('worksheet_line3', 50)->nullable();
            $table->string('worksheet_line4', 50)->nullable();
            // Page 3 - Step 4(b) Deductions Worksheet
            $table->string('worksheet_ded1', 50)->nullable();
            $table->string('worksheet_ded2', 50)->nullable();
            $table->string('worksheet_ded3', 50)->nullable();
            $table->string('worksheet_ded4', 50)->nullable();
            $table->string('worksheet_ded5', 50)->nullable();

            // Step 5 - Signature
            $table->string('employee_sign')->nullable();
            $table->date('employee_date')->nullable();

            // Employer only (for display/record)
            $table->string('employer_name')->nullable();
            $table->string('date_of_employment', 50)->nullable();


            $table->timestamps();

            $table->foreign('onboarding_list_id')->references('id')->on('onboarding_list')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('w4_forms');
    }
};
