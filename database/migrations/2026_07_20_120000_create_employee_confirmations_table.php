<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_confirmations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('employee_id')->index();
            $table->string('status')->default('unverified')->index();
            $table->string('hr_status')->default('pending')->index();
            $table->string('i9_choice')->nullable();
            $table->string('uscis_number', 50)->nullable();
            $table->date('work_authorization_exp_date')->nullable();
            $table->string('list_a_doc_type', 20)->nullable();
            $table->string('list_b_doc_type', 20)->nullable();
            $table->string('list_c_doc_type', 20)->nullable();
            $table->unsignedBigInteger('list_a_doc_id')->nullable()->index();
            $table->unsignedBigInteger('list_b_doc_id')->nullable()->index();
            $table->unsignedBigInteger('list_c_doc_id')->nullable()->index();
            $table->unsignedBigInteger('reviewed_by')->nullable()->index();
            $table->text('review_notes')->nullable();
            $table->unsignedBigInteger('authorization_doc_id')->nullable()->index();
            $table->string('tnc_doc_type', 50)->nullable();
            $table->unsignedBigInteger('tnc_document_id')->nullable()->index();
            $table->unsignedBigInteger('authorized_by')->nullable()->index();
            $table->timestamp('approved_at')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_confirmations');
    }
};
