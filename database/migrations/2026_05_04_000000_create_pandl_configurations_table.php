<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('pandl_configurations', function (Blueprint $table) {
            $table->id();
            $table->string('label');
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->foreignId('updated_by')->nullable()->constrained('users');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('pandl_configuration_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pandl_id')->constrained('pandl_configurations')->onDelete('cascade');
            $table->text('ledgers');
            $table->enum('type', ['Income', 'COGS', 'Expense']);
            $table->enum('cogs_type', ['Food Purchase', 'Labor Cost', 'Franchise fee'])->nullable();
            $table->string('label');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('pandl_configuration_details');
        Schema::dropIfExists('pandl_configurations');
    }
};
