<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chargebacks', function (Blueprint $table) {
            $table->id();
            $table->string('case_number')->nullable();
            $table->string('network_case_number')->nullable();
            $table->string('reference_number')->nullable();
            $table->integer('company_id')->nullable();
            $table->enum('reason_code', ['4837', '10.4', '4853', '13.1'])->nullable();
            $table->decimal('amount', 14, 2)->default(0);
            $table->enum('card_network', ['Visa', 'Mastercard', 'Amex', 'Discover'])->nullable();
            $table->string('card_last_four', 4)->nullable();
            $table->enum('entry_mode', ['Key entered', 'Swiped', 'Chip / EMV', 'Contactless'])->nullable();
            $table->date('transaction_date')->nullable();
            $table->date('chargeback_received_date')->nullable();
            $table->date('processor_due_date')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('document_id')->nullable()->constrained('upload_documents')->nullOnDelete();
            $table->foreignId('sales_receipt_document_id')->nullable()->constrained('upload_documents')->nullOnDelete();
            $table->date('upload_sales_receipt_date')->nullable();
            $table->integer('sales_receipt_uploaded_by')->nullable();
            $table->boolean('marked_as_received')->default(false);
            $table->integer('marked_as_received_by')->nullable();
            $table->timestamp('marked_as_received_at')->nullable();
            $table->date('credited_date')->nullable();
            $table->boolean('submitted')->default(false);
            $table->integer('submitted_by')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->integer('created_by')->nullable();
            $table->integer('updated_by')->nullable();
            $table->timestamps();

            $table->index('company_id');
            $table->index('case_number');
            $table->index('transaction_date');
            $table->index('processor_due_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chargebacks');
    }
};
