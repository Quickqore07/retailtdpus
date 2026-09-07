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
        Schema::table('employee', function (Blueprint $table) {
            $table->enum('onboarding_status', ['pending', 'form_submitted', 'in_complete_form', 'verified'])->default('pending')->after('employee_type');        
        });

        Schema::create('onboarding_list', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('employee_id')->nullable();
            $table->unsignedBigInteger('process_id')->default(1)->nullable();
            $table->string('onboarding_number')->nullable();
            $table->boolean('employee_handbook_agreed')->nullable();
            $table->unsignedBigInteger('application_job_id')->nullable();
            $table->unsignedBigInteger('job_id')->nullable();
            $table->unsignedBigInteger('company_id')->nullable();
            $table->unsignedBigInteger('store_user_id')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('nick_name')->nullable();
            $table->string('real_name')->nullable();
            $table->string('applicant_first_name')->nullable();
            $table->string('applicant_last_name')->nullable();
            $table->string('applicant_address')->nullable();
            $table->string('applicant_contact_number')->nullable();
            $table->string('applicant_email')->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('zip_code')->nullable();
            $table->string('zipcode')->nullable();
            $table->string('applicant_status')->nullable();
            $table->json('applicant_hire_detail')->nullable();
            $table->json('sent_email_hire_data')->nullable();
            $table->string('applicant_pay_type')->nullable();
            $table->string('applicant_pay_period')->nullable();
            $table->string('status')->nullable();
            $table->string('final_status')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->json('verified_i9')->nullable();
            $table->json('verified_w4')->nullable();
            $table->dateTime('read_date')->nullable();
            $table->dateTime('verified_date')->nullable();
            $table->string('submit_application_date')->nullable();
            $table->text('remark')->nullable();
            $table->string('alternative_procedure')->nullable();
            $table->date('doj')->nullable();
            $table->string('benifits')->nullable();
            $table->dateTime('date')->nullable();
            $table->string('new_change')->nullable();
            $table->string('final_onboarding_hr_status')->nullable();
            $table->date('hr_verify_date')->nullable();
            $table->date('send_onboarding_email_date')->nullable();
            $table->string('new_process_status')->nullable();
            $table->string('picture_file')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employee', function (Blueprint $table) {
            $table->dropColumn('onboarding_status');
        });
        Schema::dropIfExists('onboarding_list');
    }
};
