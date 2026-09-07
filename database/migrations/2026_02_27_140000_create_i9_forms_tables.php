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
        Schema::create('i9_forms', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('employee_id')->nullable()->index();
            $table->unsignedBigInteger('onboarding_list_id')->nullable()->index();

            // Section 1 - Only I-9 specific fields (name/address/DOB/SSN/email/phone come from onboarding_list or employee)
            $table->string('other_last_names')->nullable();
            $table->unsignedTinyInteger('citizenship_status')->nullable()->comment('1=US Citizen, 2=Noncitizen national, 3=LPR, 4=Alien authorized');
            $table->string('uscis_or_a_number', 50)->nullable()->comment('For option 3 (LPR)');
            $table->string('alien_authorized_exp_date', 20)->nullable()->comment('For option 4');
            $table->string('uscis_a_number', 50)->nullable()->comment('For option 4');
            $table->string('form_i94_admission_number', 50)->nullable()->comment('For option 4');
            $table->string('foreign_passport_number', 100)->nullable()->comment('For option 4');
            $table->string('employee_signature')->nullable();
            $table->string('section1_today_date', 20)->nullable();

            // Section 2 - Employer Review and Verification
            // List A
            $table->string('list_a_doc_title_1')->nullable();
            $table->string('list_a_issuing_authority_1')->nullable();
            $table->string('list_a_document_number_1')->nullable();
            $table->string('list_a_expiration_date_1', 20)->nullable();
            $table->string('list_a_doc_title_2')->nullable();
            $table->string('list_a_issuing_authority_2')->nullable();
            $table->string('list_a_document_number_2')->nullable();
            $table->string('list_a_expiration_date_2', 20)->nullable();
            $table->string('list_a_doc_title_3')->nullable();
            $table->string('list_a_issuing_authority_3')->nullable();
            $table->string('list_a_document_number_3')->nullable();
            $table->string('list_a_expiration_date_3', 20)->nullable();
            $table->string('list_a_file_path')->nullable();
            $table->string('list_b_file_path')->nullable();
            $table->string('list_c_file_path')->nullable();
            $table->string('additional_document_path')->nullable();
            $table->string('additional_document_label', 255)->nullable();
            // List B
            $table->string('list_b_doc_title')->nullable();
            $table->string('list_b_issuing_authority')->nullable();
            $table->string('list_b_document_number')->nullable();
            $table->string('list_b_expiration_date', 20)->nullable();
            // List C
            $table->string('list_c_doc_title')->nullable();
            $table->string('list_c_issuing_authority')->nullable();
            $table->string('list_c_document_number')->nullable();
            $table->string('list_c_expiration_date', 20)->nullable();

            $table->text('additional_information')->nullable();
            $table->boolean('alternative_procedure')->default(false);
            $table->string('first_day_employment', 20)->nullable();
            $table->string('employer_name')->nullable();
            $table->string('employer_signature')->nullable();
            $table->string('employer_today_date', 20)->nullable();
            $table->string('employer_business_name')->nullable();
            $table->text('employer_business_address')->nullable();

            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
        });

        // Supplement A - Preparer and/or Translator Certification (multiple per form)
        Schema::create('i9_preparer_translators', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('i9_form_id')->index();
            $table->string('signature')->nullable();
            $table->string('signature_date', 20)->nullable();
            $table->string('last_name')->nullable();
            $table->string('first_name')->nullable();
            $table->string('middle_initial', 10)->nullable();
            $table->string('address')->nullable();
            $table->string('city')->nullable();
            $table->string('state', 50)->nullable();
            $table->string('zip_code', 20)->nullable();
            $table->timestamps();

            $table->foreign('i9_form_id')->references('id')->on('i9_forms')->onDelete('cascade');
        });

        // Supplement B - Reverification and Rehire (multiple per form)
        Schema::create('i9_reverifications', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('i9_form_id')->index();
            $table->string('rehire_date', 20)->nullable();
            $table->string('new_last_name')->nullable();
            $table->string('new_first_name')->nullable();
            $table->string('new_middle_initial', 10)->nullable();
            $table->string('document_title')->nullable();
            $table->string('document_number')->nullable();
            $table->string('expiration_date', 20)->nullable();
            $table->string('employer_representative_name')->nullable();
            $table->string('employer_signature')->nullable();
            $table->string('today_date', 20)->nullable();
            $table->text('additional_information')->nullable();
            $table->boolean('alternative_procedure_dhs')->default(false);
            
            $table->timestamps();

            $table->foreign('i9_form_id')->references('id')->on('i9_forms')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('i9_reverifications');
        Schema::dropIfExists('i9_preparer_translators');
        Schema::dropIfExists('i9_forms');
    }
};
