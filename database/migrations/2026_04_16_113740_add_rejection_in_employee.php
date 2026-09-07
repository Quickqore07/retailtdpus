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
        Schema::table('employee', function (Blueprint $table) {
            $table->boolean('rejected')->default(false);
            $table->datetime('rejected_at')->nullable();
            $table->integer('rejected_by')->nullable();
            $table->text('rejection_reason')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employee', function (Blueprint $table) {
            $table->dropColumn('rejected');
            $table->dropColumn('rejected_at');
            $table->dropColumn('rejected_by');
            $table->dropColumn('rejection_reason');
        });
    }
};
