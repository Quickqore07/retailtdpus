<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('bank_entry_child_amounts', function (Blueprint $table) {
            $table->boolean('manual_settlement')->default(false)->after('settled');
        });
    }

    public function down()
    {
        Schema::table('bank_entry_child_amounts', function (Blueprint $table) {
            $table->dropColumn('manual_settlement');
        });
    }
};
