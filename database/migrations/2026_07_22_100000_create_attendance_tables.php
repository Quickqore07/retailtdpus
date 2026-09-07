<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendance', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('employee_id')->index();
            $table->timestamp('check_in')->nullable();
            $table->timestamp('check_out')->nullable();
            $table->unsignedInteger('work_minutes')->default(0);
            $table->unsignedInteger('break_minutes')->default(0);
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('device')->nullable();
            $table->string('status')->nullable()->index();
            $table->timestamps();
        });

        Schema::create('breaks', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('attendance_id')->index();
            $table->timestamp('start_time')->nullable();
            $table->timestamp('end_time')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('breaks');
        Schema::dropIfExists('attendance');
    }
};
