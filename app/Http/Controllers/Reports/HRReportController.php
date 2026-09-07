<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Models\Settings\Company;
use App\Models\Payroll\EmployeeWeeklySummary;
use App\Models\Payroll\EmployeeHours;
use App\Models\Employee;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Reports\PayrollReportController;
use App\Models\Payroll\PayrollCheckAmount;
use App\Models\User;

class HRReportController extends Controller
{
    public function getNetworkSummaryReport(Request $request)
    {
        $isPayrollReview = $request->is_payroll_review ?? false;
        if($isPayrollReview){
            $this->authorize('access', 'network-payroll-review-report.index');
        }else{
            $this->authorize('access', 'network-summary-report.index');
        }
        try {
            $payrollreport = new PayrollReportController();
            $user_id = $request->user_id ?? null;
            $company_ids = null;
            if($user_id){
                $user = User::find($user_id);
                $company_ids = $user->companies_array;
            }
            $networkSummary = $payrollreport->companyWisePayroll($request->start_date, $request->end_date, $company_ids);


            $payrollAmounts = PayrollCheckAmount::whereBetween('eow', [$request->start_date, $request->end_date])->with('reviewer')->select('company_id', 'reviewed_at', 'review_by')->get()->keyBy('company_id');

            foreach ($networkSummary as $company) {
                $company['regular_hours'] = 0;
                $company['overtime_hours'] = 0;
                $company['total_hours'] = 0;
                $company['gross_pay'] = 0;
                $company['tips'] = 0;
                $company['mwa_amount'] = 0;
                $company['mileage_excess'] = 0;
                $company['tips_due'] = 0;
                $company['mileage_due'] = 0;
                $company['total_earnings'] = 0;
                $company['payroll_methods'] = 0;
                $company['check_methods'] = 0;
                $company['instant_methods'] = 0;
                $company['status'] = isset($payrollAmounts[$company['id']]) ? 'Reviewed' : 'Pending';
                $company['reviewed_at'] = $payrollAmounts[$company['id']]['reviewed_at'] ?? null;
                $company['review_by'] = $payrollAmounts[$company['id']]['reviewer']?->name ?? null;

                foreach ($company->labour_data as $employee) {
                    $company['total_hours'] += $employee['total_hours'];
                    $company['regular_hours'] += $employee['regular_hours'];
                    $company['overtime_hours'] += $employee['overtime_hours'];
                    $company['gross_pay'] += $employee['gross_pay'];
                    $company['tips'] += $employee['tips'];
                    $company['mwa_amount'] += $employee['mwa_amount'];
                    $company['mileage_excess'] += $employee['mileage_excess'];
                    $company['tips_due'] += $employee['tips_due'];
                    $company['mileage_due'] += $employee['mileage_due'];
                    $company['total_earnings'] += $employee['total_earnings'];
                    $company['payroll_methods'] += $employee['payroll_methods'];
                    $company['check_methods'] += $employee['check_methods'];
                    $company['instant_methods'] += $employee['instant_methods'];

                    foreach ($employee['roles'] as $role) {
                        $company['regular_hours'] += $role['regular_hours'];
                        $company['overtime_hours'] += $role['overtime_hours'];
                    }
                }
                unset($company['labour_data']);
            }
            
            return response()->json([
                'data' => $networkSummary,
            ]);
        } catch (\Exception $e) {
            dd($e);
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }
    public function getNetworkHoursReport(Request $request)
    {
        $this->authorize('access', 'network-hours-report.index');
        try {
            $year = $request->year ?? Carbon::now()->year;
            
            $yearStart = Carbon::parse($year . '-01-01');
            
            $dayOfWeek = (int)$yearStart->format('N');
            $isDC= isDCWorkgroup();
            if($isDC){
                $daysToMonday = $dayOfWeek === 7 ? 0 : 1 - $dayOfWeek;
            }else{
                $daysToMonday = $dayOfWeek === 7 ? -6 : 1 - $dayOfWeek - 7;
            }
            $firstMonday = $yearStart->copy()->addDays($daysToMonday);

            
            $eows = [];
            for ($i = 0; $i < 26; $i++) {
                $eow = $firstMonday->copy()->addDays(($i * 14) + 13);
                if ($eow->year == $year || ($eow->year == $year + 1 && $eow->month == 1)) {
                    $eows[] = $eow->format('Y-m-d');
                }
            }
            // Get all companies
            $companies = Company::select('id', 'name', 'store_number')->authorizedCompanies('id')->orderBy('store_number')->get();
            
            $reportData = [];
            foreach ($companies as $index => $company) {
                $companyData = [
                    'sr_no' => $index + 1,
                    'store_number' => $company->store_number,
                    'store_name' => $company->name,
                    'company_id' => $company->id,
                ];
                $startDate = Carbon::parse($eows[0])->subDays(13);
                $endDate = Carbon::parse($eows[count($eows) - 1]);
                // Get weekly summaries for this company grouped by EOW
                $weeklySummaries = EmployeeWeeklySummary::where('company_id', $company->id)
                    ->whereBetween('eow', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
                    ->groupBy('eow')
                    ->selectRaw('eow, sum(total_hours) as total_hours')
                    ->authorizedCompanies('company_id')
                    ->get()
                    ->keyBy('eow');
                
                // Add data for each pay period
                foreach ($eows as $eow) {
                    $firstEow = Carbon::parse($eow)->subDays(7)->format('Y-m-d');
                    $lastEow = $eow;

                    $summaryWeek1 = isset($weeklySummaries[$firstEow]) ? $weeklySummaries[$firstEow] : null;
                    $summaryWeek2 = isset($weeklySummaries[$lastEow]) ? $weeklySummaries[$lastEow] : null;
                    $companyData['pp_' . $eow] = ($summaryWeek1 ? round($summaryWeek1->total_hours, 2) : 0 )+ ($summaryWeek2 ? round($summaryWeek2->total_hours, 2) : 0);
                }
                
                $reportData[] = $companyData;
            }
            
            return response()->json([
                'data' => $reportData,
                'eows' => $eows,
                'year' => $year,
            ]);
        }catch(\Exception $e){
            Log::error('Network Hours Report failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    public function getStoreSummaryReport(Request $request)
    {
        $this->authorize('access', 'store-summary-report.index');
        try {
            $company = $request->company_id;
            $year = $request->year ?? Carbon::now()->year;
            
            $yearStart = Carbon::parse($year . '-01-01');
            
            $dayOfWeek = (int)$yearStart->format('N');
            $daysToMonday = $dayOfWeek === 7 ? -6 : 1 - $dayOfWeek - 7;
            $firstMonday = $yearStart->copy()->addDays($daysToMonday);

            
            $eows = [];
            for ($i = 0; $i < 26; $i++) {
                $eow = $firstMonday->copy()->addDays(($i * 14) + 13);
                if ($eow->year == $year || ($eow->year == $year + 1 && $eow->month == 1)) {
                    $eows[] = $eow->format('Y-m-d');
                }
            }
            $startDate = Carbon::parse($eows[0])->subDays(13);
            $endDate = Carbon::parse($eows[count($eows) - 1]);
            
            $weeklySummaries = EmployeeWeeklySummary::where('company_id', $company)
            ->whereBetween('eow', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
            ->groupBy('eow')
            ->authorizedCompanies('company_id')
            ->selectRaw('eow, sum(regular_hours) as regular_hours, sum(overtime_hours) as overtime_hours, sum(total_hours) as total_hours, sum(payroll_methods) as payroll_methods, sum(tips) as tips, sum(mwa_amount) as mwa_amount, sum(tips_due) as tips_due, sum(mileage_due) as mileage_due, sum(gross_pay) as gross_pay, sum(check_methods) as check_methods, sum(instant_methods) as instant_methods, sum(total_earnings) as total_earnings')
            ->get()->keyBy('eow');


            $reportData = [];
            
            foreach ($eows as $key => $eow) {
                $firstEow = Carbon::parse($eow)->subDays(7)->format('Y-m-d');
                $lastEow = $eow;
                $summariesWeek1 = isset($weeklySummaries[$firstEow]) ? $weeklySummaries[$firstEow] : null;
                $summariesWeek2 = isset($weeklySummaries[$lastEow]) ? $weeklySummaries[$lastEow] : null;

                $data = [
                    'pp_ends' => Carbon::parse($eow)->format('m/d/Y'),
                    'eow' => $eow,
                    'sales' => 0,
                    'regular_hours' => ($summariesWeek1['regular_hours'] ?? 0 )+ ($summariesWeek2['regular_hours'] ?? 0),
                    'overtime_hours' => ($summariesWeek1['overtime_hours'] ?? 0 )+ ($summariesWeek2['overtime_hours'] ?? 0),
                    'total_hours' => ($summariesWeek1['total_hours'] ?? 0 )+ ($summariesWeek2['total_hours'] ?? 0),
                    'net_payroll' => ($summariesWeek1['payroll_methods'] ?? 0 )+ ($summariesWeek2['payroll_methods'] ?? 0),
                    'tips' => ($summariesWeek1['tips'] ?? 0 )+ ($summariesWeek2['tips'] ?? 0),
                    'mwa_amount' => ($summariesWeek1['mwa_amount'] ?? 0 )+ ($summariesWeek2['mwa_amount'] ?? 0),
                    'tips_due' => ($summariesWeek1['tips_due'] ?? 0 )+ ($summariesWeek2['tips_due'] ?? 0),
                    'mileage_due' => ($summariesWeek1['mileage_due'] ?? 0 )+ ($summariesWeek2['mileage_due'] ?? 0),
                    'gross_pay' => ($summariesWeek1['gross_pay'] ?? 0 )+ ($summariesWeek2['gross_pay'] ?? 0),
                    'check_methods' => ($summariesWeek1['check_methods'] ?? 0 )+ ($summariesWeek2['check_methods'] ?? 0),
                    'instant_methods' => ($summariesWeek1['instant_methods'] ?? 0 )+ ($summariesWeek2['instant_methods'] ?? 0),
                    'total_earnings' => ($summariesWeek1['total_earnings'] ?? 0 )+ ($summariesWeek2['total_earnings'] ?? 0),
                ];
                
                $reportData[] = $data;
            }
            
            return response()->json([
                'data' => $reportData,
                'year' => $year,
            ]);
        } catch (\Exception $e) {
            Log::error('Pay Period Store Report failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    public function getEmployeeHoursAnomalyReport(Request $request)
    {
        $this->authorize('access', 'employee-hours-anomaly-report.index');
        try {
            $startDate = Carbon::parse($request->start_date);
            $endDate = Carbon::parse($request->end_date);
            $filterType = $request->filter_type ?? 'greater_than_12';
            
            $query = EmployeeHours::whereBetween('date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
                ->with(['employee', 'company', 'role'])
                ->authorizedCompanies('company_id');
            
            if ($filterType === 'greater_than_12') {
                $query->where('total_hours', '>', 12);
            } elseif ($filterType === 'less_than_0') {
                $query->where('total_hours', '<', 0);
            } elseif ($filterType === 'both') {
                $query->where(function($q) {
                    $q->where('total_hours', '>', 12)
                      ->orWhere('total_hours', '<', 0);
                });
            }
            
            $anomalies = $query->orderBy('date', 'desc')
                ->orderBy('total_hours', 'desc')
                ->get();
            
            $companies = [];
            foreach ($anomalies as $anomaly) {
                $companyId = $anomaly->company_id;
                
                if (!isset($companies[$companyId])) {
                    $companies[$companyId] = [
                        'id' => $anomaly->company->id,
                        'name' => $anomaly->company->name,
                        'store_number' => $anomaly->company->store_number,
                        'anomalies' => [],
                    ];
                }
                
                $companies[$companyId]['anomalies'][] = [
                    'id' => $anomaly->id,
                    'date' => $anomaly->date,
                    'employee_id' => $anomaly->employee_id,
                    'employee_name' => $anomaly->employee_name,
                    'employee' => $anomaly->employee,
                    'company_id' => $anomaly->company_id,
                    'total_hours' => $anomaly->total_hours,
                    'tips' => $anomaly->tips,
                    'mileage_excess' => $anomaly->mileage_excess,
                    'incentive' => $anomaly->incentive,
                    'bonus' => $anomaly->bonus,
                    'pay_rate' => $anomaly->pay_rate,
                    'tips_due' => $anomaly->tips_due,
                    'mileage_due' => $anomaly->mileage_due,
                    'role' => $anomaly->role,
                    'pay_type' => $anomaly->pay_type,
                ];
            }
            
            return response()->json([
                'data' => array_values($companies),
                'total_anomalies' => $anomalies->count(),
            ]);
            
        } catch (\Exception $e) {
            Log::error('Employee Hours Anomaly Report failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    public function getEmployeePayrollInfoReport(Request $request)
    {
        $this->authorize('access', 'employee-payroll-info-report.index');
        try {
            
            $sortColumn = $request->input('sort_column', '');
            $sortDirection = $request->input('sort_direction', 'asc');
            $companyId = $request->input('company_id');
            $minRate = $request->input('min_rate');
            $maxRate = $request->input('max_rate');
            $rateType = $request->input('rate_type');
            $payType = $request->input('pay_type');
            $startDate = Carbon::parse($request->start_date);
            $endDate = Carbon::parse($request->end_date);
            $payrollPeriodLabel = $startDate->format('m/d/Y') . ' To ' . $endDate->format('m/d/Y');
            $authorizedCompanies = authorizedCompanies();

            $findApplicableRate = function ($rates, $companyIdValue, $roleId, $referenceDate) {
                return $rates
                    ->where('role_id', $roleId)
                    ->where('company_id', $companyIdValue)
                    ->filter(function ($rate) use ($referenceDate) {
                        $effectiveFrom = Carbon::parse($rate->effective_date)->startOfDay();
                        $refDate = Carbon::parse($referenceDate)->startOfDay();
                        $tillDate = !empty($rate->till_date) && $rate->till_date !== '0000-00-00'
                            ? Carbon::parse($rate->till_date)->endOfDay()
                            : null;

                        return $effectiveFrom <= $refDate
                            && (is_null($tillDate) || $tillDate >= $refDate);
                    })
                    ->sortByDesc(fn($item) => [$item->effective_date, $item->id])
                    ->first();
            };
            
            $weeklySummaries = EmployeeWeeklySummary::with(['role', 'company'])
                                ->whereBetween('eow', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
                                ->authorizedCompanies('company_id')
                                ->when($companyId, fn($query) => $query->where('company_id', $companyId))
                                ->groupBy('employee_id', 'role_id', 'company_id')
                                ->selectRaw('employee_id, role_id, company_id, sum(total_hours) as total_hours, sum(payroll_methods) as payroll_methods, sum(tips) as tips, sum(tips_due) as tips_due, sum(mileage_due) as mileage_due, sum(gross_pay) as gross_pay, sum(check_methods) as check_methods, sum(instant_methods) as instant_methods, sum(total_earnings) as total_earnings')
                                ->get();

            $passesRateFilters = function ($rate) use ($minRate, $maxRate, $rateType, $payType, $companyId) {
                if ($companyId && (int) $rate->company_id !== (int) $companyId) {
                    return false;
                }
                if ($minRate !== null && $minRate !== '' && (float) $rate->rate < (float) $minRate) {
                    return false;
                }
                if ($maxRate !== null && $maxRate !== '' && (float) $rate->rate > (float) $maxRate) {
                    return false;
                }
                if ($rateType && $rate->rate_type !== $rateType) {
                    return false;
                }
                if ($payType && $rate->pay_type !== $payType) {
                    return false;
                }

                return true;
            };

            $buildEmployeeName = function ($employee) {
                return collect([$employee->pos_name])
                    ->merge($employee->relationLoaded('aliases') ? $employee->aliasNames() : [])
                    ->filter(fn($name) => $name && trim($name) !== '')
                    ->join(' ') ?: '-';
            };

            $buildReportRow = function ($employee, $summary, $rate, $employeeName) use ($payrollPeriodLabel) {
                $role = $rate ? $rate->role : $summary->role;
                return [
                    'id' => $employee->id,
                    'employee_id' => $employee->employee_id,
                    'pos_name' => $employee->pos_name,
                    'aliases' => $employee->relationLoaded('aliases')
                        ? $employee->aliases->map(fn ($a) => [
                            'alias_employee_id' => $a->alias_employee_id,
                            'alias_name' => $a->alias_name,
                        ])->values()->all()
                        : [],
                    'employee_name' => $employeeName,
                    'company' => $rate ? $rate->company : $summary->company,
                    'company_name' => $summary->company ? $summary->company->name : '',
                    'store_number' => $summary->company ? $summary->company->store_number : '',
                    'role' => $role,
                    'role_name' => $role ? $role->name : '-',
                    'rate' => $rate ? $rate->rate : 0,
                    'rate_type' => $rate ? $rate->rate_type : '',
                    'pay_type' => $rate ? $rate->pay_type : '',
                    'payroll_type' => $rate ? $rate->payroll_type : '',
                    'payroll_period' => $payrollPeriodLabel,
                    'effective_date' => $rate ? $rate->effective_date : '',
                    'till_date' => $rate ? $rate->till_date : '',
                    'slab_first_hours' => $rate ? $rate->slab_first_hours : 0,
                    'slab_rest_rate' => $rate ? $rate->slab_rest_rate : 0,
                    'payroll_rate' => $rate ? $rate->payroll_rate : 0,
                    'payroll_hours' => $rate ? $rate->payroll_hours : 0,
                    'payroll_hours_type' => $rate ? $rate->payroll_hours_type : '',
                    'total_hours' => $summary->total_hours,
                    'payroll_methods' => $summary->payroll_methods,
                    'tips' => $summary->tips,
                    'tips_due' => $summary->tips_due,
                    'mileage_due' => $summary->mileage_due,
                    'gross_pay' => $summary->gross_pay,
                    'check_methods' => $summary->check_methods,
                    'instant_methods' => $summary->instant_methods,
                    'total_earnings' => $summary->total_earnings,
                ];
            };

            $employeeIds = $weeklySummaries->pluck('employee_id')->unique()->values();

            $employees = Employee::with([
                'employeeRatesUnrestricted.role',
                'employeeRatesUnrestricted.company',
                'company',
                'aliases',
            ])
                ->whereIn('id', $employeeIds)
                ->get()
                ->keyBy('id');

            $reportData = [];

            foreach ($weeklySummaries as $summary) {
                if (!in_array($summary->company_id, $authorizedCompanies)) {
                    continue;
                }

                $employee = $employees->get($summary->employee_id);
                if (!$employee) {
                    continue;
                }

                $applicableRate = $findApplicableRate(
                    $employee->employeeRatesUnrestricted,
                    $summary->company_id,
                    $summary->role_id,
                    $endDate
                );

                if ($applicableRate && !$passesRateFilters($applicableRate)) {
                    continue;
                }

                $reportData[] = $buildReportRow(
                    $employee,
                    $summary,
                    $applicableRate,
                    $buildEmployeeName($employee)
                );
            }
            
            // Apply sorting if requested
            if ($sortColumn) {
                $reportData = collect($reportData)->sortBy(function($item) use ($sortColumn) {
                    switch ($sortColumn) {
                        case 'employee_id':
                            return $item['employee_id'];
                        case 'total_hours':
                            return $item['total_hours'];
                        case 'employee_name':
                            return strtolower($item['employee_name']);
                        case 'company':
                            return strtolower($item['company_name']);
                        case 'store_number':
                            return $item['store_number'];
                        case 'role':
                            return strtolower($item['role_name']);
                        case 'rate':
                            return (float)$item['rate'];
                        case 'rate_type':
                            return strtolower($item['rate_type'] ?? '');
                        case 'pay_type':
                            return strtolower($item['pay_type'] ?? '');
                        case 'payroll_type':
                            return strtolower($item['payroll_type'] ?? '');
                        case 'payroll_period':
                            return strtolower($item['payroll_period'] ?? '');
                        default:
                            return $item['employee_id'];
                    }
                }, SORT_REGULAR, $sortDirection === 'desc')->values()->all();
            }
            
            return response()->json([
                'data' => $reportData,
                'total_records' => count($reportData),
                'payroll_period' => $payrollPeriodLabel,
            ]);
            
        } catch (\Exception $e) {
            dd($e);
            Log::error('Employee Payroll Info Report failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

   
}