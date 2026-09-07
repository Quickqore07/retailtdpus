<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('app_notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('content');
            $table->string('type', 64)->default('info');
            $table->timestamp('read_at')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['user_id', 'created_at']);
        });
        Schema::table('roles', function (Blueprint $table) {
            $table->json('notification_permissions')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('app_notifications');
        Schema::table('roles', function (Blueprint $table) {
            $table->dropColumn('notification_permissions');
        });
    }
};
