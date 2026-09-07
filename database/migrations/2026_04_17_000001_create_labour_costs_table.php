<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('labour_costs', function (Blueprint $table) {
            $table->id();
            $table->date('eow');
            $table->integer('company_id');
            $table->decimal('hourly_earnings', 12, 2)->default(0);
            $table->decimal('salary_earnings', 12, 2)->default(0);
            $table->decimal('ot_earnings', 12, 2)->default(0);
            $table->decimal('mwa', 12, 2)->default(0);
            $table->decimal('vacation', 12, 2)->default(0);
            $table->decimal('bonus_earnings', 12, 2)->default(0);
            $table->decimal('holiday_earnings', 12, 2)->default(0);
            $table->decimal('employer_liability', 12, 2)->default(0);
            $table->decimal('total_earnings', 12, 2)->default(0);
            $table->integer('created_by')->nullable();
            $table->integer('updated_by')->nullable();
            $table->timestamps();

            $table->index(['eow', 'company_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('labour_costs');
    }
};
