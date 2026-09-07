<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ap_vendors', function (Blueprint $table) {
            $table->unsignedSmallInteger('credit_days')->nullable()->after('fax');
        });
    }

    public function down(): void
    {
        Schema::table('ap_vendors', function (Blueprint $table) {
            $table->dropColumn('credit_days');
        });
    }
};
