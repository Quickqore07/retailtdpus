<?php

namespace App\Services;

use App\Models\Settings\Company;
use App\Models\Settings\EmployeeRoles;
use App\Models\User;
use Illuminate\Support\Facades\View;

class OvertimeHoursUploadNotificationService
{
    /**
     * Notify authorized users about overtime rows from an employee-hours bulk upload.
     *
     * @param  list<array{pos_employee_id: string, employee_name: string, date: string, total_hours: float, overtime_hours: float, role_id: int, company_id: int}>  $lineItems
     */
    public static function notifyAfterUpload(array $lineItems, int $workgroupId, string $uploadedBy): void
    {
        if ($lineItems === [] || $workgroupId <= 0) {
            return;
        }

        $companyIds = array_values(array_unique(array_filter(
            array_map(static fn ($row) => (int) ($row['company_id'] ?? 0), $lineItems),
            static fn (int $id) => $id > 0
        )));

        if ($companyIds === []) {
            return;
        }

        $validCompanyIds = Company::query()
            ->where('workgroup_id', $workgroupId)
            ->whereIn('id', $companyIds)
            ->pluck('id')
            ->map(static fn ($id) => (int) $id)
            ->all();

        if ($validCompanyIds === []) {
            return;
        }

        $recipientIds = NotificationPermission::userIdsForPermissionInCompanies(
            'overtime_notification',
            $validCompanyIds,
            $workgroupId
        );

        if ($recipientIds === []) {
            $recipientIds = User::query()
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

        $roleIds = array_values(array_unique(array_filter(
            array_map(static fn ($r) => (int) ($r['role_id'] ?? 0), $lineItems),
            static fn (int $id) => $id > 0
        )));

        $roleCache = $roleIds === []
            ? collect()
            : EmployeeRoles::query()
                ->authorizedWorkgroup()
                ->whereIn('id', $roleIds)
                ->get(['id', 'name', 'code'])
                ->keyBy('id');

        $companyCache = Company::query()
            ->whereIn('id', $validCompanyIds)
            ->get(['id', 'name', 'store_number'])
            ->keyBy('id');

        $users = User::query()
            ->whereIn('id', $recipientIds)
            ->with('role')
            ->whereNotNull('email')
            ->where('email', '!=', '')
            ->get(['id', 'email', 'companies', 'role_id']);
        $sentEmails = [];

        foreach ($users as $user) {
            $email = $user->email;
            if (in_array($email, $sentEmails, true)) {
                continue;
            }
            $sentEmails[] = $email;

            $allowedCompanyIds = self::authorizedCompanyIdsForUser($user, $workgroupId);
            $filteredLineItems = array_values(array_filter(
                $lineItems,
                static function (array $item) use ($allowedCompanyIds): bool {
                    $cid = (int) ($item['company_id'] ?? 0);

                    return $cid > 0 && in_array($cid, $allowedCompanyIds, true);
                }
            ));

            if ($filteredLineItems === []) {
                continue;
            }

            $n = count($filteredLineItems);
            $rows = self::buildRowsFromLineItems($filteredLineItems, $roleCache, $companyCache);

            $inAppContent = sprintf(
                'Employee hours upload: %d line(s) with overtime (uploaded by %s). See email for employee ID, name, date, hours, OT hours, and role.',
                $n,
                $uploadedBy
            );

            store_app_notification($user->id, $inAppContent, 'overtime_notification');

            $subject = $n === 1
                ? 'Overtime from hours upload: '.($rows[0]['employee_name'] !== '' ? $rows[0]['employee_name'] : ($rows[0]['pos_employee_id'] !== '' ? $rows[0]['pos_employee_id'] : 'details'))
                : 'Overtime from employee hours upload ('.$n.' lines)';

            $html = View::make('emails.overtime-hours-upload', [
                'rows' => $rows,
                'uploadedBy' => $uploadedBy,
            ])->render();

            try {
                MailService::sendMail($email, $subject, $html);
            } catch (\Throwable $e) {
                report($e);
            }
        }
    }

    /**
     * Same rules as {@see authorizedCompanies()} for the current workgroup (admin = all companies in workgroup).
     *
     * @return list<int>
     */
    private static function authorizedCompanyIdsForUser(User $user, int $workgroupId): array
    {
        $user->loadMissing('role');
        $role = $user->role;
        if (! $role) {
            return [];
        }

        if (strtolower((string) $role->name) === 'admin') {
            return Company::query()
                ->where('workgroup_id', $workgroupId)
                ->pluck('id')
                ->map(static fn ($id) => (int) $id)
                ->all();
        }

        $ids = [];
        if ($user->companies && count($user->companies_array) > 0) {
            $ids = $user->companies_array;
        } elseif ($role->companies && count($role->companies_array) > 0) {
            $ids = $role->companies_array;
        }

        if ($ids === []) {
            return [];
        }

        return Company::query()
            ->where('workgroup_id', $workgroupId)
            ->whereIn('id', $ids)
            ->pluck('id')
            ->map(static fn ($id) => (int) $id)
            ->all();
    }

    /**
     * @param  \Illuminate\Support\Collection<int, \App\Models\Settings\EmployeeRoles>  $roleCache
     * @param  \Illuminate\Support\Collection<int, \App\Models\Settings\Company>  $companyCache
     * @return list<array{pos_employee_id: string, employee_name: string, date: string, total_hours: float, overtime_hours: float, role: string, company: string}>
     */
    private static function buildRowsFromLineItems(array $lineItems, $roleCache, $companyCache): array
    {
        $rows = [];
        foreach ($lineItems as $item) {
            $rid = (int) ($item['role_id'] ?? 0);
            $role = $roleCache->get($rid);
            $cid = (int) ($item['company_id'] ?? 0);
            $comp = $companyCache->get($cid);

            $roleLabel = '—';
            if ($role) {
                $code = trim((string) ($role->code ?? ''));
                $name = trim((string) ($role->name ?? ''));
                $roleLabel = $code !== '' && $name !== ''
                    ? $code.' - '.$name
                    : ($code !== '' ? $code : $name);
            }

            $rows[] = [
                'pos_employee_id' => (string) ($item['pos_employee_id'] ?? ''),
                'employee_name' => (string) ($item['employee_name'] ?? ''),
                'date' => (string) ($item['date'] ?? ''),
                'total_hours' => (float) ($item['total_hours'] ?? 0),
                'overtime_hours' => (float) ($item['overtime_hours'] ?? 0),
                'role' => $roleLabel,
                'company' => $comp?->name ?? '—',
            ];
        }

        return $rows;
    }
}
