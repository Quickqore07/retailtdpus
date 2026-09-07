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
        Schema::table('employee_hours_requests', function (Blueprint $table) {
            $table->dropColumn('approved_by');
            $table->dropColumn('approved_at');
            $table->boolean('hr_approved')->default(false)->after('notes');
            $table->timestamp('hr_approved_at')->nullable()->after('hr_approved');
            $table->integer('hr_approved_by')->nullable()->after('hr_approved_at');
            $table->boolean('do_approved')->default(false)->after('hr_approved_by');
            $table->timestamp('do_approved_at')->nullable()->after('do_approved');
            $table->integer('do_approved_by')->nullable()->after('do_approved_at');
            $table->boolean('admin_approved')->default(false)->after('do_approved_by');
            $table->timestamp('admin_approved_at')->nullable()->after('admin_approved');
            $table->integer('admin_approved_by')->nullable()->after('admin_approved_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employee_hours_requests', function (Blueprint $table) {
            $table->integer('approved_by')->nullable()->after('status');
            $table->datetime('approved_at')->nullable()->after('approved_by');
            $table->dropColumn([
                'hr_approved', 'hr_approved_at', 'hr_approved_by',
                'do_approved', 'do_approved_at', 'do_approved_by',
                'admin_approved', 'admin_approved_at', 'admin_approved_by',
            ]);
        });
    }
};
