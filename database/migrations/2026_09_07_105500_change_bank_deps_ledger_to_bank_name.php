<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('other_daily_sales_bank_deps', function (Blueprint $table) {
            if (Schema::hasColumn('other_daily_sales_bank_deps', 'ledger_id')) {
                $table->dropForeign(['ledger_id']);
                $table->dropColumn('ledger_id');
            }

            if (! Schema::hasColumn('other_daily_sales_bank_deps', 'bank_name')) {
                $table->string('bank_name')->nullable()->after('other_daily_sale_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('other_daily_sales_bank_deps', function (Blueprint $table) {
            if (Schema::hasColumn('other_daily_sales_bank_deps', 'bank_name')) {
                $table->dropColumn('bank_name');
            }

            if (! Schema::hasColumn('other_daily_sales_bank_deps', 'ledger_id')) {
                $table->unsignedBigInteger('ledger_id')->nullable()->after('other_daily_sale_id');
                $table->foreign('ledger_id')->references('id')->on('ledgers')->nullOnDelete();
            }
        });
    }
};
