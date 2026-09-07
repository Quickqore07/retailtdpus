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
        Schema::table('ledger_details', function (Blueprint $table) {
            $table->string('code')->nullable()->after('ledger_id');
        });
        Schema::table('ledgers', function (Blueprint $table) {
            $table->dropColumn('code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ledger_details', function (Blueprint $table) {
            $table->dropColumn('code');
        });
        Schema::table('ledger', function (Blueprint $table) {
            $table->string('code')->nullable()->after('id');
        });
    }
};
