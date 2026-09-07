<?php

namespace App\Console\Commands;

use App\Models\Settings\Company;
use App\Services\PjPaymentSettlementService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class SettlePjPayments extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'pj:settle-payments
                            {start_date : Start date for settlement (YYYY-MM-DD)}
                            {end_date : End date for settlement (YYYY-MM-DD)}
                            {--company_id= : Specific company ID to settle payments for (optional)}
                            {--dry-run : Show what would be settled without making changes}
                            {--summary : Show settlement summary instead of processing}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Settle PJ payments for a specified date range and company';

    /**
     * PJ Payment Settlement Service
     *
     * @var PjPaymentSettlementService
     */
    protected $settlementService;

    /**
     * Create a new command instance.
     */
    public function __construct(PjPaymentSettlementService $settlementService)
    {
        parent::__construct();
        $this->settlementService = $settlementService;
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $startDate = $this->argument('start_date');
        $endDate = $this->argument('end_date');
        $companyId = $this->option('company_id');
        $dryRun = $this->option('dry-run');
        $summary = $this->option('summary');

        // Validate dates
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

        $this->info("Date range: {$start->toDateString()} to {$end->toDateString()}");
        
        if ($summary) {
            return $this->showSummary($startDate, $endDate, $companyId);
        }

        if ($dryRun) {
            return $this->showDryRun($startDate, $endDate, $companyId);
        }

        // Confirm before proceeding
        // if (!$this->confirm('Do you want to proceed with the settlement?')) {
        //     $this->info('Settlement cancelled.');
        //     return 0;
        // }

        // Process settlement
        $this->info('Starting PJ payment settlement...');
        $progressBar = $this->output->createProgressBar();
        $progressBar->start();

        $result = $this->settlementService->settlePjPayments($startDate, $endDate, $companyId);
        $resultSettleDailySales = $this->settlementService->settleDailySales($startDate, $endDate, $companyId);

        $progressBar->finish();
        $this->newLine(2);

        $results = [$result, $resultSettleDailySales];

        foreach ($results as $result) {
            if ($result['success']) {
                $this->info($result['message']);
                
                $data = $result['data'];
                $this->table(
                    ['Metric', 'Value'],
                    [
                        ['Items Settled', $data['items_settled']],
                        ['Total Amount Settled', '$' . number_format( isset($data['total_amount_settled']) ? $data['total_amount_settled'] : 0, 2)]
                    ]
                );

                if (!empty($data['settlement_details'])) {
                    $this->newLine();
                    $this->info('Settlement Details:');
                    $this->table(
                        ['Payment ID/Daily Sale ID', 'Company', 'Bank Date', 'Paid Date', 'Amount'],
                        array_map(function ($detail) {
                            return [
                                isset($detail['pj_payment_id']) ? $detail['pj_payment_id'] : $detail['daily_sale_id'],
                                $detail['company'],
                                $detail['bank_date'],
                                isset($detail['paid_date']) ? $detail['paid_date'] : '-',
                                '$' . number_format(isset($detail['amount']) ? $detail['amount'] : 0, 2)
                            ];
                        }, $data['settlement_details'])
                    );
                }

            } else {
                $this->error($result['message']);
            }
        }
    }

    /**
     * Show settlement summary
     *
     * @param string $startDate
     * @param string $endDate
     * @param int|null $companyId
     * @return int
     */
    protected function showSummary($startDate, $endDate, $companyId)
    {
        $this->info('Generating settlement summary...');
        
        $summary = $this->settlementService->getSettlementSummary($startDate, $endDate, $companyId);
        
        $this->newLine();
        $this->info('Settlement Summary:');
        
        $this->table(
            ['Metric', 'Total', 'Settled', 'Unsettled'],
            [
                [
                    'Payments',
                    $summary['total_payments'],
                    '-',
                    '-'
                ],
                [
                    'Items',
                    $summary['total_items'],
                    $summary['settled_items'],
                    $summary['unsettled_items']
                ],
                [
                    'Amount',
                    '$' . number_format($summary['total_amount'], 2),
                    '$' . number_format($summary['settled_amount'], 2),
                    '$' . number_format($summary['unsettled_amount'], 2)
                ]
            ]
        );

        if (!empty($summary['by_company'])) {
            $this->newLine();
            $this->info('By Company:');
            $this->table(
                ['Company', 'Total Items', 'Settled Items', 'Unsettled Items', 'Total Amount', 'Settled Amount', 'Unsettled Amount'],
                array_map(function ($company) {
                    return [
                        $company['company_name'],
                        $company['total_items'],
                        $company['settled_items'],
                        $company['unsettled_items'],
                        '$' . number_format($company['total_amount'], 2),
                        '$' . number_format($company['settled_amount'], 2),
                        '$' . number_format($company['unsettled_amount'], 2)
                    ];
                }, $summary['by_company'])
            );
        }

        return 0;
    }

    /**
     * Show dry run results
     *
     * @param string $startDate
     * @param string $endDate
     * @param int|null $companyId
     * @return int
     */
    protected function showDryRun($startDate, $endDate, $companyId)
    {
        $this->info('Dry run - showing what would be settled...');
        
        $unsettledPayments = $this->settlementService->getUnsettledPayments($startDate, $endDate, $companyId);
        
        if ($unsettledPayments->isEmpty()) {
            $this->info('No unsettled payments found for the specified criteria.');
            return 0;
        }

        $totalItems = 0;
        $totalAmount = 0;
        $paymentDetails = [];

        foreach ($unsettledPayments as $payment) {
            $unsettledItems = $payment->items->where('settled', false)->where('amount', '>', 0);
            $itemsCount = $unsettledItems->count();
            $itemsAmount = $unsettledItems->sum('amount');
            
            if ($itemsCount > 0) {
                $totalItems += $itemsCount;
                $totalAmount += $itemsAmount;
                
                $paymentDetails[] = [
                    $payment->id,
                    $payment->company->name ?? 'Unknown',
                    $payment->date,
                    $itemsCount,
                    '$' . number_format($itemsAmount, 2)
                ];
            }
        }

        $this->table(
            ['Payment ID', 'Company', 'Date', 'Unsettled Items', 'Amount to Settle'],
            $paymentDetails
        );

        $this->newLine();
        $this->table(
            ['Summary', 'Value'],
            [
                ['Total Payments to Process', count($paymentDetails)],
                ['Total Items to Settle', $totalItems],
                ['Total Amount to Settle', '$' . number_format($totalAmount, 2)]
            ]
        );

        return 0;
    }
}