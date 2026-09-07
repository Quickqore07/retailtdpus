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
        Schema::create('onboarding_log', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('onboarding_list_id');
            $table->string('event');
            $table->json('data');
            $table->text('description')->nullable();
            $table->dateTime('date')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('onboarding_log');
    }
};
