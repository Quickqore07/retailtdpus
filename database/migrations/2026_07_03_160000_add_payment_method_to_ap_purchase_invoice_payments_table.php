<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ap_purchase_invoice_payments', function (Blueprint $table) {
            $table->string('payment_method')->default('check')->after('amount');
            $table->string('check_number')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('ap_purchase_invoice_payments', function (Blueprint $table) {
            $table->dropColumn('payment_method');
            $table->string('check_number')->nullable(false)->change();
        });
    }
};
