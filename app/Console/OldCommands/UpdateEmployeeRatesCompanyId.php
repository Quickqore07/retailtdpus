<?php

namespace App\Console\Commands;

use App\Models\Employee;
use App\Models\Payroll\EmployeeRates;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class UpdateEmployeeRatesCompanyId extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'employee-rates:update-company-id
                            {--employee= : Specific employee ID to update}
                            {--dry-run : Preview changes without updating}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update company_id in employee_rates table from employee table';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting employee rates company_id update...');

        try {
            $employeeId = $this->option('employee');
            $dryRun = $this->option('dry-run');

            if ($dryRun) {
                $this->warn('DRY RUN MODE - No changes will be made');
            }

            // Build query
            $query = EmployeeRates::query()
                ->leftJoin('employee', 'employee_rates.employee_id', '=', 'employee.id')
                ->whereNotNull('employee.company_id')
                ->where(function ($q) {
                    $q->whereNull('employee_rates.company_id')
                      ->orWhereColumn('employee_rates.company_id', '!=', 'employee.company_id');
                });

            if ($employeeId) {
                $query->where('employee_rates.employee_id', $employeeId);
                $this->info("Filtering for employee ID: {$employeeId}");
            }

            $ratesToUpdate = $query->select(
                'employee_rates.id',
                'employee_rates.employee_id',
                'employee_rates.company_id as current_company_id',
                'employee.company_id as new_company_id'
            )->get();

            $totalRecords = $ratesToUpdate->count();

            if ($totalRecords === 0) {
                $this->info('No records need updating. All employee rates already have correct company_id.');
                return 0;
            }

            $this->info("Found {$totalRecords} employee rate records to update.");

            if ($dryRun) {
                $this->table(
                    ['Rate ID', 'Employee ID', 'Current Company ID', 'New Company ID'],
                    $ratesToUpdate->map(function ($rate) {
                        return [
                            $rate->id,
                            $rate->employee_id,
                            $rate->current_company_id ?? 'NULL',
                            $rate->new_company_id
                        ];
                    })->toArray()
                );
                $this->warn("DRY RUN: Would update {$totalRecords} records.");
                return 0;
            }

            // Confirm before proceeding
            if (!$this->confirm("Do you want to proceed with updating {$totalRecords} records?")) {
                $this->info('Operation cancelled.');
                return 0;
            }

            $bar = $this->output->createProgressBar($totalRecords);
            $bar->start();

            $updated = 0;

            DB::beginTransaction();

            foreach ($ratesToUpdate as $rate) {
                EmployeeRates::where('id', $rate->id)
                    ->update(['company_id' => $rate->new_company_id]);
                $updated++;
                $bar->advance();
            }

            DB::commit();

            $bar->finish();
            $this->newLine();

            $this->info("✓ Successfully updated {$updated} employee rate records with company_id.");

            return 0;
        } catch (\Exception $e) {
            if (DB::transactionLevel() > 0) {
                DB::rollBack();
            }

            Log::error('Employee rates company_id update failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            $this->error('Failed to update employee rates company_id: ' . $e->getMessage());
            return 1;
        }
    }
}
