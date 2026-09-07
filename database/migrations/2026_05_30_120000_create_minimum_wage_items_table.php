<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('minimum_wage_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('minimum_wage_id')->constrained('minimum_wages')->cascadeOnDelete();
            $table->date('effective_date');
            $table->decimal('minimum_wage', 10, 2);
            $table->decimal('tipped_minimum_wage', 10, 2)->default(0);
            $table->timestamps();

            $table->unique(['minimum_wage_id', 'effective_date']);
        });

        Schema::table('minimum_wages', function (Blueprint $table) {
            if (Schema::hasColumn('minimum_wages', 'year')) {
                $table->dropColumn('year');
            }
            if (Schema::hasColumn('minimum_wages', 'effective_date')) {
                $table->dropColumn('effective_date');
            }
            if (Schema::hasColumn('minimum_wages', 'minimum_wage')) {
                $table->dropColumn('minimum_wage');
            }
            if (Schema::hasColumn('minimum_wages', 'tipped_minimum_wage')) {
                $table->dropColumn('tipped_minimum_wage');
            }
        });

        Schema::table('minimum_wages', function (Blueprint $table) {
            $table->unique('state_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('minimum_wages', function (Blueprint $table) {
            $table->dropUnique(['state_id']);
            $table->string('year')->nullable();
            $table->date('effective_date')->nullable();
            $table->decimal('minimum_wage', 10, 2)->nullable();
            $table->decimal('tipped_minimum_wage', 10, 2)->default(0);
        });


        Schema::dropIfExists('minimum_wage_items');

    }

};
