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
        Schema::table('sales_projections', function (Blueprint $table) {
            $table->decimal('ideal_cost_percent', 12, 2)->default(0)->after('ideal_food_projection');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sales_projections', function (Blueprint $table) {
            $table->dropColumn('ideal_cost_percent');
        });
    }
};
