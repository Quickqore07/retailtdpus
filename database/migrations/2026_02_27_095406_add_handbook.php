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
        Schema::create('onboarding_handbook', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('onboarding_id')->nullable();
            $table->string('employee_sign')->nullable();
            $table->string('date')->nullable();
            $table->string('print_full_name')->nullable();
            $table->boolean('is_checked')->default(false);
            $table->timestamps();
        });

        Schema::table('onboarding_list', function (Blueprint $table) {
            $table->string('apt_number')->nullable()->after('picture_file');
            $table->string('work_permit_issuer')->nullable()->after('apt_number');
            $table->string('work_permit_document_path')->nullable()->after('work_permit_issuer');
            $table->string('applicant_middle_initial')->nullable()->after('applicant_last_name');
            $table->string('dob')->nullable()->after('applicant_middle_initial');
            $table->string('emergency_contact_name')->nullable()->after('dob');
            $table->string('emergency_contact_phone')->nullable()->after('emergency_contact_name');
            $table->string('emergency_contact_relationship')->nullable()->after('emergency_contact_phone');
            $table->string('pos_id')->nullable()->after('emergency_contact_relationship');
        });

        Schema::table('employee', function (Blueprint $table) {
            $table->string('apt_number')->nullable()->after('street');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('onboarding_handbook');    
        Schema::table('onboarding_list', function (Blueprint $table) {
            $table->dropColumn('apt_number');
            $table->dropColumn('work_permit_issuer');
            $table->dropColumn('work_permit_document_path');
            $table->dropColumn('applicant_middle_initial');
            $table->dropColumn('dob');
            $table->dropColumn('emergency_contact_name');
            $table->dropColumn('emergency_contact_phone');
            $table->dropColumn('emergency_contact_relationship');
            $table->dropColumn('pos_id');
        });
        Schema::table('employee', function (Blueprint $table) {
            $table->dropColumn('apt_number');
        });
    }
};
