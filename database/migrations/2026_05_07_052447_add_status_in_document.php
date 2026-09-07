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
        Schema::table('upload_documents', function (Blueprint $table) {
            $table->boolean('is_invoice')->default(0)->after('ref_id');
            $table->boolean('is_check')->default(0)->after('is_invoice');
            $table->enum('invoice_status', ['draft','approved','paid','cancelled'])->default('draft')->after('is_invoice');
            $table->timestamp('invoice_approved_at')->nullable()->after('invoice_status');
            $table->integer('invoice_approved_by')->nullable()->after('invoice_approved_at');

            $table->enum('check_status', ['pending','paid'])->default('pending')->after('is_check');
            $table->timestamp('check_paid_at')->nullable()->after('check_status');
            $table->integer('check_paid_by')->nullable()->after('check_paid_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('upload_documents', function (Blueprint $table) {
            $table->dropColumn(['invoice_status', 'invoice_approved_at', 'invoice_approved_by', 'check_status', 'check_paid_at', 'check_paid_by']);
            $table->dropColumn(['is_invoice', 'is_check']);
        });
    }
};
