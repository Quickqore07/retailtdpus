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
        Schema::table('users', function (Blueprint $table) {
            $table->json('sp_permission')->nullable();
        });
        Schema::table('check_master', function (Blueprint $table) {
            $table->boolean('am_reviewed')->default(false);
            $table->timestamp('am_reviewed_at')->nullable();
            $table->integer('am_reviewed_by')->nullable();

            $table->boolean('hr_reviewed')->default(false);
            $table->timestamp('hr_reviewed_at')->nullable();
            $table->integer('hr_reviewed_by')->nullable();

            $table->boolean('admin_reviewed')->default(false);
            $table->timestamp('admin_reviewed_at')->nullable();
            $table->integer('admin_reviewed_by')->nullable();
            
            $table->boolean('uploaded')->default(false);
            $table->timestamp('uploaded_at')->nullable();
            $table->integer('uploaded_id')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('sp_permission');
        });
        Schema::table('check_master', function (Blueprint $table) {
            $table->dropColumn('am_reviewed');
            $table->dropColumn('am_reviewed_at');
            $table->dropColumn('am_reviewed_by');

            $table->dropColumn('hr_reviewed');
            $table->dropColumn('hr_reviewed_at');
            $table->dropColumn('hr_reviewed_by');

            $table->dropColumn('admin_reviewed');
            $table->dropColumn('admin_reviewed_at');
            $table->dropColumn('admin_reviewed_by');
            
            $table->dropColumn('uploaded');
            $table->dropColumn('uploaded_at');
            $table->dropColumn('uploaded_id');
        });
    }
};
