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
        Schema::create('employee_hours', function (Blueprint $table) {
            $table->id();
            $table->date('date')->nullable();
            $table->foreignId('employee_id')->constrained('employee');
            $table->string('employee_name')->nullable();
            $table->foreignId('company_id')->constrained('company');
            $table->string('dev_id')->nullable();
            $table->string('ssn')->nullable();
            $table->string('pay_id')->nullable();
            $table->enum('pay_type', ['HR', 'WK'])->default('HR');
            $table->decimal('total_hours', 10, 2)->default(0);
            $table->decimal('tips', 10, 2)->default(0);
            $table->decimal('mileage_excess', 10, 2)->default(0);
            $table->decimal('incentive', 10, 2)->default(0);
            $table->decimal('bonus', 10, 2)->default(0);
            $table->string('home_store')->nullable();
            $table->foreignId('role_id')->constrained('employee_roles');
            $table->decimal('pay_rate', 10, 2)->default(0);
            $table->decimal('tips_due', 10, 2)->default(0);
            $table->decimal('mileage_due', 10, 2)->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employee_hours');
    }
};
