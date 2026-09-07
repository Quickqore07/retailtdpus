<?php

namespace App\Services;

use App\Models\Onboarding\OnboardingList;
use App\Models\Payroll\EmployeeRates;
use App\Models\User;
use Illuminate\Support\Facades\View;

class I9W4SubmittedNotificationService
{
    /**
     * WorkBright webhook: employee.form_submitted for document i9 or w4.
     */
    public static function notifyWorkbright(OnboardingList $onboardingList, string $documentId, bool $isResubmit): void
    {
        if (! in_array($documentId, ['i9', 'w4'], true)) {
            return;
        }

        if (! $onboardingList->i9_with_work_bright) {
            return;
        }

        self::dispatch($onboardingList, $documentId === 'i9' ? 'I-9' : 'W-4', $isResubmit, 'WorkBright');
    }

    /**
     * Internal onboarding flow (non–WorkBright): Step 4 I-9 saved.
     */
    public static function notifyInternalI9(OnboardingList $onboardingList, bool $isResubmit): void
    {
        if ($onboardingList->i9_with_work_bright) {
            return;
        }

        self::dispatch($onboardingList, 'I-9', $isResubmit, 'onboarding portal');
    }

    /**
     * Internal onboarding flow (non–WorkBright): Step 5 W-4 saved.
     */
    public static function notifyInternalW4(OnboardingList $onboardingList, bool $isResubmit): void
    {
        if ($onboardingList->i9_with_work_bright) {
            return;
        }

        self::dispatch($onboardingList, 'W-4', $isResubmit, 'onboarding portal');
    }

    private static function dispatch(OnboardingList $onboardingList, string $formLabel, bool $isResubmit, string $sourceLabel): void
    {
        try {
            $employee = $onboardingList->employee;
            $workgroupId = (int) ($employee?->workgroup_id ?? 0);
            if ($workgroupId <= 0) {
                return;
            }

            $companyIds = self::companyIdsForOnboarding($onboardingList);
            $recipientIds = NotificationPermission::userIdsForI9ReviewInCompanies($companyIds, $workgroupId);
            if ($recipientIds === []) {
                return;
            }

            $name = trim(implode(' ', array_filter([
                $onboardingList->applicant_first_name,
                $onboardingList->applicant_last_name,
            ])));
            if ($name === '' && $employee) {
                $name = trim(implode(' ', array_filter([
                    $employee->first_name,
                    $employee->last_name,
                ])));
            }
            if ($name === '') {
                $name = $employee?->pos_name ?? 'Employee';
            }

            $empId = (string) ($employee?->employee_id ?? '');
            $onbNum = (string) ($onboardingList->onboarding_number ?? '');
            $action = $isResubmit ? 'resubmitted' : 'submitted';

            $inAppContent = sprintf(
                '%s %s (%s) via %s. Onboarding #%s. Open I-9 review to process.',
                $formLabel,
                $action,
                $name,
                $sourceLabel,
                $onbNum !== '' ? $onbNum : (string) $onboardingList->id
            );

            store_app_notifications_for_users($recipientIds, $inAppContent, 'i9_review');

            $reviewUrl = url('/onboarding/i9-review/'.$onboardingList->id);
            $subject = sprintf('%s %s — %s', $formLabel, $isResubmit ? 'resubmitted' : 'submitted', $name);

            $html = View::make('emails.i9-w4-submitted-notification', [
                'formLabel' => $formLabel,
                'isResubmit' => $isResubmit,
                'sourceLabel' => $sourceLabel,
                'employeeName' => $name,
                'employeeId' => $empId,
                'onboardingNumber' => $onbNum,
                'onboardingListId' => $onboardingList->id,
                'reviewUrl' => $reviewUrl,
            ])->render();

            $emails = User::query()
                ->whereIn('id', $recipientIds)
                ->whereNotNull('email')
                ->where('email', '!=', '')
                ->pluck('email')->unique()->toArray();

            foreach ($emails as $email) {
                try {
                    MailService::sendMail($email, $subject, $html);
                } catch (\Throwable $e) {
                    report($e);
                }
            }
        } catch (\Throwable $e) {
            report($e);
        }
    }

    /**
     * @return list<int>
     */
    private static function companyIdsForOnboarding(OnboardingList $onboardingList): array
    {
        $ids = [];
        $cid = (int) ($onboardingList->company_id ?? 0);
        if ($cid > 0) {
            $ids[] = $cid;
        }

        $employee = $onboardingList->employee;
        if (! $employee) {
            return array_values(array_unique($ids));
        }

        $ec = (int) ($employee->company_id ?? 0);
        if ($ec > 0) {
            $ids[] = $ec;
        }

        $rateCompanyIds = EmployeeRates::query()
            ->where('employee_id', $employee->id)
            ->pluck('company_id')
            ->filter()
            ->map(static fn ($id) => (int) $id)
            ->all();

        $ids = array_merge($ids, $rateCompanyIds);

        return array_values(array_unique(array_filter($ids, static fn (int $id) => $id > 0)));
    }
}
