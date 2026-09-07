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
        Schema::create('wc_entries', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('year');
            $table->date('eow');
            $table->integer('company_id');
            $table->decimal('driver_pay', 10, 2)->default(0);
            $table->decimal('non_driver_pay', 10, 2)->default(0);
            $table->decimal('total_pay', 10, 2)->default(0);
            $table->integer('created_by')->nullable();
            $table->integer('updated_by')->nullable();
            $table->timestamps();

            $table->index(['year', 'eow']);
            $table->index('company_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wc_entries');
    }
};
