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
        Schema::table('bank_entry_items', function (Blueprint $table) {
            $table->text('child_amounts')->nullable()->after('amount');
        });
        Schema::table('pj_payments_items', function (Blueprint $table) { 
            $table->boolean('settled')->default(false)->after('amount');
            $table->decimal('bank_entry_amount', 10, 2)->default(0)->after('amount');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bank_entry_items', function (Blueprint $table) {
            $table->dropColumn('child_amounts');
        });
        Schema::table('pj_payments_items', function (Blueprint $table) { 
            $table->dropColumn('settled');
            $table->dropColumn('bank_entry_amount');
        });
    }
};
