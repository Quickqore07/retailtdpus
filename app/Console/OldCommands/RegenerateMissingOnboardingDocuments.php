<?php

namespace App\Console\Commands;

use App\Http\Controllers\Onboarding\OnboardingController;
use App\Models\Onboarding\EmployeeDocument;
use App\Models\Onboarding\OnboardingList;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class RegenerateMissingOnboardingDocuments extends Command
{
    protected $signature = 'onboarding:regenerate-missing-documents
                            {--dry-run : List affected onboardings without creating documents}
                            {--onboarding-id= : Process a specific onboarding list ID}
                            {--employee-id= : Process onboardings for a specific employee ID}';

    protected $description = 'Regenerate onboarding PDFs when direct deposit exists but the Direct Deposit employee document is missing';

    public function handle(OnboardingController $onboardingController): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $onboardingId = $this->option('onboarding-id');
        $employeeId = $this->option('employee-id');

        $employeeIdsWithDirectDepositDocument = EmployeeDocument::query()
            ->where('document_name', 'like', '%Direct Deposit%')
            ->distinct()
            ->pluck('employee_id');

        $query = OnboardingList::query()
            ->whereNotNull('employee_id')
            ->whereHas('directDeposit')
            ->when($onboardingId, fn ($q) => $q->where('id', $onboardingId))
            ->when($employeeId, fn ($q) => $q->where('employee_id', $employeeId))
            ->where('process_id', 10)
            ->whereNotIn('employee_id', $employeeIdsWithDirectDepositDocument)
            ->orderBy('id');

        $onboardings = $query->get();

        if ($onboardings->isEmpty()) {
            $this->info('No onboardings found with direct deposit data and a missing Direct Deposit document.');

            return self::SUCCESS;
        }

        $this->info("Found {$onboardings->count()} onboarding(s) to process.");

        if ($dryRun) {
            $this->table(
                ['Onboarding ID', 'Onboarding #', 'Employee ID'],
                $onboardings->map(fn (OnboardingList $onboarding) => [
                    $onboarding->id,
                    $onboarding->onboarding_number ?? '-',
                    $onboarding->employee_id,
                ])
            );
            $this->comment('Dry run only. Re-run without --dry-run to create documents.');

            return self::SUCCESS;
        }

        $successCount = 0;
        $failureCount = 0;

        foreach ($onboardings as $onboarding) {
            $label = "Onboarding #{$onboarding->onboarding_number} (ID {$onboarding->id}, employee {$onboarding->employee_id})";

            try {
                $this->info("Regenerating documents for {$label}...");
                $onboardingController->regenerateOnboardingEmployeeDocuments($onboarding);
                $successCount++;
                Log::info('Regenerated missing onboarding documents', [
                    'onboarding_id' => $onboarding->id,
                    'employee_id' => $onboarding->employee_id,
                ]);
            } catch (\Throwable $e) {
                $failureCount++;
                $this->error("Failed for {$label}: {$e->getMessage()}");
                Log::error('Failed to regenerate missing onboarding documents', [
                    'onboarding_id' => $onboarding->id,
                    'employee_id' => $onboarding->employee_id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $this->newLine();
        $this->info("Done. Success: {$successCount}, Failed: {$failureCount}");

        return $failureCount > 0 ? self::FAILURE : self::SUCCESS;
    }
}
