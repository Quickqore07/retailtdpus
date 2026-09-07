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
        Schema::table('upload_documents', function (Blueprint $table) {
            $table->decimal('approved_amount', 10, 2)->default(0)->after('file_size');
        });
        Schema::table('ap_invoices', function (Blueprint $table) {
            $table->decimal('approved_amount', 10, 2)->default(0)->after('amount');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('upload_documents', function (Blueprint $table) {
            $table->dropColumn('approved_amount');
        });
        Schema::table('ap_invoices', function (Blueprint $table) {
            $table->dropColumn('approved_amount');
        });
    }
};
