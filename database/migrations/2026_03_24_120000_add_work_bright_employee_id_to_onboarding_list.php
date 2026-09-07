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
        if (!Schema::hasColumn('onboarding_list', 'work_bright_employee_id')) {
            Schema::table('onboarding_list', function (Blueprint $table) {
                $table->string('work_bright_employee_id')->nullable()->after('digital_signature_date');
            });
        }
        if (!Schema::hasColumn('onboarding_list', 'work_bright_response')) {
            Schema::table('onboarding_list', function (Blueprint $table) {
                $table->json('work_bright_response')->nullable()->after('work_bright_employee_id');
            });
        }
        if (!Schema::hasColumn('onboarding_list', 'i9_with_work_bright')) {
            Schema::table('onboarding_list', function (Blueprint $table) {
                $table->boolean('i9_with_work_bright')->default(false)->after('work_bright_employee_id');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('onboarding_list', 'work_bright_employee_id')) {
            Schema::table('onboarding_list', function (Blueprint $table) {
                $table->dropColumn('work_bright_employee_id');
            });
        }
        if (Schema::hasColumn('onboarding_list', 'work_bright_response')) {
            Schema::table('onboarding_list', function (Blueprint $table) {
                $table->dropColumn('work_bright_response');
            });
        }
        if (Schema::hasColumn('onboarding_list', 'i9_with_work_bright')) {
            Schema::table('onboarding_list', function (Blueprint $table) {
                $table->dropColumn('i9_with_work_bright');
            });
        }
    }
};
