<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('daily_sales', function (Blueprint $table) {
            if (! Schema::hasColumn('daily_sales', 'bank_deposits')) {
                $table->decimal('bank_deposits', 12, 2)->default(0)->after('cash_due');
            }
        });
    }

    public function down(): void
    {
        Schema::table('daily_sales', function (Blueprint $table) {
            if (Schema::hasColumn('daily_sales', 'bank_deposits')) {
                $table->dropColumn('bank_deposits');
            }
        });
    }
};
