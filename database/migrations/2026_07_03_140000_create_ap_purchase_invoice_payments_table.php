<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ap_purchase_invoice_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_invoice_id')->constrained('ap_purchase_invoices')->cascadeOnDelete();
            $table->decimal('amount', 10, 2);
            $table->string('check_number');
            $table->date('payment_date');
            $table->integer('created_by')->nullable();
            $table->integer('updated_by')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ap_purchase_invoice_payments');
    }
};
