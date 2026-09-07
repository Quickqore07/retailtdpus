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
        Schema::table('employee_roles', function (Blueprint $table) {
            $table->boolean('tipped')->default(false);
        });
        Schema::table('minimum_wages', function (Blueprint $table) {
            $table->decimal('tipped_minimum_wage', 10, 2)->default(0)->after('minimum_wage');
            $table->integer('created_by')->nullable()->after('tipped_minimum_wage');
            $table->integer('updated_by')->nullable()->after('created_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employee_roles', function (Blueprint $table) {
            $table->dropColumn('tipped');
        });
        Schema::table('minimum_wages', function (Blueprint $table) {
            $table->dropColumn('tipped_minimum_wage');
            $table->dropColumn('created_by');
            $table->dropColumn('updated_by');
        });
    }
};
