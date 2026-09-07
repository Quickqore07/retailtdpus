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
        Schema::create('bank_rules', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->unsignedBigInteger('ledger_id')->nullable();
            $table->string('condition', 10)->default('All')->comment('All = all conditions must match, some = any condition');
            $table->integer('created_by')->nullable();
            $table->integer('updated_by')->nullable();
            $table->timestamps();
        });

        Schema::create('bank_rule_conditions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bank_rule_id')->constrained('bank_rules')->cascadeOnDelete();
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
        Schema::dropIfExists('bank_rule_conditions');
        Schema::dropIfExists('bank_rules');
    }
};
