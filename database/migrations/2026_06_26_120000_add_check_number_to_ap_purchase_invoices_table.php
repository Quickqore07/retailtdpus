<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ap_purchase_invoices', function (Blueprint $table) {
            if (!Schema::hasColumn('ap_purchase_invoices', 'check_number')) {
                $table->string('check_number')->nullable()->after('status');
            }
        });
    }

    public function down(): void
    {
        Schema::table('ap_purchase_invoices', function (Blueprint $table) {
            if (Schema::hasColumn('ap_purchase_invoices', 'check_number')) {
                $table->dropColumn('check_number');
            }
        });
    }
};
