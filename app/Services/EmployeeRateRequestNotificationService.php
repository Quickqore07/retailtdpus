<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\User;
use Illuminate\Support\Facades\View;

class EmployeeRateRequestNotificationService
{
    /**
     * @param  array<int, array<string, mixed>>  $requests  Rows for emails.employee-rate-requests
     * @param  array<int, array<string, mixed>>  $rateRows  Raw rate rows (for company_id extraction)
     */
    public static function notifyNewRequests(
        Employee $employee,
        array $rateRows,
        array $requests,
        string $requestedBy
    ): void {
        if ($requests === []) {
            return;
        }

        $companyIds = self::companyIdsFromRateRows($rateRows);
        $recipientIds = self::resolveRecipientIds($employee, $companyIds);
        if ($recipientIds === []) {
            return;
        }

        $label = $employee->pos_name ?? $employee->employee_id ?? 'Employee';
        $count = count($requests);
        $inAppContent = sprintf(
            '%d new employee rate request(s) for %s (requested by %s).',
            $count,
            $label,
            $requestedBy
        );

        store_app_notifications_for_users($recipientIds, $inAppContent, 'rate_change_request');

        $subject = 'New Employee Rate Requests - '.$label;
        $html = View::make('emails.employee-rate-requests', [
            'employeeGroups' => [['employee' => $employee, 'requests' => $requests]],
            'requests' => $requests,
            'requestedBy' => $requestedBy,
        ])->render();

        self::sendMailToRecipients($recipientIds, $subject, $html);
    }

    /**
     * @param  array<int, array<string, mixed>>  $comparisons  Rows for emails.employee-rate-requests-updated
     * @param  array<int, array<string, mixed>>  $rateRows  Raw rate rows (for company_id extraction)
     */
    public static function notifyUpdatedRequests(
        Employee $employee,
        array $rateRows,
        array $comparisons,
        string $updatedBy
    ): void {
        if ($comparisons === []) {
            return;
        }

        $companyIds = self::companyIdsFromRateRows($rateRows);
        $recipientIds = self::resolveRecipientIds($employee, $companyIds);
        if ($recipientIds === []) {
            return;
        }

        $label = $employee->pos_name ?? $employee->employee_id ?? 'Employee';
        $count = count($comparisons);
        $inAppContent = sprintf(
            '%d updated employee rate request(s) for %s (updated by %s).',
            $count,
            $label,
            $updatedBy
        );

        store_app_notifications_for_users($recipientIds, $inAppContent, 'rate_change_request');

        $subject = 'Updated Employee Rate Requests - '.$label;
        $html = View::make('emails.employee-rate-requests-updated', [
            'employee' => $employee,
            'requests' => $comparisons,
            'updatedBy' => $updatedBy,
        ])->render();

        self::sendMailToRecipients($recipientIds, $subject, $html);
    }

    /**
     * @param  list<int>  $recipientIds
     */
    private static function sendMailToRecipients(array $recipientIds, string $subject, string $html): void
    {
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
    }

    /**
     * Prefer users with the rate_change_request notification permission and matching companies;
     * fall back to HR users in the workgroup (legacy behavior).
     *
     * @return list<int>
     */
    private static function resolveRecipientIds(Employee $employee, array $companyIds): array
    {
        $workgroupId = (int) ($employee->workgroup_id ?? 0);
        if ($workgroupId <= 0) {
            return [];
        }
        $ids = NotificationPermission::userIdsForPermissionInCompanies(
            'rate_change_request',
            $companyIds,
            $workgroupId
        );

        if ($ids !== []) {
            return $ids;
        }

        return User::query()
            ->where('workgroup_id', $workgroupId)
            ->whereNotNull('email')
            ->where('email', '!=', '')
            ->authorizedWorkgroup()
            ->pluck('id')
            ->map(static fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @return list<int>
     */
    private static function companyIdsFromRateRows(array $rows): array
    {
        $ids = [];
        foreach ($rows as $row) {
            $id = (int) ($row['company_id'] ?? 0);
            if ($id > 0) {
                $ids[] = $id;
            }
        }

        return array_values(array_unique($ids));
    }
}
