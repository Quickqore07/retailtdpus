<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement(
            "ALTER TABLE employee_documents MODIFY COLUMN document_type "
            . "ENUM('i9_form', 'i9_form_unsigned', 'w4_form', 'profile_picture', 'other', 'list_a', 'list_b', 'list_c', 'authorization', 'tnc') "
            . "DEFAULT 'other'"
        );
    }

    public function down(): void
    {
        DB::statement(
            "ALTER TABLE employee_documents MODIFY COLUMN document_type "
            . "ENUM('i9_form', 'w4_form', 'profile_picture', 'other', 'list_a', 'list_b', 'list_c', 'authorization', 'tnc') "
            . "DEFAULT 'other'"
        );
    }
};
