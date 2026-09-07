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
        Schema::create('emergency_contacts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('onboarding_list_id');
            $table->unsignedBigInteger('employee_id')->nullable();
            
            // Primary Emergency Contact
            $table->string('e_emergency_contact_first_name')->nullable();
            $table->string('e_emergency_contact_last_name')->nullable();
            $table->string('e_emergency_contact_relationship')->nullable();
            $table->string('e_emergency_contact_phone_home')->nullable();
            $table->string('e_emergency_contact_phone_cell')->nullable();
            $table->string('e_emergency_contact_phone_work')->nullable();
            
            // Secondary Emergency Contact
            $table->string('e_emergency_contact2_first_name')->nullable();
            $table->string('e_emergency_contact2_last_name')->nullable();
            $table->string('e_emergency_contact2_relationship')->nullable();
            $table->string('e_emergency_contact2_phone_home')->nullable();
            $table->string('e_emergency_contact2_phone_cell')->nullable();
            $table->string('e_emergency_contact2_phone_work')->nullable();
            
            // Preferred Local Hospital
            $table->string('e_emergency_contact2_hospital')->nullable();
            
            // Insurance Information
            $table->string('e_insurance_company')->nullable();
            $table->string('e_insurance_policy_number')->nullable();
            
            // Signature
            $table->string('e_signature')->nullable();
            $table->date('e_signature_date')->nullable();
            
            $table->timestamps();
            
            $table->foreign('onboarding_list_id')->references('id')->on('onboarding_list')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('emergency_contacts');
    }
};
