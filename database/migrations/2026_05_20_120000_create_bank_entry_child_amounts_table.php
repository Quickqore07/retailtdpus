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
        Schema::create('bank_entry_child_amounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bank_entry_item_id')->constrained('bank_entry_items')->onDelete('cascade');
            $table->decimal('deposit', 10, 2)->default(0);
            $table->decimal('fees', 10, 2)->default(0);
            $table->decimal('total_amount', 10, 2)->default(0);
            $table->timestamps();
        });
        Schema::table('pj_payments_items', function (Blueprint $table) {
            $table->foreignId('bank_child_amount_id')->nullable()->after('settled')->constrained('bank_entry_child_amounts')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pj_payments_items', function (Blueprint $table) {
            $table->dropForeign(['bank_child_amount_id']);
            $table->dropColumn('bank_child_amount_id');
        });
        Schema::dropIfExists('bank_entry_child_amounts');
    }
};