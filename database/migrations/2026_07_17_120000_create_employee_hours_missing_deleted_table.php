<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_hours_missing_deleted', function (Blueprint $table) {
            $table->id();
            $table->date('date');
            $table->foreignId('company_id')->constrained('company')->onDelete('cascade');
            $table->unsignedBigInteger('deleted_by')->nullable();
            $table->timestamps();

            $table->unique(['date', 'company_id']);
            $table->index('date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_hours_missing_deleted');
    }
};
