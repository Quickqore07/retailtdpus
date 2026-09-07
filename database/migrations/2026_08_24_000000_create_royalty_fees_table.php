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
        Schema::create('royalty_fees', function (Blueprint $table) {
            $table->id();
            $table->string('type');
            $table->integer('company_id');
            $table->string('invoice_number');
            $table->date('invoice_date');
            $table->date('due_date');
            $table->decimal('amount', 12, 2)->default(0);
            $table->text('description')->nullable();
            $table->string('invoice_pdf')->nullable();
            $table->integer('created_by')->nullable();
            $table->integer('updated_by')->nullable();
            $table->timestamps();
            $table->index('type');
            $table->index('company_id');
            $table->index('invoice_date');
            $table->index('due_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('royalty_fees');
    }
};
