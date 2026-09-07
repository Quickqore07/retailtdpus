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
            $table->unsignedBigInteger('company_id')->nullable()->after('employee_id');
        });
        Schema::table('employee_rate_requests', function (Blueprint $table) {
            $table->unsignedBigInteger('company_id')->nullable()->after('employee_id');
        });
    
        Schema::table('employee', function (Blueprint $table) {
            $table->dropForeign(['company_id']);
        });
    
        Schema::table('employee', function (Blueprint $table) {
            $table->unsignedBigInteger('company_id')->nullable()->change();
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employee_rates', function (Blueprint $table) {
            $table->dropColumn('company_id');
        });
    
        Schema::table('employee', function (Blueprint $table) {
            $table->unsignedBigInteger('company_id')->nullable(false)->change();
        });
        Schema::table('employee_rate_requests', function (Blueprint $table) {
            $table->dropColumn('company_id');
        });
    
        Schema::table('employee', function (Blueprint $table) {
            $table->foreign('company_id')
                  ->references('id')
                  ->on('company');
        });
    }
};
