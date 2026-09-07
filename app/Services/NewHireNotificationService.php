<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\View;

class NewHireNotificationService
{
    /**
     * Send new-hire in-app notifications and emails to authorized users.
     *
     * Props structure:
     * - workgroup_id: int
     * - rate_company_ids: list<int> (company_id from each rate row, used for recipient resolution)
     * - employee: name, email, employee_id, ssn (display-safe), position
     * - rates: list of rows with company, rate, effective_date, rate_type
     */
    public static function notify(array $props): void
    {
        $workgroupId = (int) ($props['workgroup_id'] ?? 0);
        $rateCompanyIds = $props['rate_company_ids'] ?? [];

        if ($workgroupId <= 0) {
            return;
        }

        $recipientIds = NotificationPermission::userIdsForNewHireInCompanies(
            is_array($rateCompanyIds) ? $rateCompanyIds : [],
            $workgroupId
        );

        if ($recipientIds === []) {
            return;
        }

        $employee = is_array($props['employee'] ?? null) ? $props['employee'] : [];
        $name = (string) ($employee['name'] ?? '');
        $empId = (string) ($employee['employee_id'] ?? '');

        $inAppContent = sprintf(
            'New hire %s (Employee ID: %s) was added with pending rate requests.',
            $name !== '' ? $name : 'An employee',
            $empId
        );

        store_app_notifications_for_users($recipientIds, $inAppContent, 'new_hire');

        $subject = 'New hire: '.($name !== '' ? $name : ($empId !== '' ? $empId : 'pending rate requests'));

        $html = View::make('emails.new-hire-notification', ['props' => $props])->render();

        $users = User::query()
            ->whereIn('id', $recipientIds)
            ->whereNotNull('email')
            ->where('email', '!=', '')
            ->get(['id', 'email']);

        foreach ($users as $user) {
            MailService::sendMail($user->email, $subject, $html, $props);
        }
    }

    public static function maskSsn(?string $ssn): string
    {
        if ($ssn === null || trim($ssn) === '') {
            return '—';
        }

        $digits = preg_replace('/\D+/', '', $ssn);
        if (strlen($digits) === 9) {
            return '***-**-'.substr($digits, -4);
        }
        if (strlen($digits) >= 4) {
            return '***'.substr($digits, -4);
        }

        return '***';
    }
}
