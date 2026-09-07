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
        Schema::table('users', function (Blueprint $table) {
            $table->integer('workgroup_id')->nullable()->after('role_id')->default(1);
        });
        Schema::table('employee_roles', function (Blueprint $table) {
            $table->integer('workgroup_id')->nullable()->after('id')->default(1);
        });
        Schema::table('roles', function (Blueprint $table) {
            $table->integer('workgroup_id')->nullable()->after('companies')->default(1);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('workgroup_id');
        });
        Schema::table('employee_roles', function (Blueprint $table) {
            $table->dropColumn('workgroup_id');
        });
        Schema::table('roles', function (Blueprint $table) {
            $table->dropColumn('workgroup_id');
        });
    }
};
