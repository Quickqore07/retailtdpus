<?php

    namespace App\Services\Payroll;

use App\Models\ActivityLog;
use App\Models\MWA;
use App\Models\Payroll\EmployeeHours;
    use App\Models\Payroll\EmployeeWeeklySummary;
use App\Models\Settings\Company;
use App\Models\Settings\MinimumWage;
use App\Services\ActivityLogService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

    class WeeklySummaryRecalculator
    {
        /** @var int Chunk distinct employees per query to limit memory on large weeks */
        private const EMPLOYEE_ID_CHUNK_SIZE = 100;

        /**
         * Run weekly summary recalculation with the given options.
         *
         * @param array $options [ employee_id?, start_date?, end_date?, company_id?, clear (bool) ]
         * @return int Number of summary records recalculated
         */
        public static function run(array $options = []): int
        {
            $employeeId = $options['employee_id'] ?? null;
            $startDate = $options['start_date'] ?? null;
            $endDate = $options['end_date'] ?? null;
            $companyId = $options['company_id'] ?? null;
            $clearExisting = $options['clear'] ?? false;
            $roleId = $options['role_id'] ?? null;
            $workgroupId = $options['workgroup_id'] ?? null;


            // if ($employeeId && !$startDate && !$endDate) {
            //     $startDate = Carbon::now()->subMonths(12)->format('Y-m-d');
            //     $endDate = Carbon::now()->format('Y-m-d');
            // }
            ActivityLogService::log(
                ActivityLog::ACTION_REVIEW,
                'employee_weekly_summary',
                null,
                $options,
                null,
                'Recalculated weekly summary for ' . $employeeId . ' from ' . $startDate . ' to ' . $endDate . ' for company ' . $companyId . ' and workgroup ' . $workgroupId
            );
            return (int) DB::transaction(function () use ($employeeId, $startDate, $endDate, $companyId, $clearExisting, $roleId, $workgroupId) {
                $totalRecords = 0;
                if ($clearExisting) {
                    $query = EmployeeWeeklySummary::query();
                    if ($employeeId) {
                        $query->where('employee_id', $employeeId);
                    }
                    if ($startDate && $endDate) {
                        $query->whereBetween('eow', [$startDate, $endDate]);
                    }
                    if ($companyId) {
                        $query->where('company_id', $companyId);
                    }
                    if ($roleId) {
                        $query->where('role_id', $roleId);
                    }
                    if ($workgroupId) {
                        $companyIds = Company::where('workgroup_id', $workgroupId)->pluck('id')->toArray();
                        $query->whereIn('company_id', $companyIds);
                    }
                    $query->delete();
                }

                $query = EmployeeHours::query();
                if ($employeeId) {
                    $query->where('employee_id', $employeeId);
                }
                if ($startDate && $endDate) {
                    $query->whereBetween('date', [$startDate, $endDate]);
                } elseif ($startDate) {
                    $query->where('date', '>=', $startDate);
                } elseif ($endDate) {
                    $query->where('date', '<=', $endDate);
                }
                if ($companyId) {
                    $query->where('company_id', $companyId);
                }
                if ($roleId) {
                    $query->where('role_id', $roleId);
                }
                if ($workgroupId) {
                    $companyIds = Company::where('workgroup_id', $workgroupId)->pluck('id')->toArray();
                    $query->whereIn('company_id', $companyIds);
                }
                $dates = $query->distinct()->pluck('date')->sort();
                if ($dates->isEmpty()) {
                    return 0;
                }

                $eowGroups = [];
                foreach ($dates as $date) {
                    $eow = self::calculateEOW($date);
                    if (!isset($eowGroups[$eow])) {
                        $eowGroups[$eow] = [];
                    }
                    $eowGroups[$eow][] = $date;
                }
                $totalRecords = 0;
                foreach ($eowGroups as $eow => $datesInPeriod) {
                    // Calculate the start of the week (7 days before EOW)
                    $weekStart = Carbon::parse($eow)->subDays(6)->format('Y-m-d');
                    $weekEnd = Carbon::parse($eow)->format('Y-m-d');
                    $excludeInactiveEmployees = function ($q) {
                        $q->select(DB::raw(1))
                            ->from('employee_inactive')
                            ->whereColumn('employee_inactive.employee_id', 'employee_hours.employee_id')
                            ->whereColumn('employee_inactive.from_date', '<=', 'employee_hours.date')
                            ->where(function ($q2) {
                                $q2->whereNull('employee_inactive.to_date')
                                    ->orWhereColumn('employee_inactive.to_date', '>=', 'employee_hours.date');
                            });
                    };

                    $weekEmployeeIdQuery = EmployeeHours::whereBetween('date', [$weekStart, $weekEnd])
                        ->whereNotExists($excludeInactiveEmployees);
                    if ($companyId) {
                        $weekEmployeeIdQuery->where('company_id', $companyId);
                    }
                    if ($employeeId) {
                        $weekEmployeeIdQuery->where('employee_id', $employeeId);
                    }
                    $employeeIdChunks = $weekEmployeeIdQuery->distinct()->pluck('employee_id')->chunk(self::EMPLOYEE_ID_CHUNK_SIZE);

                    $summariesToUpsert = [];

                    foreach ($employeeIdChunks as $idChunk) {
                        $weekQuery = EmployeeHours::whereBetween('date', [$weekStart, $weekEnd])
                            ->whereNotExists($excludeInactiveEmployees)
                            ->whereIn('employee_id', $idChunk->all())
                            ->groupBy('employee_id', 'role_id', 'company_id', 'pay_type')
                            ->selectRaw('
                                    employee_id, role_id, company_id, pay_type,
                                    SUM(total_hours) AS total_hours,
                                    SUM(tips) AS tips,
                                    SUM(mileage_excess) AS mileage_excess,
                                    SUM(incentive) AS incentive,
                                    SUM(bonus) AS bonus,
                                    SUM(tips_due) AS tips_due,
                                    SUM(mileage_due) AS mileage_due
                                ')
                            ->with('employee.employeeRatesUnrestricted', 'company.state');
                        $mwaQuery = MWA::query()->whereIn('employee_id', $idChunk->all());
                        if ($companyId) {
                            $weekQuery->where('company_id', $companyId);
                            $mwaQuery->where('company_id', $companyId);
                        }
                        if ($employeeId) {
                            $weekQuery->where('employee_id', $employeeId);
                            $mwaQuery->where('employee_id', $employeeId);
                        }
                        $weekData = $weekQuery->get();
                        $mwaData = $mwaQuery->get()->keyBy(function($item) {
                            return $item->employee_id . '-' . $item->company_id . '-' . $item->role_id . '-' . $item->eow;
                        });
                        info($mwaData);
                        foreach ($weekData as $row) {
                            if (!$row || !$row->employee) {
                                continue;
                            }

                            // Get the rate that was effective for this pay period (EOW)
                            // Select the most recent rate where effective_date <= eow

                            $rate = $row->employee->employeeRatesUnrestricted
                                ->where('role_id', $row->role_id)
                                ->where('company_id', $row->company_id)
                                ->filter(function ($r) use ($eow) {

                                    $effectiveFrom = Carbon::parse($r->effective_date)->startOfDay();
                                    $eowDate = Carbon::parse($eow)->startOfDay();

                                    $tillDate = !empty($r->till_date) && $r->till_date !== '0000-00-00'
                                        ? Carbon::parse($r->till_date)->endOfDay()
                                        : null;

                                    return $effectiveFrom <= $eowDate &&
                                        (is_null($tillDate) || $tillDate >= $eowDate);
                                })
                                ->sortByDesc(fn($item) => [$item->effective_date, $item->id])
                                ->first();

                            // if(!$rate){
                            //     $rate = $row->employee->employeeRatesUnrestrictedRequests
                            //     ->where('role_id', $row->role_id)
                            //     ->where('company_id', $row->company_id)
                            //     ->filter(function ($r) use ($eow) {

                            //         $effectiveFrom = Carbon::parse($r->effective_date)->startOfDay();
                            //         $eowDate = Carbon::parse($eow)->startOfDay();

                            //         $tillDate = !empty($r->till_date) && $r->till_date !== '0000-00-00'
                            //             ? Carbon::parse($r->till_date)->endOfDay()
                            //             : null;

                            //         return $effectiveFrom <= $eowDate &&
                            //             (is_null($tillDate) || $tillDate >= $eowDate);
                            //     })
                            //     ->sortByDesc(fn($item) => [$item->effective_date, $item->id])
                            //     ->first();
                            // }
                            if (!$rate) {
                                $rate = (object)[
                                    'id' => null,
                                    'rate_type' => 'Payroll Regular',
                                    'pay_type' => 'HR',
                                    'rate' => 0,
                                    'slab_first_hours' => 0,
                                    'slab_rest_rate' => 0,
                                    'effective_date' => $eow,
                                    'till_date' => null,
                                    'payroll_hours_type' => 'fixed',
                                ];
                            }
                            $row->mwa_amount = $mwaData[$row->employee_id . '-' . $row->company_id . '-' . $row->role_id . '-' . $eow]->amount ?? 0;
                            info($row->mwa_amount);
                            info($eow);
                            $weekCalculated = self::calculatePayrollForSummary($row, $rate);
                            $minWageData = self::calculateMinimumWage($row, $rate, $eow);

                            $summariesToUpsert[] = [
                                'employee_id' => $row->employee_id,
                                'role_id' => $row->role_id,
                                'company_id' => $row->company_id,
                                'employee_rate_id' => $rate->id ?? null,
                                'check_payment_type' => $rate->check_payment_type ?? null,
                                'check_payment_amount' => $rate->check_payment_amount ?? null,
                                'payroll_rate' => $rate->payroll_rate ?? null,
                                'ten99_rate' => $rate->ten99_rate ?? null,
                                'payroll_hours' => $rate->payroll_hours ?? null,
                                'payroll_hours_type' => $rate->payroll_hours_type ?? null,
                                'eow' => $eow,
                                'pay_type' => $rate->pay_type ?? $row->pay_type,
                                'total_hours' => $row->total_hours ?? 0,
                                'regular_hours' => $weekCalculated['regular_hours'],
                                'overtime_hours' => $weekCalculated['overtime_hours'],
                                'tips' => $row->tips ?? 0,
                                'mileage_excess' => $row->mileage_excess ?? 0,
                                'incentive' => $row->incentive ?? 0,
                                'bonus' => $row->bonus ?? 0,
                                'tips_due' => $row->tips_due ?? 0,
                                'mileage_due' => $row->mileage_due ?? 0,
                                'payroll_methods' => $weekCalculated['payroll_methods'],
                                'check_methods' => $weekCalculated['check_methods'],
                                'instant_methods' => $weekCalculated['instant_methods'],
                                'gross_pay' => $weekCalculated['gross_pay'],
                                'total_earnings' => $weekCalculated['total_earnings'],
                                'hr_pay' => $weekCalculated['hr_pay'],
                                'rate' => $rate->rate ?? 0,
                                'rate_type' => $rate->rate_type ?? null,
                                'slab_first_hours' => $rate->slab_first_hours ?? null,
                                'slab_rest_rate' => $rate->slab_rest_rate ?? null,
                                'mwa_amount' => $mwaData[$row->employee_id . '-' . $row->company_id . '-' . $row->role_id . '-' . $eow]->amount ?? 0,
                                'min_wage_hourly' => $minWageData['min_wage_hourly'] ?? null,
                                'min_wage_weekly' => $minWageData['min_wage_weekly'] ?? null,
                                'min_wage_due' => ($minWageData['min_wage_weekly'] ?? 0) - ($weekCalculated['payroll_methods'] ?? 0),
                                'calculated_check_amount' => $weekCalculated['check_methods'],
                                'calculated_instant_amount' => $weekCalculated['instant_methods'],
                                'calculated_payroll_amount' => $weekCalculated['payroll_methods'],
                                'created_at' => now(),
                                'updated_at' => now(),

                            // 'normal_rate' => $weekCalculated['normal_rate'] ?? 0,
                            // 'overtime_rate' => $weekCalculated['overtime_rate'] ?? 0,
                            'max_regular_hr' => $weekCalculated['max_regular_hr'] ?? 0,
                            ];
                            $row->unsetRelation('employee');
                            $row->unsetRelation('company');
                        }
                        unset($weekData);

                    }

                    // Redistribute overtime hours for employees with multiple hourly rates
                    $employeeRates = [];

                    // Group summaries by employee for hourly pay types
                    foreach ($summariesToUpsert as $index => $summary) {
                        if ($summary['pay_type'] == 'HR') {
                            if (!isset($employeeRates[$summary['employee_id'] . '-' . $summary['company_id']])) {
                                $employeeRates[$summary['employee_id'] . '-' . $summary['company_id']] = [];
                            }
                            $employeeRates[$summary['employee_id'] . '-' . $summary['company_id']][] = array_merge($summary, ['index' => $index]);
                        } else {
                            unset($summariesToUpsert[$index]['check_payment_type']);
                            unset($summariesToUpsert[$index]['check_payment_amount']);
                            unset($summariesToUpsert[$index]['max_regular_hr']);
                            // unset($summariesToUpsert[$index]['payroll_hours']);
                            unset($summariesToUpsert[$index]['payroll_hours_type']);
                            unset($summariesToUpsert[$index]['payroll_rate']);
                            unset($summariesToUpsert[$index]['ten99_rate']);
                        }
                    }


                    // Process each employee's summaries to redistribute overtime
                    foreach ($employeeRates as $key => $summaries) {
                        // Calculate total hours across all roles for this employee
                        $totalHours = 0;
                        $maxRegularHr = 0;

                        foreach ($summaries as $summary) {

                            $totalHours += $summary['total_hours'];
                            $maxRegularHr = max($maxRegularHr, $summary['max_regular_hr']);
                        }

                        // Calculate overtime hours (anything over 40 hours)
                        $overtimeHours = max(0, $totalHours - $maxRegularHr);

                        // If no overtime, just remove the index keys and continue
                        if ($overtimeHours <= 0) {
                            foreach ($summaries as $summary) {
                                unset($summariesToUpsert[$summary['index']]['index']);
                                unset($summariesToUpsert[$summary['index']]['check_payment_type']);
                                unset($summariesToUpsert[$summary['index']]['check_payment_amount']);
                                // unset($summariesToUpsert[$summary['index']]['payroll_hours']);
                                unset($summariesToUpsert[$summary['index']]['payroll_hours_type']);
                                unset($summariesToUpsert[$summary['index']]['max_regular_hr']);
                                unset($summariesToUpsert[$summary['index']]['payroll_rate']);
                                unset($summariesToUpsert[$summary['index']]['ten99_rate']);
                            }
                            continue;
                        }

                        // Find the summary with the highest rate
                        $highestRateSummary = null;
                        foreach ($summaries as $summary) {
                            if ($highestRateSummary === null || $summary['rate'] > $highestRateSummary['rate']) {
                                $highestRateSummary = $summary;
                            }
                        }

                        // Reset overtime hours for all summaries and set all hours as regular
                        // foreach ($summaries as $summary) {
                        //     $summariesToUpsert[$summary['index']]['overtime_hours'] = 0;
                        //     unset($summariesToUpsert[$summary['index']]['index']);
                        //     unset($summariesToUpsert[$summary['index']]['employee']);
                        //     unset($summariesToUpsert[$summary['index']]['check_payment_type']);
                        //     unset($summariesToUpsert[$summary['index']]['check_payment_amount']);
                        //     // unset($summariesToUpsert[$summary['index']]['payroll_hours']);
                        //     unset($summariesToUpsert[$summary['index']]['max_regular_hr']);
                        // }

                        // Assign all overtime to the highest rate summary
                        // if ($highestRateSummary) {
                        //     $summariesToUpsert[$highestRateSummary['index']]['overtime_hours'] = $overtimeHours;
                        // }
                        
                        $regularHours = $totalHours - $overtimeHours;
                        foreach ($summaries as $summary) {

                            if ($regularHours > $summary['regular_hours']) {
                                $summariesToUpsert[$summary['index']]['regular_hours'] = $summary['regular_hours'];
                                $regularHours -= $summary['regular_hours'];
                            } else {
                                $summariesToUpsert[$summary['index']]['regular_hours'] = $regularHours;
                                $regularHours = 0;
                            }

                            $summariesToUpsert[$summary['index']]['overtime_hours'] =  $summariesToUpsert[$summary['index']]['total_hours'] -  $summariesToUpsert[$summary['index']]['regular_hours'];

                            $rate = [
                                'id' => null,
                                'rate_type' => $summary['rate_type'],
                                'pay_type' => $summary['pay_type'],
                                'rate' => $summary['rate'],
                                'slab_first_hours' => $summary['slab_first_hours'],
                                'slab_rest_rate' => $summary['slab_rest_rate'],
                                'check_payment_type' => $summary['check_payment_type'],
                                'check_payment_amount' => $summary['check_payment_amount'],
                                'payroll_hours' => $summary['payroll_hours'],
                                'payroll_hours_type' => $summary['payroll_hours_type'],
                                'payroll_rate' => $summary['payroll_rate'],
                                'ten99_rate' => $summary['ten99_rate'],
                            ];

                            $result = self::calculatePayrollForSummary((object) $summariesToUpsert[$summary['index']], (object) $rate);
                            $summariesToUpsert[$summary['index']] = array_merge($summariesToUpsert[$summary['index']], $result);
                            unset($summariesToUpsert[$summary['index']]['payroll_hours_type']);
                            unset($summariesToUpsert[$summary['index']]['max_regular_hr']);
                            unset($summariesToUpsert[$summary['index']]['index']);
                            unset($summariesToUpsert[$summary['index']]['check_payment_type']);
                            unset($summariesToUpsert[$summary['index']]['check_payment_amount']);
                            unset($summariesToUpsert[$summary['index']]['payroll_rate']);
                            unset($summariesToUpsert[$summary['index']]['ten99_rate']);
                            // $reg_pay = $summariesToUpsert[$summary['index']]['regular_hours'] * $summariesToUpsert[$summary['index']]['normal_rate'];
                            // $overtime_pay = $summariesToUpsert[$summary['index']]['overtime_hours'] * $summariesToUpsert[$summary['index']]['overtime_rate'];

                            // $summariesToUpsert[$summary['index']]['gross_pay'] = $reg_pay + $overtime_pay;
                            // $summariesToUpsert[$summary['index']]['total_earnings'] = $summariesToUpsert[$summary['index']]['gross_pay'] + $summariesToUpsert[$summary['index']]['tips_due'] + $summariesToUpsert[$summary['index']]['mileage_due'] + $summariesToUpsert[$summary['index']]['incentive'] + $summariesToUpsert[$summary['index']]['bonus'];
                            // $summariesToUpsert[$summary['index']]['hr_pay'] = $summariesToUpsert[$summary['index']]['total_hours'] > 0 ? $summariesToUpsert[$summary['index']]['total_earnings'] /( $summariesToUpsert[$summary['index']]['total_hours'] ?? 1) : 0;
                            // unset($summariesToUpsert[$summary['index']]['normal_rate']);
                            // unset($summariesToUpsert[$summary['index']]['overtime_rate']);
                        }
                    }
                    if (count($summariesToUpsert) > 0) {
                        foreach (array_chunk($summariesToUpsert, 500) as $chunk) {
                            EmployeeWeeklySummary::upsert(
                                $chunk,
                                ['employee_id', 'role_id', 'company_id', 'eow', 'pay_type'],
                                [
                                    'employee_rate_id',
                                    'total_hours',
                                    'regular_hours',
                                    'overtime_hours',
                                    'tips',
                                    'mileage_excess',
                                    'incentive',
                                    'bonus',
                                    'tips_due',
                                    'mileage_due',
                                    'payroll_methods',
                                    'check_methods',
                                    'instant_methods',
                                    'gross_pay',
                                    'total_earnings',
                                    'hr_pay',
                                    'rate',
                                    'rate_type',
                                    'slab_first_hours',
                                    'slab_rest_rate',
                                    'min_wage_hourly',
                                    'min_wage_weekly',
                                    'min_wage_due',
                                    'calculated_check_amount',
                                    'calculated_instant_amount',
                                    'calculated_payroll_amount',
                                    'updated_at',
                                ]
                            );
                        }
                        $totalRecords += count($summariesToUpsert);
                    }
                    unset($summariesToUpsert, $employeeRates, $employeeIdChunks);
                }

                return $totalRecords;
            });
        }

        public static function calculateEOW($date): string
        {
            $carbonDate = Carbon::parse($date);
            $yearStart = Carbon::parse($carbonDate->year . '-01-01');
            $dayOfWeek = (int) $yearStart->format('N');
            $daysToMonday = $dayOfWeek === 7 ? -6 : 1 - $dayOfWeek - 7;
            $firstMonday = $yearStart->copy()->addDays($daysToMonday);
            $diffDays = $firstMonday->diffInDays($carbonDate, false);
            // Calculate weekly period (7 days instead of 14)
            $periodIndex = floor($diffDays / 7);
            // EOW is the Sunday (6 days after Monday start of week)
            $eow = $firstMonday->copy()->addDays(($periodIndex * 7) + 6);
            return $eow->format('Y-m-d');
        }

        private static function calculatePayrollForSummary($row, $rate): array
        {
            $result = [
                'regular_hours' => $row->regular_hours ?? 0,
                'overtime_hours' => $row->overtime_hours ?? 0,
                'payroll_methods' => 0,
                'check_methods' => 0,
                'instant_methods' => 0,
                'gross_pay' => 0,
                'total_earnings' => 0,
                'hr_pay' => 0,
            ];
            $total_hours = $row->total_hours ?? 0;
            $tips_due = $row->tips_due ?? 0;
            $mwa_amount = $row->mwa_amount ?? 0;
            $mileage_due = $row->mileage_due ?? 0;
            $bonus = $row->bonus ?? 0;


            if ($rate->rate_type == 'Payroll Regular') {
                if ($rate->pay_type == 'WK' && !isset($row->regular_hours)) {
                    $result['regular_hours'] = $total_hours;
                    $result['overtime_hours'] = 0;
                } else if (!isset($row->overtime_hours)) {
                    $result['overtime_hours'] = ($total_hours - 40) > 0 ? ($total_hours - 40) : 0;
                    $result['regular_hours'] = $total_hours - $result['overtime_hours'];
                }
                $normal_pay = $rate->pay_type == 'HR' ? $result['regular_hours'] * $rate->rate : $rate->rate;
                $overtime_pay = $rate->pay_type == 'HR' ? $result['overtime_hours'] * $rate->rate * 1.5 : 0;
                $result['max_regular_hr'] = 40;

                // $result['normal_rate'] = $rate->pay_type == 'HR' ? $rate->rate : $rate->rate;
                // $result['overtime_rate'] = $rate->pay_type == 'HR' ? $rate->rate * 1.5 : 0;

                $result['payroll_methods'] = $normal_pay + $overtime_pay + $tips_due + $mileage_due + $mwa_amount;
                $result['mwa_amount'] = $mwa_amount;
                $result['check_methods'] = 0;
                $result['instant_methods'] = 0;
            } elseif ($rate->rate_type == 'Payroll Slab') {
                if ($rate->pay_type == 'WK' && !isset($row->regular_hours)) {
                    $result['regular_hours'] = $total_hours;
                    $result['overtime_hours'] = 0;
                } else if (!isset($row->overtime_hours)) {
                    $slab_first_hours = $rate->slab_first_hours && $rate->slab_first_hours > 0 ? $rate->slab_first_hours : 40;
                    $result['overtime_hours'] = ($total_hours - $slab_first_hours) > 0 ? ($total_hours - $slab_first_hours) : 0;
                    $result['regular_hours'] = $total_hours - $result['overtime_hours'];
                }
                $normal_pay = $rate->pay_type == 'HR' ? $result['regular_hours'] * $rate->rate : $rate->rate;
                $overtime_pay = $rate->pay_type == 'HR' ? $result['overtime_hours'] * $rate->slab_rest_rate : 0;

                // $result['normal_rate'] = $rate->pay_type == 'HR' ? $rate->rate : $rate->rate;
                // $result['overtime_rate'] = $rate->pay_type == 'HR' ? $rate->slab_rest_rate : 0;
                $result['max_regular_hr'] =isset($slab_first_hours) ? $slab_first_hours : 40;


                $total_pay = $normal_pay + $overtime_pay;
                $payroll_hours = $rate->payroll_hours_type == 'percentage' && $rate->payroll_hours > 0 ? $total_hours * $rate->payroll_hours / 100 : ($rate->payroll_hours > 0 ? $rate->payroll_hours : 0);

                $result['payroll_hours'] = $payroll_hours;

                if ($payroll_hours > 0) {
                    $regular_payroll_hours = min($result['regular_hours'], $payroll_hours);
                    $remaining_hours = max($payroll_hours - $regular_payroll_hours, 0);
                    $overtime_payroll_hours = min($result['overtime_hours'], $remaining_hours);

                    if ($rate->pay_type == 'HR') {
                        $result['payroll_methods'] = ($regular_payroll_hours * $rate->rate) + ($overtime_payroll_hours * $rate->slab_rest_rate);
                    } else {
                        $result['payroll_methods'] = $rate->rate;
                    }
                } else {
                    $result['payroll_methods'] = $total_pay;
                }

                $remaining_amount = $total_pay - $result['payroll_methods'];
                if ($remaining_amount > 0 && $rate->check_payment_type == 'percentage') {
                    $check_payment_amount = $remaining_amount * $rate->check_payment_amount / 100;
                    $result['check_methods'] = $remaining_amount > $check_payment_amount ? $check_payment_amount : $remaining_amount;
                } elseif ($remaining_amount > 0 && $rate->check_payment_type == 'fixed') {
                    $check_payment_amount = $rate->check_payment_amount;
                    $result['check_methods'] = $remaining_amount > $check_payment_amount ? $check_payment_amount : $remaining_amount;
                } else {
                    $result['check_methods'] = 0;
                }
                $result['instant_methods'] = $remaining_amount - $result['check_methods'];
                $result['payroll_methods'] += $tips_due + $mileage_due + $mwa_amount;
                $result['mwa_amount'] = $mwa_amount;
            } elseif ($rate->rate_type == 'Payroll 1099') {
                if ($rate->pay_type == 'WK' && !isset($row->regular_hours)) {
                    $result['regular_hours'] = $total_hours;
                    $result['overtime_hours'] = 0;
                } else if (!isset($row->overtime_hours)) {
                    $slab_first_hours = $rate->slab_first_hours && $rate->slab_first_hours > 0 ? $rate->slab_first_hours : 40;
                    $result['overtime_hours'] = ($total_hours - $slab_first_hours) > 0 ? ($total_hours - $slab_first_hours) : 0;
                    $result['regular_hours'] = $total_hours - $result['overtime_hours'];
                }
                $normal_pay = $rate->pay_type == 'HR' ? $result['regular_hours'] * $rate->rate : $rate->rate;
                $overtime_pay = $rate->pay_type == 'HR' ? $result['overtime_hours'] * $rate->slab_rest_rate : 0;

                // $result['normal_rate'] = $rate->pay_type == 'HR' ? $rate->rate : $rate->rate;
                // $result['overtime_rate'] = $rate->pay_type == 'HR' ? $rate->slab_rest_rate : 0;
                $result['max_regular_hr'] =isset($slab_first_hours) ? $slab_first_hours : 40;


                $total_pay = $normal_pay + $overtime_pay;

                if ($rate->pay_type == 'HR') {
                    $result['payroll_methods'] = ($result['regular_hours'] * $rate->payroll_rate) + ($result['overtime_hours'] * $rate->payroll_rate * 1.5);
                } else {
                    $result['payroll_methods'] = $rate->rate;
                }

                $remaining_amount = $total_pay - $result['payroll_methods'];
                if ($remaining_amount > 0 && $rate->check_payment_type == 'percentage') {
                    $check_payment_amount = $remaining_amount * $rate->check_payment_amount / 100;
                    $result['check_methods'] = $remaining_amount > $check_payment_amount ? $check_payment_amount : $remaining_amount;
                } elseif ($remaining_amount > 0 && $rate->check_payment_type == 'fixed') {
                    $check_payment_amount = $rate->check_payment_amount;
                    $result['check_methods'] = $remaining_amount > $check_payment_amount ? $check_payment_amount : $remaining_amount;
                } else {
                    $result['check_methods'] = 0;
                }
                $result['instant_methods'] = $remaining_amount - $result['check_methods'];
                $result['payroll_methods'] += $tips_due + $mileage_due + $mwa_amount;
                $result['mwa_amount'] = $mwa_amount;
            } elseif ($rate->rate_type == '1099 Regular') {

                if ($rate->pay_type == 'WK' && !isset($row->regular_hours)) {
                    $result['regular_hours'] = $total_hours;
                    $result['overtime_hours'] = 0;
                } else if (!isset($row->overtime_hours)) {
                    $result['overtime_hours'] = ($total_hours - 40) > 0 ? ($total_hours - 40) : 0;
                    $result['regular_hours'] = $total_hours - $result['overtime_hours'];
                }
                $result['max_regular_hr'] = 40;

                $normal_pay = $rate->pay_type == 'HR' ? $result['regular_hours'] * $rate->rate : $rate->rate;
                $overtime_pay = $rate->pay_type == 'HR' ? $result['overtime_hours'] * $rate->rate * 1.5 : 0;

                // $result['normal_rate'] = $rate->pay_type == 'HR' ? $rate->rate : $rate->rate;
                // $result['overtime_rate'] = $rate->pay_type == 'HR' ? $rate->rate * 1.5 : 0;


                $total_pay = $normal_pay + $overtime_pay + $tips_due + $mileage_due + $mwa_amount;
                if ($total_pay > 0 && $rate->check_payment_type == 'percentage') {
                    $check_payment_amount = $total_pay * $rate->check_payment_amount / 100;
                    $result['check_methods'] = $total_pay > $check_payment_amount ? $check_payment_amount : $total_pay;
                } elseif ($total_pay > 0 && $rate->check_payment_type == 'fixed') {
                    $check_payment_amount = $rate->check_payment_amount;
                    $result['check_methods'] = $total_pay > $check_payment_amount ? $check_payment_amount : $total_pay;
                } else {
                    $result['check_methods'] = 0;
                }
                $result['payroll_methods'] = 0;
                $result['mwa_amount'] = $mwa_amount;
                $result['instant_methods'] = $total_pay - $result['check_methods'];
            } elseif ($rate->rate_type == '1099 Slab') {

                if ($rate->pay_type == 'WK' && !isset($row->regular_hours)) {
                    $result['regular_hours'] = $total_hours;
                    $result['overtime_hours'] = 0;
                } else if (!isset($row->overtime_hours)) {
                    $slab_first_hours = $rate->slab_first_hours && $rate->slab_first_hours > 0 ? $rate->slab_first_hours : 40;
                    $result['overtime_hours'] = ($total_hours - $slab_first_hours) > 0 ? ($total_hours - $slab_first_hours) : 0;
                    $result['regular_hours'] = $total_hours - $result['overtime_hours'];
                }
                $normal_pay = $rate->pay_type == 'HR' ? $result['regular_hours'] * $rate->rate : $rate->rate;
                $overtime_pay = $rate->pay_type == 'HR' ? $result['overtime_hours'] * $rate->slab_rest_rate : 0;

                // $result['normal_rate'] = $rate->pay_type == 'HR' ? $rate->rate : $rate->rate;
                // $result['overtime_rate'] = $rate->pay_type == 'HR' ? $rate->slab_rest_rate : 0;
                $result['max_regular_hr'] =isset($slab_first_hours) ? $slab_first_hours : 40;

                $total_pay = $normal_pay + $overtime_pay + $tips_due + $mileage_due + $mwa_amount;
                if ($total_pay > 0 && $rate->check_payment_type == 'percentage') {
                    $check_payment_amount = $total_pay * $rate->check_payment_amount / 100;
                    $result['check_methods'] = $total_pay > $check_payment_amount ? $check_payment_amount : $total_pay;
                } elseif ($total_pay > 0 && $rate->check_payment_type == 'fixed') {
                    $check_payment_amount = $rate->check_payment_amount;
                    $result['check_methods'] = $total_pay > $check_payment_amount ? $check_payment_amount : $total_pay;
                } else {
                    $result['check_methods'] = 0;
                }
                $result['payroll_methods'] = 0;
                $result['instant_methods'] = $total_pay - $result['check_methods'];
                $result['mwa_amount'] = $mwa_amount;
            } elseif ($rate->rate_type == '1099 1099') {
                if ($rate->pay_type == 'WK' && !isset($row->regular_hours)) {
                    $result['regular_hours'] = $total_hours;
                    $result['overtime_hours'] = 0;
                } else if (!isset($row->overtime_hours)) {
                    $slab_first_hours = $rate->slab_first_hours > 0 ? $rate->slab_first_hours : 40;
                    $result['overtime_hours'] = ($total_hours - $slab_first_hours) > 0 ? ($total_hours - $slab_first_hours) : 0;
                    $result['regular_hours'] = $total_hours - $result['overtime_hours'];
                }

                $normal_pay = $rate->pay_type == 'HR' ? $result['regular_hours'] * $rate->rate : $rate->rate;
                $overtime_pay = $rate->pay_type == 'HR' ? $result['overtime_hours'] * $rate->slab_rest_rate : 0;
                $total_pay = $normal_pay + $overtime_pay + $tips_due + $mileage_due + $mwa_amount;

                // $result['normal_rate'] = $rate->pay_type == 'HR' ? $rate->rate : $rate->rate;
                // $result['overtime_rate'] = $rate->pay_type == 'HR' ? $rate->slab_rest_rate : 0;

                $total_check_pay = $rate->pay_type == 'HR' ? $result['total_hours'] *( $rate->ten99_rate ?? $rate->rate ) : 0;

                $result['max_regular_hr'] = 40;

                $total_pay = $normal_pay + $overtime_pay + $tips_due + $mileage_due + $mwa_amount;

                if ($total_check_pay > 0) {
                    $result['check_methods'] = $total_check_pay;
                } else {
                    $result['check_methods'] = 0;
                }
                $result['payroll_methods'] = 0;
                $result['instant_methods'] =  $total_pay - $result['check_methods'] > 0 ? $total_pay - $result['check_methods'] : 0;
                $result['mwa_amount'] = $mwa_amount;
            }

            $result['gross_pay'] = $result['payroll_methods'] + $result['check_methods'] + $result['instant_methods'] - $tips_due - $mileage_due - $mwa_amount;
            $result['total_earnings'] = $result['gross_pay'] + $bonus + $tips_due + $mileage_due + $mwa_amount;
            $result['hr_pay'] = $total_hours > 0 ? $result['total_earnings'] / $total_hours : 0;
            return $result;
        }


        private static function calculateMinimumWage($row, $rate, $eow): array
        {
            $result = ['min_wage_hourly' => null, 'min_wage_weekly' => null, 'min_wage_due' => 0];
            if (!$row->company || !$row->company->state_id) {
                return $result;
            }
            if (!in_array($rate->rate_type, ['Payroll Regular', 'Payroll Slab'])) {
                return $result;
            }
            $minWage = MinimumWage::latestItemForState($row->company->state_id, $eow);
            if ($minWage && isset($minWage->minimum_wage)) {
                $result['min_wage_hourly'] = $minWage->minimum_wage;
                $result['min_wage_weekly'] = $minWage->minimum_wage * ($row->total_hours ?? 0);
                $calculatedData = self::calculatePayrollForSummary($row, $rate);
                if ($calculatedData['payroll_methods'] < $result['min_wage_weekly']) {
                    $result['min_wage_due'] = $result['min_wage_weekly'] - $calculatedData['payroll_methods'];
                }
            }
            return $result;
        }
    }
