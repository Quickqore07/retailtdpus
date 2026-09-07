<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('ap_purchase_invoice_payments', 'remarks')) {
            return;
        }

        Schema::table('ap_purchase_invoice_payments', function (Blueprint $table) {
            $table->text('remarks')->nullable()->after('payment_date');
        });
    }

    public function down(): void
    {
        if (!Schema::hasColumn('ap_purchase_invoice_payments', 'remarks')) {
            return;
        }

        Schema::table('ap_purchase_invoice_payments', function (Blueprint $table) {
            $table->dropColumn('remarks');
        });
    }
};
