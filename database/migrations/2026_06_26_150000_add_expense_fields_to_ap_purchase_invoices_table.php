<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ap_purchase_invoices', function (Blueprint $table) {
            $table->foreignId('expense_id')->nullable()->after('due_date')->constrained('ap_expense_types');
            $table->decimal('other_amount', 10, 2)->default(0)->after('amount');
            $table->decimal('total_amount', 10, 2)->default(0)->after('other_amount');
        });
    }

    public function down(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS = 0');
        Schema::table('ap_purchase_invoices', function (Blueprint $table) {
            $table->dropForeign(['expense_id']);
            $table->dropColumn(['expense_id', 'other_amount', 'total_amount']);
        });
        DB::statement('SET FOREIGN_KEY_CHECKS = 1');
    }
};
