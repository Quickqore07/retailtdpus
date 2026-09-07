<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('other_daily_sales', function (Blueprint $table) {
            $table->id();
            $table->date('date');
            $table->unsignedBigInteger('company_id');
            $table->decimal('sales', 12, 2)->default(0);
            $table->decimal('tax', 12, 2)->default(0);
            $table->decimal('other', 12, 2)->default(0);
            $table->decimal('total', 12, 2)->default(0);
            $table->decimal('round_off', 12, 2)->default(0);
            $table->decimal('cash', 12, 2)->default(0);
            $table->decimal('credit_card', 12, 2)->default(0);
            $table->decimal('account', 12, 2)->default(0);
            $table->decimal('check', 12, 2)->default(0);
            $table->decimal('coupon', 12, 2)->default(0);
            $table->decimal('other_payment', 12, 2)->default(0);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            $table->foreign('company_id', 'ods_company_fk')->references('id')->on('company')->cascadeOnDelete();
            $table->unique(['company_id', 'date'], 'ods_company_date_unique');
            $table->index('date', 'ods_date_idx');
        });

        Schema::create('other_daily_sales_cash_recs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('other_daily_sale_id');
            $table->string('cash_payment')->nullable();
            $table->string('detail')->nullable();
            $table->decimal('amount', 12, 2)->default(0);
            $table->timestamps();

            $table->foreign('other_daily_sale_id', 'ods_cash_sale_fk')
                ->references('id')
                ->on('other_daily_sales')
                ->cascadeOnDelete();
        });

        Schema::create('other_daily_sales_bank_deps', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('other_daily_sale_id');
            $table->string('bank_name')->nullable();
            $table->decimal('amount', 12, 2)->default(0);
            $table->timestamps();

            $table->foreign('other_daily_sale_id', 'ods_bank_sale_fk')
                ->references('id')
                ->on('other_daily_sales')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('other_daily_sales_bank_deps');
        Schema::dropIfExists('other_daily_sales_cash_recs');
        Schema::dropIfExists('other_daily_sales');
    }
};
