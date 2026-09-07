<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
            DB::statement("
            ALTER TABLE employee_rates
            MODIFY rate_type ENUM(
                'Payroll Slab',
                'Payroll Regular',
                'Payroll 1099',
                '1099 Regular',
                '1099 Slab',
                '1099 1099'
            ) NOT NULL DEFAULT 'Payroll Regular'
        ");
        Schema::table('employee_rates', function (Blueprint $table) {
            $table->decimal('ten99_rate', 10, 2)->nullable()->after('rate');
        });

        DB::statement("
            ALTER TABLE employee_rate_requests
            MODIFY rate_type ENUM(
                'Payroll Slab',
                'Payroll Regular',
                'Payroll 1099',
                '1099 Regular',
                '1099 Slab',
                '1099 1099'
            ) NOT NULL DEFAULT 'Payroll Regular'
        ");

        Schema::table('employee_rate_requests', function (Blueprint $table) {
            $table->decimal('ten99_rate', 10, 2)->nullable()->after('rate');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // DB::statement("
        //     ALTER TABLE employee_rates
        //     MODIFY rate_type ENUM(
        //         'Payroll Slab',
        //         'Payroll Regular',
        //         '1099 Regular',
        //         '1099 Slab',
        //         '1099 1099'
        //     ) NOT NULL DEFAULT 'Payroll Regular'
        // ");
        // DB::statement("
        //     ALTER TABLE employee_rate_requests
        //     MODIFY rate_type ENUM(
        //         'Payroll Slab',
        //         'Payroll Regular',
        //         'Payroll 1099',
        //         '1099 Regular',
        //         '1099 Slab'
        //     ) NOT NULL DEFAULT 'Payroll Regular'
        // ");

        Schema::table('employee_rates', function (Blueprint $table) {
            $table->dropColumn('ten99_rate');
        });

        Schema::table('employee_rate_requests', function (Blueprint $table) {
            $table->dropColumn('ten99_rate');
        });
    }
};
