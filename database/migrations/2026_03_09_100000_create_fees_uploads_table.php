<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fees_uploads', function (Blueprint $table) {
            $table->id();
            $table->date('date');
            $table->foreignId('company_id')->constrained('company')->onDelete('cascade');
            $table->decimal('ddd_cash', 10, 2)->default(0);
            $table->decimal('ez_cater', 10, 2)->default(0);
            $table->decimal('meal_deal', 10, 2)->default(0);
            $table->decimal('visa', 10, 2)->default(0);
            $table->decimal('amex', 10, 2)->default(0);
            $table->decimal('total_amount', 10, 2)->default(0);
            $table->boolean('is_imported')->default(false);
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();

            $table->index('date');
            $table->index('company_id');
            $table->index(['date', 'company_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fees_uploads');
    }
};
