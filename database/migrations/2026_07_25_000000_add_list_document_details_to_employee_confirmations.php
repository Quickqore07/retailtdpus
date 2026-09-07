<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employee_confirmations', function (Blueprint $table) {
            $table->string('list_a_issuing_authority', 255)->nullable()->after('list_a_doc_type');
            $table->string('list_a_document_number', 100)->nullable()->after('list_a_issuing_authority');
            $table->date('list_a_expiration_date')->nullable()->after('list_a_document_number');

            $table->string('list_b_issuing_authority', 255)->nullable()->after('list_b_doc_type');
            $table->string('list_b_document_number', 100)->nullable()->after('list_b_issuing_authority');
            $table->date('list_b_expiration_date')->nullable()->after('list_b_document_number');

            $table->string('list_c_issuing_authority', 255)->nullable()->after('list_c_doc_type');
            $table->string('list_c_document_number', 100)->nullable()->after('list_c_issuing_authority');
            $table->date('list_c_expiration_date')->nullable()->after('list_c_document_number');
        });
    }

    public function down(): void
    {
        Schema::table('employee_confirmations', function (Blueprint $table) {
            $table->dropColumn([
                'list_a_issuing_authority',
                'list_a_document_number',
                'list_a_expiration_date',
                'list_b_issuing_authority',
                'list_b_document_number',
                'list_b_expiration_date',
                'list_c_issuing_authority',
                'list_c_document_number',
                'list_c_expiration_date',
            ]);
        });
    }
};
