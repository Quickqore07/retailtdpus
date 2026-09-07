<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ap_expense_types', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('amount_label')->default('Amount');
            $table->string('other_amount_label')->default('Other Amount');
            $table->boolean('show_other_amount')->default(true);
            $table->boolean('active')->default(true);
            $table->integer('created_by')->nullable();
            $table->integer('updated_by')->nullable();
            $table->timestamps();
        });

        $now = now();

        DB::table('ap_expense_types')->insert([
            [
                'name' => 'Repair',
                'amount_label' => 'Amount',
                'other_amount_label' => 'Other Amount',
                'show_other_amount' => false,
                'active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'Trash Tickets',
                'amount_label' => 'Fees',
                'other_amount_label' => 'Tickets',
                'show_other_amount' => true,
                'active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'Electric / Gas',
                'amount_label' => 'Electric',
                'other_amount_label' => 'Gas',
                'show_other_amount' => true,
                'active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'Other',
                'amount_label' => 'Amount',
                'other_amount_label' => 'Other Amount',
                'show_other_amount' => true,
                'active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);
    }

    public function down(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS = 0');
        Schema::dropIfExists('ap_expense_types');
        DB::statement('SET FOREIGN_KEY_CHECKS = 1');
    }
};
