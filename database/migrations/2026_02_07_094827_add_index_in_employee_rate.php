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
        Schema::table('employee_rates', function (Blueprint $table) {
            $table->index('company_id', 'idx_employee_rates_company');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employee_rate', function (Blueprint $table) {
            $table->dropIndex('idx_employee_rates_company');
        });
    }
};
