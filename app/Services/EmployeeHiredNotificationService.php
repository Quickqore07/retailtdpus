<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\Payroll\EmployeeRates;
use App\Models\User;
use Illuminate\Support\Facades\View;

class EmployeeHiredNotificationService
{
    /**
     * In-app + email to users with the "employee_hired" permission when an employee is marked Completed.
     */
    public static function notify(Employee $employee, string $approvedByName): void
    {
        $workgroupId = (int) ($employee->workgroup_id ?? 0);
        if ($workgroupId <= 0) {
            return;
        }

        $rates = EmployeeRates::with(['company', 'role'])
            ->where('employee_id', $employee->id)
            ->orderBy('effective_date')
            ->orderBy('id')
            ->get();

        $companyIds = $rates->pluck('company_id')
            ->filter()
            ->map(static fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();
        $companyIds = array_values(array_filter($companyIds, static fn (int $id) => $id > 0));

        $recipientIds = NotificationPermission::userIdsForEmployeeHiredInCompanies($companyIds, $workgroupId);
        if ($recipientIds === []) {
            return;
        }

        $label = $employee->pos_name ?? $employee->employee_id ?? 'Employee';
        $empId = (string) ($employee->employee_id ?? '');
        $inAppContent = sprintf(
            'Employee %s (Employee ID: %s) was hired and marked completed. See email for rate details (approved by %s).',
            $label,
            $empId !== '' ? $empId : 'N/A',
            $approvedByName
        );

        store_app_notifications_for_users($recipientIds, $inAppContent, 'employee_hired');

        $subject = 'Employee hired — '.$label.($empId !== '' ? ' ('.$empId.')' : '');
        $html = View::make('emails.employee-hired-notification', [
            'employee' => $employee,
            'rates' => $rates,
            'approvedByName' => $approvedByName,
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
