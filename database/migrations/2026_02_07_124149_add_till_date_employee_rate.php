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
            $table->date('till_date')->nullable();

        });
        Schema::table('employee_rate_requests', function (Blueprint $table) {
            $table->date('till_date')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employee_rates', function (Blueprint $table) {
            $table->dropColumn('till_date');
        });
        Schema::table('employee_rate_requests', function (Blueprint $table) {
            $table->dropColumn('till_date');
        });
    }
};
