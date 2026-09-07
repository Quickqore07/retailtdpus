<?php

namespace App\Services;

use App\Models\Settings\Company;
use App\Models\User;

class NotificationPermission
{
    /**
     * Notification types available for role assignment.
     * Stored on roles as JSON array of keys, e.g. ['new_hire', 'rate_approval'].
     */
    public static function schema(): array
    {
        return [
            ['key' => 'new_hire', 'label' => 'New Hire'],
            ['key' => 'rate_approval', 'label' => 'Rate Approval'],
            ['key' => 'rate_change_request', 'label' => 'Rate change request'],
            ['key' => 'hours_change_request', 'label' => 'Hours change request'],
            ['key' => 'employee_hired', 'label' => 'Employee hired'],
            ['key' => 'overtime_notification', 'label' => 'Overtime notification'],
            ['key' => 'i9_review', 'label' => 'I-9 & W-4 (submissions & review)'],
            ['key' => 'chargeback_submitted', 'label' => 'Chargeback submitted'],
            ['key' => 'sales_receipt_uploaded', 'label' => 'Sales receipt uploaded'],
            ['key' => 'purchase_invoice_created', 'label' => 'Purchase invoice created'],
            ['key' => 'purchase_invoice_approved', 'label' => 'Purchase invoice approved'],
        ];
    }

    public static function keys(): array
    {
        return array_column(self::schema(), 'key');
    }

    /**
     * Users in the workgroup whose role includes the given notification permission key
     * and whose company access overlaps the given company IDs (same rules as authorizedCompanies).
     *
     * @param  array<int|string>  $companyIds  company_id values from employee rate requests
     * @return list<int>
     */
    public static function userIdsForPermissionInCompanies(string $permissionKey, array $companyIds, int $workgroupId): array
    {
        if ($workgroupId <= 0 || ! in_array($permissionKey, self::keys(), true)) {
            return [];
        }

        $companyIds = array_values(array_unique(array_filter(
            array_map(static fn ($id) => (int) $id, $companyIds),
            static fn (int $id) => $id > 0
        )));

        if ($companyIds === []) {
            return [];
        }

        $validCompanyIds = Company::query()
            ->where('workgroup_id', $workgroupId)
            ->whereIn('id', $companyIds)
            ->pluck('id')
            ->all();

        if ($validCompanyIds === []) {
            return [];
        }

        $adminNames = ['admin', 'superadmin'];

        $candidates = User::query()
            ->with('role')
            ->whereNotNull('role_id')
            ->get();


        $recipientIds = [];

        foreach ($candidates as $user) {
            $role = $user->role;
            if (! $role) {
                continue;
            }

            $perms = $role->notification_permissions;
            if (! is_array($perms) || ! in_array($permissionKey, $perms, true)) {
                continue;
            }

            $roleName = strtolower((string) $role->name);
            if (in_array($roleName, $adminNames, true)) {
                $recipientIds[] = $user->id;
                continue;
            }

            $userCompanies = ($user->companies && count($user->companies_array) > 0)
                ? $user->companies_array
                : (($role->companies && count($role->companies_array) > 0) ? $role->companies_array : []);

            if ($userCompanies === []) {
                continue;
            }

            if (count(array_intersect($userCompanies, $validCompanyIds)) > 0) {
                $recipientIds[] = $user->id;
            }
        }

        return array_values(array_unique($recipientIds));
    }

    /**
     * @param  array<int|string>  $companyIds
     * @return list<int>
     */
    public static function userIdsForNewHireInCompanies(array $companyIds, int $workgroupId): array
    {
        return self::userIdsForPermissionInCompanies('new_hire', $companyIds, $workgroupId);
    }

    /**
     * @param  array<int|string>  $companyIds
     * @return list<int>
     */
    public static function userIdsForEmployeeHiredInCompanies(array $companyIds, int $workgroupId): array
    {
        return self::userIdsForPermissionInCompanies('employee_hired', $companyIds, $workgroupId);
    }

    /**
     * @param  array<int|string>  $companyIds
     * @return list<int>
     */
    public static function userIdsForI9ReviewInCompanies(array $companyIds, int $workgroupId): array
    {
        return self::userIdsForPermissionInCompanies('i9_review', $companyIds, $workgroupId);
    }

    /**
     * @param  array<int|string>  $companyIds
     * @return list<int>
     */
    public static function userIdsForSalesReceiptUploadedInCompanies(array $companyIds, int $workgroupId): array
    {
        return self::userIdsForPermissionInCompanies('sales_receipt_uploaded', $companyIds, $workgroupId);
    }

    /**
     * @param  array<int|string>  $companyIds
     * @return list<int>
     */
    public static function userIdsForChargebackSubmittedInCompanies(array $companyIds, int $workgroupId): array
    {
        return self::userIdsForPermissionInCompanies('chargeback_submitted', $companyIds, $workgroupId);
    }

    /**
     * @param  array<int|string>  $companyIds
     * @return list<int>
     */
    public static function userIdsForPurchaseInvoiceCreatedInCompanies(array $companyIds, int $workgroupId): array
    {
        return self::userIdsForPermissionInCompanies('purchase_invoice_created', $companyIds, $workgroupId);
    }

    /**
     * @param  array<int|string>  $companyIds
     * @return list<int>
     */
    public static function userIdsForPurchaseInvoiceApprovedInCompanies(array $companyIds, int $workgroupId): array
    {
        return self::userIdsForPermissionInCompanies('purchase_invoice_approved', $companyIds, $workgroupId);
    }
}
