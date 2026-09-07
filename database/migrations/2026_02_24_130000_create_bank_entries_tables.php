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
        Schema::create('bank_entries', function (Blueprint $table) {
            $table->id();
            $table->integer('company_id');
            $table->date('date');
            $table->integer('bank')->nullable();
            $table->decimal('total_amount', 10, 2)->default(0);
            $table->boolean('is_imported')->default(false);
            $table->integer('created_by')->nullable();
            $table->integer('updated_by')->nullable();
            $table->timestamps();
        });

        Schema::create('bank_entry_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bank_entry_id')->constrained('bank_entries');
            $table->integer('ledger_id');
            $table->decimal('amount', 10, 2)->default(0);
            $table->timestamps();
        });

        Schema::table('ledger_vouchers', function (Blueprint $table) {
            $table->integer('company_id')->nullable()->after('ledger_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bank_entry_items');
        Schema::dropIfExists('bank_entries');
        Schema::table('ledger_vouchers', function (Blueprint $table) {
            $table->dropColumn('company_id');
        });
    }
};
