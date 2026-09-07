<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customer_payments', function (Blueprint $table) {
            $table->decimal('amount_received', 14, 2)->default(0)->after('total_amount');
            $table->decimal('credit_applied', 14, 2)->default(0)->after('amount_received');
            $table->decimal('unapplied_amount', 14, 2)->default(0)->after('credit_applied');
        });

        DB::table('customer_payments')->update([
            'amount_received' => DB::raw('total_amount'),
            'credit_applied' => 0,
            'unapplied_amount' => 0,
        ]);

        Schema::create('customer_payment_credit_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('customer_payment_id');
            $table->unsignedBigInteger('source_customer_payment_id');
            $table->decimal('amount', 14, 2);
            $table->timestamps();

            $table->index('customer_payment_id');
            $table->index('source_customer_payment_id');
            $table->foreign('customer_payment_id')
                ->references('id')->on('customer_payments')
                ->cascadeOnDelete();
            $table->foreign('source_customer_payment_id')
                ->references('id')->on('customer_payments')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_payment_credit_items');

        Schema::table('customer_payments', function (Blueprint $table) {
            $table->dropColumn(['amount_received', 'credit_applied', 'unapplied_amount']);
        });
    }
};
