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
        Schema::table('ledgers', function (Blueprint $table) {
            $table->dropColumn('starting_check_number');
            $table->dropColumn('default_check');
            $table->dropColumn('account_no');
            $table->dropColumn('bank_name');
            $table->dropColumn('bank_address');
            $table->dropColumn('transition_code');
            $table->dropColumn('routing');
            $table->integer('created_by')->nullable();
            $table->integer('updated_by')->nullable();
        });

        Schema::create('ledger_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ledger_id')->constrained('ledgers');
            $table->foreignId('company_id')->constrained('company');
            $table->integer('starting_check_number')->nullable();
            $table->boolean('default_bank')->default(false);
            $table->string('account_type')->nullable();
            $table->string('account_no')->nullable();
            $table->string('bank_name')->nullable();
            $table->text('bank_address')->nullable();
            $table->string('transition_code')->nullable();
            $table->string('routing')->nullable();
            $table->integer('created_by')->nullable();
            $table->integer('updated_by')->nullable();
            $table->timestamps();
        });

        Schema::create('check_master', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ledger_id')->constrained('ledgers');
            $table->foreignId('company_id')->constrained('company');
            $table->foreignId('from_company_id')->constrained('company');
            $table->foreignId('employee_id')->constrained('employee')->nullable();
            $table->string('check_number')->nullable();
            $table->date('check_date')->nullable();
            $table->date('payroll_eow')->nullable();
            $table->string('check_amount')->nullable();
            $table->string('check_type')->nullable();
            $table->string('check_memo')->nullable();
            $table->integer('created_by')->nullable();
            $table->integer('updated_by')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ledgers', function (Blueprint $table) {
            $table->integer('starting_check_number')->default(0)->after('name');
            $table->boolean('default_bank')->default(false)->after('starting_check_number');
            $table->string('account_no')->nullable()->after('default_bank');
            $table->string('bank_name')->nullable()->after('account_no');
            $table->text('bank_address')->nullable()->after('bank_name');
            $table->string('transition_code')->nullable()->after('bank_address');
            $table->string('routing')->nullable()->after('transition_code');
            $table->dropColumn('created_by');
            $table->dropColumn('updated_by');
        });
        Schema::dropIfExists('ledger_details');
        Schema::dropIfExists('check_master');
    }
};
