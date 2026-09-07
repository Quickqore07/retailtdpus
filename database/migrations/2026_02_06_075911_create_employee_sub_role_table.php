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
        Schema::create('employee_sub_role', function (Blueprint $table) {
            $table->id();
            $table->string('code');
            $table->foreignId('role_id')->constrained('employee_roles');
            $table->integer('created_by')->default(0);
            $table->integer('updated_by')->default(0);
            $table->timestamps();
        });
        schema::table('area', function (Blueprint $table) {
            $table->integer('created_by')->default(0);
            $table->integer('updated_by')->default(0);
        });
        schema::table('region', function (Blueprint $table) {
            $table->integer('created_by')->default(0);
            $table->integer('updated_by')->default(0);
        });
        schema::table('state', function (Blueprint $table) {
            $table->integer('created_by')->default(0);
            $table->integer('updated_by')->default(0);
        });
        schema::table('workgroup', function (Blueprint $table) {
            $table->integer('created_by')->default(0);
            $table->integer('updated_by')->default(0);
        });
        schema::table('company', function (Blueprint $table) {
            $table->integer('created_by')->default(0);
            $table->integer('updated_by')->default(0);
        });
        schema::table('employee_roles', function (Blueprint $table) {
            $table->integer('created_by')->default(0);
            $table->integer('updated_by')->default(0);
        });
        schema::table('employee', function (Blueprint $table) {
            $table->integer('created_by')->default(0);
            $table->integer('updated_by')->default(0);
        });
        schema::table('employee_hours', function (Blueprint $table) {
            $table->integer('created_by')->default(0);
            $table->integer('updated_by')->default(0);
        });
        schema::table('employee_rates', function (Blueprint $table) {
            $table->integer('created_by')->default(0);
            $table->integer('updated_by')->default(0);
        });
        schema::table('employee_rate_requests', function (Blueprint $table) {
            $table->integer('created_by')->default(0);
            $table->integer('updated_by')->default(0);
        });
        schema::table('employee_hours_requests', function (Blueprint $table) {
            $table->integer('created_by')->default(0);
            $table->integer('updated_by')->default(0);
        });
        schema::table('users', function (Blueprint $table) {
            $table->integer('created_by')->default(0);
            $table->integer('updated_by')->default(0);
            $table->softDeletes();
        });


    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employee_sub_role');
        schema::table('area', function (Blueprint $table) {
            $table->dropColumn('created_by');
            $table->dropColumn('updated_by');
        });
        schema::table('region', function (Blueprint $table) {
            $table->dropColumn('created_by');
            $table->dropColumn('updated_by');
        });
        schema::table('state', function (Blueprint $table) {
            $table->dropColumn('created_by');
            $table->dropColumn('updated_by');
        });
        schema::table('workgroup', function (Blueprint $table) {
            $table->dropColumn('created_by');
            $table->dropColumn('updated_by');
        });
        schema::table('company', function (Blueprint $table) {
            $table->dropColumn('created_by');
            $table->dropColumn('updated_by');
        });
        schema::table('employee_roles', function (Blueprint $table) {
            $table->dropColumn('created_by');
            $table->dropColumn('updated_by');
        });
        schema::table('employee', function (Blueprint $table) {
            $table->dropColumn('created_by');
            $table->dropColumn('updated_by');
        });
        schema::table('employee_hours', function (Blueprint $table) {
            $table->dropColumn('created_by');
            $table->dropColumn('updated_by');
        });
        schema::table('employee_rates', function (Blueprint $table) {
            $table->dropColumn('created_by');
            $table->dropColumn('updated_by');
        });
        schema::table('employee_rate_requests', function (Blueprint $table) {
            $table->dropColumn('created_by');
            $table->dropColumn('updated_by');
        });
        schema::table('employee_hours_requests', function (Blueprint $table) {
            $table->dropColumn('created_by');
            $table->dropColumn('updated_by');
        });
        schema::table('users', function (Blueprint $table) {
            $table->dropColumn('created_by');
            $table->dropColumn('updated_by');
            $table->dropSoftDeletes();
        });
    }
};
