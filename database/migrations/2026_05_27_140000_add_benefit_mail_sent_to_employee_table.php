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
        Schema::table('employee', function (Blueprint $table) {
            $table->timestamp('benefit_mail_sent')->nullable()->after('mail_sent');
            $table->timestamp('benefit_submitted_at')->nullable()->after('benefit_mail_sent');
            $table->string('benefit_code')->nullable()->after('benefit_mail_sent');
        });
        Schema::table('benefit_enrollments', function (Blueprint $table) {
            $table->dropForeign(['onboarding_list_id']);
            $table->dropUnique(['onboarding_list_id']);
            $table->unsignedBigInteger('employee_id')->nullable()->after('id');
            $table->foreign('employee_id')->references('id')->on('employee')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employee', function (Blueprint $table) {
            $table->dropColumn('benefit_mail_sent');
            $table->dropColumn('benefit_submitted_at');
            $table->dropColumn('benefit_code');
        });
        Schema::table('benefit_enrollments', function (Blueprint $table) {
            $table->dropForeign(['employee_id']);
            $table->dropColumn('employee_id');
        });
    }
};
