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
        Schema::table('upload_folders', function (Blueprint $table) {
            $table->boolean('is_sales_invoice')->default(0)->after('is_invoice');
        });
        Schema::table('upload_documents', function (Blueprint $table) {
            $table->boolean('is_sales_invoice')->default(0)->after('is_invoice');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('upload_folders', function (Blueprint $table) {
            $table->dropColumn('is_sales_invoice');
        });
        Schema::table('upload_documents', function (Blueprint $table) {
            $table->dropColumn('is_sales_invoice');
        });
    }
};
