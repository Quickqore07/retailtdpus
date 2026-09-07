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
        Schema::create('payroll_journals', function (Blueprint $table) {
            $table->id();
            $table->integer('company_id')->foreignId('company_id')->constrained('company');
            $table->date('eow');

            $table->decimal('cs_accident', 12, 2)->default(0);
            $table->decimal('cs_term_life', 12, 2)->default(0);
            $table->decimal('crit_illness', 12, 2)->default(0);
            $table->decimal('dental_a', 12, 2)->default(0);
            $table->decimal('dental_b', 12, 2)->default(0);
            $table->decimal('dental_d', 12, 2)->default(0);
            $table->decimal('ee_term_life', 12, 2)->default(0);
            $table->decimal('hospital_ind', 12, 2)->default(0);
            $table->decimal('id_theft', 12, 2)->default(0);
            $table->decimal('legal', 12, 2)->default(0);
            $table->decimal('life_ltc', 12, 2)->default(0);
            $table->decimal('pet_Well', 12, 2)->default(0);
            $table->decimal('sp_term_life', 12, 2)->default(0);
            $table->decimal('std', 12, 2)->default(0);
            $table->decimal('vision', 12, 2)->default(0);

            $table->decimal('cash_tips', 12, 2)->default(0);
            $table->decimal('charge_tips', 12, 2)->default(0);
            $table->decimal('charge_tips_reimb', 12, 2)->default(0);
            $table->decimal('deduction', 12, 2)->default(0);
            $table->decimal('dental', 12, 2)->default(0);
            $table->decimal('direct_deposit_debit', 12, 2)->default(0);
            $table->decimal('hours', 12, 2)->default(0);
            $table->decimal('wages', 12, 2)->default(0);
            $table->decimal('medical', 12, 2)->default(0);
            $table->decimal('mileage_reimb', 12, 2)->default(0);
            $table->decimal('min_wage_adjust', 12, 2)->default(0);
            $table->decimal('min_wage_adjust_anne', 12, 2)->default(0);
            
            $table->decimal('min_wage_adjust_bcit', 12, 2)->default(0);
            $table->decimal('min_wage_adjust_balt', 12, 2)->default(0);
            $table->decimal('min_wage_adjust_calv', 12, 2)->default(0);
            $table->decimal('min_wage_adjust_carr', 12, 2)->default(0);
            $table->decimal('min_wage_adjust_char', 12, 2)->default(0);
            $table->decimal('min_wage_adjust_fred', 12, 2)->default(0);
            $table->decimal('min_wage_adjust_harf', 12, 2)->default(0);
            $table->decimal('min_wage_adjust_howa', 12, 2)->default(0);
            $table->decimal('min_wage_adjust_mont', 12, 2)->default(0);
            $table->decimal('min_wage_adjust_prin', 12, 2)->default(0);
            $table->decimal('min_wage_adjust_stma', 12, 2)->default(0);
            
            $table->decimal('overtime', 12, 2)->default(0);
            $table->decimal('overtime_wages', 12, 2)->default(0);

            $table->decimal('px_garnishment', 12, 2)->default(0);
            $table->decimal('px_garnishment_2', 12, 2)->default(0);
            $table->decimal('retro_pretax_premium', 12, 2)->default(0);
            $table->decimal('salary', 12, 2)->default(0);

            $table->decimal('sick', 12, 2)->default(0);
            $table->decimal('term_life_pretax', 12, 2)->default(0);
            $table->decimal('vacation', 12, 2)->default(0);

            $table->decimal('total_hours', 12, 2)->default(0);
            $table->decimal('total_earnings', 12, 2)->default(0);
            $table->decimal('total_deductions', 12, 2)->default(0);
            $table->decimal('total_other_payments', 12, 2)->default(0);
            
            $table->decimal('ee_withholdings', 12, 2)->default(0);
            $table->decimal('er_withholdings', 12, 2)->default(0);
            $table->decimal('manual_net_pay', 12, 2)->default(0);
            $table->decimal('negotiable_net_pay', 12, 2)->default(0);
            $table->decimal('non_negotiable_net_pay', 12, 2)->default(0);
            $table->decimal('ctc', 12, 2)->default(0);

            $table->integer('created_by')->nullable();
            $table->integer('updated_by')->nullable();


            $table->timestamps();
        });

        Schema::table('company', function (Blueprint $table) {
            $table->string('payroll_id')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payroll_journals');
        Schema::table('company', function (Blueprint $table) {
            $table->dropColumn('payroll_id');
        });
    }
};
