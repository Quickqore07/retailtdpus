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
        Schema::create('sales_projections', function (Blueprint $table) {
            $table->id();
            $table->integer('year');
            $table->date('start_date');
            $table->date('end_date');
            $table->unsignedBigInteger('company_id');
            $table->decimal('sales_projection', 12, 2)->default(0);
            $table->decimal('ideal_food_projection', 12, 2)->default(0);
            $table->integer('created_by')->nullable();
            $table->integer('updated_by')->nullable();
            $table->timestamps();

            $table->unique(['start_date', 'end_date', 'company_id'], 'sales_projections_week_company_unique');
            $table->index(['year', 'start_date', 'end_date'], 'sales_projections_year_week_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sales_projections');
    }
};
