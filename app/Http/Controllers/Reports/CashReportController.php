<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Models\DataEntry\DailySale;
use App\Models\DataEntry\BankDeposit;
use App\Models\DataEntry\Shortage;
use App\Models\Settings\Company;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class CashReportController extends Controller
{
    /**
     * Bank Deposit Report: All companies as rows, each day of the selected month as columns.
     * Amount = cash_bag - deposit - shortage for each day.
     */
    public function getBankDepositReport(Request $request)
    {
        $this->authorize('access', 'bank-deposit-report.index');
        $request->validate([
            'year' => 'required|integer|min:2020|max:2100',
            'month' => 'required|integer|min:1|max:12',
        ]);

        $year = (int) $request->input('year');
        $month = (int) $request->input('month');

        $startDate = Carbon::create($year, $month, 1)->startOfMonth();
        $endDate = Carbon::create($year, $month, 1)->endOfMonth();

        // Get all days in the month
        $dates = [];
        $current = $startDate->copy();
        while ($current->lte($endDate)) {
            $dates[] = $current->format('Y-m-d');
            $current->addDay();
        }

        // Get all authorized companies
        $companies = Company::selectRaw('id, store_number, name, CONCAT(store_number, " - ", name) as company_name')
            ->authorizedCompanies('id')
            ->orderBy('company_name')
            ->get();

        // Get daily sales data for the month
        $dailySales = DailySale::whereIn('company_id', $companies->pluck('id'))
            ->whereBetween('date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
            ->groupBy('date','company_id')
            ->selectRaw('company_id, date, sum(cash_bag) as cash_bag, sum(id) as daily_sale_id, count(id) as total_daily_sales')
            ->get()
            ->keyBy(function ($item) {
                return $item->company_id . '|' . $item->date;
            });

        // Get bank deposits for the month
        $bankDeposits = BankDeposit::selectRaw('SUM(amount) as total_deposit, daily_sale_id')
            ->groupBy('daily_sale_id')
            ->get()->keyBy('daily_sale_id');

        // Get shortages for the month
        $shortages = Shortage::selectRaw('SUM(amount) as total_shortage, daily_sale_id')
            ->groupBy('daily_sale_id')
            ->get()->keyBy('daily_sale_id');

        // Build report data
        $reportData = [];
        foreach ($companies as $index => $company) {
            $companyData = [
                'sr_no' => $index + 1,
                'company_name' => $company->company_name,
                'store_number' => $company->store_number,
                'company_id' => $company->id,
            ];

            $rowTotal = 0;

            foreach ($dates as $date) {
                $cashBag = isset($dailySales[$company->id . '|' . $date]) 
                    ? (float) $dailySales[$company->id . '|' . $date]->cash_bag ?? 0
                    : 0;

                if(isset($dailySales[$company->id . '|' . $date]) && $dailySales[$company->id . '|' . $date]->total_daily_sales > 1){
                    $salesIds = DailySale::where('company_id', $company->id)->where('date', $date)->select('id')->get()->pluck('id');
                    $depositTotal = 0;
                    $shortageTotal = 0;
                    foreach($salesIds as $salesId){
                        $depositTotal += isset($bankDeposits[$salesId]) 
                            ? (float) $bankDeposits[$salesId]->total_deposit 
                            : 0;
                        $shortageTotal += isset($shortages[$salesId]) 
                            ? (float) $shortages[$salesId]->total_shortage 
                            : 0;
                    }
                    $deposit = $depositTotal;
                    $shortage = $shortageTotal;
                }else{
                    $salesId = isset($dailySales[$company->id . '|' . $date]) 
                        ? $dailySales[$company->id . '|' . $date]->daily_sale_id 
                        : null;

                        $deposit = isset($bankDeposits[$salesId]) 
                        ? (float) $bankDeposits[$salesId]->total_deposit 
                        : 0;
    
                        $shortage = isset($shortages[$salesId]) 
                        ? (float) $shortages[$salesId]->total_shortage 
                        : 0;
                }
               

                // Amount = cash_bag - deposit - shortage
                $amount = $cashBag - $deposit - $shortage;
                if(isset($companyData['date_' . $date])){
                    $companyData['date_' . $date]['amount'] += $amount;
                    $companyData['date_' . $date]['daily_sale_id'] = $salesId;
                }else{
                    $companyData['date_' . $date] = [
                        'amount' => round($amount, 2),
                        'daily_sale_id' => $salesId,
                    ];
                }
                $rowTotal += $amount;
            }

            $companyData['total'] = round($rowTotal, 2);
            $reportData[] = $companyData;
        }

        return response()->json([
            'data' => $reportData,
            'dates' => $dates,
            'year' => $year,
            'month' => $month,
            'month_name' => $startDate->format('F'),
        ]);
    }

    /**
     * Cash Ledger Report: Shows detailed cash flow for each date.
     * Columns: cash_received, partial_void, total, tips, mileage, total, dd_tip, etip, etip_payroll, total,
     * cash_payment, small_maintenance, office_exp, food, supplies, misc, cashbag, short/over, total_cash
     */
    public function getCashLedgerReport(Request $request)
    {
        $this->authorize('access', 'cash-ledger-report.index');
        $request->validate([
            'year' => 'required|integer|min:2020|max:2100',
            'month' => 'required|integer|min:1|max:12',
            'company_id' => 'nullable|integer|exists:company,id',
        ]);

        $year = (int) $request->input('year');
        $month = (int) $request->input('month');
        $companyId = $request->input('company_id');

        $startDate = Carbon::create($year, $month, 1)->startOfMonth();
        $endDate = Carbon::create($year, $month, 1)->endOfMonth();

        // Get all days in the month
        $dates = [];
        $current = $startDate->copy();
        while ($current->lte($endDate)) {
            $dates[] = $current->format('Y-m-d');
            $current->addDay();
            if($current->isFuture()){
                break;
            }
        }

        // Build query for daily sales
        $query = DailySale::with('otherPayments');
        
        if ($companyId) {
            $query->where('company_id', $companyId);
        } else {
            $companies = Company::authorizedCompanies('id')->pluck('id');
            $query->whereIn('company_id', $companies);
        }

        $dailySales = $query
            ->whereBetween('date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
            ->groupBy('date','company_id')
            ->selectRaw('date, company_id,sum(id) as daily_sale_id,count(id) as total_daily_sales, SUM(cash_received) as cash_received, SUM(partial_void) as partial_void, SUM(total_cash) as total_cash, SUM(tips) as tips, SUM(mileage) as mileage, SUM(total_tips_mileage) as total_tips_mileage, SUM(dd_tips) as dd_tips, SUM(e_tips) as e_tips, SUM(e_tips_payroll) as e_tips_payroll, SUM(total_e_and_dd_tips) as total_e_and_dd_tips, SUM(cash_payment) as cash_payment, SUM(net_cash_due) as net_cash_due, SUM(cash_bag) as cash_bag, SUM(short_over) as short_over')
            ->get()
            ->keyBy('date');
        
        $bankDeposits = BankDeposit::selectRaw('SUM(amount) as total_deposit, daily_sale_id')->groupBy('daily_sale_id')->get()->keyBy('daily_sale_id');

        // Get company name if specific company selected
        $companyName = null;
        if ($companyId) {
            $company = Company::selectRaw('CONCAT(store_number, " - ", name) as company_name')
                ->find($companyId);
            $companyName = $company ? $company->company_name : null;
        }

        // Build report data
        $reportData = [];
        
        foreach ($dates as $date) {
            $sale = $dailySales->get($date);
            
            if (!$sale && !$companyId) {
                continue;
            }


            // Initialize row data
            $rowData = [
                'date' => $date,
                'cash_received' => $sale ? (float) $sale->cash_received : 0,
                'partial_void' => $sale ? (float) $sale->partial_void : 0,
                'total_cash' => $sale ? (float) $sale->total_cash : 0,
                'tips' => $sale ? (float) $sale->tips : 0,
                'mileage' => $sale ? (float) $sale->mileage : 0,
                'total_tips_mileage' => $sale ? (float) $sale->total_tips_mileage : 0,
                'dd_tips' => $sale ? (float) $sale->dd_tips : 0,
                'e_tips' => $sale ? (float) $sale->e_tips : 0,
                'e_tips_payroll' => $sale ? (float) $sale->e_tips_payroll : 0,
                'total_e_and_dd_tips' => $sale ? (float) $sale->total_e_and_dd_tips : 0,
                'cash_payment' => $sale ? (float) $sale->cash_payment : 0,
                'net_cash_due' => $sale ? (float) $sale->net_cash_due : 0,
            ];

            // Initialize other payments
            $smallMaintenance = 0;
            $officeExp = 0;
            $food = 0;
            $supplies = 0;
            $misc = 0;

            if ($sale && $sale->otherPayments) {
                foreach ($sale->otherPayments as $payment) {
                    switch ($payment->expense) {
                        case 'Small Maintenance':
                            $smallMaintenance += (float) $payment->amount;
                            break;
                        case 'Office Exp':
                            $officeExp += (float) $payment->amount;
                            break;
                        case 'Food':
                            $food += (float) $payment->amount;
                            break;
                        case 'Supplies':
                            $supplies += (float) $payment->amount;
                            break;
                        case 'Misc':
                            $misc += (float) $payment->amount;
                            break;
                    }
                }
            }

            $rowData['small_maintenance'] = $smallMaintenance;
            $rowData['office_exp'] = $officeExp;
            $rowData['food'] = $food;
            $rowData['supplies'] = $supplies;
            $rowData['misc'] = $misc;
            $rowData['cash_bag'] = $sale ? (float) $sale->cash_bag : 0;
            $rowData['short_over'] = $sale ?  (float) $sale->short_over : 0;
            $rowData['net_cash_due'] = $rowData['net_cash_due'];
            $rowData['daily_sale_id'] = $sale && $sale->total_daily_sales > 1 ? 0 : ($sale ? $sale->daily_sale_id : null);
            
            
            if($sale && $sale->total_daily_sales > 1){
                $salesIds = DailySale::where('company_id', $sale->company_id)->where('date', $date)->select('id')->get()->pluck('id');
                $depositTotal = 0;
                foreach($salesIds as $salesId){
                    $depositTotal += isset($bankDeposits[$salesId]) 
                    ? (float) $bankDeposits[$salesId]->total_deposit 
                    : 0;
                }
            }else{
                $depositTotal = $sale && isset($bankDeposits[$sale->daily_sale_id]) 
                    ? (float) $bankDeposits[$sale->daily_sale_id]->total_deposit 
                    : 0;
            }
            
            $rowData['deposit'] = $depositTotal;
            // Calculate total cash payment
            $rowData['total_cash_payment'] = $rowData['cash_payment'] + 
            $smallMaintenance + $officeExp + $food + $supplies + $misc;

            $rowData['diff'] = $rowData['cash_bag'] - $rowData['deposit'];
            $reportData[] = $rowData;
        }

        return response()->json([
            'data' => $reportData,
            'dates' => $dates,
            'year' => $year,
            'month' => $month,
            'month_name' => $startDate->format('F'),
            'company_name' => $companyName,
        ]);
    }

    /**
     * Daily Cash Report: All companies as rows, with key cash metrics as columns.
     * Shows: total_sales, cash_received, cash_payment, net_cash_due, cash_bag, short_over
     */
    public function getDailyCashReport(Request $request)
    {
        $this->authorize('access', 'daily-cash-report.index');
        $request->validate([
            'date' => 'required|date',
        ]);

        $date = $request->input('date');

        // Get all authorized companies
        $companies = Company::selectRaw('id, store_number, name, CONCAT(store_number, " - ", name) as company_name')
            ->authorizedCompanies('id')
            ->orderBy('company_name')
            ->get();

        // Get daily sales data for the date
        $dailySales = DailySale::with('otherPayments')
            ->whereIn('company_id', $companies->pluck('id'))
            ->where('date', $date)
            ->groupBy('company_id')
            ->selectRaw('company_id, sum(id) as daily_sale_id, count(id) as total_daily_sales, SUM(total_sales) as total_sales, SUM(cash_received) as cash_received, SUM(cash_payment) as cash_payment, SUM(other_payments_total) as other_payments_total, SUM(net_cash_due) as net_cash_due, SUM(cash_bag) as cash_bag, SUM(short_over) as short_over')
            ->get()
            ->keyBy('company_id');

        // Build report data
        $reportData = [];
        foreach ($companies as $index => $company) {
            $sale = $dailySales->get($company->id);
            
            $totalSales = $sale ? (float) $sale->total_sales : 0;
            $cashReceived = $sale ? (float) $sale->cash_received : 0;
            $cashPayment = $sale ? (float) $sale->cash_payment : 0;
            $otherPaymentsTotal = $sale ? (float) $sale->other_payments_total : 0;
            $totalCashPayment = $cashPayment + $otherPaymentsTotal;
            $netCashDue = $sale ? (float) $sale->net_cash_due : 0;
            $cashBag = $sale ? (float) $sale->cash_bag : 0;
            $shortOver = $sale ? (float) $sale->short_over : 0;

            $companyData = [
                'sr_no' => $index + 1,
                'company_id' => $company->id,
                'company_name' => $company->company_name,
                'total_sales' => round($totalSales, 2),
                'cash_received' => round($cashReceived, 2),
                'cash_payment' => round($totalCashPayment, 2),
                'net_cash_due' => round($netCashDue, 2),
                'cash_bag' => round($cashBag, 2),
                'short_over' => round($shortOver, 2),
                'daily_sale_id' => $sale && $sale->total_daily_sales > 1 ? 0 : ($sale ? $sale->daily_sale_id : null),
            ];

            $reportData[] = $companyData;
        }

        return response()->json([
            'data' => $reportData,
            'date' => $date,
        ]);
    }

    /**
     * Cash Short Sales Report: All companies as rows, each day of the selected month as columns.
     * Amount = short_over for each day.
     * Can be filtered by Regional Director.
     */
    public function getCashShortSalesReport(Request $request)
    {
        $this->authorize('access', 'cash-short-sales-report.index');
        $request->validate([
            'year' => 'required|integer|min:2020|max:2100',
            'month' => 'required|integer|min:1|max:12',
            'user_id' => 'nullable|integer|exists:users,id',
        ]);

        $year = (int) $request->input('year');
        $month = (int) $request->input('month');
        $userId = $request->input('user_id');

        $startDate = Carbon::create($year, $month, 1)->startOfMonth();
        $endDate = Carbon::create($year, $month, 1)->endOfMonth();

        // Get all days in the month
        $dates = [];
        $current = $startDate->copy();
        while ($current->lte($endDate)) {
            $dates[] = $current->format('Y-m-d');
            $current->addDay();
        }

        // Get company IDs for Regional Director if provided
        $companyIds = null;
        if ($userId) {
            $user = \App\Models\User::find($userId);
            if ($user) {
                $companyIds = $user->getCompaniesArrayAttribute();
            }
        }

        // Get all authorized companies
        $companiesQuery = Company::selectRaw('id, store_number, name, CONCAT(store_number, " - ", name) as company_name')
            ->authorizedCompanies('id');

        // Filter by Regional Director companies if provided
        if ($companyIds) {
            $companiesQuery->whereIn('id', $companyIds);
        }

        $companies = $companiesQuery->orderBy('company_name')->get();

        // Get daily sales data for the month
        $dailySales = DailySale::whereIn('company_id', $companies->pluck('id'))
            ->whereBetween('date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
            ->select('company_id', 'date', 'short_over', 'id')
            ->get()
            ->keyBy(function ($item) {
                return $item->company_id . '|' . $item->date;
            });

        // Build report data
        $reportData = [];
        foreach ($companies as $index => $company) {
            $companyData = [
                'sr_no' => $index + 1,
                'company_name' => $company->company_name,
                'company_id' => $company->id,
                'store_number' => $company->store_number,
            ];

            $rowTotal = 0;

            foreach ($dates as $date) {
                $sale = $dailySales->get($company->id . '|' . $date);
                
                $shortOver = $sale ? (float) $sale->short_over : 0;
                $salesId = $sale ? $sale->id : null;

                $companyData['date_' . $date] = [
                    'amount' => round($shortOver, 2),
                    'daily_sale_id' => $salesId,
                ];
                
                $rowTotal += $shortOver;
            }

            $companyData['total'] = round($rowTotal, 2);
            $reportData[] = $companyData;
        }

        return response()->json([
            'data' => $reportData,
            'dates' => $dates,
            'year' => $year,
            'month' => $month,
            'month_name' => $startDate->format('F'),
        ]);
    }

    /**
     * Cash Payout Sales Report: All companies as rows, each day of the selected month as columns.
     * Amount = total_other_payments for each day.
     * Can be filtered by Regional Director.
     */
    public function getCashPayoutSalesReport(Request $request)
    {
        $this->authorize('access', 'cash-payout-sales-report.index');
        $request->validate([
            'year' => 'required|integer|min:2020|max:2100',
            'month' => 'required|integer|min:1|max:12',
            'user_id' => 'nullable|integer|exists:users,id',
        ]);

        $year = (int) $request->input('year');
        $month = (int) $request->input('month');
        $userId = $request->input('user_id');

        $startDate = Carbon::create($year, $month, 1)->startOfMonth();
        $endDate = Carbon::create($year, $month, 1)->endOfMonth();

        // Get all days in the month
        $dates = [];
        $current = $startDate->copy();
        while ($current->lte($endDate)) {
            $dates[] = $current->format('Y-m-d');
            $current->addDay();
        }

        // Get company IDs for Regional Director if provided
        $companyIds = null;
        if ($userId) {
            $user = \App\Models\User::find($userId);
            if ($user) {
                $companyIds = $user->getCompaniesArrayAttribute();
            }
        }

        // Get all authorized companies
        $companiesQuery = Company::selectRaw('id, store_number, name, CONCAT(store_number, " - ", name) as company_name')
            ->authorizedCompanies('id');

        // Filter by Regional Director companies if provided
        if ($companyIds !== null) {
            $companiesQuery->whereIn('id', $companyIds);
        }

        $companies = $companiesQuery->orderBy('company_name')->get();

        // Get daily sales data for the month
        $dailySales = DailySale::whereIn('company_id', $companies->pluck('id'))
            ->whereBetween('date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
            ->select('company_id', 'date', 'other_payments_total', 'id')
            ->get()
            ->keyBy(function ($item) {
                return $item->company_id . '|' . $item->date;
            });

        // Build report data
        $reportData = [];
        foreach ($companies as $index => $company) {
            $companyData = [
                'sr_no' => $index + 1,
                'company_name' => $company->company_name,
                'company_id' => $company->id,
                'store_number' => $company->store_number,
            ];

            $rowTotal = 0;

            foreach ($dates as $date) {
                $sale = $dailySales->get($company->id . '|' . $date);
                
                $otherPayments = $sale ? (float) $sale->other_payments_total : 0;
                $salesId = $sale ? $sale->id : null;

                $companyData['date_' . $date] = [
                    'amount' => round($otherPayments, 2),
                    'daily_sale_id' => $salesId,
                ];
                
                $rowTotal += $otherPayments;
            }

            $companyData['total'] = round($rowTotal, 2);
            $reportData[] = $companyData;
        }

        return response()->json([
            'data' => $reportData,
            'dates' => $dates,
            'year' => $year,
            'month' => $month,
            'month_name' => $startDate->format('F'),
        ]);
    }
    
    public function getPendingBankDepositReport(Request $request)
    {
        try {
            $this->authorize('access', 'pending-bank-deposit-report.index');

            $remainingAmount = DailySale::leftJoinSub(
                    DB::table('bank_deposits')
                        ->selectRaw('daily_sale_id, SUM(amount) as deposit_amount, MAX(date) as latest_date')
                        ->groupBy('daily_sale_id'),
                    'bd',
                    'daily_sales.id',
                    '=',
                    'bd.daily_sale_id'
                )
                ->leftJoinSub(
                    DB::table('shortage')
                        ->selectRaw('daily_sale_id, SUM(amount) as shortage_amount')
                        ->groupBy('daily_sale_id'),
                    'sh',
                    'daily_sales.id',
                    '=',
                    'sh.daily_sale_id'
                )
                ->groupBy('daily_sales.company_id')
                ->selectRaw('
                    daily_sales.company_id,
                    MAX(bd.latest_date) as latest_date,
                    SUM(daily_sales.cash_bag)
                    - SUM(COALESCE(bd.deposit_amount,0))
                    - SUM(COALESCE(sh.shortage_amount,0)) as remaining_amount
                ')
                ->get()
                ->keyBy('company_id');

            $companies = Company::selectRaw('id, store_number, name, CONCAT(store_number, " - ", name) as company_name')->authorizedCompanies('id')->orderBy('company_name')->get();
            $reportData = [];
            foreach ($companies as $index => $company) {
                $amountData = isset($remainingAmount[$company->id]) ? $remainingAmount[$company->id] : null;
                $data = [
                    'sr_no' => $index + 1,
                    'company_name' => $company->company_name,
                    'remaining_amount' => $amountData ? $amountData['remaining_amount'] : 0,
                    'latest_date' => $amountData ? $amountData['latest_date'] : null,
                    'store_number' => $company->store_number,
                ];
                $reportData[] = $data;
            }
            return response()->json([
                'data' => $reportData,
            ]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }
}
