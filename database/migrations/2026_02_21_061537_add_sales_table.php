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
        Schema::create('pj_payments', function (Blueprint $table) {
            $table->id();
            $table->integer('company_id');
            $table->date('date');
            $table->decimal('total_amount', 10, 2);
            $table->boolean('is_imported')->default(false);
            $table->integer('created_by')->nullable();
            $table->integer('updated_by')->nullable();
            $table->timestamps();
        });


        Schema::create('pj_payments_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pj_payment_id')->constrained('pj_payments');
            $table->integer('ledger_id');
            $table->string('name');
            $table->decimal('amount', 10, 2);
            $table->timestamps();
        });


        Schema::create('food_purchase', function (Blueprint $table) {
            $table->id();
            $table->date('date');
            $table->decimal('total_amount', 10, 2);
            $table->integer('created_by')->nullable();
            $table->integer('updated_by')->nullable();
            $table->timestamps();
        });


        Schema::create('food_purchase_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('food_purchase_id')->constrained('food_purchase');
            $table->integer('company_id');
            $table->decimal('food_product_purchase', 10, 2);
            $table->decimal('paper_supplies', 10, 2);
            $table->decimal('smallware_supplies', 10, 2);
            $table->decimal('cleaning_supplies', 10, 2);
            $table->decimal('pepsi', 10, 2);
            $table->decimal('total_amount', 10, 2);
            $table->timestamps();
        });


        Schema::create('ledger_vouchers', function (Blueprint $table) {
            $table->id();
            $table->integer('ledger_id');
            $table->integer('opp_ledger_id');
            $table->decimal('amount', 10, 2);
            $table->decimal('debit', 10, 2);
            $table->decimal('credit', 10, 2);
            $table->integer('voucher_id');
            $table->integer('voucher_items_id')->nullable();
            $table->string('voucher_type')->nullable();
            $table->string('dbtable')->nullable();
            $table->integer('check_number')->nullable();
            $table->string('description')->nullable();
            $table->date('date')->nullable();
            $table->integer('created_by')->nullable();
            $table->integer('updated_by')->nullable();
            $table->timestamps();
        });



    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ledger_vouchers');
        Schema::dropIfExists('pj_payments_items');
        Schema::dropIfExists('pj_payments');
        Schema::dropIfExists('food_purchase_items');
        Schema::dropIfExists('food_purchase');
    }
};
