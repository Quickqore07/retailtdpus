<?php

namespace App\Console\Commands;

use App\Models\AR\FeesUpload;
use App\Models\AR\PjPayment;
use App\Models\DataEntry\BankEntry;
use App\Models\Settings\LedgerDetails;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class RecalculateLedgerVouchers extends Command
{
    protected $signature = 'ledger:recalculate
                          {--dry-run : Run without making actual changes}
                          {--verify : Verify the recalculation after completion}';

    protected $description = 'Recalculate all ledger vouchers from source tables (fees_uploads, bank_entries, pj_payments)';

    protected $tempTable = 'ledger_vouchers_temp';
    protected $originalTable = 'ledger_vouchers';

    public function handle()
    {
        $dryRun = $this->option('dry-run');
        $verify = $this->option('verify');

        $this->info('Starting ledger vouchers recalculation...');
        $this->newLine();

        if ($dryRun) {
            $this->warn('🔍 DRY RUN MODE - No changes will be made');
            $this->newLine();
        }

        try {
            $originalCount = DB::table($this->originalTable)->count();
            $this->info("📊 Current ledger_vouchers count: {$originalCount}");
            $this->newLine();

            // Step 1: Create temporary table
            $this->info('Step 1: Creating temporary table...');
            if (!$dryRun) {
                $this->createTempTable();
                $this->info('✅ Temporary table created');
            } else {
                $this->info('Would create temporary table: ' . $this->tempTable);
            }
            $this->newLine();

            // Step 2: Populate from fees_uploads
            $this->info('Step 2: Processing fees_uploads...');
            $feesCount = $this->processFeesUploads($dryRun);
            $this->info("✅ Processed {$feesCount} fees_uploads records");
            $this->newLine();

            // Step 3: Populate from bank_entries
            $this->info('Step 3: Processing bank_entries...');
            $bankCount = $this->processBankEntries($dryRun);
            $this->info("✅ Processed {$bankCount} bank_entries records");
            $this->newLine();

            // Step 4: Populate from pj_payments
            $this->info('Step 4: Processing pj_payments...');
            $pjCount = $this->processPjPayments($dryRun);
            $this->info("✅ Processed {$pjCount} pj_payments records");
            $this->newLine();

            if (!$dryRun) {
                $tempCount = DB::table($this->tempTable)->count();
                $this->info("📊 New ledger_vouchers_temp count: {$tempCount}");
                $this->newLine();

                // Step 5: Drop original table and rename temp
                $this->info('Step 5: Replacing original table with recalculated data...');
                $this->swapTables();
                $this->info('✅ Tables swapped successfully');
                $this->newLine();

                $finalCount = DB::table($this->originalTable)->count();
                $this->info("📊 Final ledger_vouchers count: {$finalCount}");
                $this->info("📈 Difference: " . ($finalCount - $originalCount));
                $this->newLine();

                if ($verify) {
                    $this->verifyRecalculation();
                }

                $this->info('✨ Ledger vouchers recalculation completed successfully!');
            } else {
                $this->info('📋 Summary (DRY RUN):');
                $this->info("  - Would process {$feesCount} fees_uploads records");
                $this->info("  - Would process {$bankCount} bank_entries records");
                $this->info("  - Would process {$pjCount} pj_payments records");
                $this->newLine();
                $this->info('Run without --dry-run to execute the recalculation');
            }

            return Command::SUCCESS;

        } catch (\Exception $e) {
            if (!$dryRun) {
                $this->cleanupTempTable();
            }

            $this->error('❌ Error: ' . $e->getMessage());
            $this->error($e->getTraceAsString());
            return Command::FAILURE;
        }
    }

    protected function createTempTable()
    {
        DB::statement("DROP TABLE IF EXISTS {$this->tempTable}");
        
        DB::statement("
            CREATE TABLE {$this->tempTable} (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                ledger_id INT UNSIGNED NULL,
                company_id INT UNSIGNED NULL,
                opp_ledger_id INT UNSIGNED NULL,
                amount DECIMAL(10,2) NULL,
                debit DECIMAL(10,2) NULL,
                credit DECIMAL(10,2) NULL,
                voucher_id INT UNSIGNED NULL,
                voucher_items_id INT UNSIGNED NULL,
                voucher_type VARCHAR(255) NULL,
                dbtable VARCHAR(255) NULL,
                check_number VARCHAR(255) NULL,
                description TEXT NULL,
                date DATE NULL,
                created_at TIMESTAMP NULL,
                updated_at TIMESTAMP NULL,
                KEY idx_ledger_id (ledger_id),
                KEY idx_company_id (company_id),
                KEY idx_voucher (voucher_id, voucher_type),
                KEY idx_date (date)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    }

    protected function processFeesUploads($dryRun = false)
    {
        if (!$dryRun) {
            DB::beginTransaction();
        }

        try {
            $feesUploads = FeesUpload::orderBy('id')->get();
            $count = 0;
            $ledgerVouchers = [];

            $dddCashLedger = LedgerDetails::where('code', '1001.49')->pluck('ledger_id', 'company_id')->toArray();
            $ezCaterLedger = LedgerDetails::where('code', '1001.53')->pluck('ledger_id', 'company_id')->toArray();
            $mealDealLedger = LedgerDetails::where('code', '1001.04')->pluck('ledger_id', 'company_id')->toArray();
            $visaLedger = LedgerDetails::where('code', '1001.01')->pluck('ledger_id', 'company_id')->toArray();
            $amexLedger = LedgerDetails::where('code', '1001.02')->pluck('ledger_id', 'company_id')->toArray();

            foreach ($feesUploads as $feesUpload) {
                $companyId = $feesUpload->company_id;

                if (isset($dddCashLedger[$companyId]) && $feesUpload->ddd_cash > 0) {
                    $ledgerVouchers[] = [
                        'company_id' => $companyId,
                        'ledger_id' => $dddCashLedger[$companyId],
                        'opp_ledger_id' => 0,
                        'amount' => -1 * floatval($feesUpload->ddd_cash),
                        'debit' => floatval($feesUpload->ddd_cash),
                        'credit' => 0,
                        'voucher_id' => $feesUpload->id,
                        'voucher_items_id' => null,
                        'voucher_type' => 'fees_uploads',
                        'dbtable' => 'fees_uploads',
                        'check_number' => null,
                        'description' => null,
                        'date' => $feesUpload->date,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }

                if (isset($ezCaterLedger[$companyId]) && $feesUpload->ez_cater > 0) {
                    $ledgerVouchers[] = [
                        'company_id' => $companyId,
                        'ledger_id' => $ezCaterLedger[$companyId],
                        'opp_ledger_id' => 0,
                        'amount' => -1 * floatval($feesUpload->ez_cater),
                        'debit' => floatval($feesUpload->ez_cater),
                        'credit' => 0,
                        'voucher_id' => $feesUpload->id,
                        'voucher_items_id' => null,
                        'voucher_type' => 'fees_uploads',
                        'dbtable' => 'fees_uploads',
                        'check_number' => null,
                        'description' => null,
                        'date' => $feesUpload->date,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }

                if (isset($mealDealLedger[$companyId]) && $feesUpload->meal_deal > 0) {
                    $ledgerVouchers[] = [
                        'company_id' => $companyId,
                        'ledger_id' => $mealDealLedger[$companyId],
                        'opp_ledger_id' => 0,
                        'amount' => -1 * floatval($feesUpload->meal_deal),
                        'debit' => floatval($feesUpload->meal_deal),
                        'credit' => 0,
                        'voucher_id' => $feesUpload->id,
                        'voucher_items_id' => null,
                        'voucher_type' => 'fees_uploads',
                        'dbtable' => 'fees_uploads',
                        'check_number' => null,
                        'description' => null,
                        'date' => $feesUpload->date,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }

                if (isset($visaLedger[$companyId]) && $feesUpload->visa > 0) {
                    $ledgerVouchers[] = [
                        'company_id' => $companyId,
                        'ledger_id' => $visaLedger[$companyId],
                        'opp_ledger_id' => 0,
                        'amount' => -1 * floatval($feesUpload->visa),
                        'debit' => floatval($feesUpload->visa),
                        'credit' => 0,
                        'voucher_id' => $feesUpload->id,
                        'voucher_items_id' => null,
                        'voucher_type' => 'fees_uploads',
                        'dbtable' => 'fees_uploads',
                        'check_number' => null,
                        'description' => null,
                        'date' => $feesUpload->date,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }

                if (isset($amexLedger[$companyId]) && $feesUpload->amex > 0) {
                    $ledgerVouchers[] = [
                        'company_id' => $companyId,
                        'ledger_id' => $amexLedger[$companyId],
                        'opp_ledger_id' => 0,
                        'amount' => -1 * floatval($feesUpload->amex),
                        'debit' => floatval($feesUpload->amex),
                        'credit' => 0,
                        'voucher_id' => $feesUpload->id,
                        'voucher_items_id' => null,
                        'voucher_type' => 'fees_uploads',
                        'dbtable' => 'fees_uploads',
                        'check_number' => null,
                        'description' => null,
                        'date' => $feesUpload->date,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }

                $count++;

                if (!$dryRun && count($ledgerVouchers) >= 500) {
                    DB::table($this->tempTable)->insert($ledgerVouchers);
                    $ledgerVouchers = [];
                }
            }

            if (!$dryRun && !empty($ledgerVouchers)) {
                DB::table($this->tempTable)->insert($ledgerVouchers);
            }

            if (!$dryRun) {
                DB::commit();
            }

            return $count;

        } catch (\Exception $e) {
            if (!$dryRun) {
                DB::rollBack();
            }
            throw $e;
        }
    }

    protected function processBankEntries($dryRun = false)
    {
        if (!$dryRun) {
            DB::beginTransaction();
        }

        try {
            $bankEntries = BankEntry::with('items')->orderBy('id')->get();
            $count = 0;
            $ledgerVouchers = [];

            foreach ($bankEntries as $entry) {
                foreach ($entry->items as $item) {
                    if ($item->amount == 0) continue;

                    $ledgerVouchers[] = [
                        'company_id' => $entry->company_id,
                        'ledger_id' => $item->ledger_id,
                        'opp_ledger_id' => 0,
                        'amount' => $item->amount,
                        'debit' => 0,
                        'credit' => $item->amount,
                        'voucher_id' => $entry->id,
                        'voucher_items_id' => null,
                        'voucher_type' => 'bank_entries',
                        'dbtable' => 'bank_entries',
                        'check_number' => null,
                        'description' => null,
                        'date' => $entry->date,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }

                $ledgerVouchers[] = [
                    'company_id' => $entry->company_id,
                    'ledger_id' => $entry->bank,
                    'opp_ledger_id' => 0,
                    'amount' => -1 * $entry->total_amount,
                    'debit' => $entry->total_amount,
                    'credit' => 0,
                    'voucher_id' => $entry->id,
                    'voucher_items_id' => null,
                    'voucher_type' => 'bank_entries',
                    'dbtable' => 'bank_entries',
                    'check_number' => null,
                    'description' => null,
                    'date' => $entry->date,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];

                $count++;

                if (!$dryRun && count($ledgerVouchers) >= 500) {
                    DB::table($this->tempTable)->insert($ledgerVouchers);
                    $ledgerVouchers = [];
                }
            }

            if (!$dryRun && !empty($ledgerVouchers)) {
                DB::table($this->tempTable)->insert($ledgerVouchers);
            }

            if (!$dryRun) {
                DB::commit();
            }

            return $count;

        } catch (\Exception $e) {
            if (!$dryRun) {
                DB::rollBack();
            }
            throw $e;
        }
    }

    protected function processPjPayments($dryRun = false)
    {
        if (!$dryRun) {
            DB::beginTransaction();
        }

        try {
            $pjPayments = PjPayment::with('items')->orderBy('id')->get();
            $count = 0;
            $ledgerVouchers = [];

            foreach ($pjPayments as $payment) {
                foreach ($payment->items as $item) {
                    if ($item->amount == 0) continue;

                    $ledgerVouchers[] = [
                        'company_id' => $payment->company_id,
                        'ledger_id' => $item->ledger_id,
                        'opp_ledger_id' => 0,
                        'amount' => -1 * $item->amount,
                        'debit' => $item->amount,
                        'credit' => 0,
                        'voucher_id' => $payment->id,
                        'voucher_items_id' => null,
                        'voucher_type' => 'pj_payments',
                        'dbtable' => 'pj_payments',
                        'check_number' => null,
                        'description' => null,
                        'date' => $payment->date,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }

                $count++;

                if (!$dryRun && count($ledgerVouchers) >= 500) {
                    DB::table($this->tempTable)->insert($ledgerVouchers);
                    $ledgerVouchers = [];
                }
            }

            if (!$dryRun && !empty($ledgerVouchers)) {
                DB::table($this->tempTable)->insert($ledgerVouchers);
            }

            if (!$dryRun) {
                DB::commit();
            }

            return $count;

        } catch (\Exception $e) {
            if (!$dryRun) {
                DB::rollBack();
            }
            throw $e;
        }
    }

    protected function swapTables()
    {
        DB::statement("SET FOREIGN_KEY_CHECKS=0");
        DB::statement("DROP TABLE IF EXISTS {$this->originalTable}");
        DB::statement("RENAME TABLE {$this->tempTable} TO {$this->originalTable}");
        DB::statement("SET FOREIGN_KEY_CHECKS=1");
    }

    protected function cleanupTempTable()
    {
        try {
            DB::statement("DROP TABLE IF EXISTS {$this->tempTable}");
        } catch (\Exception $e) {
            $this->warn("Could not cleanup temp table: {$e->getMessage()}");
        }
    }

    protected function verifyRecalculation()
    {
        $this->info('🔍 Verifying recalculation...');
        $this->newLine();

        $feesUploadCount = FeesUpload::count();
        $bankEntryCount = BankEntry::count();
        $pjPaymentCount = PjPayment::count();

        $feesVouchersCount = DB::table($this->originalTable)
            ->where('voucher_type', 'fees_uploads')
            ->distinct('voucher_id')
            ->count();

        $bankVouchersCount = DB::table($this->originalTable)
            ->where('voucher_type', 'bank_entries')
            ->distinct('voucher_id')
            ->count();

        $pjVouchersCount = DB::table($this->originalTable)
            ->where('voucher_type', 'pj_payments')
            ->distinct('voucher_id')
            ->count();

        $this->info("Verification Results:");
        $this->info("  Fees Uploads: {$feesUploadCount} records → {$feesVouchersCount} vouchers");
        $this->info("  Bank Entries: {$bankEntryCount} records → {$bankVouchersCount} vouchers");
        $this->info("  PJ Payments: {$pjPaymentCount} records → {$pjVouchersCount} vouchers");
        $this->newLine();

        if ($feesUploadCount == $feesVouchersCount && 
            $bankEntryCount == $bankVouchersCount && 
            $pjPaymentCount == $pjVouchersCount) {
            $this->info('✅ Verification passed: All source records have corresponding vouchers');
        } else {
            $this->warn('⚠️  Warning: Some discrepancies found. This may be expected if some records have zero amounts.');
        }
    }
}
