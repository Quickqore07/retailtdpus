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
        Schema::create('mwa', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('employee_id');
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('role_id');
            $table->date('eow');
            $table->decimal('amount', 10, 2)->default(0);
            $table->integer('created_by')->nullable();
            $table->integer('updated_by')->nullable();
            $table->timestamps();
            
            // Indexes for fast querying
            $table->index(['employee_id', 'company_id', 'role_id', 'eow']);
            $table->index(['company_id', 'eow']);
            $table->index('eow');
            
            // Unique constraint to prevent duplicates
            $table->unique(['employee_id', 'company_id', 'role_id', 'eow'], 'mwa_unique');
            
            // Foreign keys
            $table->foreign('employee_id')->references('id')->on('employee')->onDelete('cascade');
            $table->foreign('company_id')->references('id')->on('company')->onDelete('cascade');
            $table->foreign('role_id')->references('id')->on('employee_roles')->onDelete('cascade');
        });

        Schema::table('employee_weekly_summary', function (Blueprint $table) {
            $table->decimal('mwa_amount', 10, 2)->default(0)->after('min_wage_due');
        }); 
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mwa');
        Schema::table('employee_weekly_summary', function (Blueprint $table) {
            $table->dropColumn('mwa_amount');
        }); 
    }
};
