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
        if (! Schema::hasColumn('activity_logs', 'index_value')) {
            Schema::table('activity_logs', function (Blueprint $table) {
                $table->string('index_value')->nullable()->after('auditable_id');
                $table->index('index_value');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('activity_logs', 'index_value')) {
            Schema::table('activity_logs', function (Blueprint $table) {
                $table->dropIndex(['index_value']);
                $table->dropColumn('index_value');
            });
        }
    }
};
