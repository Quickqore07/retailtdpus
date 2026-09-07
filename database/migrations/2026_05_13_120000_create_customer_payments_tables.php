<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_payments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('customer_id');
            $table->date('payment_date');
            $table->string('payment_type', 32);
            $table->decimal('total_amount', 14, 2)->default(0);
            $table->text('remarks')->nullable();
            $table->integer('created_by')->nullable();
            $table->integer('updated_by')->nullable();
            $table->timestamps();

            $table->index('customer_id');
            $table->index('payment_date');
            $table->foreign('customer_id')
                ->references('id')->on('upload_portal_customers')
                ->restrictOnDelete();
        });

        Schema::create('customer_payment_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('customer_payment_id');
            $table->unsignedBigInteger('sales_invoice_id');
            $table->decimal('amount_applied', 14, 2);
            $table->decimal('discount', 14, 2);
            $table->timestamps();

            $table->index('customer_payment_id');
            $table->index('sales_invoice_id');
            $table->foreign('customer_payment_id')
                ->references('id')->on('customer_payments')
                ->cascadeOnDelete();
            $table->foreign('sales_invoice_id')
                ->references('id')->on('sales_invoices')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_payment_items');
        Schema::dropIfExists('customer_payments');
    }
};
