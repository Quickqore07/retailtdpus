<?php

namespace App\Http\Controllers\Reports;

use App\Models\Payroll\EmployeeWeeklySummary;
use App\Models\Settings\Company;
use App\Models\Settings\MinimumWage;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\Payroll\PayrollCheckAmount;
use App\Models\Settings\LedgerDetails;
use App\Models\Settings\CheckMaster;
use App\Services\ActivityLogService;
use App\Http\Controllers\Controller;
use App\Models\DataEntry\Drivers;
use App\Models\DataEntry\IdealCost;
use App\Models\DataEntry\PayrollJournal;
use App\Models\MWA;
use App\Services\WeeklySummaryRecalculationService;

class PayrollReportController extends Controller
{
    public function getWeeklyPayrollReport(Request $request)
    {
        $this->authorize('access', 'payroll-report.index');
        try {
            $company = $request->session()->get('company');
            
            
            // Parse dates
            $startDate = Carbon::parse($request->start_date);
            $endDate = Carbon::parse($request->end_date);

            $checkAmounts = PayrollCheckAmount::where('company_id', $company)->whereBetween('eow', [Carbon::parse($request->start_date), Carbon::parse($request->end_date)])
            ->get()
            ->keyBy(function($item) {
                return $item->employee_id . '-' . $item->role_id . '-' . $item->eow . '-' . $item->company_id;
            });
            
            // Fetch pre-calculated weekly summaries for the date range

            $weeklySummaries = EmployeeWeeklySummary::where('company_id', $company)
            ->whereBetween('eow', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
            ->with('employee','employee.employeeRates', 'employee.employeeRatesRequests', 'role', 'company')
            ->authorizedCompanies('company_id')
            ->get();
            
            $min_wage_review = false;
            
            // Group by employee+role+pay_type and separate by week
            $array = [];
            $minWageRates = MinimumWage::latestItemsKeyedByState($endDate);
            foreach ($weeklySummaries as $summary) {

                if($summary->total_hours == 0 && $summary->payroll_methods == 0 && $summary->check_methods == 0 && $summary->instant_methods == 0 && $summary->pay_type == 'HR' && $summary->tips == 0 && $summary->mileage_excess == 0 && $summary->incentive == 0 && $summary->bonus == 0 && $summary->tips_due == 0 && $summary->mileage_due == 0) {
                    continue;
                }
                $minWage = isset($minWageRates[$summary->company->state_id]) ? $minWageRates[$summary->company->state_id] : null;
                $key = $summary->employee_id . '-' . $summary->role_id . '-' . $summary->pay_type;
                
                // Calculate week period from EOW (Sunday back to Monday)
                $weekEndDate = Carbon::parse($summary->eow);
                $weekStartDate = $weekEndDate->copy()->subDays(6);
                $summary->week_period = $weekStartDate->format('m/d/Y') . ' - ' . $weekEndDate->format('m/d/Y');
                
                // Build employee_rate structure for compatibility
                $summary->employee_rate = [
                    'rate' => $summary->rate,
                    'rate_type' => $summary->rate_type,
                    'slab_first_hours' => $summary->slab_first_hours,
                    'slab_rest_rate' => $summary->slab_rest_rate,
                    'pay_type' => $summary->pay_type,
                ];
                
                // Check min wage review
               
                
                // Check if this record has been edited from calculated values
                $checkKey = $summary->employee_id . '-' . $summary->role_id . '-' . $summary->eow . '-' . $summary->company_id;
                if(isset($checkAmounts[$checkKey])){
                    $summary->check_edited = abs((float)($checkAmounts[$checkKey]->amount ?? 0) - (float)($summary->check_methods ?? 0)) > 0.1 ? true : false;
                    $summary->check_methods = $checkAmounts[$checkKey]->amount ?? $summary->check_methods;
                    $summary->payroll_methods = $checkAmounts[$checkKey]->payroll_amount ?? $summary->payroll_methods;
                    $summary->check_amount_id = $checkAmounts[$checkKey]->id ?? null;
                    $instant_methods = $summary->total_earnings - $summary->payroll_methods - $summary->check_methods;
                    $summary->instant_methods = $instant_methods > 0 ? $instant_methods : 0;
                    $summary->total_earnings = $summary->payroll_methods + $summary->check_methods + $summary->instant_methods;
                }
                
                if (!isset($array[$key])) {
                    $array[$key] = [];
                }

                // if($summary->role->tipped){
                //     $summary->min_wage_rate = isset($minWage->tipped_minimum_wage) ? $minWage->tipped_minimum_wage : 0;
                // }else{
                    $summary->min_wage_rate = isset($minWage->minimum_wage) ? $minWage->minimum_wage : 0;
                // }
                $summary->hr_pay = ($summary->gross_pay + $summary->tips + $summary->mwa_amount) / ($summary->total_hours == 0 ? 1 : $summary->total_hours);
                
                $summary->employee && ($rate = $summary->employee->employeeRates->where('role_id', $summary->role_id)->where('company_id', $summary->company_id)->first());
                if(!$rate){
                    $rate = $summary->employee->employeeRatesRequests->where('role_id', $summary->role_id) ->where('company_id', $summary->company_id)->first();
                }

                if($rate && ($rate->rate_type == 'Payroll Slab' || $rate->rate_type == 'Payroll Regular' || $rate->rate_type == 'Payroll 1099')){
                    $summary->payroll_type = $rate->payroll_type;
                }
                $summary->min_wage_due = 0;
                if (isset($minWage)  && isset($rate)&& $rate->pay_type == 'HR' && ($rate->rate_type == 'Payroll Regular' || $rate->rate_type == 'Payroll Slab')) {

                    $summary->min_wage_weekly =  $summary->min_wage_rate * ($summary->total_hours ?? 0);
                    $summary->min_wage_hourly =  $summary->min_wage_rate;
                    if ($summary->hr_pay * ($summary->total_hours ?? 0) < $summary->min_wage_weekly) {
                        $summary->min_wage_due = $summary->min_wage_weekly - $summary->hr_pay * ($summary->total_hours ?? 0);
                    } else {
                        $summary->min_wage_due = 0;
                    }
                }
                if ($summary->min_wage_due > 0) {
                    $min_wage_review = true;
                }

                $summary->tipped = false;
                $summary->is_1099 = $rate && $rate->rate_type == '1099 1099' ? true : false;
                
                $array[$key][] = $summary;
            }
            
            // Check if any records have been reviewed
            $reviewed = count($checkAmounts) > 0 ? true : false;

            $now = Carbon::now('America/New_York');

            $currentPayrollEndDate = calculateCurrentPayrollEndDate($now);
            
            $daysDiff = Carbon::parse($currentPayrollEndDate, 'America/New_York')
                ->diffInDays($now);

                
            $thisPayrollEndDate = Carbon::parse($currentPayrollEndDate, 'America/New_York')->subDays(14)->format('Y-m-d');
            $reviewAvaible = $daysDiff > -13  && $daysDiff < -10 && $thisPayrollEndDate == $endDate->format('Y-m-d');
            return response()->json([
                'data' => array_values($array),
                'reviewed' => $reviewed,
                'min_wage_review' => $min_wage_review,
                'reviewAvaible' => $reviewAvaible,
            ]);
            
        } catch (\Exception $e) {
            dd($e);
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    public function getPaychexReport(Request $request)
    {
        $this->authorize('access', 'paychex-report.index');
        try {
            
            $startDate = Carbon::parse($request->start_date);
            $endDate = Carbon::parse($request->end_date);

            if(isDCWorkgroup()){
                return response()->json([
                    'message' => 'unauthorized access',
                ],401);
            }
            
            $networkPayroll = EmployeeWeeklySummary::whereBetween('eow', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
                ->where('payroll_methods', '>', 0)
                ->groupBy('employee_id', 'company_id', 'role_id')
                ->selectRaw('employee_id, company_id, role_id, sum(payroll_methods) as amount,sum(regular_hours) as regular_hours,sum(overtime_hours) as overtime_hours,sum(total_hours) as total_hours,sum(gross_pay) as gross_pay,sum(tips) as tips,sum(tips_due) as tips_due,sum(mileage_due) as mileage_due,sum(total_earnings) as total_earnings,sum(mwa_amount) as mwa_amount')
                ->with('employee.employeeRates', 'employee.employeeRatesRequests', 'company', 'role')
                ->authorizedCompanies('company_id')
                ->get();

            $minWageRates = MinimumWage::latestItemsKeyedByState($endDate);



            
            foreach ($networkPayroll as &$check) {
                $rate = $check->employee->employeeRates->where('role_id', $check->role_id)->where('company_id', $check->company_id)->first();
                if(!$rate){
                    $rate = $check->employee->employeeRatesRequests->where('role_id', $check->role_id)->first();
                }
                if($rate && $rate->rate_type == 'Payroll 1099'){
                    $check->employee_rate = $rate->payroll_rate;
                    $check->payroll_type = $rate->payroll_type;
                }else if($rate && ($rate->rate_type == 'Payroll Slab' || $rate->rate_type == 'Payroll Regular' || $rate->rate_type == 'Payroll 1099')){
                    $check->employee_rate = $rate ?$rate->rate : 0;
                    $check->payroll_type = $rate->payroll_type;
                }

                // if($check->employee->pos_name == 'MEHMET ACAR'){
                //     dd($rate);
                // }
                $minWage = isset($minWageRates[$check->company->state_id]) ? $minWageRates[$check->company->state_id] : null;
                if($check->role->tipped){
                    $check->min_wage_rate = isset($minWage->tipped_minimum_wage) ? $minWage->tipped_minimum_wage : 0;
                }else{
                    $check->min_wage_rate = isset($minWage->minimum_wage) ? $minWage->minimum_wage : 0;
                }

                if($check->total_hours > 0){
                    $check->min_wage_weekly =  $check->min_wage_rate * ($check->total_hours ?? 0);
                    $check->min_wage_hourly =  $check->min_wage_rate;
                    if($check->total_earnings < $check->min_wage_weekly){
                        $check->min_wage_due = $check->min_wage_weekly - $check->total_earnings;
                    }else{
                        $check->min_wage_due = 0;
                    }
                }else{
                    $check->min_wage_due = 0;
                }

                $check->gross_pay = $check->amount;

                $check->total_earnings = $check->gross_pay +$check->tips_due + $check->mileage_due + $check->mwa_amount;

              

                unset($check->employee->employeeRates);
            }
            
            
            return response()->json([
                'data' => $networkPayroll,
            ]);
            
        } catch (\Exception $e) {
            dd($e);
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }
    
    public function reviewPayroll(Request $request)
    {
        $this->authorize('access', 'payroll-report.update');
        try {
            $request->validate([
                'check_data' => 'required|array',
                'check_data.*.employee_id' => 'required|integer',
                'check_data.*.role_id' => 'required|integer',
                'check_data.*.eow' => 'required|date',
                'check_data.*.amount' => 'required|numeric',
                'check_data.*.payroll_amount' => 'required|numeric',
                'check_data.*.instant_amount' => 'required|numeric',
                'check_data.*.is_1099' => 'required|boolean',
                'check_data.*.company_id' => 'required|integer',
            ]);
    
            $company = $request->session()->get('company');
            $checkData = $request->input('check_data');
    
            DB::beginTransaction();
    
            $eows = collect($checkData)
                    ->pluck('eow')
                    ->unique()
                    ->values();
            
            $companies = array_unique(array_column($checkData, 'company_id'));
            PayrollCheckAmount::whereIn('eow', $eows)->whereIn('company_id', $companies)->delete();

            $inputData = [];
            $userId = Auth::user()->id;
            $now = now();
            foreach ($checkData as $key => $item) {
                $inputData[] = [
                    'employee_id' => $item['employee_id'],
                    'company_id' => session('company'),
                    'role_id' => $item['role_id'],
                    'eow' => $item['eow'],
                    'amount' => $item['amount'],
                    'payroll_amount' => $item['payroll_amount'],
                    'instant_amount' => $item['instant_amount'],    
                    'is_1099' => $item['is_1099'],
                    'review_by' => $userId,
                    'reviewed_at' => $now,
                ];
                unset($checkData[$key]['is_1099']);
            }


            ActivityLogService::logReview('payroll_check_amounts', null, null, $inputData, "Payroll reviewed for eows: " . implode(', ', $eows->toArray()));
            $userId = Auth::user()->id;
            foreach ($checkData as &$item) {
                $item['company_id'] = $company;
                $item['review_by'] = $userId;
                $item['reviewed_at'] = $now;
                $item['created_at'] = $now;
                $item['updated_at'] = $now;

                // $item['created_by'] = $userId;
                // $item['updated_by'] = $userId;
            }
    
            PayrollCheckAmount::insert($checkData);
            DB::commit();
    
            return response()->json([
                'saved' => true,
                'message' => 'Payroll reviewed successfully',
            ]);
    
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Payroll review failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    public function getLabourReport(Request $request)
    {
        $this->authorize('access', 'payroll-labour-report.index');
        try {
            $networkSummary = $this->companyWisePayroll($request->start_date, $request->end_date);
            return response()->json([
                'data' => $networkSummary,
            ]);
           } catch (\Exception $e) {
            dd($e);
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    public function getNetworkCheckReport(Request $request)
    {
        $this->authorize('access', 'network-check-report.index');
        if(isDCWorkgroup()){
            return response()->json([
                'message' => 'unauthorized access',
            ],401);
        }
        try {
            $startDate = Carbon::parse($request->start_date);
            $endDate = Carbon::parse($request->end_date);
            $checkDate = $endDate->copy()->addDays(5)->format('Y-m-d');
            
            $networkCheckAmounts = EmployeeWeeklySummary::whereBetween('eow', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
                ->where('check_methods', '>', 0)
                ->groupBy('employee_id', 'company_id')
                ->selectRaw('employee_id, company_id, sum(check_methods) as amount')
                ->with('employee', 'company')
                ->authorizedCompanies('company_id')
                ->get()->toArray();

            $instantAmounts = EmployeeWeeklySummary::whereBetween('eow', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
                ->where('instant_methods', '>', 0)
                ->whereHas('employee', function($query){
                    $query->whereHas('employeeRates', function($query){
                        $query->where('rate_type', '1099 1099');
                    });
                })
                ->groupBy('employee_id', 'company_id')
                ->selectRaw('employee_id, company_id, sum(instant_methods) as amount, true as is_1099')
                ->with('employee', 'company')
                ->authorizedCompanies('company_id')
                ->get()->toArray();
                


            $checkAmounts = PayrollCheckAmount::whereBetween('eow', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
                            ->groupBy('employee_id', 'company_id')
                            ->selectRaw('employee_id, company_id, sum(amount) as amount')
                            ->get()
                            ->keyBy(function($item) {
                                return $item->employee_id .'|'.$item->company_id ;
                            });
                            

            $checMaster = CheckMaster::with('ledger','fromCompany','company','amReviewer','hrReviewer','adminReviewer')->where('check_type', 'check')->where('payroll_eow', $endDate->format('Y-m-d'))->get()->keyBy( function($item) {
                return $item->employee_id.'|'.$item->company_id .'|'.$item->is_1099;
            });
            $lastCheck = CheckMaster::groupBy('company_id','ledger_id')->selectRaw('company_id, ledger_id, max(check_number) as last_check_number')->get()->keyBy( function($item) {
                return $item->company_id.'|'.$item->ledger_id;
            });
            $defaultBank = LedgerDetails::with('ledger')->where('default_bank', true)->get()->keyBy('company_id');

            foreach ($instantAmounts as $key => $instant) {
                $instantAmounts[$key]['is_1099'] = true;
            }

            $networkCheckAmounts = array_merge($networkCheckAmounts, $instantAmounts);
            $nextCheckNumber=[];


            foreach ($networkCheckAmounts as $key => $check) {
                $checkKey =  $check['employee_id'].'|'.$check['company_id']  ?? 0;
                if(isset($checkAmounts[$checkKey])){
                    $networkCheckAmounts[$key]['check_edited'] = abs((float)($checkAmounts[$checkKey]['check_amount'] - (float)($check['amount']))) > 0.1 ? true : false;
                    $networkCheckAmounts[$key]['amount'] = $checkAmounts[$checkKey]['amount'] ?? 0;
                }
                $checkKey = $checkKey.'|'.($check['is_1099'] ?? 0);
                if(isset($checMaster[$checkKey])){
                    $networkCheckAmounts[$key]['ledger'] = $checMaster[$checkKey]['ledger'];
                    $networkCheckAmounts[$key]['fromCompany'] = $checMaster[$checkKey]['fromCompany'];
                    $networkCheckAmounts[$key]['company'] = $checMaster[$checkKey]['company'];
                    $networkCheckAmounts[$key]['check_number'] =  $checMaster[$checkKey]['check_number'];
                    $networkCheckAmounts[$key]['check_methods'] =  $checMaster[$checkKey]['amount'];
                    $networkCheckAmounts[$key]['check_date'] =  $checMaster[$checkKey]['check_date'];
                    $networkCheckAmounts[$key]['reviewed'] = true;
                    $networkCheckAmounts[$key]['check_id'] =  $checMaster[$checkKey]['id'];
                    $networkCheckAmounts[$key]['am_reviewed'] = $checMaster[$checkKey]['am_reviewed'];
                    $networkCheckAmounts[$key]['am_reviewed_at'] = $checMaster[$checkKey]['am_reviewed_at'];
                    $networkCheckAmounts[$key]['am_reviewed_by_name'] = $checMaster[$checkKey]['amReviewer']?->name;
                    $networkCheckAmounts[$key]['hr_reviewed'] = $checMaster[$checkKey]['hr_reviewed'];
                    $networkCheckAmounts[$key]['hr_reviewed_at'] = $checMaster[$checkKey]['hr_reviewed_at'];
                    $networkCheckAmounts[$key]['hr_reviewed_by_name'] = $checMaster[$checkKey]['hrReviewer']?->name;
                    $networkCheckAmounts[$key]['admin_reviewed'] = $checMaster[$checkKey]['admin_reviewed'];
                    $networkCheckAmounts[$key]['admin_reviewed_at'] = $checMaster[$checkKey]['admin_reviewed_at'];
                    $networkCheckAmounts[$key]['admin_reviewed_by_name'] = $checMaster[$checkKey]['adminReviewer']?->name;
                    $networkCheckAmounts[$key]['is_1099'] = $checMaster[$checkKey]['is_1099'];
                }else{
                    $companyId = $networkCheckAmounts[$key]['company_id'];
                    $bank      = $defaultBank[$companyId] ?? null;
                    
                    $networkCheckAmounts[$key]['ledger']      = $bank->ledger ?? null;
                    $ledgerDetails      = $bank;
                    $networkCheckAmounts[$key]['fromCompany'] = $networkCheckAmounts[$key]['company'];  
                    
                    $ledgerId = $networkCheckAmounts[$key]['ledger']?->id;
                    $ledgerKey = $ledgerId ? "{$companyId}|{$ledgerId}" : null;
                    
                    if ($ledgerId) {
                        if (isset($lastCheck[$ledgerKey])) {
                            $networkCheckAmounts[$key]['check_number'] =
                                $nextCheckNumber[$ledgerKey]
                                ?? ($lastCheck[$ledgerKey]['last_check_number'] + 1);
                        } else {
                            $networkCheckAmounts[$key]['check_number'] =
                                $nextCheckNumber[$ledgerKey]
                                ?? ($ledgerDetails->starting_check_number ?? 0);
                        }
                    
                        if ($networkCheckAmounts[$key]['check_number'] !== 0) {
                            $nextCheckNumber[$ledgerKey] = $networkCheckAmounts[$key]['check_number'] + 1;
                        }
                    } else {
                        $networkCheckAmounts[$key]['check_number'] = 0; 
                    }
                    // if($networkCheckAmounts[$key]['amount'] == 242.04){
                    //     dd($networkCheckAmounts[$key]);
                    // }
                    
                    $networkCheckAmounts[$key]['check_date'] = $checkDate;
                    $networkCheckAmounts[$key]['is_1099'] = $networkCheckAmounts[$key]['is_1099'] ?? false;
                    $networkCheckAmounts[$key]['reviewed'] = false;
                }
            }
            
            
            return response()->json([
                'data' => array_values($networkCheckAmounts),
            ]);
        } catch (\Exception $e) {
            dd($e);
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }


    public function getNetworkInstantReport(Request $request)
    {
        $this->authorize('access', 'network-instant-report.index');
        if(isDCWorkgroup()){
            return response()->json([
                'message' => 'unauthorized access',
            ],401);
        }
        try {
            $startDate = Carbon::parse($request->start_date);
            $endDate = Carbon::parse($request->end_date);
            
            $networkInstantAmounts = EmployeeWeeklySummary::whereBetween('eow', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
                ->groupBy('employee_id', 'company_id')
                ->selectRaw('employee_id, company_id, sum(instant_methods) as amount')
                ->whereExists(function ($query) {
                    $query->select(DB::raw(1))
                        ->from('employee_rates as er')
                        ->whereColumn('er.employee_id', 'employee_weekly_summary.employee_id')
                        ->whereColumn('er.company_id', 'employee_weekly_summary.company_id')
                        ->whereColumn('er.role_id', 'employee_weekly_summary.role_id')
            
                        ->whereRaw('er.effective_date = (
                            SELECT MAX(effective_date)
                            FROM employee_rates
                            WHERE employee_id = er.employee_id
                              AND company_id = er.company_id
                              AND role_id = er.role_id
                        )')
            
                        ->where('er.rate_type', '!=', '1099 1099');
                })
                ->with('employee', 'company')
                ->authorizedCompanies('company_id')
                ->get();

            
            $checkAmounts = PayrollCheckAmount::whereBetween('eow', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
                ->groupBy('employee_id', 'company_id')
                ->whereExists(function ($query) {
                    $query->select(DB::raw(1))
                        ->from('employee_rates as er')
                        ->whereColumn('er.employee_id', 'payroll_check_amounts.employee_id')
                        ->whereColumn('er.company_id', 'payroll_check_amounts.company_id')
                        ->whereColumn('er.role_id', 'payroll_check_amounts.role_id')
                    
                        ->whereRaw('er.effective_date = (
                            SELECT MAX(effective_date)
                            FROM employee_rates
                            WHERE employee_id = er.employee_id
                            AND company_id = er.company_id
                            AND role_id = er.role_id
                        )')
                    
                        ->where('er.rate_type', '!=', '1099 1099');
                })
                ->selectRaw('employee_id, company_id, sum(instant_amount) as amount')
                ->get()
                ->keyBy(function($item) {
                    return $item->employee_id .'|'.$item->company_id;
                });
                foreach ($networkInstantAmounts as $instant) {
                    $checkKey =  $instant->employee_id.'|'.$instant->company_id;
                    if(isset($checkAmounts[$checkKey])){
                        $instant->amount = $checkAmounts[$checkKey]->amount ?? 0;
                        $instant->check_edited = abs((float)($instant->check_amount - (float)($instant->amount))) > 0.1 ? true : false;
                    }
                }

            $networkInstantAmounts = array_filter($networkInstantAmounts->toArray(), function($item) {
                return $item['amount'] > 0;
            });
            
            return response()->json([
                'data' => array_values($networkInstantAmounts),
            ]);
        } catch (\Exception $e) {
            dd($e);
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }
   
    public function companyWisePayroll($start_date, $end_date, $company_ids = null)
    {
        $minWages = MinimumWage::latestItemsKeyedByState($start_date);
        
        $startDate = Carbon::parse($start_date);
        $endDate = Carbon::parse($end_date);
        $checkAmounts = PayrollCheckAmount::whereBetween('eow', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
            ->when($company_ids, function ($query) use ($company_ids) {
                return $query->whereIn('company_id', $company_ids);
            })
            ->authorizedCompanies('company_id')
            ->get()
            ->keyBy(function($item) {
                return $item->employee_id . '-' . $item->role_id . '-' . $item->eow . '-' . $item->company_id;
            });
        
        // Fetch pre-calculated weekly summaries for all companies
        $weeklySummaries = EmployeeWeeklySummary::whereBetween('eow', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
            ->with(['employee', 'role', 'company:id,state_id'])
            ->when($company_ids, function ($query) use ($company_ids) {
                return $query->whereIn('company_id', $company_ids);
            })
            ->authorizedCompanies('company_id')
            ->get();
        
        // Group by employee_id-role_id-company_id and aggregate all weeks
        $summariesByEmployee = [];
        foreach ($weeklySummaries as $summary) {
            $key = $summary->employee_id . '-' . $summary->role_id . '-' . $summary->company_id;
            if (!isset($summariesByEmployee[$key])) {
                $summariesByEmployee[$key] = [
                    'weeks' => [],
                    'employee' => $summary->employee,
                    'role' => $summary->role,
                    'company' => $summary->company,
                    'company_id' => $summary->company_id,
                ];
            }
            
            $summariesByEmployee[$key]['weeks'][] = $summary;
        }
        
        $array = [];
        foreach ($summariesByEmployee as $key => $data) {
            $weeks = $data['weeks'];
            
            if (empty($weeks)) continue;
            
            // Use first week to get base data
            $baseWeek = $weeks[0];

            
            // Aggregate data from all weeks
            $row = [
                'employee_id' => $baseWeek->employee_id,
                'role_id' => $baseWeek->role_id,
                'company_id' => $baseWeek->company_id,
                'employee' => $data['employee'],
                'role' => $data['role'],
                'company' => $data['company'],
                'regular_hours' => 0,
                'overtime_hours' => 0,
                'total_hours' => 0,
                'tips' => 0,
                'mwa_amount' => 0,
                'mileage_excess' => 0,
                'incentive' => 0,
                'bonus' => 0,
                'tips_due' => 0,
                'mileage_due' => 0,
                'payroll_methods' => 0,
                'check_methods' => 0,
                'instant_methods' => 0,
                'gross_pay' => 0,
                'total_earnings' => 0,
                'rate' => $baseWeek->rate,
                'rate_type' => $baseWeek->rate_type,
                'pay_type' => $baseWeek->pay_type,
            ];
            
            // Sum all weeks
            foreach ($weeks as $week) {
                $checkKey = $week->employee_id . '-' . $week->role_id . '-' . $week->eow . '-' . $week->company_id;
                if(isset($checkAmounts[$checkKey])){
                    $row['check_methods'] += $checkAmounts[$checkKey]->amount ?? 0;
                    $row['payroll_methods'] += $checkAmounts[$checkKey]->payroll_amount ?? 0;
                    $row['instant_methods'] += $checkAmounts[$checkKey]->instant_amount ?? 0;
                }else{
                    $row['payroll_methods'] += $week->payroll_methods ?? 0;
                    $row['check_methods'] += $week->check_methods ?? 0;
                    $row['instant_methods'] += $week->instant_methods ?? 0;
                }
                $row['total_earnings'] = $row['payroll_methods'] + $row['check_methods'] + $row['instant_methods'];
                $row['regular_hours'] += $week->regular_hours ?? 0;
                $row['overtime_hours'] += $week->overtime_hours ?? 0;
                $row['total_hours'] += $week->total_hours ?? 0;
                $row['tips'] += $week->tips ?? 0;
                $row['mwa_amount'] += $week->mwa_amount ?? 0;
                $row['mileage_excess'] += $week->mileage_excess ?? 0;
                $row['incentive'] += $week->incentive ?? 0;
                $row['bonus'] += $week->bonus ?? 0;
                $row['tips_due'] += $week->tips_due ?? 0;
                $row['mileage_due'] += $week->mileage_due ?? 0;
                // $row['payroll_methods'] += $week->payroll_methods ?? 0;
                // $row['check_methods'] += $week->check_methods ?? 0;
                // $row['instant_methods'] += $week->instant_methods ?? 0;
                $row['gross_pay'] += $week->gross_pay ?? 0;
                // $row['total_earnings'] += $week->total_earnings ?? 0;
            }
            
            $row['hr_pay'] = ($row['gross_pay'] + $row['tips'] + $row['mwa_amount']) / ($row['total_hours'] == 0 ? 1 : $row['total_hours']);
            $row['week_period'] = $startDate->format('m/d/Y') . ' - ' . $endDate->format('m/d/Y');
            
            $minWage = isset($minWages[$data['company']->state_id]) ? $minWages[$data['company']->state_id] : null;
            $row['min_wage_rate'] = isset($minWage->minimum_wage) ? $minWage->minimum_wage : 0;

            
            if (isset($minWage) && ($baseWeek->rate_type == 'Payroll Regular' || $baseWeek->rate_type == 'Payroll Slab')) {
                $row['min_wage_weekly'] = $minWage->minimum_wage * ($row['total_hours'] ?? 0);
                $row['min_wage_hourly'] = $minWage->minimum_wage;
                if ($row['payroll_methods'] < $row['min_wage_weekly']) {
                    $row['min_wage_due'] = $row['min_wage_weekly'] - $row['payroll_methods'];
                } else {
                    $row['min_wage_due'] = 0;
                }
            }
            
            $row['employee_rate'] = [
                'rate' => $baseWeek->rate,
                'rate_type' => $baseWeek->rate_type,
            ];

            $key = $row['employee_id'] . '-' . $row['company_id'];

            if($row['total_hours'] == 0 && $row['tips'] == 0 && $row['mileage_excess'] == 0 && $row['incentive'] == 0 && $row['bonus'] == 0 && $row['tips_due'] == 0 && $row['mileage_due'] == 0 && $row['payroll_methods'] == 0 && $row['check_methods'] == 0 && $row['instant_methods'] == 0){
                continue;
            }
            if (!isset($array[$key])) {
                $array[$key] = $row;
                $array[$key]['roles'] = [];
                
                if ($data['role']->code == 'DR') {
                    $array[$key]['roles'][$data['role']->name] = [
                        'regular_hours' => $row['regular_hours'],
                        'rate' => $baseWeek->rate,
                        'overtime_hours' => $row['overtime_hours'],
                    ];
                    $array[$key]['rate'] = 0;
                    $array[$key]['regular_hours'] = 0;
                    $array[$key]['overtime_hours'] = 0;
                    $array[$key]['missing_data'] = true;
                }else{
                    $array[$key]['primary_role'] = $data['role']->name;
                }
            } else {
                if (isset($array[$key]['missing_data']) && $array[$key]['missing_data']) {
                    $array[$key]['regular_hours'] += $row['regular_hours'];
                    $array[$key]['overtime_hours'] += $row['overtime_hours'];
                    $array[$key]['rate'] = $baseWeek->rate;
                    $array[$key]['primary_role'] = $data['role']->name;
                } else {
                    if (!isset($array[$key]['primary_role'])) {
                        $array[$key]['primary_role'] = $data['role']->name;
                    }
                    if ($array[$key]['primary_role'] == $data['role']->name) {
                        $array[$key]['regular_hours'] += $row['regular_hours'];
                        $array[$key]['overtime_hours'] += $row['overtime_hours'];
                    } else if (isset($array[$key]['roles'][$data['role']->name])) {
                        $array[$key]['roles'][$data['role']->name]['regular_hours'] += $row['regular_hours'];
                        $array[$key]['roles'][$data['role']->name]['overtime_hours'] += $row['overtime_hours'];
                    } else {
                        $array[$key]['roles'][$data['role']->name] = [
                            'regular_hours' => $row['regular_hours'],
                            'rate' => $baseWeek->rate,
                            'overtime_hours' => $row['overtime_hours'],
                        ];
                    }
                }
                $array[$key]['total_hours'] += $row['total_hours'];
                $array[$key]['gross_pay'] += $row['gross_pay'];
                $array[$key]['tips_due'] += $row['tips_due'];
                $array[$key]['tips'] += $row['tips'];
                $array[$key]['mwa_amount'] += $row['mwa_amount'];
                $array[$key]['mileage_due'] += $row['mileage_due'];
                $array[$key]['total_earnings'] += $row['total_earnings'];
                $array[$key]['payroll_methods'] += $row['payroll_methods'];
                $array[$key]['check_methods'] += $row['check_methods'];
                $array[$key]['instant_methods'] += $row['instant_methods'];
                $array[$key]['hr_pay'] = ($array[$key]['gross_pay'] + $array[$key]['tips'] + $array[$key]['mwa_amount']) / ($array[$key]['total_hours'] == 0 ? 1 : $array[$key]['total_hours']);
            }
        }
        
        $companies = Company::select('id', 'name', 'store_number', 'contact_person')->when($company_ids, function ($query) use ($company_ids) {
            return $query->whereIn('id', $company_ids);
        })->authorizedCompanies('id')->get();
        foreach ($companies as $company) {
            $company['labour_data'] = array_values(array_filter($array, function ($item) use ($company) {
                return $item['company_id'] == $company->id;
            }));
        }
        return $companies;
    }

    public function getDtmReport(Request $request)
    {

        $this->authorize('access', 'dtm-report.index');
        try {
            $startDate = Carbon::parse($request->start_date)->startOfDay();
            $endDate = Carbon::parse($request->end_date)->endOfDay();

            $companyDetails = Company::select('id', 'name', 'store_number')
                ->authorizedCompanies('id')
                ->get()
                ->keyBy('id');

            $driverRows = Drivers::query()
                ->whereBetween('date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
                ->authorizedCompanies('company_id')
                ->selectRaw('
                    company_id,
                    employee_id,
                    driver_name,
                    SUM(road_hours) as road_hr,
                    SUM(cash_tips) as cash_tips,
                    SUM(cc_tips) as cc_tips,
                    SUM(mileage) as mileage,
                    SUM(delivery) as delivery,
                    SUM(on_road_pay) as driver_on_road_pay
                ')
                ->groupBy('company_id', 'employee_id', 'driver_name')
                ->with('employee:id,employee_id,pos_name')
                ->get();

            $idealCost = IdealCost::join('ideal_cost_items', 'ideal_cost.id', '=', 'ideal_cost_items.ideal_cost_id')
                ->whereBetween('ideal_cost.date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
                ->groupBy('ideal_cost_items.company_id')
                ->selectRaw('ideal_cost_items.company_id, SUM(ideal_cost_items.ideal_cost) as total_ideal_cost, SUM(ideal_cost_items.mileage) as total_mileage, SUM(ideal_cost_items.delivery) as total_delivery')
                ->get()
                ->keyBy('company_id');


            $companies = [];
            foreach ($driverRows as $row) {
                $companyId = $row->company_id;
                if (!isset($companyDetails[$companyId])) {
                    continue;
                }

                if (!isset($companies[$companyId])) {
                    $companies[$companyId] = [
                        'id' => $companyId,
                        'name' =>  $companyDetails[$companyId]->store_number.' - '.$companyDetails[$companyId]->name ,
                        'rows' => [],
                    ];
                }
               

                $roadHr = (float) ($row->road_hr ?? 0);
                $cashTips = (float) ($row->cash_tips ?? 0);
                $ccTips = (float) ($row->cc_tips ?? 0);
                $totalTips = $cashTips + $ccTips;
                $mileage = (float) ($row->mileage ?? 0);
                $totalTm = $totalTips + $mileage;
                $driverPay = (float) ($row->driver_on_road_pay ?? 0);
                $totalPay = $driverPay + $totalTm;
                $delivery = (float) ($row->delivery ?? 0);

                if(isset($idealCost[$companyId]) && isset( $companies[$companyId]['delivery_ideal_cost']) ){
                    $companies[$companyId]['total_delivery'] +=  $delivery;
                    $companies[$companyId]['total_milage'] += $mileage;
                }else{
                    $companies[$companyId]['delivery_ideal_cost'] = $idealCost[$companyId]->total_delivery ; 
                    $companies[$companyId]['total_delivery'] = $idealCost[$companyId]->total_delivery + $delivery; 
                    $companies[$companyId]['mileage_ideal_cost'] = $idealCost[$companyId]->total_mileage;

                    $companies[$companyId]['ddd_fee'] = $idealCost[$companyId]->total_delivery > 0 ? ($idealCost[$companyId]->total_mileage / $idealCost[$companyId]->total_delivery) :$idealCost[$companyId]->total_mileage;
                    $companies[$companyId]['total_milage'] = $idealCost[$companyId]->total_mileage + $mileage;
                }

                $companies[$companyId]['rows'][] = [
                    'employee_id' => $row->employee_id,
                    'driver_id' => $row->employee?->employee_id ?? $row->employee_id,
                    'driver_name' => $row->employee?->pos_name ?? $row->driver_name,
                    'road_hr' => $roadHr,
                    'cash_tips' => $cashTips,
                    'cc_tips' => $ccTips,
                    'total_tips' => $totalTips,
                    'mileage' => $mileage,
                    'total_tm' => $totalTm,
                    'avg_drv_pay' => $roadHr > 0 ? ($driverPay / $roadHr) : 0,
                    'drv_pay' => $driverPay,
                    'total_pay' => $totalPay,
                    'avg_pay' => $roadHr > 0 ? ($totalPay / $roadHr) : 0,
                    'delivery' => $delivery,
                    'delivery_mileage' => $delivery > 0 ? ($mileage / $delivery) : 0,
                    'cpd' => $delivery > 0 ? ($totalPay / $delivery) : 0,
                ];
            }

            return response()->json([
                'data' => array_values($companies),
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function getPayrollJournalReport(Request $request)
    {
        $this->authorize('access', 'payroll-journal-report.index');

        if (!isDCWorkgroup()) {
            return response()->json([
                'message' => 'unauthorized access',
            ], 401);
        }

        $validated = $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date',
        ]);

        $startDate = Carbon::parse($validated['start_date'])->format('Y-m-d');
        $endDate = Carbon::parse($validated['end_date'])->format('Y-m-d');

        $detailRows = PayrollJournal::query()
            ->whereBetween('eow', [$startDate, $endDate])
            ->authorizedCompanies('company_id')
            ->with('company')
            ->orderBy('eow')
            ->orderBy('company_id')
            ->selectRaw('eow, company_id, total_earnings, er_withholdings, charge_tips_reimb, mileage_reimb, total_earnings + er_withholdings - charge_tips_reimb - mileage_reimb as ctc')
            ->get();

        $rows=[];
        foreach ($detailRows as $row) {
            $rows[] = array_merge($row->toArray(), [
                'company_name' => $row->company?->name ?? '-',
            ]);
        }

        return response()->json([
            'data' => $rows,
        ]);
    }

    public function getMWAReport(Request $request)
    {
        $this->authorize('access', 'mwa-report.index');
        try {
            // Parse dates
            $startDate = Carbon::parse($request->start_date);
            $endDate = Carbon::parse($request->end_date);

            // Fetch pre-calculated weekly summaries for the date range
            $weeklySummaries = EmployeeWeeklySummary::whereBetween('eow', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
                ->with('employee', 'employee.employeeRates', 'employee.employeeRatesRequests', 'role', 'company')
                ->authorizedCompanies('company_id')
                ->get();
            $minWageRates = MinimumWage::latestItemsKeyedByState($endDate);
            
            // Group by employee and company, sum min_wage_due
            $array = [];
            foreach ($weeklySummaries as $summary) {
                // Skip if no hours and no earnings
                if($summary->total_hours == 0 && $summary->payroll_methods == 0 && $summary->check_methods == 0 && $summary->instant_methods == 0 && $summary->pay_type == 'HR' && $summary->tips == 0 && $summary->mileage_excess == 0 && $summary->incentive == 0 && $summary->bonus == 0 && $summary->tips_due == 0 && $summary->mileage_due == 0) {
                    continue;
                }
                
                $minWage = isset($minWageRates[$summary->company->state_id]) ? $minWageRates[$summary->company->state_id] : null;
                
                // Calculate min wage due
                $summary->min_wage_rate = isset($minWage->minimum_wage) ? $minWage->minimum_wage : 0;
                $summary->hr_pay = ($summary->gross_pay + $summary->tips + $summary->mwa_amount) / ($summary->total_hours == 0 ? 1 : $summary->total_hours);
                
                $rate = $summary->employee->employeeRates->where('role_id', $summary->role_id)->where('company_id', $summary->company_id)->first();
                if(!$rate){
                    $rate = $summary->employee->employeeRatesRequests->where('role_id', $summary->role_id)->where('company_id', $summary->company_id)->first();
                }

                $min_wage_due = 0;
                if (isset($minWage) && isset($rate) && $rate->pay_type == 'HR' && ($rate->rate_type == 'Payroll Regular' || $rate->rate_type == 'Payroll Slab')) {
                    $summary->min_wage_weekly = $summary->min_wage_rate * ($summary->total_hours ?? 0);
                    $summary->min_wage_hourly = $summary->min_wage_rate;
                    if ($summary->hr_pay * ($summary->total_hours ?? 0) < $summary->min_wage_weekly) {
                        $min_wage_due = $summary->min_wage_weekly - $summary->hr_pay * ($summary->total_hours ?? 0);
                    }
                }
                
                // Only include if min_wage_due > 0
                if ($min_wage_due > 0.01) {
                    $key = $summary->employee_id . '-' . $summary->company_id .'-'.$summary->role_id.'-'.$summary->eow;
                    
                    if (!isset($array[$key])) {
                        $array[$key] = [
                            'employee_id' => $summary->employee_id,
                            'company_id' => $summary->company_id,
                            'role_id' => $summary->role_id,
                            'eow' => $summary->eow,
                            'employee' => $summary->employee,
                            'company' => $summary->company,
                            'role' => $summary->role,
                            'min_wage_due' => 0
                        ];
                    }
                    
                    $array[$key]['min_wage_due'] += $min_wage_due;
                }
            }
            
            return response()->json([
                'data' => array_values($array),
            ]);
            
        } catch (\Exception $e) {
            Log::error('MWA Report Error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    public function updateMWA(Request $request)
    {
        try {
            $request->validate([
                'selected_items' => 'required|array',
                'selected_items.*.employee_id' => 'required|exists:employee,id',
                'selected_items.*.company_id' => 'required|exists:company,id',
                'selected_items.*.role_id' => 'required|exists:employee_roles,id',
                'selected_items.*.eow' => 'required|date',
                'selected_items.*.amount' => 'required|numeric|min:0',
            ]);

            DB::beginTransaction();

            $dates = array_values(collect($request->selected_items)->pluck('eow')->unique()->sort()->toArray());
            $mwa = MWA::whereIn('eow', $dates)->get()->keyBy(function($item) {
                return $item->employee_id . '-' . $item->company_id . '-' . $item->role_id . '-' . $item->eow;
            });

            $mwaData = [];  
            $now = now();
            $userId = Auth::user()->id;
            foreach ($request->selected_items as $item) {
                $mwaKey = $item['employee_id'] . '-' . $item['company_id'] . '-' . $item['role_id'] . '-' . $item['eow'];
                if(isset($mwa[$mwaKey])){
                    $mwa[$mwaKey]->amount = $item['amount'] + $mwa[$mwaKey]->amount;
                    $mwa[$mwaKey]->save();
                }else{
                    $mwaData[] = [
                        'employee_id' => $item['employee_id'],
                        'company_id' => $item['company_id'],
                        'role_id' => $item['role_id'],
                        'eow' => $item['eow'],
                        'amount' => $item['amount'],
                        'created_by' => $userId,
                        'updated_by' => $userId,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }
            }
            MWA::insert($mwaData);
            ActivityLogService::logReview('mwa', null, null, $mwaData, "MWA data updated for eows: " . implode(', ', $dates));
            WeeklySummaryRecalculationService::recalculateForEmployee(['start_date' => $dates[0], 'end_date' => $dates[count($dates) - 1], 'mwa_data' => $mwaData]);

            DB::commit();

            return response()->json([
                'message' => 'MWA data updated successfully',
                'count' => count($request->selected_items)
            ]);

        } catch (\Exception $e) {
            dd($e);
            DB::rollBack();
            Log::error('MWA Update Error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }
    
}