<?php

namespace App\Services;

use App\Services\Payroll\WeeklySummaryRecalculator;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class WeeklySummaryRecalculationService
{
    /**
     * Recalculate weekly summary for a specific employee (e.g. after rate or payroll data change).
     * Uses default date range of last 12 months when not specified.
     */
    public static function recalculateForEmployee(array $options = []
    ): void {
        $employeeId = $options['employee_id'] ?? null;
        $startDate = $options['start_date'] ?? null;
        $endDate = $options['end_date'] ?? null;
        $companyIds = $options['company_ids'] ?? null;
        $clear = $options['clear'] ?? true;
        $roleId = $options['role_id'] ?? null;
        $workgroupId = $options['workgroup_id'] ?? null;

        // $startDate = $startDate ?? Carbon::now()->subMonths(12)->format('Y-m-d');
        // $endDate = $endDate ?? Carbon::now()->format('Y-m-d');
        try {
            WeeklySummaryRecalculator::run([
                'employee_id' => $employeeId,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'company_ids' => explode(',', $companyIds),
                'clear' => $clear,
                'role_id' => $roleId,
                'workgroup_id' => $workgroupId,
            ]);
        } catch (\Throwable $e) {
            Log::warning('Weekly summary recalculation for employee failed', [
                'employee_id' => $employeeId,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
