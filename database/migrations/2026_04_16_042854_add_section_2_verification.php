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
        Schema::table('onboarding_list', function (Blueprint $table) {
            $table->boolean('section_2_verification')->default(false)->after('workbright_status');
            $table->boolean('tnc')->default(false)->after('section_2_verification');
        });
        DB::statement("
            ALTER TABLE onboarding_list

            MODIFY status ENUM(
                'pending',
                'form_submitted',
                'in_complete_form',
                'document_approved',
                'i9_submitted',
                'i9_submission_requested',
                'w4_submission_requested',
                'i9_and_w4_submission_requested',
                'waiting_for_section_2_verification',
                'section_2_verification_done',
                'employee_athorized',
                'verified'
            ) NOT NULL DEFAULT 'pending'
        ");

            DB::statement("
            ALTER TABLE employee
            MODIFY onboarding_status ENUM(
                'pending',
                'form_submitted',
                'in_complete_form',
                'document_approved',
                'i9_submitted',
                'i9_submission_requested',
                'w4_submission_requested',
                'i9_and_w4_submission_requested',
                'waiting_for_section_2_verification',
                'section_2_verification_done',
                'employee_athorized',
                'verified'
            ) NOT NULL DEFAULT 'pending'
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('onboarding_list', function (Blueprint $table) {
            $table->dropColumn('section_2_verification');
            $table->dropColumn('tnc');
        });
    }
};
