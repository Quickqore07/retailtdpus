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
        Schema::table('bank_entry_child_amounts', function (Blueprint $table) {
            $table->string('source')->nullable()->after('id');
            $table->string('source_id')->nullable()->after('source');
            $table->boolean('settled')->default(false)->after('source_id');
        });

        Schema::table('daily_sales', function (Blueprint $table) {
            $table->boolean('settled')->default(false)->after('id');
            $table->foreignId('bank_child_amount_id')->nullable()->after('settled')->constrained('bank_entry_child_amounts')->onDelete('set null');

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bank_entry_child_amounts', function (Blueprint $table) {
            $table->dropColumn('source');
            $table->dropColumn('source_id');
            $table->dropColumn('settled');
        });
        Schema::table('daily_sales', function (Blueprint $table) {
            $table->dropForeign(['bank_child_amount_id']);
            $table->dropColumn('bank_child_amount_id');
            $table->dropColumn('settled');
        });
    }
};
