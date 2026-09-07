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
        Schema::table('upload_folders', function (Blueprint $table) {
            $table->boolean('is_invoice')->default(false);
            $table->boolean('is_check')->default(false);
        });

        Schema::table('upload_documents', function (Blueprint $table) {
            $table->integer('ref_id')->nullable();
            $table->enum('ref_type', ['purchase','payment'])->nullable();
        });

        Schema::create('ap_invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')->constrained('upload_documents');
            $table->integer('qq_purchase_id')->nullable();
            $table->integer('company_id')->nullable();
            $table->integer('qq_vendor_id')->nullable();
            $table->date('date')->nullable();
            $table->string('bill_number')->nullable();
            $table->integer('qq_ledger_id')->nullable();
            $table->date('due_date')->nullable();
            $table->decimal('amount', 10, 2)->default(0);
            $table->enum('status', ['draft','approved','paid','cancelled'])->default('draft');
            $table->integer('created_by')->nullable();
            $table->integer('updated_by')->nullable();
            $table->integer('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->integer('paid_by')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->integer('cancelled_by')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();
        });

        Schema::create('ap_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ap_invoice_id')->constrained('ap_invoices');
            $table->foreignId('document_id')->constrained('upload_documents');
            $table->integer('company_id')->nullable();
            $table->integer('qq_ledger_id')->nullable();
            $table->string('description')->nullable();
            $table->decimal('amount', 10, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('ap_payments', function (Blueprint $table) {
            $table->id();
            $table->integer('qq_paybill_id')->nullable();
            $table->integer('company_id')->nullable();
            $table->date('date')->nullable();
            $table->integer('qq_vendor_id')->nullable();
            $table->integer('qq_bank_id')->nullable();
            $table->enum('payment_type', ['cash','cheque','online'])->default('cash');
            $table->integer('qq_ledger_id')->nullable();
            $table->string('check_no')->default(0);
            $table->string('memo')->nullable();
            $table->decimal('amount', 10, 2)->default(0);
            $table->integer('created_by')->nullable();
            $table->integer('updated_by')->nullable();
            $table->integer('check_document_id')->nullable();
            $table->timestamps();
        });
        Schema::create('ap_payment_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ap_payment_id')->constrained('ap_payments');
            $table->integer('bill_id')->nullable();
            $table->decimal('amount', 10, 2)->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('upload_folders', function (Blueprint $table) {
            $table->dropColumn('is_invoice');
            $table->dropColumn('is_check');
        });
        Schema::dropIfExists('ap_items');
        Schema::dropIfExists('ap_invoices');
        Schema::dropIfExists('ap_payment_items');
        Schema::dropIfExists('ap_payments');
    }
};
