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
        Schema::table('emergency_contacts', function (Blueprint $table) {
            $table->string('e_emergency_contact_email')->nullable()->after('e_emergency_contact_phone_work');
            $table->string('e_emergency_contact2_email')->nullable()->after('e_emergency_contact2_phone_work');
        });
        Schema::table('employee', function (Blueprint $table) {
            $table->string('profile_picture')->nullable()->after('email');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('emergency_contacts', function (Blueprint $table) {
            $table->dropColumn(['e_emergency_contact_email', 'e_emergency_contact2_email']);
        });
        Schema::table('employee', function (Blueprint $table) {
            $table->dropColumn('profile_picture');
        });
    }
};
