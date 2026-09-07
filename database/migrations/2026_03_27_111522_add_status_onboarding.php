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
                'verified'
            ) NOT NULL DEFAULT 'pending'
        ");

        DB::statement("
            ALTER TABLE onboarding_list

            MODIFY final_status ENUM(
                'pending',
                'approved',
                'rejected'
            ) NOT NULL DEFAULT 'pending'
        ");
        
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
