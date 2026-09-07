<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('upload_portal_customers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('mobile', 50)->nullable();
            $table->string('fax', 50)->nullable();
            $table->string('primary_person_name')->nullable();

            $table->string('address_line_1')->nullable();
            $table->string('address_line_2')->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('country')->nullable();
            $table->string('zip_code', 32)->nullable();

            $table->string('bank_name')->nullable();
            $table->string('account_number')->nullable();
            $table->string('routing_number')->nullable();
            $table->string('credit_card_number')->nullable();
            $table->string('name_on_card')->nullable();

            $table->string('card_expiry', 16)->nullable();

            $table->integer('invoice_due')->default(0);
            $table->string('invoice_condition')->nullable();

            $table->integer('created_by')->nullable();
            $table->integer('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('upload_portal_customers');
    }
};
