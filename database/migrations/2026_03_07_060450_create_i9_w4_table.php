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
        Schema::create('i9_w4', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('employee_id')->nullable()->index();
            $table->unsignedBigInteger('onboarding_list_id')->nullable()->index();

            // Profile Information (Step 1)
            $table->string('type', 50)->nullable()->comment('i9 or 1099');
            $table->string('email')->nullable();
            $table->string('first_name')->nullable();
            $table->string('middle_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('street')->nullable();
            $table->string('apt')->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('zip')->nullable();
            $table->string('country')->nullable();
            $table->string('phone')->nullable();
            $table->string('gender', 50)->nullable();
            $table->date('birthdate')->nullable();
            $table->string('ssn')->nullable();
            $table->string('preferred_name')->nullable();

            // Groups (Step 2)
            $table->unsignedBigInteger('workgroup_id')->nullable();
            $table->string('workgroup_name')->nullable();

            // Current Employment (Step 3)
            $table->string('job_title')->nullable();
            $table->string('employment_type', 50)->nullable();
            $table->date('start_date')->nullable();
            $table->unsignedBigInteger('manager_id')->nullable();
            $table->string('manager')->nullable();
            $table->decimal('salary', 15, 2)->nullable();
            $table->string('salary_period', 50)->nullable();

            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            $table->foreign('onboarding_list_id')->references('id')->on('onboarding_list')->onDelete('cascade');
        });

        Schema::table('onboarding_list', function (Blueprint $table) {
            $table->boolean('i9_w4_completed')->default(false)->after('process_id');
            $table->dateTime('i9_w4_date')->nullable()->after('i9_w4_completed');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('i9_w4');
        Schema::table('onboarding_list', function (Blueprint $table) {
            $table->dropColumn('i9_w4_completed');
            $table->dropColumn('i9_w4_date');
        });
    }
};
