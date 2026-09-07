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
            $table->boolean('document_approved')->default(false)->after('onboarding_status');
        });
        Schema::table('onboarding_list', function (Blueprint $table) {
            $table->boolean('document_approved')->default(false)->after('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employee', function (Blueprint $table) {
            $table->dropColumn('document_approved');
        });
        Schema::table('onboarding_list', function (Blueprint $table) {
            $table->dropColumn('document_approved');
        });
    }
};
