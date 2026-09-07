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
        Schema::table('ap_invoices', function (Blueprint $table) {
            $table->text('remarks')->nullable()->after('status');
            $table->integer('qq_company_id')->nullable()->after('company_id');
            $table->integer('qq_business_unit_id')->nullable()->after('qq_company_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ap_invoices', function (Blueprint $table) {
            $table->dropColumn('remarks');
            $table->dropColumn('qq_company_id');
            $table->dropColumn('qq_business_unit_id');
        });
    }
};
