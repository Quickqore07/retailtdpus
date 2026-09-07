<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Onboarding\OnboardingList;
use App\Services\WorkbrightService;
use Illuminate\Support\Facades\Log;

class SyncWorkbrightStatus extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'workbright:sync-status';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync employee WorkBright status from API and update local database';

    protected WorkbrightService $workbrightService;

    /**
     * Create a new command instance.
     */
    public function __construct(WorkbrightService $workbrightService)
    {
        parent::__construct();
        $this->workbrightService = $workbrightService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Check if WorkBright integration is enabled
        if (!$this->workbrightService->isI9WithWorkBrightEnabled()) {
            $this->info('WorkBright integration is disabled. Skipping sync.');
            Log::info('WorkBright sync skipped - integration disabled');
            return 0;
        }

        $this->info('Starting WorkBright status sync...');
        Log::info('WorkBright status sync started');

        // Get all onboarding records that have a WorkBright employee ID
        $onboardingRecords = OnboardingList::whereNotNull('work_bright_employee_id')
            ->get();

        if ($onboardingRecords->isEmpty()) {
            $this->info('No onboarding records found with WorkBright employee IDs to sync.');
            Log::info('WorkBright sync completed - no records to process');
            return 0;
        }

        $this->info("Found {$onboardingRecords->count()} records to sync.");
        $successCount = 0;
        $failureCount = 0;

        foreach ($onboardingRecords as $onboarding) {
            try {
                $this->info("Syncing employee ID: {$onboarding->work_bright_employee_id} (Onboarding #{$onboarding->onboarding_number})");
                // Fetch employee data from WorkBright API
                $employeeData = $this->workbrightService->getEmployeeById($onboarding->work_bright_employee_id);

                if (!$employeeData) {
                    $this->error("Failed to fetch data for employee ID: {$onboarding->work_bright_employee_id}");
                    $failureCount++;
                    continue;
                }

                // Fetch document assignments to check submission statuses
                $submissions = $this->workbrightService->getAllSubmissions($onboarding->work_bright_employee_id);
                if (!is_array($submissions)) {
                    $this->error("Failed to fetch document assignments for employee ID: {$onboarding->work_bright_employee_id}");
                    $failureCount++;
                    continue;
                }

                $updated = false;

                // Process document assignments to check if I-9 and W-4 are accepted
                $i9Accepted = false;
                $w4Accepted = false;
                $i9Submitted = false;
                $w4Submitted = false;
                $pendingDocuments = [];

                foreach ($submissions as $submission) {
                    $documentId = $submission['document_id'] ?? null;
                    $status = $submission['status'] ?? null;

                    if(!$submission['is_current_submission_of_assignment']){
                        continue;
                    }
                    if ($documentId === 'i9') {
                        $i9Submitted = true;
                        if (($status === 'accepted' || $status === 'finalized')) {
                            $i9Accepted = true;
                            if (!$onboarding->i9_approved) {
                                $onboarding->i9_approved = true;
                                $onboarding->i9_completed = true;
                                $onboarding->i9_completed_datetime = $submission['submitted_at'];
                                $onboarding->i9_approved_datetime = $submission['status_changed_at'];
                                $this->line("  I-9 marked as accepted");
                            }
                            $onboarding->section_2_verification = $submission['countersigned'] ?? false;
                            $updated = true;
                        } else if($status === 'rejected') {
                            $onboarding->i9_rejected = true;
                            $onboarding->i9_rejected_datetime = $submission['status_changed_at'];
                            $updated = true;
                            $this->line("  I-9 marked as rejected");
                        } else {
                            $pendingDocuments['I-9'] = $submission['status_changed_at'];
                        }
                    } elseif ($documentId === 'w4') {
                        $w4Submitted = true;
                        if ($status === 'accepted' || $status === 'finalized') {
                            $w4Accepted = true;
                            if (!$onboarding->w4_approved) {
                                $onboarding->w4_approved = true;
                                $onboarding->w4_completed = true;
                                $onboarding->w4_completed_datetime = $submission['submitted_at'];
                                $onboarding->w4_approved_datetime = $submission['status_changed_at'];
                                $updated = true;
                                $this->line("  W-4 marked as accepted");
                            }
                        } else if($status === 'rejected') {
                            $onboarding->w4_rejected = true;
                            $onboarding->w4_rejected_datetime = $submission['status_changed_at'];
                            $updated = true;
                            $this->line("  W-4 marked as rejected");
                        } else {
                            $pendingDocuments['W-4'] = $submission['status_changed_at'];
                        }
                    }
                }

                // Update status based on I-9 and W-4 acceptance
                if ($i9Accepted && $w4Accepted) {
                  
                        if($onboarding->section_2_verification){
                            $onboarding->status = 'verified';
                        }else if($onboarding->section_2_verification){
                            $onboarding->status = 'section_2_verification_done';
                        }
                        $onboarding->workbright_status = 'forms_approved';
                        $onboarding->final_status = $onboarding->status == 'verified' ? 'approved' : 'pending';

                        $onboarding->employee->update(['onboarding_status' => $onboarding->status, 'employee_type' => $onboarding->status == 'verified' && $onboarding->employee->employeeRates->count() > 0 ? 'Completed' : 'New']);
                        $updated = true;
                        $this->line("  Status updated to '{$onboarding->status}' - Both I-9 and W-4 accepted");
                } else {
                    // If either I-9 or W-4 is not accepted, keep status as form_submitted
                    if (!empty($pendingDocuments)) {
                        $pendingList = implode(' and ', array_keys($pendingDocuments));
                        $this->warn("  {$pendingList} pending acceptance");

                        if(isset($pendingDocuments['I-9']) && !$onboarding->i9_completed) {
                            $onboarding->i9_completed = true;
                            $onboarding->i9_completed_datetime = $pendingDocuments['I-9'];
                            $this->line("  I-9 marked as completed at {$pendingDocuments['I-9']}");
                        }
                        if(isset($pendingDocuments['W-4']) && !$onboarding->w4_completed) {
                            $onboarding->w4_completed = true;
                            $onboarding->w4_completed_datetime = $pendingDocuments['W-4'];
                            $this->line("  W-4 marked as completed at {$pendingDocuments['W-4']}");
                        }
                    }
                    if(!$i9Submitted){
                        $onboarding->i9_completed = false;
                        $onboarding->i9_completed_datetime = null;
                        $onboarding->i9_approved = false;
                        $onboarding->i9_approved_datetime = null;
                        $onboarding->i9_rejected = false;
                        $onboarding->i9_rejected_datetime = null;
                    }
                    if(!$w4Submitted){
                        $onboarding->w4_completed = false;
                        $onboarding->w4_completed_datetime = null;
                        $onboarding->w4_approved = false;
                        $onboarding->w4_approved_datetime = null;
                        $onboarding->w4_rejected = false;
                        $onboarding->w4_rejected_datetime = null;
                    }

                    if ($onboarding->status !== 'form_submitted') {
                        $onboarding->status = 'form_submitted';
                        $onboarding->employee->update(['onboarding_status' => 'form_submitted']);
                        $updated = true;
                    }
                }

                if ($updated) {
                    $onboarding->save();
                    $successCount++;
                    $this->info("  ✓ Successfully updated onboarding #{$onboarding->onboarding_number}");
                } else {
                    $this->line("  No changes needed for onboarding #{$onboarding->onboarding_number}");
                }

            } catch (\Exception $e) {
                $this->error("Error processing employee ID: {$onboarding->work_bright_employee_id}");
                $this->error("Error: " . $e->getMessage());
                Log::error($e);
                Log::error("WorkBright sync error for onboarding #{$onboarding->onboarding_number}: " . $e->getMessage());
                $failureCount++;
            }
        }

        $this->newLine();
        $this->info("WorkBright status sync completed!");
        $this->info("Successfully updated: {$successCount}");
        if ($failureCount > 0) {
            $this->warn("Failed: {$failureCount}");
        }

        Log::info("WorkBright sync completed - Success: {$successCount}, Failed: {$failureCount}");

        return 0;
    }
}
