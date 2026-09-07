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
        Schema::create('ideal_cost', function (Blueprint $table) {
            $table->id();
            $table->date('date');
            $table->decimal('total_cost', 10, 2)->default(0);
            $table->decimal('total_mileage', 10, 2)->default(0);
            $table->decimal('total_delivery', 10, 2)->default(0);
            $table->integer('created_by')->nullable();
            $table->integer('updated_by')->nullable();
            $table->timestamps();
        });

        Schema::create('ideal_cost_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ideal_cost_id')->constrained('ideal_cost');
            $table->integer('company_id');
            $table->decimal('ideal_cost', 10, 2)->default(0);
            $table->decimal('mileage', 10, 2)->default(0);
            $table->decimal('delivery', 10, 2)->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ideal_cost_items');
        Schema::dropIfExists('ideal_cost');
    }
};
