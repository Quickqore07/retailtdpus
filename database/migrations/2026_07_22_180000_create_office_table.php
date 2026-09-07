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
        Schema::create('office', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);
            $table->unsignedInteger('authorized_radius')->default(300);
            $table->string('timezone', 64)->default('America/New_York');
            $table->boolean('active')->default(true);
            $table->integer('created_by')->default(0);
            $table->integer('updated_by')->default(0);
            $table->timestamps();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('office_id')->nullable()->after('workgroup_id')->constrained('office')->nullOnDelete();
            $table->time('checkout_time')->nullable()->after('office_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('office_id');
            $table->dropColumn('checkout_time');
        });

        Schema::dropIfExists('office');
    }
};
