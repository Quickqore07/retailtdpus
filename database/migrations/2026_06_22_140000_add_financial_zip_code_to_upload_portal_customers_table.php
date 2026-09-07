<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('upload_portal_customers', function (Blueprint $table) {
            $table->string('financial_zip_code', 32)->nullable()->after('name_on_card');
        });
    }

    public function down(): void
    {
        Schema::table('upload_portal_customers', function (Blueprint $table) {
            $table->dropColumn('financial_zip_code');
        });
    }
};
