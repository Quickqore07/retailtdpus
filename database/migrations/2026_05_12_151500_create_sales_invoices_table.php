<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales_invoices', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('customer_id');
            $table->string('invoice_number')->unique();
            $table->date('invoice_date');
            $table->date('due_date')->nullable();
            $table->decimal('subtotal', 14, 2)->default(0);
            $table->decimal('discount_total', 14, 2)->default(0);
            $table->decimal('tax_total', 14, 2)->default(0);
            $table->decimal('amount', 14, 2)->default(0);
            $table->text('remarks')->nullable();
            $table->enum('status', ['draft', 'sent', 'paid', 'cancelled'])->default('draft');
            $table->integer('created_by')->nullable();
            $table->integer('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('customer_id');
            $table->index('invoice_date');
            $table->index('status');
        });

        Schema::create('sales_invoice_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('sales_invoice_id');
            $table->string('item_name');
            $table->decimal('qty', 12, 2)->default(0);
            $table->decimal('unit_price', 14, 2)->default(0);
            $table->decimal('discount', 14, 2)->default(0);
            $table->decimal('tax_amount', 14, 2)->default(0);
            $table->decimal('total', 14, 2)->default(0);
            $table->timestamps();

            $table->index('sales_invoice_id');
            $table->foreign('sales_invoice_id')
                ->references('id')->on('sales_invoices')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_invoice_items');
        Schema::dropIfExists('sales_invoices');
    }
};
