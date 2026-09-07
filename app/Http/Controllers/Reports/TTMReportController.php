<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Models\Caching\QQCaching;
use App\Models\Settings\PandlConfigurationDetail;
use App\Models\Ttm\TtmReport;
use Carbon\Carbon;
use Illuminate\Http\Request;

class TTMReportController extends Controller
{
   
    public function getTtmReport(Request $request)
    {
        $this->authorize('access', 'ttm-report.index');
        try {
            $year = (int) $request->input('year');
            $month = $request->input('month');
            $quarter = $request->input('quarter');

            if($quarter){
                [$start_month, $end_month] = array_map('intval', explode('-', $quarter));

                $periods = [];
                for($m = $start_month; $m <= $end_month; $m++){
                    $effective_year = Carbon::parse($year . '-' . $m . '-01')->isPast()
                        ? $year
                        : $year - 1;
                    $periods[] = ['year' => $effective_year, 'month' => $m];
                }

                $ttm_data = TtmReport::query()
                    ->where(function ($q) use ($periods) {
                        foreach ($periods as $p) {
                            $q->orWhere(function ($qq) use ($p) {
                                $qq->where('year', $p['year'])->where('month', $p['month']);
                            });
                        }
                    })
                    ->get();
            }else{
                $ttm_data = TtmReport::where('year', $year)->where('month', $month)->get();
            }

            $pandl_data = PandlConfigurationDetail::get()->keyBy('label');

            $income_labels = $pandl_data->where('type', 'Income')->pluck('label')->toArray();
            $food_purchase_labels = $pandl_data->where('type', 'COGS')->where('cogs_type', 'Food Purchase')->pluck('label')->toArray();
            $labor_cost_labels = $pandl_data->where('type', 'COGS')->where('cogs_type', 'Labor Cost')->pluck('label')->toArray();
            $franchise_fee_labels = $pandl_data->where('type', 'COGS')->where('cogs_type', 'Franchise fee')->pluck('label')->toArray();
            $retail_labels = $pandl_data->where('type', 'COGS')->where('cogs_type', 'Retail')->pluck('label')->toArray();
            $expense_labels = $pandl_data->where('type', 'Expense')->pluck('label')->toArray();

            $store_numbers = $ttm_data->pluck('store_number')->unique()->toArray();
            $qq_caching = QQCaching::getCache('companies');
            if($qq_caching){
                $qq_companies = $qq_caching->data;
            }else{
                $qq_companies = [];
            }


            $income_data = [];
            $totals=[];
            $food_purchase_data = [];
            $labor_cost_data = [];
            $franchise_fee_data = [];
            $retail_data = [];
            $expense_data = [];


            function getCompanyName($store_number, $qq_companies){
                foreach($qq_companies as $company){
                    if($company['store_no'] == $store_number){
                        return $company['store_no'] != $company['name'] ? $company['store_no'] . ' - ' . $company['name'] : $company['store_no'];
                    }
                }
                return $store_number;
            }
            foreach($ttm_data as $ttm){
                $company = getCompanyName($ttm->store_number, $qq_companies);
                $label = $ttm->label;
                if(in_array($label, $income_labels)){
                    $income_data[$label][$company] = isset($income_data[$label][$company]) ? $income_data[$label][$company] + $ttm->amount : $ttm->amount;
                    $totals['Total Revenue'][$company] =  isset($totals['Total Revenue'][$company]) ? $totals['Total Revenue'][$company] + $ttm->amount : $ttm->amount;
                }
                else if(in_array($label, $food_purchase_labels)){
                    $food_purchase_data[$label][$company] = isset($food_purchase_data[$label][$company]) ? $food_purchase_data[$label][$company] + $ttm->amount : $ttm->amount;
                    $totals['Total Food Purchase'][$company] =  isset($totals['Total Food Purchase'][$company]) ? $totals['Total Food Purchase'][$company] + $ttm->amount : $ttm->amount;
                }
                else if(in_array($label, $labor_cost_labels)){
                    $labor_cost_data[$label][$company] = isset($labor_cost_data[$label][$company]) ? $labor_cost_data[$label][$company] + $ttm->amount : $ttm->amount;
                    $totals['Total Labor Cost'][$company] =  isset($totals['Total Labor Cost'][$company]) ? $totals['Total Labor Cost'][$company] + $ttm->amount : $ttm->amount;
                }
                else if(in_array($label, $franchise_fee_labels)){
                    $franchise_fee_data[$label][$company] = isset($franchise_fee_data[$label][$company]) ? $franchise_fee_data[$label][$company] + $ttm->amount : $ttm->amount;
                    $totals['Total Franchise Fee'][$company] =  isset($totals['Total Franchise Fee'][$company]) ? $totals['Total Franchise Fee'][$company] + $ttm->amount : $ttm->amount;
                }
                else if(in_array($label, $retail_labels)){
                    $retail_data[$label][$company] = isset($retail_data[$label][$company]) ? $retail_data[$label][$company] + $ttm->amount : $ttm->amount;
                    $totals['Total Retail'][$company] =  isset($totals['Total Retail'][$company]) ? $totals['Total Retail'][$company] + $ttm->amount : $ttm->amount;
                }
                else if(in_array($label, $expense_labels)){
                    $expense_data[$label][$company] = isset($expense_data[$label][$company]) ? $expense_data[$label][$company] + $ttm->amount : $ttm->amount;
                    $totals['Total Expense'][$company] =  isset($totals['Total Expense'][$company]) ? $totals['Total Expense'][$company] + $ttm->amount : $ttm->amount;
                }
            }
            $company_data = [];
            foreach($store_numbers as $store_number){
                $company = getCompanyName($store_number, $qq_companies);
                $company_data[] = $company;


                $food_purchase = isset($totals['Total Food Purchase'][$company]) ? $totals['Total Food Purchase'][$company] : 0;
                $labor_cost = isset($totals['Total Labor Cost'][$company]) ? $totals['Total Labor Cost'][$company] : 0;
                $franchise_fee = isset($totals['Total Franchise Fee'][$company]) ? $totals['Total Franchise Fee'][$company] : 0;
                $retail = isset($totals['Total Retail'][$company]) ? $totals['Total Retail'][$company] : 0;

                $income = isset($totals['Total Revenue'][$company]) ? $totals['Total Revenue'][$company] : 0;
                $expense = isset($totals['Total Expense'][$company]) ? $totals['Total Expense'][$company] : 0;
                $totals['Total COGS'][$company] = $food_purchase + $labor_cost + $franchise_fee + $retail;
                $totals['Net Income'][$company] = $income - $totals['Total COGS'][$company] - $expense;

            }

            $report_data = [];

            foreach($income_data as $label => $data){
                $report_data[] = [
                    'label' => $label,
                    'data' => $data,
                    'total' => array_sum($data),
                ];
            }
            $report_data[] = [
                'label' => 'Total Revenue',
                'data' => isset($totals['Total Revenue']) ? $totals['Total Revenue'] : [],
                'total' => array_sum(isset($totals['Total Revenue']) ? $totals['Total Revenue'] : []),
            ];

            foreach($food_purchase_data as $label => $data){
                $report_data[] = [
                    'label' => $label,
                    'data' => $data,
                    'total' => array_sum($data),
                ];
            }
            foreach($labor_cost_data as $label => $data){
                $report_data[] = [
                    'label' => $label,
                    'data' => $data,
                    'total' => array_sum($data),
                ];
            }
            foreach($franchise_fee_data as $label => $data){
                $report_data[] = [
                    'label' => $label,
                    'data' => $data,
                    'total' => array_sum($data),
                ];
            }
            foreach($retail_data as $label => $data){
                $report_data[] = [
                    'label' => $label,
                    'data' => $data,
                    'total' => array_sum($data),
                ];
            }

            $report_data[] = [
                'label' => 'Total COGS',
                'data' => isset($totals['Total COGS']) ? $totals['Total COGS'] : [],
                'total' => array_sum(isset($totals['Total COGS']) ? $totals['Total COGS'] : []),
            ];
            foreach($expense_data as $label => $data){
                $report_data[] = [
                    'label' => $label,
                    'data' => $data,
                    'total' => array_sum($data),
                ];
            }
            $report_data[] = [
                'label' => 'Total Expense',
                'data' => isset($totals['Total Expense']) ? $totals['Total Expense'] : [],
                'total' => array_sum(isset($totals['Total Expense']) ? $totals['Total Expense'] : []),
            ];
            $report_data[] = [
                'label' => 'Net Income',
                'data' => isset($totals['Net Income']) ? $totals['Net Income'] : [],
                'total' => array_sum(isset($totals['Net Income']) ? $totals['Net Income'] : []),
            ];
            return to_json([
                'success' => true,
                'message' => 'TTM Report',
                'report_data' => $report_data,
                'store_numbers' => $company_data,
            ]);
        } catch (\Exception $e) {
            info($e);
            return to_json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

}