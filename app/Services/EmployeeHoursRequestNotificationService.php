<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\User;
use Illuminate\Support\Facades\View;

class EmployeeHoursRequestNotificationService
{
    /**
     * Notify users with the hours_change_request permission (fallback: HR in workgroup) about a new pending hours request.
     *
     * @param  array<string, mixed>  $emailRequestRow  Row for emails.employee-hours-requests
     */
    public static function notifyNewRequest(
        Employee $employee,
        array $emailRequestRow,
        int $companyId,
        string $requestedBy
    ): void {
        $workgroupId = (int) ($employee->workgroup_id ?? 0);
        if ($workgroupId <= 0) {
            return;
        }

        $recipientIds = NotificationPermission::userIdsForPermissionInCompanies(
            'hours_change_request',
            [$companyId],
            $workgroupId
        );

        if ($recipientIds === []) {
            $recipientIds = User::query()
                ->whereHas('role', fn ($q) => $q->where('name', 'HR'))
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

        if ($recipientIds === []) {
            return;
        }

        $label = $employee->pos_name ?? $employee->employee_id ?? 'Employee';
        $inAppContent = sprintf(
            'New employee hours request for %s (requested by %s). Awaiting approval.',
            $label,
            $requestedBy
        );

        store_app_notifications_for_users($recipientIds, $inAppContent, 'hours_change_request');

        $subject = 'New Employee Hours Request - '.$label;
        $html = View::make('emails.employee-hours-requests', [
            'employee' => $employee,
            'requests' => [$emailRequestRow],
            'requestedBy' => $requestedBy,
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
    }
}
