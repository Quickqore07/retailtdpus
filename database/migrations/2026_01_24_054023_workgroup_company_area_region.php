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
        Schema::create('state', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->boolean('active')->default(true);
            $table->timestamps();
        });
        Schema::create('region', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->foreignId('state_id')->constrained('state');
            $table->boolean('active')->default(true);
            $table->timestamps();
        });
        Schema::create('area', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->foreignId('region_id')->constrained('region');
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        Schema::create('workgroup', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        Schema::create('company', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('store_number')->unique();
            $table->string('password')->nullable();
            $table->foreignId('workgroup_id')->constrained('workgroup');
            $table->foreignId('state_id')->constrained('state');
            $table->foreignId('region_id')->constrained('region');
            $table->foreignId('area_id')->constrained('area');

            $table->string('address')->nullable();
            $table->string('email')->nullable();
            $table->string('contact_person')->nullable();
            $table->string('contact_number')->nullable();
            $table->string('website')->nullable();
            $table->string('employer_identification_number')->nullable();
            $table->date('payroll_start_date')->nullable();
            $table->enum('payroll_frequency', ['Weekly', 'Bi-Weekly'])->nullable();
            $table->enum('tax', ['Monthly', 'Quarterly', 'No Tax'])->nullable();
            $table->string('st_number')->nullable();
            $table->string('pin')->nullable();
            $table->integer('payroll_percentage')->nullable();
            $table->integer('sales_tax_percentage')->nullable();

            $table->boolean('active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('company');
        Schema::dropIfExists('workgroup');
        Schema::dropIfExists('area');
        Schema::dropIfExists('region');
    }
};
