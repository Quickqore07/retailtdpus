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
        Schema::table('ap_payments', function (Blueprint $table) {
            $table->integer('qq_company_id')->nullable()->after('company_id');
            $table->integer('qq_business_unit_id')->nullable()->after('qq_company_id');
            $table->enum('payment_type', ['cash','check','online'])->default('cash')->change();

        });
        Schema::table('ap_invoices', function (Blueprint $table) {
            $table->text('approval_remarks')->nullable()->after('remarks');
            $table->integer('check_document_id')->nullable()->after('document_id');
        });
        Schema::table('bank_uploads', function (Blueprint $table) {
            $table->boolean('is_opening_balance')->default(0)->after('amount');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ap_payments', function (Blueprint $table) {
            $table->dropColumn('qq_company_id');
            $table->dropColumn('qq_business_unit_id');
        });
        Schema::table('ap_invoices', function (Blueprint $table) {
            $table->dropColumn('remarks');
            $table->dropColumn('approval_remarks');
            $table->dropColumn('check_document_id');
        });
        Schema::table('bank_uploads', function (Blueprint $table) { 
            $table->dropColumn('is_opening_balance');
        });
    }
};
