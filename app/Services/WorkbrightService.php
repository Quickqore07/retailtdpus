<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\Onboarding\OnboardingList;
use App\Models\Onboarding\OnboardingLogs;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WorkbrightService
{
    public function isI9WithWorkBrightEnabled(): bool
    {
        return filter_var(env('I9_WITH_WORK_BRIGHT', false), FILTER_VALIDATE_BOOLEAN);
    }

    public function workBrightBaseUrl(): string
    {
        return rtrim((string) env('WORK_BRIGHT_BASE_URL', 'https://api.workbright.com/api'), '/');
    }

    public function workBrightApiKey(): string
    {
        return trim((string) env('WORK_BRIGHT_API_KEY', ''));
    }


    public function workBrightPacketId(): ?int
    {
        $packetId = env('WORK_BRIGHT_PACKET_ID');
        if ($packetId === null || $packetId === '') {
            return null;
        }

        return (int) $packetId;
    }

    public function workBrightHttpClient()
    {
        return Http::withHeaders([
            'API-Key' => $this->workBrightApiKey(),
        ])->acceptJson();
    }

    public function workBrightGroups()
    {
        $response = $this->workBrightHttpClient()
            ->asJson()
            ->get($this->workBrightBaseUrl() . '/api/employee_groups');
        /** @var \Illuminate\Http\Client\Response $response */
        if (!$response->successful()) {
            throw new \RuntimeException('WorkBright employee groups get failed: ' . $response->status() . ' ' . $response->body());
        }

        return $response->json();
    }

    public function getEmployeeById(int $employeeId): ?array
    {
        try {
            $response = $this->workBrightHttpClient()
                ->get($this->workBrightBaseUrl() . '/api/employees/' . $employeeId);
            /** @var \Illuminate\Http\Client\Response $response */
            if (!$response->successful()) {
                Log::error("WorkBright API error for employee {$employeeId}: " . $response->status() . ' ' . $response->body());
                return null;
            }

            return $response->json();
        } catch (\Exception $e) {
            Log::error("WorkBright API exception for employee {$employeeId}: " . $e->getMessage());
            return null;
        }
    }

    public function getEmployeeDocumentAssignments(int $employeeId): ?array
    {
        try {
            $response = $this->workBrightHttpClient()
                ->get($this->workBrightBaseUrl() . '/api/employees/' . $employeeId . '/document_assignments');
            /** @var \Illuminate\Http\Client\Response $response */
            if (!$response->successful()) {
                Log::error("WorkBright API error fetching document assignments for employee {$employeeId}: " . $response->status() . ' ' . $response->body());
                return null;
            }

            return $response->json();
        } catch (\Exception $e) {
            Log::error("WorkBright API exception fetching document assignments for employee {$employeeId}: " . $e->getMessage());
            return null;
        }
    }

    public function workbrightWebhook(Request $request)
    {
        $payload = json_decode($request->getContent(), true);
        Log::info('Workbright webhook payload: ' . json_encode($payload));
        $event = $payload['event_key'] ?? null;
        $occurredAt = $payload['occurred_at'] ?? now()->toIso8601String();
        $data = $payload['data'] ?? [];

        if (!$event) {
            return response()->json(['ok' => false, 'message' => 'Invalid webhook payload']);
        }

        $onboardingList = null;
        $employeeId = null;
        $log = [];

        // Find onboarding record based on WorkBright employee ID
        if (isset($data['employee']['id'])) {
            $employeeId = $data['employee']['id'];
            $onboardingList = OnboardingList::where('work_bright_employee_id', $employeeId)->first();
        } elseif (isset($data['assignments'][0]['employee']['id'])) {
            $employeeId = $data['assignments'][0]['employee']['id'];
            $onboardingList = OnboardingList::where('work_bright_employee_id', $employeeId)->first();
        }

        if (!$onboardingList) {
            info("Onboarding list not found for WorkBright employee ID: {$employeeId}");
            return response()->json(['ok' => false, 'message' => 'Onboarding list not found']);
        }

        // Process webhook events
        switch ($event) {
            case 'employee.new_form_submission_requested':
                $this->handleNewFormSubmissionRequested($onboardingList, $data, $occurredAt, $log);
                break;

            case 'employee.form_submitted':
                $this->handleFormSubmitted($onboardingList, $data, $occurredAt, $log);
                break;

            case 'employee.submission_accepted':
                $this->handleSubmissionAccepted($onboardingList, $data, $occurredAt, $log);
                break;

            case 'employee.submission_rejected':
                $this->handleSubmissionRejected($onboardingList, $data, $occurredAt, $log);
                break;

            case 'employee.all_forms_received':
                $this->handleAllFormsReceived($onboardingList, $data, $occurredAt, $log);
                break;

            case 'employee.all_required_submissions_finalized':
                $this->handleAllRequiredSubmissionsFinalized($onboardingList, $data, $occurredAt, $log);
                break;

            case 'employee.all_submissions_finalized':
                $this->handleAllSubmissionsFinalized($onboardingList, $data, $occurredAt, $log);
                break;

            case 'employee.everify_case_updated':
            case 'everify.updated':
                $this->handleEverifyUpdated($onboardingList, $data, $occurredAt, $log);
                break;

            case 'employee.submission_countersigned':
                $this->handleSubmissionCountersigned($onboardingList, $data, $occurredAt, $log);
                break;

            case 'employee.profile_updated':
                $this->handleProfileUpdated($onboardingList, $data, $occurredAt, $log);
                break;

            case 'employee.submission_expiring_documents':
                $this->handleExpiringDocuments($onboardingList, $data, $occurredAt, $log);
                break;

            case 'employee.i9_restarted':
                $this->handleI9Restarted($onboardingList, $data, $occurredAt, $log);
                break;

            default:
                info("Unhandled webhook event: {$event}");
                $log = [
                    'event' => $event,
                    'date' => $occurredAt,
                    'data' => $data,
                    'description' => "Webhook event received: {$event}",
                ];
        }

        // Save changes and log
        if ($onboardingList) {
            $onboardingList->save();
            if (!empty($log)) {
                $log['data'] = is_string($log['data']) ? $log['data'] : json_encode($log['data']);
                OnboardingLogs::create(array_merge(
                    ['onboarding_list_id' => $onboardingList->id],
                    $log
                ));
            }
        }

        return response()->json(['ok' => true]);
    }

    private function handleNewFormSubmissionRequested($onboardingList, $data, $occurredAt, &$log)
    {
        $assignments = $data['assignments'] ?? [];
        $assignmentCount = count($assignments);

        $log = [
            'date' => $occurredAt,
            'data' => $data,
        ];

        if ($assignmentCount == 2) {
            $onboardingList->workbright_status = 'i9_and_w4_submission_requested';
            $onboardingList->status = 'i9_and_w4_submission_requested';
            Employee::where('id', $onboardingList->employee_id)->update(['onboarding_status' => 'i9_and_w4_submission_requested']);
            $log['event'] = 'i9_and_w4_submission_requested';
            $log['description'] = 'I-9 and W-4 submission requested';
        } elseif ($assignmentCount == 1) {
            $isI9 = $assignments[0]['document_id'] == 'i9';
            if ($isI9) {
                $newStatus = $onboardingList->workbright_status === 'w4_submission_requested' ? 'i9_and_w4_submission_requested' : 'i9_submission_requested';
                $onboardingList->workbright_status = $newStatus;
                $onboardingList->status = $newStatus;
                Employee::where('id', $onboardingList->employee_id)->update(['onboarding_status' => $newStatus]);
                $log['event'] = 'i9_submission_requested';
                $log['description'] = 'I-9 submission requested';
            } else {
                $newStatus = $onboardingList->workbright_status === 'i9_submission_requested' ? 'i9_and_w4_submission_requested' : 'w4_submission_requested';
                $onboardingList->workbright_status = $newStatus;
                $onboardingList->status = $newStatus;
                Employee::where('id', $onboardingList->employee_id)->update(['onboarding_status' => $newStatus]);
                $log['event'] = 'w4_submission_requested';
                $log['description'] = 'W-4 submission requested';
            }
        }

        $isI9 = array_filter($assignments, function ($assignment) {
            return $assignment['document_id'] == 'i9';
        });
        $isW4 = array_filter($assignments, function ($assignment) {
            return $assignment['document_id'] == 'w4';
        });
        if (count($isI9) > 0) {
            $onboardingList->i9_completed = false;
            $onboardingList->i9_completed_datetime = null;
            $onboardingList->i9_approved = false;
            $onboardingList->i9_approved_datetime = null;
            $onboardingList->i9_rejected = false;
            $onboardingList->i9_rejected_reason = null;
            $onboardingList->i9_rejected_datetime = null;
        }
        if (count($isW4) > 0) {
            $onboardingList->w4_completed = false;
            $onboardingList->w4_completed_datetime = null;
            $onboardingList->w4_approved = false;
            $onboardingList->w4_approved_datetime = null;
            $onboardingList->w4_rejected = false;
            $onboardingList->w4_rejected_reason = null;
            $onboardingList->w4_rejected_datetime = null;
        }
    }

    private function handleFormSubmitted($onboardingList, $data, $occurredAt, &$log)
    {
        $submission = $data['submission'] ?? null;
        if (!$submission) {
            return;
        }

        $log = [
            'date' => $occurredAt,
            'data' => $data,
        ];

        $documentId = $submission['document_id'];
        if ($documentId == 'i9') {
            $onboardingList->i9_completed = true;
            $onboardingList->i9_completed_datetime = $occurredAt;
            $onboardingList->workbright_status = 'i9_submitted';
            $log['event'] = 'i9_submitted';
            $log['description'] = 'I-9 form submitted by employee';
        } elseif ($documentId == 'w4') {
            $onboardingList->w4_completed = true;
            $onboardingList->w4_completed_datetime = $occurredAt;
            $onboardingList->workbright_status = 'w4_submitted';
            $log['event'] = 'w4_submitted';
            $log['description'] = 'W-4 form submitted by employee';
        }
        $onboardingList->final_status = 'pending';

        if (isset($log['event']) && in_array($log['event'], ['i9_submitted', 'w4_submitted'], true)) {
            $isResubmit = OnboardingLogs::query()
                ->where('onboarding_list_id', $onboardingList->id)
                ->where('event', $log['event'])
                ->exists();
            $onboardingList->loadMissing('employee');
            I9W4SubmittedNotificationService::notifyWorkbright($onboardingList, $documentId, $isResubmit);
        }
    }

    private function handleSubmissionAccepted($onboardingList, $data, $occurredAt, &$log)
    {
        $submission = $data['submission'] ?? null;
        if (!$submission) {
            return;
        }

        $log = [
            'date' => $occurredAt,
            'data' => $data,
        ];

        $documentId = $submission['document_id'];
        if ($documentId == 'i9') {
            $onboardingList->i9_approved = true;
            $onboardingList->i9_approved_datetime = $occurredAt;
            $onboardingList->i9_rejected = false;
            $onboardingList->i9_rejected_reason = null;
            $onboardingList->i9_rejected_datetime = null;
            $onboardingList->workbright_status = 'i9_approved';
            $log['event'] = 'i9_approved';
            $log['description'] = 'I-9 submission accepted by administrator';
        } elseif ($documentId == 'w4') {
            $onboardingList->w4_approved = true;
            $onboardingList->w4_approved_datetime = $occurredAt;
            $onboardingList->w4_rejected = false;
            $onboardingList->w4_rejected_reason = null;
            $onboardingList->w4_rejected_datetime = null;
            $onboardingList->workbright_status = 'w4_approved';
            $log['event'] = 'w4_approved';
            $log['description'] = 'W-4 submission accepted by administrator';
        }
        $onboardingList->final_status = 'pending';

        // Update status if both forms are approved
        if ($onboardingList->i9_approved && $onboardingList->w4_approved && $onboardingList->section_2_verification) {
            $onboardingList->status = 'section_2_verification_done';
            Employee::where('id', $onboardingList->employee_id)->update(['onboarding_status' => 'section_2_verification_done']);
        } elseif ($onboardingList->i9_approved && $onboardingList->w4_approved && !$onboardingList->section_2_verification) {
            $onboardingList->status = 'waiting_for_section_2_verification';
            Employee::where('id', $onboardingList->employee_id)->update(['onboarding_status' => 'waiting_for_section_2_verification']);
        }
    }

    private function handleSubmissionRejected($onboardingList, $data, $occurredAt, &$log)
    {
        $submission = $data['submission'] ?? null;
        if (!$submission) {
            return;
        }

        $log = [
            'date' => $occurredAt,
            'data' => $data,
        ];

        $documentId = $submission['document_id'];
        $rejectionReason = $submission['rejection_reason'] ?? 'No reason provided';

        if ($documentId == 'i9') {
            $onboardingList->i9_rejected = true;
            $onboardingList->i9_rejected_reason = $rejectionReason;
            $onboardingList->i9_rejected_datetime = $occurredAt;
            $onboardingList->i9_approved = false;
            $onboardingList->i9_approved_datetime = null;
            $onboardingList->workbright_status = 'i9_rejected';
            $log['event'] = 'i9_rejected';
            $log['description'] = "I-9 submission rejected: {$rejectionReason}";
        } elseif ($documentId == 'w4') {
            $onboardingList->w4_rejected = true;
            $onboardingList->w4_rejected_reason = $rejectionReason;
            $onboardingList->w4_rejected_datetime = $occurredAt;
            $onboardingList->w4_approved = false;
            $onboardingList->w4_approved_datetime = null;
            $onboardingList->workbright_status = 'w4_rejected';
            $log['event'] = 'w4_rejected';
            $log['description'] = "W-4 submission rejected: {$rejectionReason}";
        }

        $onboardingList->status = 'form_submitted';
        Employee::where('id', $onboardingList->employee_id)->update(['onboarding_status' => 'form_submitted']);
    }

    private function handleAllFormsReceived($onboardingList, $data, $occurredAt, &$log)
    {
        $log = [
            'event' => 'all_forms_received',
            'date' => $occurredAt,
            'data' => $data,
            'description' => 'All required forms received from employee',
        ];

        $onboardingList->workbright_status = 'all_forms_received';
    }

    private function handleAllRequiredSubmissionsFinalized($onboardingList, $data, $occurredAt, &$log)
    {
        $log = [
            'event' => 'all_required_submissions_finalized',
            'date' => $occurredAt,
            'data' => $data,
            'description' => 'All required submissions finalized (accepted and countersigned)',
        ];

        $onboardingList->workbright_status = 'all_required_submissions_finalized';
        $onboardingList->final_status = 'pending';
        $onboardingList->tnc = false;
        // Check if employee is fully verified (WorkBright verified + documents approved)
        if ($onboardingList->section_2_verification) {
            $onboardingList->status = 'section_2_verification_done';
            Employee::where('id', $onboardingList->employee_id)->update(['onboarding_status' => 'section_2_verification_done']);
        } else {
            $onboardingList->status = 'waiting_for_section_2_verification';
            Employee::where('id', $onboardingList->employee_id)->update(['onboarding_status' => 'waiting_for_section_2_verification']);
        }
    }

    private function handleAllSubmissionsFinalized($onboardingList, $data, $occurredAt, &$log)
    {
        $log = [
            'event' => 'all_submissions_finalized',
            'date' => $occurredAt,
            'data' => $data,
            'description' => 'All submissions finalized (including optional forms)',
        ];

        $onboardingList->workbright_status = 'all_submissions_finalized';
    }

    private function handleEverifyUpdated($onboardingList, $data, $occurredAt, &$log)
    {
        $everifyStatus = $data['case_status'] ?? null;

        $log = [
            'event' => 'everify_status_updated',
            'date' => $occurredAt,
            'data' => $data,
            'description' => "E-Verify status updated to: {$everifyStatus}",
        ];

        if ($everifyStatus) {
            $onboardingList->everify_status = $everifyStatus;
            $onboardingList->everify_status_updated_at = $occurredAt;
            $onboardingList->workbright_status = "everify_{$everifyStatus}";

            // TNC = Tentative Non-Confirmation
            if ($everifyStatus === 'pending_referral') {
                Employee::where('id', $onboardingList->employee_id)->update(['tnc' => true]);
                $onboardingList->tnc = true;
                $log['description'] = 'E-Verify returned Tentative Non-Confirmation (TNC)';
            } elseif ($everifyStatus === 'employment_authorized') {
                $log['description'] = 'E-Verify confirmed employment authorization';

                // Check if documents are also approved to mark as verified
                $onboardingList->status = 'verified';
                $onboardingList->final_status = 'approved';
                $onboardingList->section_2_verification = true;
                $onboardingList->tnc = false;

                if ($onboardingList->employee->employee_rates->count() > 0) {
                    Employee::where('id', $onboardingList->employee_id)->update([
                        'employee_type' => 'Completed',
                        'onboarding_status' => 'verified'
                    ]);
                } else {
                    Employee::where('id', $onboardingList->employee_id)->update([
                        'employee_type' => 'New',
                        'onboarding_status' => 'verified'
                    ]);
                }
            }
        }
    }

    private function handleSubmissionCountersigned($onboardingList, $data, $occurredAt, &$log)
    {
        $submission = $data['submission'] ?? null;

        $log = [
            'event' => 'submission_countersigned',
            'date' => $occurredAt,
            'data' => $data,
            'description' => 'Form submission countersigned by authorized representative',
        ];

        $onboardingList->workbright_status = 'countersigned';
        $onboardingList->section_2_verification = true;
        $onboardingList->status = 'section_2_verification_done';
        Employee::where('id', $onboardingList->employee_id)->update(['onboarding_status' => 'section_2_verification_done']);
    }

    private function handleProfileUpdated($onboardingList, $data, $occurredAt, &$log)
    {
        $log = [
            'event' => 'profile_updated',
            'date' => $occurredAt,
            'data' => $data,
            'description' => 'Employee profile updated',
        ];
    }

    private function handleExpiringDocuments($onboardingList, $data, $occurredAt, &$log)
    {
        $log = [
            'event' => 'expiring_documents',
            'date' => $occurredAt,
            'data' => $data,
            'description' => 'Employee work authorization documents expiring soon',
        ];

        $onboardingList->workbright_status = 'documents_expiring';
    }

    private function handleI9Restarted($onboardingList, $data, $occurredAt, &$log)
    {
        $log = [
            'event' => 'i9_restarted',
            'date' => $occurredAt,
            'data' => $data,
            'description' => 'I-9 process restarted due to document mismatch',
        ];

        $onboardingList->workbright_status = 'i9_restarted';
        $onboardingList->i9_completed = false;
        $onboardingList->i9_approved = false;
    }

    public function getEmployees()
    {
        $response = $this->workBrightHttpClient()
            ->asJson()
            ->get($this->workBrightBaseUrl() . '/api/employees');
        /** @var \Illuminate\Http\Client\Response $response */
        if (!$response->successful()) {
            throw new \RuntimeException('WorkBright employees get failed: ' . $response->status() . ' ' . $response->body());
        }
        return $response->json();
    }

    public function getRequiredActionCases()
    {
        $response = $this->workBrightHttpClient()
            ->asJson()
            ->get($this->workBrightBaseUrl() . '/api/everify/cases/action_required');
        /** @var \Illuminate\Http\Client\Response $response */
        if (!$response->successful()) {
            throw new \RuntimeException('WorkBright required action cases get failed: ' . $response->status() . ' ' . $response->body());
        }
        return $response->json();
    }

    public function getAllCases()
    {
        $response = $this->workBrightHttpClient()
            ->asJson()
            ->get($this->workBrightBaseUrl() . '/api/everify/cases');
        /** @var \Illuminate\Http\Client\Response $response */
        if (!$response->successful()) {
            throw new \RuntimeException('WorkBright all cases get failed: ' . $response->status() . ' ' . $response->body());
        }
        return $response->json();
    }
    public function getAllSubmissions($employeeId)
    {
        try {

            $response = $this->workBrightHttpClient()
                ->asJson()
                ->get($this->workBrightBaseUrl() . '/api/employees/' . $employeeId . '/submissions');
            /** @var \Illuminate\Http\Client\Response $response */
            if (!$response->successful()) {
                throw new \RuntimeException('WorkBright all submissions get failed: ' . $response->status() . ' ' . $response->body());
            }
            return $response->json();
        } catch (\Throwable $th) {
            Log::error('WorkBright all submissions get failed: ' . $th->getMessage());
            return null;
        }
    }
}
