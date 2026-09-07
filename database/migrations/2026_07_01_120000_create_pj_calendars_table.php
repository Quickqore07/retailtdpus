<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pj_calendars', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('year')->unique();
            $table->integer('created_by')->nullable();
            $table->integer('updated_by')->nullable();
            $table->timestamps();
        });

        Schema::create('pj_calendar_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pj_calendar_id')->constrained('pj_calendars')->cascadeOnDelete();
            $table->string('label');
            $table->json('weeks');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pj_calendar_items');
        Schema::dropIfExists('pj_calendars');
    }
};
