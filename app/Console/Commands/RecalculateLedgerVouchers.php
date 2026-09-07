<?php

namespace App\Console\Commands;

use App\Http\Controllers\Settings\LedgerController;
use App\Models\AR\FeesUpload;
use App\Models\AR\PjPayment;
use App\Models\DataEntry\BankEntry;
use App\Models\DataEntry\BankEntryChildAmount;
use App\Models\Settings\LedgerDetails;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class RecalculateLedgerVouchers extends Command
{
    protected $signature = 'ledger:recalculate
                          {--dry-run : Run without making actual changes}
                          {--verify : Verify the recalculation after completion}';

    protected $description = 'Recalculate all ledger vouchers from source tables (fees_uploads, bank_entries, pj_payments, bank_entry_child_amounts)';

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
            $feesUploadCount = $this->processFeesUploads($dryRun);
            $this->info("✅ Processed {$feesUploadCount} fees_uploads records");
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

            // Step 5: Populate from bank_entry_child_amounts (fees)
            $this->info('Step 5: Processing bank_entry_child_amounts (fees)...');
            $bankChildAmountFeesCount = $this->processBankEntryChildAmounts($dryRun);
            $this->info("✅ Processed {$bankChildAmountFeesCount} bank_entry_child_amounts records");
            $this->newLine();

            if (!$dryRun) {
                $tempCount = DB::table($this->tempTable)->count();
                $this->info("📊 New ledger_vouchers_temp count: {$tempCount}");
                $this->newLine();

                // Step 6: Drop original table and rename temp
                $this->info('Step 6: Replacing original table with recalculated data...');
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
                $this->info("  - Would process {$feesUploadCount} fees_uploads records");
                $this->info("  - Would process {$bankCount} bank_entries records");
                $this->info("  - Would process {$pjCount} pj_payments records");
                $this->info("  - Would process {$bankChildAmountFeesCount} bank_entry_child_amounts records");
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
        try {
            $count = 0;
            $ledgerVouchers = [];

            $dddCashLedger = LedgerDetails::where('code', '1001.49')->pluck('ledger_id', 'company_id')->toArray();
            $ezCaterLedger = LedgerDetails::where('code', '1001.53')->pluck('ledger_id', 'company_id')->toArray();
            $mealDealLedger = LedgerDetails::where('code', '1001.04')->pluck('ledger_id', 'company_id')->toArray();
            $visaLedger = LedgerDetails::where('code', '1001.01')->pluck('ledger_id', 'company_id')->toArray();
            $amexLedger = LedgerDetails::where('code', '1001.02')->pluck('ledger_id', 'company_id')->toArray();
            $doordashLedger = LedgerDetails::where('code', '1001.52')->pluck('ledger_id', 'company_id')->toArray();
            $uberLedger = LedgerDetails::where('code', '1001.50')->pluck('ledger_id', 'company_id')->toArray();
            $grubhubLedger = LedgerDetails::where('code', '1001.51')->pluck('ledger_id', 'company_id')->toArray();
            
            FeesUpload::orderBy('id')->chunk(500, function ($feesUploads) use (&$count, &$ledgerVouchers, $dryRun, $dddCashLedger, $ezCaterLedger, $mealDealLedger, $visaLedger, $amexLedger, $doordashLedger, $uberLedger, $grubhubLedger) {
                if (!$dryRun) {
                    DB::beginTransaction();
                }

                foreach ($feesUploads as $feesUpload) {
                    $companyId = $feesUpload->company_id;

                    if (isset($dddCashLedger[$companyId]) && $feesUpload->ddd_cash > 0) {
                        $ledgerVouchers[] = [
                            'company_id' => $companyId,
                            'ledger_id' => $dddCashLedger[$companyId],
                            'opp_ledger_id' => 0,
                            'amount' =>  floatval($feesUpload->ddd_cash),
                            'debit' => 0,
                            'credit' => floatval($feesUpload->ddd_cash),
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
                            'amount' =>  floatval($feesUpload->ez_cater),
                            'debit' => 0,
                            'credit' => floatval($feesUpload->ez_cater),
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
                            'amount' =>  floatval($feesUpload->meal_deal),
                            'debit' => 0,
                            'credit' => floatval($feesUpload->meal_deal),
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
                            'amount' =>  floatval($feesUpload->visa),
                            'debit' => 0,
                            'credit' => floatval($feesUpload->visa),
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
                            'amount' =>  floatval($feesUpload->amex),
                            'debit' => 0,
                            'credit' => floatval($feesUpload->amex),
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
                    
                    if (isset($doordashLedger[$companyId]) && $feesUpload->doordash > 0) {
                        $ledgerVouchers[] = [
                            'company_id' => $companyId,
                            'ledger_id' => $doordashLedger[$companyId],
                            'opp_ledger_id' => 0,
                            'amount' =>  floatval($feesUpload->doordash),
                            'debit' => 0,
                            'credit' => floatval($feesUpload->doordash),
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

                    if (isset($doordashLedger[$companyId]) && $feesUpload->ddc_doordash > 0) {
                        $ledgerVouchers[] = [
                            'company_id' => $companyId,
                            'ledger_id' => $doordashLedger[$companyId],
                            'opp_ledger_id' => 0,
                            'amount' =>  floatval($feesUpload->ddc_doordash),
                            'debit' => 0,
                            'credit' => floatval($feesUpload->ddc_doordash),
                            'voucher_id' => $feesUpload->id,
                            'voucher_items_id' => null,
                            'voucher_type' => 'ddc_doordash',
                            'dbtable' => 'fees_uploads',
                            'check_number' => null,
                            'description' => null,
                            'date' => $feesUpload->date,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ];
                    }

                    if (isset($uberLedger[$companyId]) && $feesUpload->uber > 0) {
                        $ledgerVouchers[] = [
                            'company_id' => $companyId,
                            'ledger_id' => $uberLedger[$companyId],
                            'opp_ledger_id' => 0,
                            'amount' =>  floatval($feesUpload->uber),
                            'debit' => 0,
                            'credit' => floatval($feesUpload->uber),
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

                    if (isset($grubhubLedger[$companyId]) && $feesUpload->grubhub > 0) {
                        $ledgerVouchers[] = [
                            'company_id' => $companyId,
                            'ledger_id' => $grubhubLedger[$companyId],
                            'opp_ledger_id' => 0,
                            'amount' =>  floatval($feesUpload->grubhub),
                            'debit' => 0,
                            'credit' => floatval($feesUpload->grubhub),
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
                }

                if (!$dryRun && !empty($ledgerVouchers)) {
                    DB::table($this->tempTable)->insert($ledgerVouchers);
                    $ledgerVouchers = [];
                }

                if (!$dryRun) {
                    DB::commit();
                }
            });
            
            unset($ledgerVouchers);

            return $count;

        } catch (\Exception $e) {
            throw $e;
        }
    }

    protected function processBankEntries($dryRun = false)
    {
        try {
            $count = 0;
            $ledgerVouchers = [];

            BankEntry::with('items:ledger_id,amount,id,bank_entry_id')
                ->select('id','company_id','total_amount','bank','date')
                ->orderBy('id')
                ->chunk(500, function ($bankEntries) use (&$count, &$ledgerVouchers, $dryRun) {
                    if (!$dryRun) {
                        DB::beginTransaction();
                    }

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
                                'voucher_items_id' => $item->id,
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
                    }

                    if (!$dryRun && !empty($ledgerVouchers)) {
                        DB::table($this->tempTable)->insert($ledgerVouchers);
                        $ledgerVouchers = [];
                    }

                    if (!$dryRun) {
                        DB::commit();
                    }
                });

            unset($ledgerVouchers);

            return $count;

        } catch (\Exception $e) {
            throw $e;
        }
    }

    protected function processPjPayments($dryRun = false)
    {
        try {
            $count = 0;
            $ledgerVouchers = [];

            PjPayment::select('id','company_id','date')
                ->with('items:id,ledger_id,amount,pj_payment_id')
                ->orderBy('id')
                ->chunk(500, function ($pjPayments) use (&$count, &$ledgerVouchers, $dryRun) {
                    if (!$dryRun) {
                        DB::beginTransaction();
                    }

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
                                'voucher_items_id' => $item->id,
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
                    }

                    if (!$dryRun && !empty($ledgerVouchers)) {
                        DB::table($this->tempTable)->insert($ledgerVouchers);
                        $ledgerVouchers = [];
                    }

                    if (!$dryRun) {
                        DB::commit();
                    }
                });

            unset($ledgerVouchers);
            return $count;
                
        } catch (\Exception $e) {
            throw $e;
        }
    }

    protected function processBankEntryChildAmounts($dryRun = false)
    {
        try {
            $count = 0;
            $ledgerVouchers = [];

            // Get ledger mappings for visa and cash
            $visaLedger = LedgerDetails::where('code', LedgerController::VISA_MASTER_LEDGER_CODE)
                ->pluck('ledger_id', 'company_id')
                ->toArray();
            $cashLedger = LedgerDetails::where('code', LedgerController::CASH_CLEARING_LEDGER_CODE)
                ->pluck('ledger_id', 'company_id')
                ->toArray();

            BankEntryChildAmount::with(['bankEntryItem.entry:id,company_id,date'])
                ->where('fees', '>', 0)
                ->orderBy('id')
                ->chunk(500, function ($childAmounts) use (&$count, &$ledgerVouchers, $dryRun, $visaLedger, $cashLedger) {
                    if (!$dryRun) {
                        DB::beginTransaction();
                    }

                    foreach ($childAmounts as $childAmount) {
                        if (!$childAmount->bankEntryItem || !$childAmount->bankEntryItem->entry) {
                            continue;
                        }

                        $bankEntry = $childAmount->bankEntryItem->entry;
                        $companyId = $bankEntry->company_id;
                        $date = $bankEntry->date;

                        // Determine voucher type based on source
                        $voucherType = ($childAmount->source === 'pj_payment_item') ? 'pj_payment_fees' : 'daily_sale_fees';
                        $description = ($childAmount->source === 'pj_payment_item') 
                            ? 'Visa fees for PJ Payment' 
                            : 'Cash fees for Daily Sale';
                        
                        // Use appropriate ledger based on voucher type
                        $ledgerMap = ($voucherType === 'pj_payment_fees') ? $visaLedger : $cashLedger;

                        if (isset($ledgerMap[$companyId])) {
                            $ledgerVouchers[] = [
                                'company_id' => $companyId,
                                'ledger_id' => $ledgerMap[$companyId],
                                'opp_ledger_id' => 0,
                                'amount' => floatval($childAmount->fees),
                                'debit' => 0,
                                'credit' => floatval($childAmount->fees),
                                'voucher_id' => $childAmount->id,
                                'voucher_items_id' => null,
                                'voucher_type' => $voucherType,
                                'dbtable' => 'bank_entry_child_amounts',
                                'check_number' => null,
                                'description' => $description,
                                'date' => $date,
                                'created_at' => now(),
                                'updated_at' => now(),
                            ];
                        }

                        $count++;
                    }

                    if (!$dryRun && !empty($ledgerVouchers)) {
                        DB::table($this->tempTable)->insert($ledgerVouchers);
                        $ledgerVouchers = [];
                    }

                    if (!$dryRun) {
                        DB::commit();
                    }
                });

            unset($ledgerVouchers);

            return $count;

        } catch (\Exception $e) {
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
        $bankChildAmountCount = BankEntryChildAmount::where('fees', '>', 0)->count();

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

        $pjFeesVouchersCount = DB::table($this->originalTable)
            ->where('voucher_type', 'pj_payment_fees')
            ->distinct('voucher_id')
            ->count();

        $dailySaleFeesVouchersCount = DB::table($this->originalTable)
            ->where('voucher_type', 'daily_sale_fees')
            ->distinct('voucher_id')
            ->count();

        $totalFeesVouchersCount = $pjFeesVouchersCount + $dailySaleFeesVouchersCount;

        $this->info("Verification Results:");
        $this->info("  Fees Uploads: {$feesUploadCount} records → {$feesVouchersCount} vouchers");
        $this->info("  Bank Entries: {$bankEntryCount} records → {$bankVouchersCount} vouchers");
        $this->info("  PJ Payments: {$pjPaymentCount} records → {$pjVouchersCount} vouchers");
        $this->info("  Bank Child Amounts (fees): {$bankChildAmountCount} records → {$totalFeesVouchersCount} vouchers");
        $this->info("    - PJ Payment Fees: {$pjFeesVouchersCount} vouchers");
        $this->info("    - Daily Sale Fees: {$dailySaleFeesVouchersCount} vouchers");
        $this->newLine();

        if ($feesUploadCount == $feesVouchersCount && 
            $bankEntryCount == $bankVouchersCount && 
            $pjPaymentCount == $pjVouchersCount &&
            $bankChildAmountCount == $totalFeesVouchersCount) {
            $this->info('✅ Verification passed: All source records have corresponding vouchers');
        } else {
            $this->warn('⚠️  Warning: Some discrepancies found. This may be expected if some records have zero amounts.');
        }
    }
}
