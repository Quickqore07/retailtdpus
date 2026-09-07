<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chargeback_reason_codes', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('description');
            $table->integer('created_by')->nullable();
            $table->integer('updated_by')->nullable();
            $table->timestamps();
        });

        $now = now();

        DB::table('chargeback_reason_codes')->insert([
            [
                'code' => '4837',
                'description' => 'FRAUDULENT TRANSACTION NO CARDHOLDER AUTH',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'code' => '10.4',
                'description' => 'OTHER FRAUD-CARD ABSENT ENVIRONMENT',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'code' => '4853',
                'description' => 'CARDHOLDER DISPUTES',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'code' => '13.1',
                'description' => 'MERCHANDISE/SERVICES NOT RECEIVED',
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);

        if (Schema::hasColumn('chargebacks', 'reason_code')) {
            Schema::table('chargebacks', function (Blueprint $table) {
                $table->string('reason_code', 50)->nullable()->change();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('chargeback_reason_codes');
    }
};
