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
        Schema::table('benefit_enrollments', function (Blueprint $table) {
            $table->enum('benefit_acknowledgment', ['qualify', 'decline'])->nullable()->after('employee_date');
            $table->boolean('waive_ack_point_1')->nullable()->after('benefit_acknowledgment');
            $table->boolean('waive_ack_point_2')->nullable()->after('waive_ack_point_1');
            $table->string('waive_ack_name')->nullable()->after('waive_ack_point_2');
            $table->string('waive_ack_signature')->nullable()->after('waive_ack_name');
            $table->date('waive_ack_date')->nullable()->after('waive_ack_signature');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('benefit_enrollments', function (Blueprint $table) {
            $table->dropColumn('benefit_acknowledgment');
            $table->dropColumn([
                'waive_ack_point_1',
                'waive_ack_point_2',
                'waive_ack_name',
                'waive_ack_signature',
                'waive_ack_date',
            ]);
        });
    }
};
