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
        Schema::table('settings', function (Blueprint $table) {
            $table->string('value_type')->nullable();
        });
        Schema::table('payroll_journals', function (Blueprint $table) {
            $table->dropColumn('ctc');
        }); 
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->dropColumn('value_type');
        });
        Schema::table('payroll_journals', function (Blueprint $table) {
            $table->decimal('ctc', 12, 2)->default(0);
        });
    }
};
