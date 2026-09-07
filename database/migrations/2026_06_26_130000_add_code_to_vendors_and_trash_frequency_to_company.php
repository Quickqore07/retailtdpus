<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ap_vendors', function (Blueprint $table) {
            $table->string('code', 50)->nullable()->after('name');
        });

        Schema::table('company', function (Blueprint $table) {
            $table->string('trash_frequency', 50)->nullable()->after('payroll_frequency');
        });
    }

    public function down(): void
    {
        Schema::table('ap_vendors', function (Blueprint $table) {
            $table->dropColumn('code');
        });

        Schema::table('company', function (Blueprint $table) {
            $table->dropColumn('trash_frequency');
        });
    }
};
