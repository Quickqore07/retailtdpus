<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('upload_portal_customers', function (Blueprint $table) {
            $table->text('email_notes')->nullable()->after('primary_person_name');
        });
    }

    public function down(): void
    {
        Schema::table('upload_portal_customers', function (Blueprint $table) {
            $table->dropColumn('email_notes');
        });
    }
};
