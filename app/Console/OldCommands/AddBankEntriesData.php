<?php

namespace App\Console\Commands;

use App\Models\DataEntry\BankEntry;
use App\Models\DataEntry\BankEntryItem;
use App\Models\LedgerVouchers;
use App\Models\Settings\Company;
use App\Models\Settings\LedgerDetails;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class AddBankEntriesData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'add:bank-entries-data';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Add Bank Entries Data';
    /**
     * Execute the console command.
     */

    private $ledgerMap = [
        'AMEX Card'=> 3,
        "Cash"=> 4,                // cash clearing
        "Cash Deposit"=> 4,        // cash clearing  
        "Credit Card Deposit"=> 6, // visa master
        "DD Capital Deposit"=> 0,  
        "DDD Account"=> 13,       // account
        "DDD Cash"=> 5,             // Doordash Cash
        "Dept  Education Student LN"=> 0,
        "Door Dash Credit Cards Tips"=> 6,
        "Chinmay/Personal"=>0,
        "Grubhub" => 9,
        'DoorDash' => 7,
        "Uber"=>12
    ];

    public function handle()
    {
        $chunkSize = 500;
    
        // Pre-load lookups once
        $mntBank = LedgerDetails::select('ledger_id', 'company_id', 'code')
            ->where('code', '1000.02')
            ->get()
            ->keyBy('company_id');
    
        $companies = Company::select('id', 'store_number')
            ->where('workgroup_id', 2)
            ->get()
            ->pluck('id', 'store_number')
            ->toArray();
    
        // Backup tables once, outside transaction
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        DB::statement('DROP TABLE IF EXISTS bank_entries_backup');
        DB::statement('DROP TABLE IF EXISTS bank_entry_items_backup');
        DB::statement('CREATE TABLE IF NOT EXISTS bank_entries_backup LIKE bank_entries');
        DB::statement('INSERT INTO bank_entries_backup SELECT * FROM bank_entries');

        DB::statement('CREATE TABLE IF NOT EXISTS bank_entry_items_backup LIKE bank_entry_items');
        DB::statement('INSERT INTO bank_entry_items_backup SELECT * FROM bank_entry_items');
        DB::statement('SET FOREIGN_KEY_CHECKS=1');
        // Delete old entries once, outside loop

        
        $bankEntryIds = BankEntry::whereHas('company', function($query) {
            $query->where('workgroup_id', 2);
        })->pluck('id')->toArray();
        LedgerVouchers::whereIn('voucher_id', $bankEntryIds)->where('dbtable', 'bank_entries')->delete();
        BankEntryItem::whereIn('bank_entry_id', $bankEntryIds)->delete();
        BankEntry::whereIn('id', $bankEntryIds)->delete();
        
        $maxId = BankEntry::max('id');
        DB::statement("ALTER TABLE bank_entries AUTO_INCREMENT = " . $maxId + 1);

        
        $maxId = LedgerVouchers::max('id');
        DB::statement("ALTER TABLE ledger_vouchers AUTO_INCREMENT = " . $maxId + 1);

        $maxId = BankEntryItem::max('id');
        DB::statement("ALTER TABLE bank_entry_items AUTO_INCREMENT = " . $maxId + 1);

        $this->info('Bank Entries data import started...');
        
        $errors = [];
    
        $now = now();
        DB::table('daily_bank_entry')
            ->orderBy('id')
            ->chunk($chunkSize, function ($bankEntries) use ($mntBank, $companies, &$errors, $now) {
    
                $bankEntryInserts = [];
    
                // Build batch insert data
                foreach ($bankEntries as $key => $bankEntry) {
                    $companyId     = $companies[$bankEntry->company_code] ?? null;
                    $mntBankDetails = $mntBank[$companyId] ?? null;
    
                    if (!$companyId) {
                        $errors[] = "Bank Entry {$key} company not found: {$bankEntry->company_code}";
                        continue;
                    }
                    if (!$mntBankDetails) {
                        $errors[] = "Bank Entry {$key} TD bank not found: {$bankEntry->company_code}";
                        continue;
                    }
    
                    $bankEntryInserts[] = [
                        'company_id'   => $companyId,
                        'date'         => $bankEntry->entry_date,
                        'bank'         => $mntBankDetails->ledger_id,
                        'total_amount' => $bankEntry->total_deposit,
                        'is_imported'  => true,
                        'created_by'   => 1,
                        'updated_by'   => 1,
                        'created_at'   => $now,
                        'updated_at'   => $now,
                    ];
                }
    
                if (empty($bankEntryInserts)) {
                    return;
                }
    
                DB::beginTransaction();
                try {
                    // Bulk insert all bank entries in one query
                    DB::table('bank_entries')->insert($bankEntryInserts);
    
                    // Get the inserted IDs (by matching company_id + date in this chunk)
                    $insertedEntries = DB::table('bank_entries')
                        ->where('is_imported', true)
                        ->where('created_at', $now)
                        ->select('id', 'company_id', 'date', 'total_amount')
                        ->get()
                        ->keyBy(fn($e) => $e->company_id . '_' . $e->date . '_' . $e->total_amount);
    
                    // Build bank entry items batch
                    $itemsToInsert = [];
    
                    foreach ($bankEntries as $bankEntry) {
                        $companyId = $companies[$bankEntry->company_code] ?? null;
                        if (!$companyId) continue;
    
                        $entryKey     = $companyId . '_' . $bankEntry->entry_date . '_' . $bankEntry->total_deposit;
                        $insertedEntry = $insertedEntries[$entryKey] ?? null;
                        if (!$insertedEntry) continue;
    
                        $deposits = json_decode($bankEntry->beginning_balance, true) ?? [];
    
                        foreach ($deposits as $deposit) {
                            $ledgerId = $this->ledgerMap[$deposit['label']] ?? 0;
                            if ($ledgerId <= 0 || $deposit['value'] == 0) continue;
                            if(isset($itemsToInsert[ $entryKey . '_' . $ledgerId])){
                                $itemsToInsert[ $entryKey . '_' . $ledgerId]['amount']+=(float)$deposit['value'] ?? 0;
                            }else{   
                                $itemsToInsert[ $entryKey . '_' . $ledgerId] = [
                                    'bank_entry_id' => $insertedEntry->id,
                                    'ledger_id'     => $ledgerId,
                                    'amount'        => (float)$deposit['value'] ?? 0,
                                    'created_at'    => $now,
                                    'updated_at'    => $now,
                                ];
                            }
                        }
                    }
    
                    // Bulk insert all items in one query per chunk
                    if (!empty($itemsToInsert)) {
                        foreach (array_chunk($itemsToInsert, 1000) as $batch) {
                            BankEntryItem::insert($batch);
                        }
                    }

                    
                    DB::commit();


                    
                    $this->info('Chunk processed: ' . count($bankEntryInserts) . ' entries');
    
                } catch (\Throwable $th) {
                    DB::rollBack();
                    $this->error('Chunk failed: ' . $th->getMessage());
                    info($th);
                }
            });
            $bankEntries = BankEntryItem::where('bank_entry_items.id' ,'>', $maxId)
                    
            ->join('bank_entries', 'bank_entry_items.bank_entry_id', '=', 'bank_entries.id')
            ->select('bank_entry_items.id as item_id', 'bank_entry_items.bank_entry_id as entry_id', 'bank_entries.company_id as company_id', 'bank_entry_items.ledger_id as ledger_id', 'bank_entry_items.amount as amount', 'bank_entries.date as date')
            ->groupBy('bank_entry_items.id', 'bank_entry_items.bank_entry_id', 'bank_entries.company_id', 'bank_entry_items.ledger_id', 'bank_entry_items.amount', 'bank_entries.date')
            ->get();

            $ledgerVouchers = [];
            foreach($bankEntries as $bankEntry){
                $ledgerVouchers[] = [
                    'company_id' => $bankEntry->company_id,
                    'ledger_id' => $bankEntry->ledger_id,
                    'opp_ledger_id' => 0,
                    'amount' => $bankEntry->amount,
                    'debit' => 0,
                    'credit' => $bankEntry->amount,  
                    'voucher_id' => $bankEntry->entry_id,   
                    'voucher_items_id' => $bankEntry->item_id,
                    'voucher_type' => 'bank_entries',
                    'dbtable' => 'bank_entries',
                    'check_number' => null,
                    'description' => null,
                    'date' => $bankEntry->date,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
            foreach(array_chunk($ledgerVouchers, 1000) as $batch) {
                LedgerVouchers::insert($batch);
            }
        // Print all collected errors at the end
        foreach ($errors as $error) {
            $this->error($error);
        }
    
        $this->info('Bank Entries import complete.');
    }
}
