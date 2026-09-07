<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fees_uploads', function (Blueprint $table) {
            $table->decimal('ddc_doordash', 10, 2)->nullable()->default(0)->after('doordash');
        });
    }

    public function down(): void
    {
        Schema::table('fees_uploads', function (Blueprint $table) {
            $table->dropColumn('ddc_doordash');
        });
    }
};
