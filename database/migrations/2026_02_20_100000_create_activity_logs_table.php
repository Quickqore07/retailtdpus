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
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->enum('action', [
                'create',
                'update',
                'delete',
                'approve',
                'bulk_import',
                'printing',
                'review'
            ]);
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('auditable_type'); // e.g. "employee_hours", "employee_rates"
            $table->unsignedBigInteger('auditable_id')->nullable(); // record id
            $table->json('old_values')->nullable(); // previous record data (for update/delete)
            $table->json('new_values')->nullable(); // new record data (for create/update)
            $table->text('description')->nullable(); // human-readable description
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();

            $table->index(['auditable_type', 'auditable_id']);
            $table->index(['user_id', 'created_at']);
            $table->index('action');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
    }
};
