<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('other_daily_sales', function (Blueprint $table) {
            if (! Schema::hasColumn('other_daily_sales', 'total_payment')) {
                $table->decimal('total_payment', 12, 2)->default(0)->after('other_payment');
            }
            if (! Schema::hasColumn('other_daily_sales', 'cash_due')) {
                $table->decimal('cash_due', 12, 2)->default(0)->after('total_payment');
            }
        });

        Schema::table('other_daily_sales_cash_recs', function (Blueprint $table) {
            if (! Schema::hasColumn('other_daily_sales_cash_recs', 'is_default')) {
                $table->boolean('is_default')->default(false)->after('amount');
            }
        });
    }

    public function down(): void
    {
        Schema::table('other_daily_sales', function (Blueprint $table) {
            if (Schema::hasColumn('other_daily_sales', 'cash_due')) {
                $table->dropColumn('cash_due');
            }
            if (Schema::hasColumn('other_daily_sales', 'total_payment')) {
                $table->dropColumn('total_payment');
            }
        });

        Schema::table('other_daily_sales_cash_recs', function (Blueprint $table) {
            if (Schema::hasColumn('other_daily_sales_cash_recs', 'is_default')) {
                $table->dropColumn('is_default');
            }
        });
    }
};
