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
            $table->boolean('i9_doc_skip')->default(false)->after('onboarding_status');
            $table->boolean('move_from_pending')->default(false)->after('i9_doc_skip');

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employee', function (Blueprint $table) {
            $table->dropColumn('i9_doc_skip');
            $table->dropColumn('move_from_pending');
        });
    }
};
