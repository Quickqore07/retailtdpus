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
        Schema::table('onboarding_list', function (Blueprint $table) {
            $table->boolean('i9_completed')->default(false)->after('i9_with_work_bright');
            $table->string('i9_completed_datetime')->nullable()->after('i9_completed');
            $table->boolean('w4_completed')->default(false)->after('i9_completed');
            $table->string('w4_completed_datetime')->nullable()->after('w4_completed');
            
            $table->boolean('i9_approved')->default(false)->after('w4_completed');
            $table->string('i9_approved_datetime')->nullable()->after('i9_approved');
            $table->boolean('w4_approved')->default(false)->after('i9_approved');
            $table->string('w4_approved_datetime')->nullable()->after('w4_approved');
            
            $table->boolean('i9_rejected')->default(false)->after('w4_approved');
            $table->text('i9_rejected_reason')->nullable()->after('i9_rejected');
            $table->string('i9_rejected_datetime')->nullable()->after('i9_rejected');
            $table->boolean('w4_rejected')->default(false)->after('i9_rejected');
            $table->text('w4_rejected_reason')->nullable()->after('w4_rejected');
            $table->string('w4_rejected_datetime')->nullable()->after('w4_rejected');
            
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('onboarding_list', function (Blueprint $table) {
            $table->dropColumn('i9_completed');
            $table->dropColumn('i9_completed_datetime');
            $table->dropColumn('w4_completed');
            $table->dropColumn('w4_completed_datetime');

            $table->dropColumn('i9_approved');
            $table->dropColumn('i9_approved_datetime');
            $table->dropColumn('w4_approved');
            $table->dropColumn('w4_approved_datetime');

            $table->dropColumn('i9_rejected');
            $table->dropColumn('i9_rejected_datetime');
            $table->dropColumn('i9_rejected_reason');
            $table->dropColumn('w4_rejected');
            $table->dropColumn('w4_rejected_datetime');
            $table->dropColumn('w4_rejected_reason');
        });
    }
};
