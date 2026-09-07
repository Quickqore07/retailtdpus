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
        Schema::create('fund_requirements', function (Blueprint $table) {
            $table->id();
            $table->string('label');
            $table->enum('type', ['company wise', 'fixed']);
            $table->enum('condition_type', ['monthly', 'weekly', 'bi-weekly']);
            $table->date('condition_value');
            $table->decimal('amount', 15, 2)->nullable();
            $table->boolean('active')->default(true);
            $table->integer('order')->default(0);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
        });

        Schema::create('fund_requirement_companies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fund_requirement_id')->constrained('fund_requirements')->onDelete('cascade');
            $table->foreignId('company_id')->constrained('company')->onDelete('cascade');
            $table->decimal('amount', 15, 2);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fund_requirement_companies');
        Schema::dropIfExists('fund_requirements');
    }
};
