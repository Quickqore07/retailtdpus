<?php

namespace App\Console\Commands;

use App\Models\DataEntry\BankEntry;
use App\Models\DataEntry\BankEntryChildAmount;
use App\Models\DataEntry\BankEntryItem;
use App\Models\Settings\Company;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SettleBankAmounts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bank:settle-amounts
                            {--start_date= : Start date for settlement (YYYY-MM-DD)}
                            {--end_date= : End date for settlement (YYYY-MM-DD)}
                            {--company_id= : Specific company ID to settle amounts for (optional)}
                            {--dry-run : Show what would be settled without making changes}
                            {--all : Process all bank entries regardless of date}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create child amounts for all bank entry items that don\'t have them';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $startDate = $this->option('start_date');
        $endDate = $this->option('end_date');
        $companyId = $this->option('company_id');
        $dryRun = $this->option('dry-run');
        $all = $this->option('all');

        // Validate dates if not processing all
        if (!$all) {
            if (!$startDate || !$endDate) {
                $this->error('Please provide both --start_date and --end_date, or use --all flag.');
                return 1;
            }

            try {
                $start = Carbon::parse($startDate);
                $end = Carbon::parse($endDate);
            } catch (\Exception $e) {
                $this->error('Invalid date format. Please use YYYY-MM-DD format.');
                return 1;
            }

            if ($start->gt($end)) {
                $this->error('Start date must be before or equal to end date.');
                return 1;
            }

            $this->info("Date range: {$start->toDateString()} to {$end->toDateString()}");
        } else {
            $this->info("Processing all bank entries");
        }

        // Validate company if provided
        if ($companyId) {
            $company = Company::find($companyId);
            if (!$company) {
                $this->error("Company with ID {$companyId} not found.");
                return 1;
            }
            $this->info("Processing for company: {$company->name} (ID: {$companyId})");
        } else {
            $this->info("Processing for all companies");
        }

        // Get bank entry items without child amounts
        $query = BankEntryItem::query()
            ->join('bank_entries', 'bank_entry_items.bank_entry_id', '=', 'bank_entries.id')
            ->whereDoesntHave('bankEntryChildAmounts')
            ->where('bank_entry_items.amount', '>', 0);

        if (!$all) {
            $query->whereBetween('bank_entries.date', [$startDate, $endDate]);
        }

        if ($companyId) {
            $query->where('bank_entries.company_id', $companyId);
        }

        $bankEntryItems = $query->select('bank_entry_items.id', 'bank_entry_items.amount')
            ->with(['entry.company', 'ledger'])
            ->get();

        if ($bankEntryItems->isEmpty()) {
            $this->info('No bank entry items found without child amounts.');
            return 0;
        }

        $this->info("Found {$bankEntryItems->count()} bank entry items without child amounts.");

        if ($dryRun) {
            return $this->showDryRun($bankEntryItems);
        }

        // Confirm before proceeding
        if (!$this->confirm('Do you want to proceed with creating child amounts?')) {
            $this->info('Operation cancelled.');
            return 0;
        }

        // Process items
        return $this->processItems($bankEntryItems);
    }

    /**
     * Show dry run results
     *
     * @param \Illuminate\Database\Eloquent\Collection $bankEntryItems
     * @return int
     */
    protected function showDryRun($bankEntryItems)
    {
        $this->info('Dry run - showing what would be processed...');
        
        $totalAmount = 0;
        $itemDetails = [];

        foreach ($bankEntryItems as $item) {
            $totalAmount += $item->amount;
            
            $itemDetails[] = [
                $item->id,
                $item->entry->company->name ?? 'Unknown',
                $item->entry->date,
                $item->ledger->name ?? 'Unknown',
                '$' . number_format($item->amount, 2)
            ];
        }

        $this->table(
            ['Item ID', 'Company', 'Entry Date', 'Ledger', 'Amount'],
            $itemDetails
        );

        $this->newLine();
        $this->table(
            ['Summary', 'Value'],
            [
                ['Total Items to Process', count($itemDetails)],
                ['Total Amount', '$' . number_format($totalAmount, 2)]
            ]
        );

        return 0;
    }

    /**
     * Process bank entry items and create child amounts
     *
     * @param \Illuminate\Database\Eloquent\Collection $bankEntryItems
     * @return int
     */
    protected function processItems($bankEntryItems)
    {
        $this->info('Creating child amounts for bank entry items...');
        
        $progressBar = $this->output->createProgressBar($bankEntryItems->count());
        $progressBar->start();

        $totalProcessed = 0;
        $totalAmount = 0;
        $errors = [];

        DB::beginTransaction();
        try {
            $childAmounts = [];

            foreach ($bankEntryItems as $item) {
                try {
                    $childAmounts[] = [
                        'bank_entry_item_id' => $item->id,
                        'deposit' => $item->amount,
                        'fees' => 0,
                        'total_amount' => $item->amount,
                        'settled' => false,
                        'source' => null,
                        'source_id' => null,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];

                    $totalProcessed++;
                    $totalAmount += $item->amount;
                    
                } catch (\Exception $e) {
                    $errors[] = "Error processing item {$item->id}: " . $e->getMessage();
                }

                if(count($childAmounts) == 500) {
                    BankEntryChildAmount::insert($childAmounts);
                    $childAmounts = [];
                }

                $progressBar->advance();
            }

            // Batch insert child amounts
            if (!empty($childAmounts)) {
                foreach (array_chunk($childAmounts, 500) as $chunk) {
                    BankEntryChildAmount::insert($chunk);
                }
            }

            DB::commit();
            
            $progressBar->finish();
            $this->newLine(2);

            $this->info("Successfully created child amounts for {$totalProcessed} bank entry items.");
            
            $this->table(
                ['Metric', 'Value'],
                [
                    ['Items Processed', $totalProcessed],
                    ['Total Amount', '$' . number_format($totalAmount, 2)]
                ]
            );

            if (!empty($errors)) {
                $this->newLine();
                $this->warn('Errors encountered:');
                foreach ($errors as $error) {
                    $this->error($error);
                }
            }

            return 0;

        } catch (\Exception $e) {
            DB::rollBack();
            $progressBar->finish();
            $this->newLine(2);
            $this->error('Operation failed: ' . $e->getMessage());
            return 1;
        }
    }
}
