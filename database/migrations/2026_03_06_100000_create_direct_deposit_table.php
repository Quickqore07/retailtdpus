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
        Schema::create('direct_deposits', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('onboarding_list_id');
            $table->string('company_code')->nullable();
            
            // Account #1
            $table->enum('acct_type_1', ['checking', 'savings']);
            $table->string('bank_name_1');
            $table->string('bank_routing_1');
            $table->string('account_number_1');
            $table->string('deposit_amount_1'); // Can be percentage or dollar amount
            
            // Account #2 (optional)
            $table->enum('acct_type_2', ['checking', 'savings'])->nullable();
            $table->string('bank_name_2')->nullable();
            $table->string('bank_routing_2')->nullable();
            $table->string('account_number_2')->nullable();
            $table->string('deposit_amount_2')->nullable();
            
            // Signature & Authorization
            $table->string('signature');
            $table->string('printed_name');
            $table->string('employee_id');
            $table->date('date');
            
            $table->timestamps();
            
            // Foreign key
            $table->foreign('onboarding_list_id')->references('id')->on('onboarding_list')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('direct_deposits');
    }
};
