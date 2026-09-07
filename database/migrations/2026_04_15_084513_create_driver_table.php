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
        Schema::create('drivers', function (Blueprint $table) {
            $table->id();
            $table->date('date');
            $table->foreignId('company_id')->constrained('company');
            $table->string('driver_id')->nullable();
            $table->string('driver_name')->nullable();
            $table->integer('employee_id')->nullable();
            $table->string('time_in', 10)->nullable();
            $table->string('time_out', 10)->nullable();
            $table->decimal('driver_in_store_pay', 10, 2)->nullable();
            $table->decimal('other_in_store_pay', 10, 2)->nullable();
            $table->decimal('on_road_pay', 10, 2)->nullable();
            $table->decimal('cash_tips', 10, 2)->nullable();
            $table->decimal('cc_tips', 10, 2)->nullable();
            $table->decimal('mileage', 10, 2)->nullable();
            $table->decimal('all_in_pay', 10, 2)->nullable();
            $table->decimal('store_hours', 10, 2)->nullable();
            $table->decimal('road_hours', 10, 2)->nullable();
            $table->decimal('total_hours', 10, 2)->nullable();
            $table->decimal('avg_pay_per_hour', 10, 2)->nullable();
            $table->integer('delivery')->default(0);
            $table->integer('created_by')->nullable();
            $table->integer('updated_by')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('drivers');
    }
};
