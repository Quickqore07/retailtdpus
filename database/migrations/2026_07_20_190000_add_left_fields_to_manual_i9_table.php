<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('manual_i9', function (Blueprint $table) {
            $table->boolean('left_terminate')->default(false)->index();
            $table->text('left_note')->nullable();
            $table->dateTime('left_at')->nullable();
            $table->unsignedBigInteger('left_updated_by')->nullable()->index();
        });
    }

    public function down(): void
    {
        Schema::table('manual_i9', function (Blueprint $table) {
            $table->dropColumn(['left_terminate', 'left_note', 'left_at', 'left_updated_by']);
        });
    }
};
