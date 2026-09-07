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
        Schema::create('ttm_reports', function (Blueprint $table) {
            $table->id();
            $table->string('store_number');
            $table->string('year');
            $table->string('month');
            $table->foreignId('label_id')->nullable()->constrained('pandl_configuration_details')->onDelete('set null');
            $table->decimal('amount', 10, 2)->default(0);
            $table->integer('created_by')->nullable();
            $table->integer('updated_by')->nullable();
            $table->timestamps();
        });
        Schema::table('ttm_reports', function (Blueprint $table) {
            $table->index(['year', 'month'], 'ttm_reports_year_month_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ttm_reports');
    }
};
