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
        Schema::create('payroll_check_amounts', function (Blueprint $table) {
            $table->id();
            $table->integer('employee_id');
            $table->integer('company_id');
            $table->integer('role_id');
            $table->date('eow');
            $table->decimal('amount', 10, 2)->default(0);
            $table->decimal('payroll_amount', 10, 2)->default(0);
            $table->decimal('instant_amount', 10, 2)->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payroll_check_amounts');
    }
};
