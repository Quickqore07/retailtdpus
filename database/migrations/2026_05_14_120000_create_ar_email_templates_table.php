<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ar_email_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->enum('template_type', ['invoice', 'statement']);
            $table->boolean('is_default')->default(false);
            $table->string('subject', 500)->nullable();
            $table->text('body')->nullable();
            $table->timestamps();

            $table->index(['template_type', 'is_default']);
        });

        Schema::table('upload_portal_customers', function (Blueprint $table) {
            $table->integer('cvv')->nullable()->after('credit_card_number');
            $table->string('card_type', 100)->nullable()->after('cvv');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ar_email_templates');
        Schema::table('upload_portal_customers', function (Blueprint $table) {
            $table->dropColumn('cvv');
            $table->dropColumn('card_type');
        });
    }
};
