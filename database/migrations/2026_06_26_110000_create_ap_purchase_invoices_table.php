<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ap_purchase_invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workgroup_id')->constrained('workgroup');
            $table->foreignId('company_id')->constrained('company');
            $table->foreignId('vendor_id')->constrained('ap_vendors');
            $table->date('invoice_date');
            $table->string('invoice_no');
            $table->date('due_date')->nullable();
            $table->decimal('amount', 10, 2)->default(0);
            $table->text('remarks')->nullable();
            $table->foreignId('document_id')->nullable()->constrained('upload_documents');
            $table->enum('status', ['draft', 'approved', 'paid', 'cancelled'])->default('draft');
            $table->string('check_number')->nullable();
            $table->integer('created_by')->nullable();
            $table->integer('updated_by')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ap_purchase_invoices');
    }
};
