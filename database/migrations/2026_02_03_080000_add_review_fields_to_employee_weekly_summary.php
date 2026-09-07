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
        Schema::table('employee_weekly_summary', function (Blueprint $table) {
            // Original calculated amounts (auto-calculated)
            $table->decimal('calculated_check_amount', 10, 2)->default(0)->after('check_methods');
            $table->decimal('calculated_instant_amount', 10, 2)->default(0)->after('calculated_check_amount');
            $table->decimal('calculated_payroll_amount', 10, 2)->default(0)->after('calculated_instant_amount');
            
            // Review status and history
            $table->boolean('is_reviewed')->default(false)->after('calculated_payroll_amount');
            $table->timestamp('reviewed_at')->nullable()->after('is_reviewed');
            $table->unsignedBigInteger('reviewed_by')->nullable()->after('reviewed_at');
            
            // Store original calculated data as JSON for history
            $table->json('original_calculated_data')->nullable()->after('reviewed_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employee_weekly_summary', function (Blueprint $table) {
            $table->dropColumn([
                'calculated_check_amount',
                'calculated_instant_amount',
                'calculated_payroll_amount',
                'is_reviewed',
                'reviewed_at',
                'reviewed_by',
                'original_calculated_data',
            ]);
        });
    }
};
