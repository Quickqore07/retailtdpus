<?php

namespace App\Http\Controllers;

use App\Models\Payroll\EmployeeHours;
use App\Models\Payroll\PayrollCheckAmount;
use App\Models\Settings\Company;
use App\Models\Settings\MinimumWage;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
class PayrollReportController extends Controller
{
    public function getWeeklyPayrollReport(Request $request)
    {
        try {
            $company = $request->session()->get('company');
            $employeeHoursFirstWeek = EmployeeHours::where('company_id', $company)
                ->whereBetween('date', [$request->start_date, Carbon::parse($request->start_date)->addDays(7)])
                ->groupBy('employee_id','role_id','pay_type')
                ->selectRaw('employee_id, role_id, pay_type, sum(total_hours) as total_hours, sum(tips) as tips, sum(mileage_excess) as mileage_excess, sum(incentive) as incentive, sum(bonus) as bonus, sum(tips_due) as tips_due, sum(mileage_due) as mileage_due')
                ->with('employee','role','employee.employeeRates')
                ->get();
            $min_wage_review = false;

            $companyState = Company::where('id', $company)->select('state_id')->first();
            $firstWeekMinWage = MinimumWage::where('state_id', $companyState->state_id)->where('effective_date', '<=', Carbon::parse($request->start_date)->addDays(7))->orderBy('effective_date', 'desc')->first();
            $secondWeekMinWage = MinimumWage::where('state_id', $companyState->state_id)->where('effective_date', '<=', Carbon::parse($request->end_date))->orderBy('effective_date', 'desc')->first();

            foreach ($employeeHoursFirstWeek as $row) {

                $rate = $row->employee->employeeRates
                            ->where('role_id', $row->role_id)
                            ->select('rate','payroll_hours', 'pay_type','slab_first_hours','slab_rest_rate','check_payment_type','check_payment_amount','rate_type')
                            ->first();
                if(!$rate){
                    continue;
                }

                            
                $this->calculatePayrollMethods($row, $rate,1);
                $row['employee_rate'] = $rate;  
                $row['gross_pay'] = $row['payroll_methods'] + $row['check_methods'] + $row['instant_methods'] - $row['tips_due'] - $row['mileage_due'];
                $row['total_earnings'] = ((float) ($row['gross_pay'] ?? 0) +  (float) ($row['bonus'] ?? 0) + (float) ($row['tips_due'] ?? 0) + (float) ($row['mileage_due'] ?? 0));
                $row['hr_pay'] = $row['total_earnings']  / ($row['total_hours'] ==0 ? 1 : $row['total_hours']);
                $row['week_period'] = Carbon::parse($request->start_date)->format('m/d/Y') . ' - ' . Carbon::parse($request->start_date)->addDays(7)->format('m/d/Y');

                if( isset($firstWeekMinWage) && isset($firstWeekMinWage->minimum_wage) && ($rate['rate_type'] == 'Payroll Regular' || $rate['rate_type'] == 'Payroll Slab')){
                    $row['min_wage_weekly'] = $firstWeekMinWage->minimum_wage * ($row['total_hours'] ?? 0);
                    $row['min_wage_hourly'] = $firstWeekMinWage->minimum_wage;
                    if($row['payroll_methods'] < $row['min_wage_weekly']){
                        $row['min_wage_due'] = $row['min_wage_weekly'] - $row['payroll_methods'];
                        $min_wage_review = true;
                    }else{
                        $row['min_wage_due'] = 0;
                    }
                }
                
                unset($row['employee']['employeeRates']);
            }

            
            $employeeHoursSecondWeek = EmployeeHours::where('company_id', $company)
                ->whereBetween('date', [Carbon::parse($request->start_date)->addDays(8), Carbon::parse($request->end_date)])
                ->groupBy('employee_id','role_id','pay_type')
                ->selectRaw('employee_id, role_id, pay_type, sum(total_hours) as total_hours, sum(tips) as tips, sum(mileage_excess) as mileage_excess, sum(incentive) as incentive, sum(bonus) as bonus, sum(tips_due) as tips_due, sum(mileage_due) as mileage_due')
                ->with('employee','role','employee.employeeRates')
                ->get();

            foreach ($employeeHoursSecondWeek as $row) {
                $rate = $row->employee->employeeRates
                    ->where('role_id', $row->role_id)
                    ->select('rate','payroll_hours', 'pay_type','slab_first_hours','slab_rest_rate','check_payment_type','check_payment_amount','rate_type')
                    ->first();
                    
                if(!$rate){
                    continue;
                }
    
                $this->calculatePayrollMethods($row, $rate,1);
                $row['employee_rate'] = $rate;  
                $row['gross_pay'] = $row['payroll_methods'] + $row['check_methods'] + $row['instant_methods'] - $row['tips_due'] - $row['mileage_due'];
                $row['total_earnings'] = (float) ($row['gross_pay'] ?? 0) +  (float) ($row['bonus'] ?? 0) + (float) ($row['tips_due'] ?? 0) + (float) ($row['mileage_due'] ?? 0);
                $row['hr_pay'] = $row['total_earnings']  / ($row['total_hours'] ==0 ? 1 : $row['total_hours']);
                $row['week_period'] = Carbon::parse($request->start_date)->addDays(8)->format('m/d/Y') . ' - ' . Carbon::parse($request->end_date)->format('m/d/Y');
                if(isset($secondWeekMinWage) && isset($secondWeekMinWage->minimum_wage) && ($rate['rate_type'] == 'Payroll Regular' || $rate['rate_type'] == 'Payroll Slab')){
                    $row['min_wage_weekly'] = $secondWeekMinWage->minimum_wage * ($row['total_hours'] ?? 0);
                    $row['min_wage_hourly'] = $secondWeekMinWage->minimum_wage;

                    if($row['payroll_methods'] < $row['min_wage_weekly']){
                        $row['min_wage_due'] = $row['min_wage_weekly'] - $row['payroll_methods'];
                        $min_wage_review = true;
                    }else{
                        $row['min_wage_due'] = 0;
                    }
                }
                unset($row['employee']['employeeRates']);
            }


            $array = [];
            foreach ($employeeHoursFirstWeek as $row) {
                $array[$row->employee_id . '-' . $row->role_id . '-' . $row->pay_type] = [$row];
            }
            foreach ($employeeHoursSecondWeek as $row) {
                $array[$row->employee_id . '-' . $row->role_id . '-' . $row->pay_type][] = $row;
            }
            
            // Get check amounts for the period
            $checkAmounts = PayrollCheckAmount::where('company_id', $company)->whereBetween('eow', [Carbon::parse($request->start_date), Carbon::parse($request->end_date)])
            ->get()
            ->keyBy(function($item) {
                return $item->employee_id . '-' . $item->role_id . '-' . $item->eow;
            });
            $reviewed = count($checkAmounts) > 0 ? true : false;
            // Add check amounts to the data
            foreach ($array as &$records) {
                foreach ($records as &$record) {
                    if(!$record->week_period) continue;
                    $eow = Carbon::createFromFormat('m/d/Y', explode(' - ', $record->week_period)[1]);
                    $key = $record->employee_id . '-' . $record->role_id . '-' . $eow->format('Y-m-d');
                    if($checkAmounts->get($key)){
                        $record['check_edited'] = abs((float)($checkAmounts->get($key)?->amount ?? 0) - (float)($record['check_methods'] ?? 0)) > 0.1 ? true : false;
                        
                        $record['check_methods'] = $checkAmounts->get($key)?->amount ?? $record['check_methods'];
                        $record['payroll_methods'] = $checkAmounts->get($key)?->payroll_amount ?? $record['payroll_methods'];
                        $record['check_amount_id'] = $checkAmounts->get($key)?->id ?? null;
                        $instant_methods = $record['total_earnings'] - $record['payroll_methods'] - $record['check_methods'];
                        $record['instant_methods'] = $instant_methods > 0 ? $instant_methods : 0;
                    }
                }
            }
            
            return response()->json([
                'data' => array_values($array),
                'reviewed' => $reviewed ? true : false,
                'min_wage_review' => $min_wage_review ? true : false,
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
            ]);
    
            $company = $request->session()->get('company');
    
            $checkData = $request->input('check_data');
    
            $eows = collect($checkData)
                    ->pluck('eow')
                    ->unique()
                    ->values();

            PayrollCheckAmount::whereIn('eow', $eows)->delete();
    
            foreach ($checkData as &$item) {
                $item['company_id'] = $company;
            }
    
            PayrollCheckAmount::insert($checkData);
    
            return response()->json([
                'saved' => true,
                'message' => 'Payroll reviewed successfully',
            ]);
        } catch (\Exception $e) {
            dd($e);
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }
    
    private function calculatePayrollMethods(&$row, $rate){
        if($rate['rate_type'] == 'Payroll Regular'){

            if($rate['pay_type'] == 'WK'){
                $row['regular_hours'] = $row['total_hours'];
                $row['overtime_hours'] = 0;

            }else{
                $row['overtime_hours'] = (($row['total_hours'] ?? 0) - 40) > 0 ? (($row['total_hours'] ?? 0) - 40) : 0;
                $row['regular_hours'] = (($row['total_hours'] ?? 0) -  $row['overtime_hours']);
            }
            $normal_pay = $rate['pay_type'] == 'HR' ? $row['regular_hours'] * $rate['rate'] :  $rate['rate'] ;
            $overtime_pay = $rate['pay_type'] == 'HR' ? $row['overtime_hours'] * $rate['rate'] * 1.5 : 0;

            $row['payroll_methods'] = $normal_pay + $overtime_pay + $row['tips_due'] + $row['mileage_due'];
            $row['check_methods'] = 0;
            $row['instant_methods'] = 0;
        }else if($rate['rate_type'] == 'Payroll Slab'){
            if($rate['pay_type'] == 'WK'){
                $row['regular_hours'] = $row['total_hours'];
                $row['overtime_hours'] = 0;

            }else{
                $row['overtime_hours'] = (($row['total_hours'] ?? 0) - $rate['slab_first_hours']) > 0 ? (($row['total_hours'] ?? 0) - $rate['slab_first_hours']) : 0;
                $row['regular_hours'] = (($row['total_hours'] ?? 0) -  $row['overtime_hours']);
            }
            $normal_pay = $rate['pay_type'] == 'HR' ? $row['regular_hours'] * $rate['rate'] : ( $rate['rate']  );
            $overtime_pay = $rate['pay_type'] == 'HR' ? $row['overtime_hours'] * $rate['slab_rest_rate'] : 0;


            $total_pay = $normal_pay + $overtime_pay;

            if (!empty($rate['payroll_hours'])) {

                $regular_payroll_hours = min(
                    $row['regular_hours'],
                    $rate['payroll_hours']
                );
            
                $remaining_hours = max(
                    $rate['payroll_hours'] - $regular_payroll_hours,
                    0
                );
            
                $overtime_payroll_hours = min(
                    $row['overtime_hours'],
                    $remaining_hours
                );
            
                $row['payroll_methods'] =
                    ($regular_payroll_hours * $rate['rate']) +
                    ($overtime_payroll_hours * $rate['slab_rest_rate']);
            
            } else {
                $row['payroll_methods'] = $total_pay;
            }
            
            $remaining_amount  = $total_pay - $row['payroll_methods'] + $row['tips_due'] + $row['mileage_due'];

            if($remaining_amount > 0 && $rate['check_payment_type'] == 'percentage'){
                $check_payment_amount = $remaining_amount * $rate['check_payment_amount'] / 100;

                $row['check_methods'] = $remaining_amount > $check_payment_amount ? $check_payment_amount : $remaining_amount;

            }else if($remaining_amount > 0 && $rate['check_payment_type'] == 'fixed'){
                $check_payment_amount = $rate['check_payment_amount'];
                $row['check_methods'] = $remaining_amount > $check_payment_amount ? $check_payment_amount : $remaining_amount;
            }else{
                $row['check_methods'] = 0;
            }
            $row['instant_methods'] = $remaining_amount - $row['check_methods'];

        } else if($rate['rate_type'] == '1099 Regular'){
            if($rate['pay_type'] == 'WK'){
                $row['regular_hours'] = $row['total_hours'];
                $row['overtime_hours'] = 0;

            }else{
                $row['overtime_hours'] = (($row['total_hours'] ?? 0) - 40) > 0 ? (($row['total_hours'] ?? 0) - 40) : 0;
                $row['regular_hours'] = (($row['total_hours'] ?? 0) -  $row['overtime_hours']);
            }
            $normal_pay = $rate['pay_type'] == 'HR' ? $row['regular_hours'] * $rate['rate'] : $rate['rate']  ;
            $overtime_pay = $rate['pay_type'] == 'HR' ? $row['overtime_hours'] * $rate['rate'] * 1.5 : 0;

            $total_pay = $normal_pay + $overtime_pay + $row['tips_due'] + $row['mileage_due'];
            if($total_pay > 0 && $rate['check_payment_type'] == 'percentage'){
                $check_payment_amount = $total_pay * $rate['check_payment_amount'] / 100;

                $row['check_methods'] = $total_pay > $check_payment_amount ? $check_payment_amount : $total_pay;

            }else if($total_pay > 0 && $rate['check_payment_type'] == 'fixed'){
                $check_payment_amount = $rate['check_payment_amount'];
                $row['check_methods'] = $total_pay > $check_payment_amount ? $check_payment_amount : $total_pay;
            }else{
                $row['check_methods'] = 0;
            }
            $row['payroll_methods'] = 0;
            $row['instant_methods'] = $total_pay - $row['check_methods'];
        } else if($rate['rate_type'] == '1099 Slab'){
            if($rate['pay_type'] == 'WK'){
                $row['regular_hours'] = $row['total_hours'];
                $row['overtime_hours'] = 0;

            }else{
                $row['overtime_hours'] = (($row['total_hours'] ?? 0) - $rate['slab_first_hours']) > 0 ? (($row['total_hours'] ?? 0) - $rate['slab_first_hours']) : 0;
                $row['regular_hours'] = (($row['total_hours'] ?? 0) -  $row['overtime_hours']);
            }
            $normal_pay = $rate['pay_type'] == 'HR' ? $row['regular_hours'] * $rate['rate'] : $rate['rate']  ;
            $overtime_pay = $rate['pay_type'] == 'HR' ? $row['overtime_hours'] * $rate['slab_rest_rate'] : 0;
            $total_pay = $normal_pay + $overtime_pay + $row['tips_due'] + $row['mileage_due'];

            if($total_pay > 0 && $rate['check_payment_type'] == 'percentage'){
                $check_payment_amount = $total_pay * $rate['check_payment_amount'] / 100;

                $row['check_methods'] = $total_pay > $check_payment_amount ? $check_payment_amount : $total_pay;

            }else if($total_pay > 0 && $rate['check_payment_type'] == 'fixed'){
                $check_payment_amount = $rate['check_payment_amount'];
                $row['check_methods'] = $total_pay > $check_payment_amount ? $check_payment_amount : $total_pay;
            }else{
                $row['check_methods'] = 0;
            }
            $row['payroll_methods'] = 0;
            $row['instant_methods'] = $total_pay - $row['check_methods'];
        }
    }

    public function getLabourReport(Request $request)
    {
        try {
            $company = $request->session()->get('company');
            $companyState = Company::where('id', $company)->select('state_id')->first();

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
        try {
            $networkCheckAmounts = PayrollCheckAmount::whereBetween('eow', [Carbon::parse($request->start_date), Carbon::parse($request->end_date)])
            ->where('amount', '>', 0)
            ->groupBy('employee_id','company_id')
            ->selectRaw('employee_id, company_id, sum(amount) as amount')
            ->with('employee','employee.company')
            ->get();
            return response()->json([
                'data' => $networkCheckAmounts,
            ]);
        } catch (\Exception $e) {
            dd($e);
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    public function getNetworkInstantReport(Request $request)
    {
        try {
            $networkInstantAmounts = PayrollCheckAmount::whereBetween('eow', [Carbon::parse($request->start_date), Carbon::parse($request->end_date)])
            ->where('instant_amount', '>', 0)
            ->groupBy('employee_id','company_id')
            ->selectRaw('employee_id, company_id, sum(instant_amount) as amount')
            ->with('employee','employee.company')

            ->get();
            return response()->json([
                'data' => $networkInstantAmounts,
            ]);
        } catch (\Exception $e) {
            dd($e);
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }
    public function getNetworkSummaryReport(Request $request)
    {
        try {
            $networkSummary = $this->companyWisePayroll($request->start_date, $request->end_date);

            foreach ($networkSummary as $company) {
                $company['regular_hours'] = 0;
                $company['overtime_hours'] = 0;
                $company['total_hours'] = 0;
                $company['gross_pay'] = 0;
                $company['tips'] = 0;
                $company['mileage_excess'] = 0;
                $company['tips_due'] = 0;
                $company['mileage_due'] = 0;
                $company['total_earnings'] = 0;
                $company['payroll_methods'] = 0;
                $company['check_methods'] = 0;
                $company['instant_methods'] = 0;
                foreach ($company->labour_data as $employee) {
                    $company['total_hours'] += $employee['total_hours'];
                    $company['regular_hours'] += $employee['regular_hours'];
                    $company['overtime_hours'] += $employee['overtime_hours'];
                    $company['gross_pay'] += $employee['gross_pay'];
                    $company['tips'] += $employee['tips'];
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
    private function companyWisePayroll($start_date, $end_date)
    {
        DB::enableQueryLog();
        $minWages = MinimumWage::where('effective_date', '<=', Carbon::parse($start_date))->orderBy('effective_date', 'desc')->get()->keyBy('state_id');
        $employeeHours = EmployeeHours::whereBetween('date', [$start_date, $end_date])
            ->groupBy('employee_id','role_id','company_id')
            ->selectRaw('employee_id, role_id, company_id, sum(total_hours) as total_hours, sum(tips) as tips, sum(mileage_excess) as mileage_excess, sum(incentive) as incentive, sum(bonus) as bonus, sum(tips_due) as tips_due, sum(mileage_due) as mileage_due')
            ->with([
                'employee',
                'role',
                'employee.employeeRates',
                'company:id,state_id'
            ])
            ->get();

        $checkAmounts = PayrollCheckAmount::whereBetween('eow', [Carbon::parse($start_date), Carbon::parse($end_date)])
            ->get()
            ->keyBy(function($item) {
                return $item->employee_id . '-' . $item->role_id . '-' . $item->eow;
            });

        $employeeHoursFirstWeekTotalHours = EmployeeHours::whereBetween('date', [$start_date, Carbon::parse($start_date)->addDays(7)])
            ->groupBy('employee_id','role_id','company_id')
            ->selectRaw('sum(total_hours) as total_hours,role_id,employee_id,company_id,sum(tips_due) as tips_due,sum(mileage_due) as mileage_due')
            ->with([
                'employee',
                'role',
                'employee.employeeRates',
                'company:id,state_id'
            ])
            ->get()
            ->keyBy(function($item) {
                return $item->employee_id . '-' . $item->role_id . '-' . $item->company_id;
            });

        $employeeHoursSecondWeekTotalHours = EmployeeHours::whereBetween('date', [Carbon::parse($start_date)->addDays(8), $end_date])
            ->groupBy('employee_id','role_id','company_id')
            ->selectRaw('sum(total_hours) as total_hours,role_id,employee_id,company_id,sum(tips_due) as tips_due,sum(mileage_due) as mileage_due')
            ->with('employee','role','employee.employeeRates.role','company')
            ->get()
            ->keyBy(function($item) {
                return $item->employee_id . '-' . $item->role_id . '-' . $item->company_id;
            });

        $array=[];
        foreach ($employeeHours as $row) {
            $rates = $row->employee->employeeRates;
            
            $start = Carbon::parse($start_date);
            $firstWeekEnd = $start->copy()->addDays(7)->format('Y-m-d');
            $secondWeekStart = $start->copy()->addDays(8)->format('Y-m-d');
            
            $rate = $rates->firstWhere('role_id', $row->role_id);
            if(!$rate){
                continue;
            }

                
            $row['total_hours'] =  isset($employeeHoursFirstWeekTotalHours[$row->employee_id . '-' . $row->role_id . '-' . $row->company_id]) ? $employeeHoursFirstWeekTotalHours[$row->employee_id . '-' . $row->role_id . '-' . $row->company_id]->total_hours : 0;
            $row['tips_due'] =  isset($employeeHoursFirstWeekTotalHours[$row->employee_id . '-' . $row->role_id . '-' . $row->company_id]) ? $employeeHoursFirstWeekTotalHours[$row->employee_id . '-' . $row->role_id . '-' . $row->company_id]->tips_due : 0;
            $row['mileage_due'] =  isset($employeeHoursFirstWeekTotalHours[$row->employee_id . '-' . $row->role_id . '-' . $row->company_id]) ? $employeeHoursFirstWeekTotalHours[$row->employee_id . '-' . $row->role_id . '-' . $row->company_id]->mileage_due : 0;

            $this->calculatePayrollMethods($row, $rate);
            $key = $row->employee_id . '-' . $row->role_id . '-' . $firstWeekEnd;
            if(isset($checkAmounts[$key])){
                $row['check_methods'] = $checkAmounts[$key]->amount;
                $row['payroll_methods'] = $checkAmounts[$key]->payroll_amount;
                $row['instant_methods'] = $checkAmounts[$key]->instant_amount;
            }
            if(isset($employeeHoursSecondWeekTotalHours[$row->employee_id . '-' . $row->role_id . '-' . $row->company_id])){
                $data=[
                    'total_hours' => $employeeHoursSecondWeekTotalHours[$row->employee_id . '-' . $row->role_id . '-' . $row->company_id]->total_hours ?? 0,
                    'tips_due' => $employeeHoursSecondWeekTotalHours[$row->employee_id . '-' . $row->role_id . '-' . $row->company_id]->tips_due ?? 0,
                    'mileage_due' => $employeeHoursSecondWeekTotalHours[$row->employee_id . '-' . $row->role_id . '-' . $row->company_id]->mileage_due ?? 0

                ];
                $this->calculatePayrollMethods($data, $rate);
                $key = $row->employee_id . '-' . $row->role_id . '-' . $secondWeekStart;
                if(isset($checkAmounts[$key])){
                    $data['check_methods'] = $checkAmounts[$key]->amount ?? $data['check_methods'];
                    $data['payroll_methods'] = $checkAmounts[$key]->payroll_amount ?? $data['payroll_methods'];
                    $data['instant_methods'] = $checkAmounts[$key]->instant_amount ?? $data['instant_methods'];
                }
                $row['regular_hours'] += $data['regular_hours'];
                $row['overtime_hours'] += $data['overtime_hours'];
                $row['payroll_methods'] += $data['payroll_methods'];
                $row['check_methods'] += $data['check_methods'];
                $row['instant_methods'] += $data['instant_methods'];
                $row['tips_due'] += $data['tips_due'];
                $row['mileage_due'] += $data['mileage_due'];
                $row['total_hours'] += $data['total_hours'];
            }
            $row['employee_rate'] = $rate;  
            $row['gross_pay'] = $row['payroll_methods'] + $row['check_methods'] + $row['instant_methods'] - $row['tips_due'] - $row['mileage_due'];
            $row['total_earnings'] = ((float) ($row['gross_pay'] ?? 0) +  (float) ($row['bonus'] ?? 0) + (float) ($row['tips_due'] ?? 0) + (float) ($row['mileage_due'] ?? 0));
            $row['hr_pay'] = $row['total_earnings']  / ($row['total_hours'] ==0 ? 1 : $row['total_hours']);
            $row['week_period'] = Carbon::parse($start_date)->format('m/d/Y') . ' - ' . Carbon::parse($start_date)->addDays(7)->format('m/d/Y');

            $minWage = isset($minWages[$row->company->state_id]) ? $minWages[$row->company->state_id] : null;
            $row['min_wage_rate'] = isset($minWage->minimum_wage) ? $minWage->minimum_wage : 0;

            if(isset($minWage) && ($rate['rate_type'] == 'Payroll Regular' || $rate['rate_type'] == 'Payroll Slab')){
                $row['min_wage_weekly'] = $minWage->minimum_wage * ($row['total_hours'] ?? 0);
                $row['min_wage_hourly'] = $minWage->minimum_wage;
                if($row['payroll_methods'] < $row['min_wage_weekly']){
                    $row['min_wage_due'] = $row['min_wage_weekly'] - $row['payroll_methods'];
                }else{
                    $row['min_wage_due'] = 0;
                }
            }
            $key = $row->employee_id . '-' . $row->role_id;
            if(isset($checkAmounts[$key])){
                $row['check_edited'] = abs((float)($checkAmounts->get($key)?->amount ?? 0) - (float)($row['check_methods'] ?? 0)) > 0.1 ? true : false;
                $row['check_methods'] = $checkAmounts[$key]->amount ?? $row['check_methods'];
                $row['payroll_methods'] = $checkAmounts[$key]->payroll_amount ?? $row['payroll_methods'];
                $row['check_amount_id'] = $checkAmounts[$key]->id ?? null;
                $row['instant_methods'] = $checkAmounts[$key]->instant_amount ?? $row['instant_methods'];
            }
            if(!isset($array[$row->employee_id])){
                $array[$row->employee_id] = $row->toArray();
                $array[$row->employee_id]['roles']=[];
                $array[$row->employee_id]['rate'] = $row->employee_rate['rate'];
                if($row->role->code == 'DR'){
                    $array[$row->employee_id]['roles']=[
                        $row->role->name=>[
                            'regular_hours'=> $row['regular_hours'],
                            'rate'=>$rate['rate'],
                            'overtime_hours'=>$row['overtime_hours'],
                            ]
                        ];
                    $array[$row->employee_id]['rate'] = 0;
                    $array[$row->employee_id]['regular_hours'] =0;
                    $array[$row->employee_id]['overtime_hours'] =0;
                    $row['missing_data'] = true;
                }
                
            }else{
                
                if(isset($array[$row->employee_id]['missing_data']) && $array[$row->employee_id]['missing_data']){
                    $array[$row->employee_id]['regular_hours'] +=$row['regular_hours'];
                    $array[$row->employee_id]['overtime_hours'] +=$row['overtime_hours'];
                    $array[$row->employee_id]['rate'] = $rate['rate'];
                    $array[$row->employee_id]['primary_role'] = $row->role->name;
                }else{
                    if(!isset($array[$row->employee_id]['primary_role'])){
                        $array[$row->employee_id]['primary_role'] = $row->role->name;
                    }
                    if($array[$row->employee_id]['primary_role'] == $row->role->name){
                        $array[$row->employee_id]['regular_hours'] +=$row['regular_hours'];
                        $array[$row->employee_id]['overtime_hours'] +=$row['overtime_hours'];
                    }else if( isset($array[$row->employee_id]['roles'][$row->role->name]) && $array[$row->employee_id]['roles'][$row->role->name]){
                        $array[$row->employee_id]['roles'][$row->role->name]['regular_hours'] +=$row['regular_hours'];
                        $array[$row->employee_id]['roles'][$row->role->name]['overtime_hours'] +=$row['overtime_hours'];
                    }else{
                        $array[$row->employee_id]['roles'][$row->role->name] = [
                            'regular_hours'=>$row['regular_hours'],
                            'rate'=>$rate['rate'],
                            'overtime_hours'=>$row['overtime_hours'],
                        ];
                    }
                }
                $array[$row->employee_id]['total_hours'] += $row['total_hours'];
                $array[$row->employee_id]['gross_pay'] += $row['gross_pay'];
                $array[$row->employee_id]['tips_due'] += $row['tips_due'];
                $array[$row->employee_id]['mileage_due'] += $row['mileage_due'];
                $array[$row->employee_id]['total_earnings'] += $row['total_earnings'];
                $array[$row->employee_id]['payroll_methods'] += $row['payroll_methods'];
                $array[$row->employee_id]['check_methods'] += $row['check_methods'];
                $array[$row->employee_id]['instant_methods'] += $row['instant_methods'];


            }
        }
         $companies = Company::select('id','name','store_number','contact_person')->get();
        foreach ($companies as $company) {
            $company['labour_data'] = array_values(array_filter($array, function($item) use ($company) {
                return $item['company_id'] == $company->id;
            }));
        }
        return $companies;
    }
    public function getNetworkHoursReport(Request $request)
    {
        try {
            $company = $request->company;
        }catch(\Exception $e){
            dd($e);
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    public function getPayPeriodStoreReport(Request $request)
    {
        try {
            $company = $request->company;
            $year = $request->year;

            $weekPeriods = $this->weekPeriods($year);
            $startDate = Carbon::parse($weekPeriods[0]['startDate']);
            $endDate = Carbon::parse($weekPeriods[count($weekPeriods) - 1]['endDate']);

            $workHours = EmployeeHours::where('company_id', $company)
                        ->whereBetween('date', [$startDate, $endDate])
                        ->groupBy('employee_id','role_id','pay_type')
                        ->selectRaw('employee_id, role_id, pay_type, sum(total_hours) as total_hours, sum(tips) as tips, sum(mileage_excess) as mileage_excess, sum(incentive) as incentive, sum(bonus) as bonus, sum(tips_due) as tips_due, sum(mileage_due) as mileage_due')
                        ->with('employee','role','employee.employeeRates')->get();

            
            $weekwiseWorkHours = [];
            foreach ($weekPeriods as $index => $weekPeriod) {
                $key = $weekPeriod['startDate'].'-'.$weekPeriod['endDate'];

                $weekwiseWorkHours[$index] = [
                    'key' => $key,
                    'startDate' => $weekPeriod['startDate'],
                    'endDate' => $weekPeriod['endDate'],
                    'employeeHours' => []
                ];
            }
            $firstPeriodStart = Carbon::parse($weekPeriods[0]['startDate']);
            
            foreach ($workHours as $workHour) {

                $diffDays = $firstPeriodStart
                    ->diffInDays(Carbon::parse($workHour->date), false);

                // Each period = 14 days
                $periodIndex = floor($diffDays / 14);

                if (isset($weekwiseWorkHours[$periodIndex])) {
                    $weekwiseWorkHours[$periodIndex]['employeeHours'][] = $workHour;
                }
            }

            foreach ($weekwiseWorkHours as $weekwiseWorkHour) {
                foreach($weekwiseWorkHour['employeeHours'] as $employeeHour){
                    
                }
            }

            
        } catch (\Exception $e) {
            dd($e);
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    public function weekPeriods($year)
    {
        $periods = [];
        
        $startDate = new \DateTime($year . '-01-01', new \DateTimeZone('UTC')); // January 1st UTC
        
        $dayOfWeek = (int)$startDate->format('N'); 
        $daysToMonday = $dayOfWeek === 7 ? -6 : 1 - $dayOfWeek - 7; 
        $startDate->modify($daysToMonday . ' days');
        
        $periodNumber = 1;
        
        while ((int)$startDate->format('Y') === (int)$year || $periodNumber === 1) {
            $endDate = clone $startDate;
            $endDate->modify('+13 days'); 
            
            if ((int)$endDate->format('Y') > (int)$year && (int)$endDate->format('m') > 1) {
                break;
            }
            
            $startFormatted = $startDate->format('m-d-Y');
            $endFormatted = $endDate->format('m-d-Y');
            
            $label = "{$startFormatted} To {$endFormatted}";
            $value = $startDate->format('Y-m-d\TH:i:s.u\Z') . ' to ' . $endDate->format('Y-m-d\TH:i:s.u\Z');
            
            $periods[] = [
                'label' => $label,
                'value' => $value,
                'startDate' => $startDate->format('c'),
                'endDate' => $endDate->format('c')
            ];
            
            $startDate->modify('+14 days');
            $periodNumber++;
        }
        
        return $periods;
    }
}