<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class AddAllDataImport extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'add:all-data
                            {--pj-payment : Run only PJ Payment import}
                            {--daily-sales : Run only Daily Sales import}
                            {--ideal-cost : Run only Ideal Cost import}
                            {--purchase : Run only Purchase import}
                            {--workgroup= : Workgroup name to pass to imports that support it}
                            ';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run PJ Payment, Daily Sales, Ideal Cost, and Purchase data imports (or selected ones via options)';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $runPjPayment = $this->option('pj-payment');
        $runDailySales = $this->option('daily-sales');
        $runIdealCost = $this->option('ideal-cost');
        $runPurchase = $this->option('purchase');
        $workgroup = $this->option('workgroup');
        $runAll = !$runPjPayment && !$runDailySales && !$runIdealCost && !$runPurchase;

        $commands = [];

        if ($runAll || $runPjPayment) {
            $commands[] = ['add:pj-payment-data', 'PJ Payment', $workgroup];
        }
        if ($runAll || $runDailySales) {
            $commands[] = ['add:daily-sales-data', 'Daily Sales', $workgroup];
        }
        if ($runAll || $runIdealCost) {
            $commands[] = ['add:ideal-cost-data', 'Ideal Cost', $workgroup];
        }
        if ($runAll || $runPurchase) {
            $commands[] = ['add:purchase-data', 'Purchase', $workgroup];
        }

        $commandsAcceptingWorkgroup = ['add:pj-payment-data', 'add:daily-sales-data', 'add:ideal-cost-data', 'add:purchase-data'];

        foreach ($commands as [$signature, $label, $wg]) {
            $this->info("Running {$label} import...");
            $params = [];
            if ($workgroup !== null && $workgroup !== '' && in_array($signature, $commandsAcceptingWorkgroup, true)) {
                $params['--workgroup'] = $workgroup;
            }
            $exitCode = $this->call($signature, $params);
            if ($exitCode !== 0) {
                $this->error("{$label} import failed with exit code {$exitCode}");
                return $exitCode;
            }
        }

        $this->info('All selected data imports completed successfully.');
        return Command::SUCCESS;
    }
}
