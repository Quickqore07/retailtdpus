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
        Schema::create('bank_uploads', function (Blueprint $table) {
            $table->id();
            $table->integer('company_id')->nullable();
            $table->string('account_number');
            $table->string('account_name')->nullable();
            $table->integer('bank_id')->unsigned()->nullable();
            $table->text('description')->nullable();
            $table->decimal('amount', 12, 2)->default(0);
            $table->date('date');
            $table->integer('created_by')->nullable();
            $table->integer('updated_by')->nullable();
            $table->timestamps();
            
            $table->index(['company_id', 'date']);
            $table->index(['account_number', 'account_name']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bank_uploads');
    }
};
