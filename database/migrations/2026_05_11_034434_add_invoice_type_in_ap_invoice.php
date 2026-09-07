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
            $table->enum('invoice_type', ['check','auto'])->default('auto')->after('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ap_invoices', function (Blueprint $table) {
            $table->dropColumn('invoice_type');
        });
    }
};
