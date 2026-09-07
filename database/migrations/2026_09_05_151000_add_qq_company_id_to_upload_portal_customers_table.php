<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('upload_portal_customers', function (Blueprint $table) {
            $table->unsignedBigInteger('qq_company_id')->nullable()->after('qq_id');
            $table->index('qq_company_id');
        });
    }

    public function down(): void
    {
        Schema::table('upload_portal_customers', function (Blueprint $table) {
            $table->dropIndex(['qq_company_id']);
            $table->dropColumn('qq_company_id');
        });
    }
};
