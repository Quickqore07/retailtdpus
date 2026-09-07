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
            // make state_id, region_id, area_id nullable
            $table->dropForeign(['state_id']);
            $table->dropForeign(['region_id']);
            $table->dropForeign(['area_id']);
        });

        Schema::table('company', function (Blueprint $table) {
            $table->unsignedBigInteger('state_id')->nullable()->change();
            $table->unsignedBigInteger('region_id')->nullable()->change();
            $table->unsignedBigInteger('area_id')->nullable()->change();
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('company', function (Blueprint $table) {
            // make state_id, region_id, area_id not nullable
            $table->integer('state_id')->nullable(false)->change();
            $table->integer('region_id')->nullable(false)->change();
            $table->integer('area_id')->nullable(false)->change();
        });
    }
};
