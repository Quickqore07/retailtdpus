<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chargeback_entry_modes', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->integer('created_by')->nullable();
            $table->integer('updated_by')->nullable();
            $table->timestamps();
        });

        $now = now();

        DB::table('chargeback_entry_modes')->insert([
            ['name' => 'Key entered', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Swiped', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Chip / EMV', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Contactless', 'created_at' => $now, 'updated_at' => $now],
        ]);

        if (Schema::hasColumn('chargebacks', 'entry_mode')) {
            Schema::table('chargebacks', function (Blueprint $table) {
                $table->string('entry_mode', 100)->nullable()->change();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('chargeback_entry_modes');
    }
};
