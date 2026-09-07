<?php

namespace App\Console\Commands;

use App\Services\Payroll\WeeklySummaryRecalculator;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class RecalculateWeeklySummary extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'payroll:recalculate-weekly-summary
                            {--start-date= : Start date for recalculation (Y-m-d format)}
                            {--end-date= : End date for recalculation (Y-m-d format)}
                            {--company-ids= : Specific company IDs to recalculate (comma separated)}
                            {--employee= : Specific employee ID to recalculate (uses default date range if no dates)}
                            {--clear : Clear existing summaries before recalculating}
                            {--workgroup= : Specific workgroup ID to recalculate}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Recalculate weekly summary data for existing employee hours';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting weekly summary recalculation...');

        try {
            $startDate = $this->option('start-date');
            $endDate = $this->option('end-date');
            $companyIds = $this->option('company-ids');
            $employeeId = $this->option('employee');
            $clearExisting = $this->option('clear');
            $workgroupId = $this->option('workgroup');
            if ($employeeId && !$startDate && !$endDate) {
                $startDate = Carbon::now()->subMonths(12)->format('Y-m-d');
                $endDate = Carbon::now()->format('Y-m-d');
                $this->info("Employee filter: {$employeeId}, using default date range: {$startDate} to {$endDate}");
            }

            $options = [
                'start_date' => $startDate,
                'end_date' => $endDate,
                'company_ids' => explode(',', $companyIds),
                'employee_id' => $employeeId,
                'clear' => true,
                'workgroup_id' => $workgroupId,
            ];
            
            $totalRecords = WeeklySummaryRecalculator::run($options);

            $this->info("✓ Successfully recalculated {$totalRecords} weekly summary records.");

            return 0;
        } catch (\Exception $e) {
            Log::error('Weekly summary recalculation failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            $this->error('Failed to recalculate weekly summary: ' . $e->getMessage());
            return 1;
        }
    }
}
