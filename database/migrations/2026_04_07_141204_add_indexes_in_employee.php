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
            $table->index(['employee_type', 'onboarding_status']);
            $table->index(['employee_type', 'mail_sent']);
            $table->index('created_at');
            $table->index('workgroup_id');
        });
        
        Schema::table('employee_rates', function (Blueprint $table) {
            $table->index(['employee_id', 'company_id']);
            $table->index(['employee_id', 'rate_type']);
        });
        
        Schema::table('employee_documents', function (Blueprint $table) {
            $table->index(['employee_id', 'document_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employee', function (Blueprint $table) {
            $table->dropIndex(['employee_type', 'onboarding_status']);
            $table->dropIndex(['employee_type', 'mail_sent']);
            $table->dropIndex('created_at');
            $table->dropIndex('workgroup_id');
        });
        Schema::table('employee_rates', function (Blueprint $table) {
            $table->dropIndex(['employee_id', 'company_id']);
            $table->dropIndex(['employee_id', 'rate_type']);
        });
        Schema::table('employee_documents', function (Blueprint $table) {
            $table->dropIndex(['employee_id', 'document_type']);
        });
    }
};
