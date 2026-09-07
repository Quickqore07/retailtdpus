<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Free the daily_sales name for the new retail daily sales feature.
        if(Schema::hasTable('daily_sales') && Schema::hasTable('other_daily_sales')) {
            Schema::rename('daily_sales', 'old_daily_sales');
        }

        if (Schema::hasTable('other_daily_sales_bank_deps')) {
            Schema::dropIfExists('other_daily_sales_bank_deps');
        }

        if (Schema::hasTable('other_daily_sales_cash_recs')) {
            Schema::table('other_daily_sales_cash_recs', function (Blueprint $table) {
                $table->dropForeign('ods_cash_sale_fk');
            });
        }

        if (Schema::hasTable('other_daily_sales') && ! Schema::hasTable('daily_sales')) {
            Schema::rename('other_daily_sales', 'daily_sales');
        }

        if (Schema::hasTable('other_daily_sales_cash_recs') && ! Schema::hasTable('daily_sales_cash_recs')) {
            Schema::rename('other_daily_sales_cash_recs', 'daily_sales_cash_recs');
        }

        if (Schema::hasTable('daily_sales_cash_recs') && Schema::hasColumn('daily_sales_cash_recs', 'other_daily_sale_id')) {
            Schema::table('daily_sales_cash_recs', function (Blueprint $table) {
                $table->renameColumn('other_daily_sale_id', 'daily_sales_id');
            });

            Schema::table('daily_sales_cash_recs', function (Blueprint $table) {
                $table->foreign('daily_sales_id', 'dsc_sale_fk')
                    ->references('id')
                    ->on('daily_sales')
                    ->cascadeOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('daily_sales_cash_recs') && Schema::hasColumn('daily_sales_cash_recs', 'daily_sales_id')) {
            Schema::table('daily_sales_cash_recs', function (Blueprint $table) {
                $table->dropForeign('dsc_sale_fk');
            });

            Schema::table('daily_sales_cash_recs', function (Blueprint $table) {
                $table->renameColumn('daily_sales_id', 'other_daily_sale_id');
            });
        }

        if (Schema::hasTable('daily_sales_cash_recs') && ! Schema::hasTable('other_daily_sales_cash_recs')) {
            Schema::rename('daily_sales_cash_recs', 'other_daily_sales_cash_recs');
        }

        if (Schema::hasTable('daily_sales') && ! Schema::hasTable('other_daily_sales')) {
            Schema::rename('daily_sales', 'other_daily_sales');
        }

        if (Schema::hasTable('other_daily_sales_cash_recs') && Schema::hasColumn('other_daily_sales_cash_recs', 'other_daily_sale_id')) {
            Schema::table('other_daily_sales_cash_recs', function (Blueprint $table) {
                $table->foreign('other_daily_sale_id', 'ods_cash_sale_fk')
                    ->references('id')
                    ->on('other_daily_sales')
                    ->cascadeOnDelete();
            });
        }

        if (! Schema::hasTable('other_daily_sales_bank_deps') && Schema::hasTable('other_daily_sales')) {
            Schema::create('other_daily_sales_bank_deps', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('other_daily_sale_id');
                $table->string('bank_name')->nullable();
                $table->decimal('amount', 12, 2)->default(0);
                $table->timestamps();

                $table->foreign('other_daily_sale_id', 'ods_bank_sale_fk')
                    ->references('id')
                    ->on('other_daily_sales')
                    ->cascadeOnDelete();
            });
        }

        if (Schema::hasTable('old_daily_sales') && ! Schema::hasTable('daily_sales')) {
            Schema::table('bank_deposits', function (Blueprint $table) {
                $table->dropForeign(['daily_sale_id']);
            });

            Schema::table('daily_sales_other_payments', function (Blueprint $table) {
                $table->dropForeign(['daily_sale_id']);
            });

            Schema::table('shortage', function (Blueprint $table) {
                $table->dropForeign(['daily_sale_id']);
            });

            Schema::rename('old_daily_sales', 'daily_sales');

            Schema::table('bank_deposits', function (Blueprint $table) {
                $table->foreign('daily_sale_id')
                    ->references('id')
                    ->on('daily_sales')
                    ->cascadeOnDelete();
            });

            Schema::table('daily_sales_other_payments', function (Blueprint $table) {
                $table->foreign('daily_sale_id')
                    ->references('id')
                    ->on('daily_sales')
                    ->cascadeOnDelete();
            });

            Schema::table('shortage', function (Blueprint $table) {
                $table->foreign('daily_sale_id')
                    ->references('id')
                    ->on('daily_sales')
                    ->cascadeOnDelete();
            });
        }
    }
};
