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
        Schema::table('company', function (Blueprint $table) {
            $table->unsignedBigInteger('county_id')->nullable()->after('region_id');
            $table->foreign('county_id')->references('id')->on('county')->onDelete('set null');
        });
        Schema::table('minimum_wages', function (Blueprint $table) {
            $table->unsignedBigInteger('county_id')->nullable()->after('state_id');
            $table->foreign('county_id')->references('id')->on('county')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('company', function (Blueprint $table) {
            $table->dropForeign(['county_id']);
            $table->dropColumn('county_id');
        });
        Schema::table('minimum_wages', function (Blueprint $table) {
            $table->dropForeign(['county_id']);
            $table->dropColumn('county_id');
        });
    }
};
