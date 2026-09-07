<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('employee_confirmations')
            ->whereNull('hr_status')
            ->update(['hr_status' => 'pending']);

        DB::statement(
            "ALTER TABLE employee_documents MODIFY COLUMN document_type "
            . "ENUM('i9_form', 'w4_form', 'profile_picture', 'other', 'list_a', 'list_b', 'list_c', 'authorization', 'tnc') "
            . "DEFAULT 'other'"
        );
    }

    public function down(): void
    {
       

        DB::statement(
            "ALTER TABLE employee_documents MODIFY COLUMN document_type "
            . "ENUM('i9_form', 'w4_form', 'profile_picture', 'other', 'list_a', 'list_b', 'list_c', 'authorization') "
            . "DEFAULT 'other'"
        );
    }
};
