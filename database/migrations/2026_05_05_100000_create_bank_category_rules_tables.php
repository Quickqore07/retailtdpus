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
        Schema::create('bank_category_rules', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('label')->comment('Category label instead of ledger');
            $table->string('condition', 10)->default('All')->comment('All = all conditions must match, some = any condition');
            $table->integer('created_by')->nullable();
            $table->integer('updated_by')->nullable();
            $table->timestamps();
        });

        Schema::create('bank_category_rule_conditions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bank_category_rule_id')->constrained('bank_category_rules')->cascadeOnDelete();
            $table->string('condition_type');
            $table->string('value');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bank_category_rule_conditions');
        Schema::dropIfExists('bank_category_rules');
    }
};
