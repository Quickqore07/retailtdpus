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
        Schema::create('qq_caching', function (Blueprint $table) {
            $table->id();
            $table->string('key');
            $table->json('data');
            $table->timestamps();
        });
        Schema::table('qq_caching', function (Blueprint $table) {
            $table->index('key');
        });

        Schema::table('ttm_reports', function (Blueprint $table) {
            $table->integer('year')->nullable()->change();
            $table->integer('month')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('qq_caching');
        Schema::table('ttm_reports', function (Blueprint $table) {
            $table->string('year')->nullable(false)->change();
            $table->string('month')->nullable(false)->change();
        });
    }
};
