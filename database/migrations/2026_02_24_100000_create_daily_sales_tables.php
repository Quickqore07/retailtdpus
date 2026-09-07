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
        Schema::create('daily_sales', function (Blueprint $table) {
            $table->id();
            $table->date('date');
            $table->integer('company_id')->nullable();
            $table->decimal('net_sales', 10, 2)->default(0);
            $table->decimal('beverage_tax', 10, 2)->default(0);
            $table->decimal('food_tax', 10, 2)->default(0);
            $table->decimal('total_sales', 10, 2)->default(0);
            $table->decimal('cash_received', 10, 2)->default(0);
            $table->decimal('partial_void', 10, 2)->default(0);
            $table->decimal('total_cash', 10, 2)->default(0);
            $table->decimal('tips', 10, 2)->default(0);
            $table->decimal('mileage', 10, 2)->default(0);
            $table->decimal('total_tips_mileage', 10, 2)->default(0);
            $table->decimal('dd_tips', 10, 2)->default(0);
            $table->decimal('e_tips', 10, 2)->default(0);
            $table->decimal('e_tips_payroll', 10, 2)->default(0);
            $table->decimal('total_e_and_dd_tips', 10, 2)->default(0);
            $table->decimal('cash_payment', 10, 2)->default(0);
            $table->decimal('other_payments_total', 10, 2)->default(0);
            $table->decimal('total_cash_payment', 10, 2)->default(0);
            $table->decimal('net_cash_due', 10, 2)->default(0);
            $table->decimal('cash_bag', 10, 2)->default(0);
            $table->decimal('short_over', 10, 2)->default(0);
            $table->integer('created_by')->nullable();
            $table->integer('updated_by')->nullable();
            $table->timestamps();
        });

        Schema::create('daily_sales_other_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('daily_sale_id')->constrained('daily_sales')->cascadeOnDelete();
            $table->enum('expense', ['Small Maintenance', 'Office Expense', 'Food', 'Supplies', 'MISC']);
            $table->decimal('amount', 10, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('bank_deposits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('daily_sale_id')->constrained('daily_sales')->cascadeOnDelete();
            $table->date('date');
            $table->decimal('amount', 10, 2)->default(0);
            $table->string('notes')->nullable();
            $table->integer('created_by')->nullable();
            $table->integer('updated_by')->nullable();
            $table->timestamps();
        });

        Schema::create('shortage', function (Blueprint $table) {
            $table->id();
            $table->foreignId('daily_sale_id')->constrained('daily_sales')->cascadeOnDelete();
            $table->date('date');
            $table->decimal('amount', 10, 2)->default(0);
            $table->string('notes')->nullable();
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
        Schema::dropIfExists('daily_sales_other_payments');
        Schema::dropIfExists('bank_deposits');
        Schema::dropIfExists('shortage');
        Schema::dropIfExists('daily_sales');
    }
};
