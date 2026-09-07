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
        Schema::table('onboarding_list', function (Blueprint $table) {
            $table->string('workbright_status')->nullable()->after('status');
            $table->string('everify_status')->nullable()->after('workbright_status');
            $table->dateTime('everify_status_updated_at')->nullable()->after('everify_status');
            $table->dateTime('document_approved_at')->nullable()->after('document_approved');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('onboarding_list', function (Blueprint $table) {
            $table->dropColumn(['workbright_status', 'everify_status', 'everify_status_updated_at',  'document_approved_at']);
        });
    }
};
