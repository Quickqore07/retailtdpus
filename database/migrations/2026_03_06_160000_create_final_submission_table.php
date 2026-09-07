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
        Schema::create('final_submission', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('onboarding_list_id')->unique();
            $table->unsignedBigInteger('employee_id')->nullable();
            $table->boolean('check_confirmation')->default(false);
            $table->boolean('certification_complete')->default(false);
            $table->boolean('work_authorization_confirmed')->default(false);
            $table->boolean('false_info_acknowledgment')->default(false);
            $table->boolean('electronic_signature_consent')->default(false);
            $table->string('signature')->nullable();
            $table->date('signature_date')->nullable();
            $table->timestamps();

            $table->foreign('onboarding_list_id')
                ->references('id')
                ->on('onboarding_list')
                ->onDelete('cascade');
        });

        Schema::create('digital_signature', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('onboarding_list_id')->unique();
            $table->unsignedBigInteger('employee_id')->nullable();
            $table->string('employee_name')->nullable();
            $table->string('signature')->nullable();
            $table->string('date')->nullable();
            $table->timestamps();

            $table->foreign('onboarding_list_id')
                ->references('id')
                ->on('onboarding_list')
                ->onDelete('cascade');
        });

        Schema::table('onboarding_list', function (Blueprint $table) {
            $table->boolean('final_submission_completed')->default(false)->after('process_id');
            $table->dateTime('final_submission_date')->nullable()->after('final_submission_completed');
            $table->boolean('digital_signature_completed')->default(false)->after('final_submission_date');
            $table->dateTime('digital_signature_date')->nullable()->after('digital_signature_completed');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('onboarding_list', function (Blueprint $table) {
            $table->dropColumn(['final_submission_completed', 'final_submission_date', 'digital_signature_completed', 'digital_signature_date']);
        });
        
        Schema::dropIfExists('final_submission');
        Schema::dropIfExists('digital_signature');
    }
};
