<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Models\Settings\Company;
use App\Models\Settings\PandlConfiguration;
use App\Models\Settings\Workgroup;
use App\Models\User;
use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Services\Quickqore\QuickqoreService;

use function Safe\inotify_add_watch;

class FinanceReportController extends Controller
{
    /**
     * Get Finance Report
     * Filters: year, regional_director_id, area_manager_id
     */
    public function getFinanceReport(Request $request)
    {
        $this->authorize('access', 'finance-report.index');
        
        try {
            $year = $request->input('year', Carbon::now()->year);
            $user_id = $request->input('user_id');
            $workgroup_id = $request->input('workgroup_id');
            if($user_id){
                $user = User::find($user_id);
                $userCompanyIds = $user->companies_array;
                $store_numbers = Company::whereIn('id', $userCompanyIds)->pluck('store_number')->toArray();
            }else{
                $store_numbers = null;
            }

            $workgroup = null;
            if($workgroup_id){
                $workgroup = Workgroup::where('id',$workgroup_id)->select('id','name')->first();
            }
            // Sample data - replace with actual database querie
            $quickqoreService = new QuickqoreService();
            $response = $quickqoreService->handleFinanceReport([
                'year' => $year,
                'store_numbers' => $store_numbers,
                'workgroup'=>  $workgroup ?  $workgroup->name :null
            ]);
            $data = $response['data'];
            $companies = Company::pluck('workgroup_id','store_number')->toArray();
            foreach($data['data'] as $key => $d){
                $store_number =  is_numeric($d['store_data']['store_number']) ? (int) $d['store_data']['store_number'] : null;
                $workgroup_id = isset($companies[$store_number]) ? $companies[$store_number] : null;
                $data['data'][$key]['store_data']['workgroup_id'] = $workgroup_id;
            }

            return response()->json([
                'success' => true,
                'data' => $data,
                
            ]);
        } catch (\Exception $e) {
            info($e);
            return response()->json([
                'success' => false,
                'message' => 'Something went wrong'
            ], 500);
        }
    }

    /**
     * Get Detailed Finance Report with category breakdowns
     * Filters: year, regional_director_id, area_manager_id
     */
    public function getDetailedFinanceReport(Request $request)
    {
        $this->authorize('access', 'detailed-finance-report.index');
        
        try {
            $year = $request->input('year', Carbon::now()->year);
            $user_id = $request->input('user_id');
            $company_ids = $request->input('company_ids', []);
            $pandl_configuration_id = $request->input('pandl_configuration_id');
            $workgroup_id = $request->input('workgroup_id');
            $report_type = $request->input('report_type', 'summary');
            $isDetailed = $report_type === 'detailed';
            // Determine store numbers based on company_ids or user_id
            if (!empty($company_ids)) {
                // Use company_ids if provided
                $store_numbers = Company::whereIn('id', $company_ids)->pluck('store_number')->toArray();
            } elseif ($user_id) {
                // Fall back to user's companies if user_id is provided
                $user = User::find($user_id);
                $userCompanyIds = $user->companies_array;
                $store_numbers = Company::whereIn('id', $userCompanyIds)
                ->when($workgroup_id, function ($query) use ($workgroup_id) {
                    return $query->where('workgroup_id', $workgroup_id);
                })
                ->pluck('store_number')->toArray();
            } else {
                $store_numbers = null;
            }

            $workgroup = null;
            if($workgroup_id){
                $workgroup = Workgroup::where('id',$workgroup_id)->select('id','name')->first();
            }
            $quickqoreService = new QuickqoreService();
            $response = $quickqoreService->handleDetailFinanceReport([
                'year' => $year,
                'store_numbers' => $store_numbers,
                'workgroup'=>  $workgroup ?  $workgroup->name :null
            ]);

            $ledger_codes_with_company = $response['data']['ledger_codes_with_company'] ?? [];

            $months = $response['data']['months'];
            $data = $response['data']['data'];
            $ledgers_with_names = $response['data']['ledger_codes'];
            $ledger_codes = array_keys($response['data']['ledger_codes']);
           

            $pandlData =[];
            $pandlConfiguration = PandlConfiguration::with('details')->where('id', $pandl_configuration_id)->first();
            $incomeLegdgers = $pandlConfiguration->details->where('type', 'Income');
            $foodPurchaseLegdgers = $pandlConfiguration->details->where('type', 'COGS')->where('cogs_type', 'Food Purchase');
            $laborCostLegdgers = $pandlConfiguration->details->where('type', 'COGS')->where('cogs_type', 'Labor Cost');
            $franchiseFeeLegdgers = $pandlConfiguration->details->where('type', 'COGS')->where('cogs_type', 'Franchise fee');
            $retailLegdgers = $pandlConfiguration->details->where('type', 'COGS')->where('cogs_type', 'Retail');

            $expenseLegdgers = $pandlConfiguration->details->where('type', 'Expense');
            $pandlConfigurationLedgers =  $pandlConfiguration->details->pluck('ledgers')->toArray();
            $allLedgers = [];
            foreach($pandlConfigurationLedgers as $ledgers){
                $ledgers = explode(',', $ledgers);
                foreach($ledgers as $ledger){
                    if(!in_array($ledger, $allLedgers)){
                        $allLedgers[] = $ledger;
                    }
                }
            }
            $missingLedgers = [];
            // $pandlData['income'] = [];
            // $pandlData['cogs'] = [];
            // $pandlData['expense'] = [];
            $missingQuickqoreLedgers = [];
            $missingLedgers = [];

            foreach($ledger_codes as $ledger_code){
                if(!in_array($ledger_code, $allLedgers)){
                    $missingLedgers[$ledger_code] = $ledger_code . ' - ' . $ledger_codes_with_company[$ledger_code];
                }
            }
            foreach($allLedgers as $ledger){
                if(!in_array($ledger, $ledger_codes)){
                    $missingQuickqoreLedgers[$ledger] = $ledger;
                }
            }
            $missingLedgers && ksort($missingLedgers, SORT_NATURAL);
            $missingQuickqoreLedgers && ksort($missingQuickqoreLedgers, SORT_NATURAL);
            $totals=[];
            
            foreach($months as $month){
                if(isset($data[$month])){
                    $pandlMonthData = $data[$month];
                }else{
                    continue;
                }
                $totalIncome = 0;
                foreach($incomeLegdgers as $key => $incomeLedger){
                    !$isDetailed && $pandlData[$incomeLedger->label][$month] = 0;
                    $ledgers = explode(',', $incomeLedger->ledgers);
                    foreach($ledgers as $ledger){
                        if(isset($pandlMonthData['i_'.$ledger])){
                            !$isDetailed && $pandlData[$incomeLedger->label][$month] += $pandlMonthData['i_'.$ledger];
                            $isDetailed && $pandlData[$key.'_'.$incomeLedger->label.'_'.$ledger][$month] = $pandlMonthData['i_'.$ledger];
                            !$isDetailed && $totals[$incomeLedger->label]  =  (isset($totals[$incomeLedger->label]) ? $totals[$incomeLedger->label] : 0) + $pandlMonthData['i_'.$ledger];
                            $isDetailed && $totals[$key.'_'.$incomeLedger->label.'_'.$ledger] = (isset($totals[$key.'_'.$incomeLedger->label.'_'.$ledger]) ? $totals[$key.'_'.$incomeLedger->label.'_'.$ledger] : 0) + $pandlMonthData['i_'.$ledger];
                            $totalIncome += $pandlMonthData['i_'.$ledger];
                        }
                    }
                }
                $pandlData['Total Revenue'][$month] = $totalIncome;

                $totals['Total Revenue'] = (isset($totals['Total Revenue']) ? $totals['Total Revenue'] : 0) + $totalIncome;
                $totalFoodPurchase = 0;

                if(count($foodPurchaseLegdgers) > 0){
                    foreach($foodPurchaseLegdgers as $key => $foodPurchaseLedger){
                        !$isDetailed && $pandlData[$foodPurchaseLedger->label][$month] = 0;
                        $ledgers = explode(',', $foodPurchaseLedger->ledgers);
                        foreach($ledgers as $ledger){
                            if(isset($pandlMonthData['c_'.$ledger]) && $pandlMonthData['c_'.$ledger] !== 0){
                                !$isDetailed && $pandlData[$foodPurchaseLedger->label][$month] +=  (-1 * $pandlMonthData['c_'.$ledger]);
                                $isDetailed && $pandlData[$key.'_'.$foodPurchaseLedger->label.'_'.$ledger][$month] = (-1 * $pandlMonthData['c_'.$ledger]);
                                !$isDetailed && $totals[$foodPurchaseLedger->label]  =  (isset($totals[$foodPurchaseLedger->label]) ? $totals[$foodPurchaseLedger->label] : 0) + (-1 * $pandlMonthData['c_'.$ledger]);
                                $isDetailed && $totals[$key.'_'.$foodPurchaseLedger->label.'_'.$ledger] = (isset($totals[$key.'_'.$foodPurchaseLedger->label.'_'.$ledger]) ? $totals[$key.'_'.$foodPurchaseLedger->label.'_'.$ledger] : 0) + (-1 * $pandlMonthData['c_'.$ledger]);
                                $totalFoodPurchase += (-1 * $pandlMonthData['c_'.$ledger]);
                            }
                        }
                    }
                    $pandlData['Total Food Purchase'][$month] = $totalFoodPurchase;
                    $totals['Total Food Purchase'] = (isset($totals['Total Food Purchase']) ? $totals['Total Food Purchase'] : 0) + $totalFoodPurchase;
                    $pandlData['Food Percentage'][$month] = $totalIncome !== 0 ? ($totalFoodPurchase / $totalIncome * 100) : 0;
                    $totals['Food Percentage'] = $totals['Total Revenue'] !== 0 ? ($totals['Total Food Purchase'] / $totals['Total Revenue'] * 100) : 0;
                }


                $totalLaborCost = 0;

                if(count($laborCostLegdgers) > 0){
                    foreach($laborCostLegdgers as $key => $laborCostLedger){
                        !$isDetailed && $pandlData[$laborCostLedger->label][$month] = 0;
                        $ledgers = explode(',', $laborCostLedger->ledgers);
                        foreach($ledgers as $ledger){
                            if(isset($pandlMonthData['c_'.$ledger]) && $pandlMonthData['c_'.$ledger] !== 0){
                                !$isDetailed && $pandlData[$laborCostLedger->label][$month] +=  (-1 * $pandlMonthData['c_'.$ledger]);
                                $isDetailed && $pandlData[$key.'_'.$laborCostLedger->label.'_'.$ledger][$month] = (-1 * $pandlMonthData['c_'.$ledger]);
                                !$isDetailed && $totals[$laborCostLedger->label]  =  (isset($totals[$laborCostLedger->label]) ? $totals[$laborCostLedger->label] : 0) + (-1 * $pandlMonthData['c_'.$ledger]);
                                $isDetailed && $totals[$key.'_'.$laborCostLedger->label.'_'.$ledger] = (isset($totals[$key.'_'.$laborCostLedger->label.'_'.$ledger]) ? $totals[$key.'_'.$laborCostLedger->label.'_'.$ledger] : 0) + (-1 * $pandlMonthData['c_'.$ledger]);
                                $totalLaborCost += (-1 * $pandlMonthData['c_'.$ledger]);
                            }
                        }
                    }
                    $pandlData['Total Labor Cost'][$month] = $totalLaborCost;
                    $totals['Total Labor Cost'] = (isset($totals['Total Labor Cost']) ? $totals['Total Labor Cost'] : 0) + $totalLaborCost;
                    $pandlData['Labor Cost Percentage'][$month] = $totalIncome !== 0 ? ($totalLaborCost / $totalIncome * 100) : 0;
                    $totals['Labor Cost Percentage'] = $totals['Total Revenue'] !== 0 ? ($totals['Total Labor Cost'] / $totals['Total Revenue'] * 100) : 0;
                }
                
                $totalFranchiseFee = 0;
                if(count($franchiseFeeLegdgers) > 0){
                    foreach($franchiseFeeLegdgers as $key => $franchiseFeeLedger){
                        !$isDetailed && $pandlData[$franchiseFeeLedger->label][$month] = 0;
                        $ledgers = explode(',', $franchiseFeeLedger->ledgers);
                        foreach($ledgers as $ledger){
                            if(isset($pandlMonthData['c_'.$ledger]) && $pandlMonthData['c_'.$ledger] !== 0){
                                !$isDetailed && $pandlData[$franchiseFeeLedger->label][$month] +=  (-1 * $pandlMonthData['c_'.$ledger]);
                                $isDetailed && $pandlData[$key.'_'.$franchiseFeeLedger->label.'_'.$ledger][$month] = (-1 * $pandlMonthData['c_'.$ledger]);
                                !$isDetailed && $totals[$franchiseFeeLedger->label]  =  (isset($totals[$franchiseFeeLedger->label]) ? $totals[$franchiseFeeLedger->label] : 0) + (-1 * $pandlMonthData['c_'.$ledger]);
                                $isDetailed && $totals[$key.'_'.$franchiseFeeLedger->label.'_'.$ledger] = (isset($totals[$key.'_'.$franchiseFeeLedger->label.'_'.$ledger]) ? $totals[$key.'_'.$franchiseFeeLedger->label.'_'.$ledger] : 0) + (-1 * $pandlMonthData['c_'.$ledger]);
                                $totalFranchiseFee += (-1 * $pandlMonthData['c_'.$ledger]);
                            }
                        }
                    }
                    $pandlData['Total Franchise Fee'][$month] = $totalFranchiseFee;
                    $totals['Total Franchise Fee'] = (isset($totals['Total Franchise Fee']) ? $totals['Total Franchise Fee'] : 0) + $totalFranchiseFee;
                    $pandlData['Franchise Fee Percentage'][$month] = $totalIncome !== 0 ? ($totalFranchiseFee / $totalIncome * 100) : 0;
                    $totals['Franchise Fee Percentage'] = $totals['Total Revenue'] !== 0 ? ($totals['Total Franchise Fee'] / $totals['Total Revenue'] * 100) : 0;
                }


                $totalRetail = 0;
                if(count($retailLegdgers) > 0){
                    foreach($retailLegdgers as $key => $retailLedger){
                        !$isDetailed && $pandlData[$retailLedger->label][$month] = 0;
                        $ledgers = explode(',', $retailLedger->ledgers);
                        foreach($ledgers as $ledger){
                            if(isset($pandlMonthData['c_'.$ledger]) && $pandlMonthData['c_'.$ledger] !== 0){
                                !$isDetailed && $pandlData[$retailLedger->label][$month] +=  (-1 * $pandlMonthData['c_'.$ledger]);
                                $isDetailed && $pandlData[$key.'_'.$retailLedger->label.'_'.$ledger][$month] = (-1 * $pandlMonthData['c_'.$ledger]);
                                !$isDetailed && $totals[$retailLedger->label]  =  (isset($totals[$retailLedger->label]) ? $totals[$retailLedger->label] : 0) + (-1 * $pandlMonthData['c_'.$ledger]);
                                $isDetailed && $totals[$key.'_'.$retailLedger->label.'_'.$ledger] = (isset($totals[$key.'_'.$retailLedger->label.'_'.$ledger]) ? $totals[$key.'_'.$retailLedger->label.'_'.$ledger] : 0) + (-1 * $pandlMonthData['c_'.$ledger]);
                                $totalRetail += (-1 * $pandlMonthData['c_'.$ledger]);
                            }
                        }
                    }
                    $pandlData['Total Retail'][$month] = $totalRetail;
                    $totals['Total Retail'] = (isset($totals['Total Retail']) ? $totals['Total Retail'] : 0) + $totalRetail;

                    $pandlData['Retail Percentage'][$month] = $totalIncome !== 0 ? ($totalRetail / $totalIncome * 100) : 0;
                    $totals['Retail Percentage'] = $totals['Total Revenue'] !== 0 ? ($totals['Total Retail'] / $totals['Total Revenue'] * 100) : 0;

                }


                
                $totalCOGS = $totalFoodPurchase + $totalLaborCost + $totalFranchiseFee + $totalRetail;
                
                $pandlData['Total COGS'][$month] = $totalCOGS;
                $totals['Total COGS'] = (isset($totals['Total COGS']) ? $totals['Total COGS'] : 0) + $totalCOGS;
                
                $pandlData['COGS Percentage'][$month] = $totalIncome !== 0 ? ($totalCOGS / $totalIncome * 100) : 0;
                $totals['COGS Percentage'] = $totals['Total Revenue'] !== 0 ? ($totals['Total COGS'] / $totals['Total Revenue'] * 100) : 0;
                $totalExpense = 0;
                foreach($expenseLegdgers as $key => $expenseLedger){
                    !$isDetailed && $pandlData[$expenseLedger->label][$month] = 0;
                    $ledgers = explode(',', $expenseLedger->ledgers);
                    foreach($ledgers as $ledger){
                        if(isset($pandlMonthData['e_'.$ledger]) && $pandlMonthData['e_'.$ledger] !== 0){
                            !$isDetailed && $pandlData[$expenseLedger->label][$month] +=  (-1 * $pandlMonthData['e_'.$ledger]);
                            $isDetailed && $pandlData[$key.'_'.$expenseLedger->label.'_'.$ledger][$month] = (-1 * $pandlMonthData['e_'.$ledger]);
                            !$isDetailed && $totals[$expenseLedger->label]  =  (isset($totals[$expenseLedger->label]) ? $totals[$expenseLedger->label] : 0) + (-1 * $pandlMonthData['e_'.$ledger]);
                            $isDetailed && $totals[$key.'_'.$expenseLedger->label.'_'.$ledger] = (isset($totals[$key.'_'.$expenseLedger->label.'_'.$ledger]) ? $totals[$key.'_'.$expenseLedger->label.'_'.$ledger] : 0) + (-1 * $pandlMonthData['e_'.$ledger]);
                           
                            $totalExpense += (-1 * $pandlMonthData['e_'.$ledger]);
                        }
                    }
                }
                $pandlData['Total Expense'][$month] = $totalExpense;

                $totals['Total Expense'] = (isset($totals['Total Expense']) ? $totals['Total Expense'] : 0) + $totalExpense;
                $pandlData['Expense Percentage'][$month] = $totalIncome !== 0 ? ($totalExpense / $totalIncome * 100) : 0;
                $totals['Expense Percentage'] = $totals['Total Revenue'] !== 0 ? ($totals['Total Expense'] / $totals['Total Revenue'] * 100) : 0;
                
                $pandlData['Net Income'][$month] = $totalIncome - $totalCOGS - $totalExpense;
                $totals['Net Income'] = (isset($totals['Net Income']) ? $totals['Net Income'] : 0) + $totalIncome - $totalCOGS - $totalExpense;
                
                $pandlData['Net Income Percentage'][$month] = $totalIncome !== 0 ? ($pandlData['Net Income'][$month] / $totalIncome * 100) : 0;
                $totals['Net Income Percentage'] = $totals['Total Revenue'] !== 0 ? ($totals['Net Income'] / $totals['Total Revenue'] * 100) : 0;
            }


            $responseData =[];
            function addResponseData(&$responseData, $pandlData, $totals, $lables, $isDetailed, $ledgers_with_names) : int{
                $count = 0;
               foreach($lables as $key => $label){
                    if(!$isDetailed){
                        $nonZero = array_filter(isset($pandlData[$label['label']]) ? $pandlData[$label['label']] : [], function($value){
                            return $value !== 0;
                        });
                        if(count($nonZero) > 0){
                            $responseData[] = [
                                'label' => $label['label'],
                                    'value' => array_merge(isset($pandlData[$label['label']]) ? $pandlData[$label['label']] : [], ['total' => isset($totals[$label['label']]) ? $totals[$label['label']] : 0]),
                                ];
                                $count++;
                        }
                    }else{
                        $ledgers = explode(',', $label['ledgers']);
                        foreach($ledgers as $ledger){
                            $nonZero = array_filter(isset($pandlData[$key.'_'.$label['label'].'_'.$ledger]) ? $pandlData[$key.'_'.$label['label'].'_'.$ledger] : [], function($value){
                                return $value !== 0;
                            });
                            if(count($nonZero) > 0){
                                $responseData[] = [
                                    'label' => isset($ledgers_with_names[$ledger]) ? $ledger.' - '.$ledgers_with_names[$ledger] : $ledger.' - '.$ledger,
                                    'value' => array_merge(isset($pandlData[$key.'_'.$label['label'].'_'.$ledger]) ? $pandlData[$key.'_'.$label['label'].'_'.$ledger] : [], ['total' => isset($totals[$key.'_'.$label['label'].'_'.$ledger]) ? $totals[$key.'_'.$label['label'].'_'.$ledger] : 0]),
                                ];
                                $count++;
                            }
                        }
                    }
               }
               return $count;
            }
            $count = addResponseData($responseData, $pandlData, $totals, $incomeLegdgers, $isDetailed, $ledgers_with_names);
            $responseData[] = ['label' => 'Total Revenue', 'value' => array_merge(isset($pandlData['Total Revenue']) ? $pandlData['Total Revenue'] : [], ['total' => isset($totals['Total Revenue']) ? $totals['Total Revenue'] : 0])];

            $count = addResponseData($responseData, $pandlData, $totals, $foodPurchaseLegdgers, $isDetailed, $ledgers_with_names);
            ($count > 0) &&  $responseData[] = ['label' => 'Total Food Purchase', 'value' => array_merge(isset($pandlData['Total Food Purchase']) ? $pandlData['Total Food Purchase'] : [], ['total' => isset($totals['Total Food Purchase']) ? $totals['Total Food Purchase'] : 0])     ];
            ($count > 0) && $responseData[] = ['label' => 'Food Percentage', 'value' => array_merge(isset($pandlData['Food Percentage']) ? $pandlData['Food Percentage'] : [], ['total' => isset($totals['Food Percentage']) ? $totals['Food Percentage'] : 0])];
            
            $count = addResponseData($responseData, $pandlData, $totals, $laborCostLegdgers, $isDetailed, $ledgers_with_names);
            ($count > 0) && $responseData[] = ['label' => 'Total Labor Cost', 'value' => array_merge(isset($pandlData['Total Labor Cost']) ? $pandlData['Total Labor Cost'] : [], ['total' => isset($totals['Total Labor Cost']) ? $totals['Total Labor Cost'] : 0])];
            ($count > 0) && $responseData[] = ['label' => 'Labor Cost Percentage', 'value' => array_merge(isset($pandlData['Labor Cost Percentage']) ? $pandlData['Labor Cost Percentage'] : [], ['total' => isset($totals['Labor Cost Percentage']) ? $totals['Labor Cost Percentage'] : 0])];
            
            $count = addResponseData($responseData, $pandlData, $totals, $franchiseFeeLegdgers, $isDetailed, $ledgers_with_names);
            ($count > 0) && $responseData[] = ['label' => 'Total Franchise Fee', 'value' => array_merge(isset($pandlData['Total Franchise Fee']) ? $pandlData['Total Franchise Fee'] : [], ['total' => isset($totals['Total Franchise Fee']) ? $totals['Total Franchise Fee'] : 0])];
            ($count > 0) && $responseData[] = ['label' => 'Franchise Fee Percentage', 'value' => array_merge(isset($pandlData['Franchise Fee Percentage']) ? $pandlData['Franchise Fee Percentage'] : [], ['total' => isset($totals['Franchise Fee Percentage']) ? $totals['Franchise Fee Percentage'] : 0])];


            $count = addResponseData($responseData, $pandlData, $totals, $retailLegdgers, $isDetailed, $ledgers_with_names);
            ($count > 0) && $responseData[] = ['label' => 'Total Retail', 'value' => array_merge(isset($pandlData['Total Retail']) ? $pandlData['Total Retail'] : [], ['total' => isset($totals['Total Retail']) ? $totals['Total Retail'] : 0])];
            ($count > 0) && $responseData[] = ['label' => 'Retail Percentage', 'value' => array_merge(isset($pandlData['Retail Percentage']) ? $pandlData['Retail Percentage'] : [], ['total' => isset($totals['Retail Percentage']) ? $totals['Retail Percentage'] : 0])];

            $responseData[] = ['label' => 'Total COGS', 'value' => array_merge(isset($pandlData['Total COGS']) ? $pandlData['Total COGS'] : [], ['total' => isset($totals['Total COGS']) ? $totals['Total COGS'] : 0])];
            $responseData[] = ['label' => 'COGS Percentage', 'value' => array_merge(isset($pandlData['COGS Percentage']) ? $pandlData['COGS Percentage'] : [], ['total' => isset($totals['COGS Percentage']) ? $totals['COGS Percentage'] : 0])];
            
            addResponseData($responseData, $pandlData, $totals, $expenseLegdgers, $isDetailed, $ledgers_with_names);
            $responseData[] = ['label' => 'Total Expense', 'value' => array_merge(isset($pandlData['Total Expense']) ? $pandlData['Total Expense'] : [], ['total' => isset($totals['Total Expense']) ? $totals['Total Expense'] : 0])];
            $responseData[] = ['label' => 'Expense Percentage', 'value' => array_merge(isset($pandlData['Expense Percentage']) ? $pandlData['Expense Percentage'] : [], ['total' => isset($totals['Expense Percentage']) ? $totals['Expense Percentage'] : 0])];
           
            $responseData[] = ['label' => 'Net Income', 'value' => array_merge(isset($pandlData['Net Income']) ? $pandlData['Net Income'] : [], ['total' => isset($totals['Net Income']) ? $totals['Net Income'] : 0])];
            $responseData[] = ['label' => 'Net Income Percentage', 'value' => array_merge(isset($pandlData['Net Income Percentage']) ? $pandlData['Net Income Percentage'] : [], ['total' => isset($totals['Net Income Percentage']) ? $totals['Net Income Percentage'] : 0])];
            return response()->json([
                'success' => true,
                'months' => $months,
                'data' => $responseData,
                'missing_ledgers' => array_values($missingLedgers),
                'missing_quickqore_ledgers' => array_values($missingQuickqoreLedgers),
            ]);
        } catch (\Exception $e) {
            info($e);
            return response()->json([
                'success' => false,
                'message' => 'Something went wrong'
            ], 500);
        }
    }

    public function getStoreWiseFinanceReport(Request $request)
    {
        $this->authorize('access', 'store-wise-finance-report.index');

        $request->validate([
            'start_date' => ['nullable', 'date_format:Y-m-d', 'required_with:end_date'],
            'end_date' => ['nullable', 'date_format:Y-m-d', 'required_with:start_date', 'after_or_equal:start_date'],
        ]);
        
        try {
            $year = $request->input('year', Carbon::now()->year);
            $month = $request->input('month', Carbon::now()->month);
            $quarter = $request->input('quarter',null);
            $start_date = $request->input('start_date');
            $end_date = $request->input('end_date');

            if ($start_date || $end_date) {
                $month = null;
                $quarter = null;
            } elseif ($quarter) {
                $month = null;
            } elseif ($month) {
                $quarter = null;
            }

            $user_id = $request->input('user_id');
            $company_ids = $request->input('company_ids', []);
            $pandl_configuration_id = $request->input('pandl_configuration_id');
            $workgroup_id = $request->input('workgroup_id');
            $report_type = $request->input('report_type', 'summary');
            $isDetailed = $report_type === 'detailed';
            $workgroup=null;
            
            // Determine store numbers based on company_ids or user_id
            if (!empty($company_ids)) {
                // Use company_ids if provided
                $store_numbers = Company::whereIn('id', $company_ids)->pluck('store_number')->toArray();
            } elseif ($user_id) {
                // Fall back to user's companies if user_id is provided
                $user = User::find($user_id);
                $userCompanyIds = $user->companies_array;
                $store_numbers = Company::whereIn('id', $userCompanyIds)
                ->when($workgroup_id, function ($query) use ($workgroup_id) {
                    return $query->where('workgroup_id', $workgroup_id);
                })
                ->pluck('store_number')->toArray();
            } else if($workgroup_id) {
                $store_numbers = Company::where('workgroup_id', $workgroup_id)->pluck('store_number')->toArray();
                $workgroup = Workgroup::where('id',$workgroup_id)->select('id','name')->first();
            } else {
                $store_numbers = null;
            }
            $quickqoreService = new QuickqoreService();
            $response = $quickqoreService->handleStoreWiseFinanceReport([
                'year' => $year,
                'quarter' => $quarter,
                'month' => $month,
                'start_date' => $start_date,
                'end_date' => $end_date,
                'store_numbers' => $store_numbers,
                'workgroup'=>  $workgroup ?  $workgroup->name :null
            ]);

            $ledger_codes_with_company = $response['data']['ledger_codes_with_company'] ?? [];
            $companies = $response['data']['companies'];
            $data = $response['data']['data'];
            $ledgers_with_names = $response['data']['ledger_codes'];
            $ledger_codes = array_keys($response['data']['ledger_codes']);
            

            $pandlData =[];
            $pandlConfiguration = PandlConfiguration::with('details')->where('id', $pandl_configuration_id)->first();
            $incomeLegdgers = $pandlConfiguration->details->where('type', 'Income');
            $foodPurchaseLegdgers = $pandlConfiguration->details->where('type', 'COGS')->where('cogs_type', 'Food Purchase');
            $laborCostLegdgers = $pandlConfiguration->details->where('type', 'COGS')->where('cogs_type', 'Labor Cost');
            $franchiseFeeLegdgers = $pandlConfiguration->details->where('type', 'COGS')->where('cogs_type', 'Franchise fee');
            $retailLegdgers = $pandlConfiguration->details->where('type', 'COGS')->where('cogs_type', 'Retail');

            $expenseLegdgers = $pandlConfiguration->details->where('type', 'Expense');
            $pandlConfigurationLedgers =  $pandlConfiguration->details->pluck('ledgers')->toArray();
            $allLedgers = [];
            foreach($pandlConfigurationLedgers as $ledgers){
                $ledgers = explode(',', $ledgers);
                foreach($ledgers as $ledger){
                    if(!in_array($ledger, $allLedgers)){
                        $allLedgers[] = $ledger;
                    }
                }
            }

            


            $missingLedgers = [];
            // $pandlData['income'] = [];
            // $pandlData['cogs'] = [];
            // $pandlData['expense'] = [];
            $missingQuickqoreLedgers = [];
            $totals=[];

            foreach($ledger_codes as $ledger_code){
                if(!in_array($ledger_code, $allLedgers)){
                    $missingLedgers[$ledger_code] = $ledger_code . ' - ' . $ledger_codes_with_company[$ledger_code];
                }
            }
            foreach($allLedgers as $ledger){
                if(!in_array($ledger, $ledger_codes)){
                    $missingQuickqoreLedgers[$ledger] = $ledger;
                }
            }
            ksort($missingLedgers, SORT_NATURAL);
            ksort($missingQuickqoreLedgers, SORT_NATURAL);
            
            foreach($companies as $company){
                if(isset($data[$company])){
                    $pandlCompanyData = $data[$company];
                }else{
                    continue;
                }
                $totalIncome = 0;
                foreach($incomeLegdgers as $key => $incomeLedger){
                    !$isDetailed && $pandlData[$incomeLedger->label][$company] = 0;
                    $ledgers = explode(',', $incomeLedger->ledgers);
                    foreach($ledgers as $ledger){
                        if(isset($pandlCompanyData['i_'.$ledger])){
                            !$isDetailed && $pandlData[$incomeLedger->label][$company] += $pandlCompanyData['i_'.$ledger];
                            $isDetailed && $pandlData[$key.'_'.$incomeLedger->label.'_'.$ledger][$company] = $pandlCompanyData['i_'.$ledger];

                            !$isDetailed && $totals[$incomeLedger->label]  =  (isset($totals[$incomeLedger->label]) ? $totals[$incomeLedger->label] : 0) + $pandlCompanyData['i_'.$ledger];
                            $isDetailed && $totals[$key.'_'.$incomeLedger->label.'_'.$ledger] = (isset($totals[$key.'_'.$incomeLedger->label.'_'.$ledger]) ? $totals[$key.'_'.$incomeLedger->label.'_'.$ledger] : 0) + $pandlCompanyData['i_'.$ledger];

                            $totalIncome += $pandlCompanyData['i_'.$ledger];
                        }
                    }
                }
                $pandlData['Total Revenue'][$company] = $totalIncome;

                $totals['Total Revenue'] = (isset($totals['Total Revenue']) ? $totals['Total Revenue'] : 0) + $totalIncome;
                $totalFoodPurchase = 0;
                if(count($foodPurchaseLegdgers) > 0){
                    foreach($foodPurchaseLegdgers as $key => $foodPurchaseLedger){
                        !$isDetailed && $pandlData[$foodPurchaseLedger->label][$company] = 0;
                        $ledgers = explode(',', $foodPurchaseLedger->ledgers);
                        foreach($ledgers as $ledger){
                            if(isset($pandlCompanyData['c_'.$ledger]) && $pandlCompanyData['c_'.$ledger] !== 0){
                                !$isDetailed && $pandlData[$foodPurchaseLedger->label][$company] +=  (-1 * $pandlCompanyData['c_'.$ledger]);
                                $isDetailed && $pandlData[$key.'_'.$foodPurchaseLedger->label.'_'.$ledger][$company] = (-1 * $pandlCompanyData['c_'.$ledger]);
                                !$isDetailed && $totals[$foodPurchaseLedger->label]  =  (isset($totals[$foodPurchaseLedger->label]) ? $totals[$foodPurchaseLedger->label] : 0) + (-1 * $pandlCompanyData['c_'.$ledger]);
                                $isDetailed && $totals[$key.'_'.$foodPurchaseLedger->label.'_'.$ledger] = (isset($totals[$key.'_'.$foodPurchaseLedger->label.'_'.$ledger]) ? $totals[$key.'_'.$foodPurchaseLedger->label.'_'.$ledger] : 0) + (-1 * $pandlCompanyData['c_'.$ledger]);
                                $totalFoodPurchase += (-1 * $pandlCompanyData['c_'.$ledger]);
                            }
                        }
                    }
                    $pandlData['Total Food Purchase'][$company] = $totalFoodPurchase;
                    $totals['Total Food Purchase'] = (isset($totals['Total Food Purchase']) ? $totals['Total Food Purchase'] : 0) + $totalFoodPurchase;
                    $pandlData['Food Percentage'][$company] = $totalIncome !== 0 ? ($totalFoodPurchase / $totalIncome * 100) : 0;
                    $totals['Food Percentage'] = $totals['Total Revenue'] !== 0 ? ($totals['Total Food Purchase'] / $totals['Total Revenue'] * 100) : 0;
                
                }
                $totalLaborCost = 0;
                if(count($laborCostLegdgers) > 0){      
                    foreach($laborCostLegdgers as $key => $laborCostLedger){
                        !$isDetailed && $pandlData[$laborCostLedger->label][$company] = 0;
                        $ledgers = explode(',', $laborCostLedger->ledgers);
                        foreach($ledgers as $ledger){
                            if(isset($pandlCompanyData['c_'.$ledger]) && $pandlCompanyData['c_'.$ledger] !== 0){
                                !$isDetailed && $pandlData[$laborCostLedger->label][$company] +=  (-1 * $pandlCompanyData['c_'.$ledger]);
                                $isDetailed && $pandlData[$key.'_'.$laborCostLedger->label.'_'.$ledger][$company] = (-1 * $pandlCompanyData['c_'.$ledger]);
                                !$isDetailed && $totals[$laborCostLedger->label]  =  (isset($totals[$laborCostLedger->label]) ? $totals[$laborCostLedger->label] : 0) + (-1 * $pandlCompanyData['c_'.$ledger]);
                                $isDetailed && $totals[$key.'_'.$laborCostLedger->label.'_'.$ledger] = (isset($totals[$key.'_'.$laborCostLedger->label.'_'.$ledger]) ? $totals[$key.'_'.$laborCostLedger->label.'_'.$ledger] : 0) + (-1 * $pandlCompanyData['c_'.$ledger]);
                                $totalLaborCost += (-1 * $pandlCompanyData['c_'.$ledger]);
                            }
                        }
                    }
                    $pandlData['Total Labor Cost'][$company] = $totalLaborCost;
                    $totals['Total Labor Cost'] = (isset($totals['Total Labor Cost']) ? $totals['Total Labor Cost'] : 0) + $totalLaborCost;
                    $pandlData['Labor Cost Percentage'][$company] = $totalIncome !== 0 ? ($totalLaborCost / $totalIncome * 100) : 0;
                    $totals['Labor Cost Percentage'] = $totals['Total Revenue'] !== 0 ? ($totals['Total Labor Cost'] / $totals['Total Revenue'] * 100) : 0;
                }
                
                $totalFranchiseFee = 0;
                if(count($franchiseFeeLegdgers) > 0){
                    foreach($franchiseFeeLegdgers as $key => $franchiseFeeLedger){
                        !$isDetailed && $pandlData[$franchiseFeeLedger->label][$company] = 0;
                        $ledgers = explode(',', $franchiseFeeLedger->ledgers);
                        foreach($ledgers as $ledger){
                            if(isset($pandlCompanyData['c_'.$ledger]) && $pandlCompanyData['c_'.$ledger] !== 0){
                                !$isDetailed && $pandlData[$franchiseFeeLedger->label][$company] +=  (-1 * $pandlCompanyData['c_'.$ledger]);
                                $isDetailed && $pandlData[$key.'_'.$franchiseFeeLedger->label.'_'.$ledger][$company] = (-1 * $pandlCompanyData['c_'.$ledger]);
                                !$isDetailed && $totals[$franchiseFeeLedger->label]  =  (isset($totals[$franchiseFeeLedger->label]) ? $totals[$franchiseFeeLedger->label] : 0) + (-1 * $pandlCompanyData['c_'.$ledger]);
                                $isDetailed && $totals[$key.'_'.$franchiseFeeLedger->label.'_'.$ledger] = (isset($totals[$key.'_'.$franchiseFeeLedger->label.'_'.$ledger]) ? $totals[$key.'_'.$franchiseFeeLedger->label.'_'.$ledger] : 0) + (-1 * $pandlCompanyData['c_'.$ledger]);
                                $totalFranchiseFee += (-1 * $pandlCompanyData['c_'.$ledger]);
                            }
                        }
                    }
                    $pandlData['Total Franchise Fee'][$company] = $totalFranchiseFee;
                    $totals['Total Franchise Fee'] = (isset($totals['Total Franchise Fee']) ? $totals['Total Franchise Fee'] : 0) + $totalFranchiseFee;

                    $pandlData['Franchise Fee Percentage'][$company] = $totalIncome !== 0 ? ($totalFranchiseFee / $totalIncome * 100) : 0;
                    $totals['Franchise Fee Percentage'] = $totals['Total Revenue'] !== 0 ? ($totals['Total Franchise Fee'] / $totals['Total Revenue'] * 100) : 0;
                }


                $totalRetail = 0;
                if(count($retailLegdgers) > 0){
                    foreach($retailLegdgers as $key => $retailLedger){
                        !$isDetailed && $pandlData[$retailLedger->label][$company] = 0;
                        $ledgers = explode(',', $retailLedger->ledgers);
                        foreach($ledgers as $ledger){
                            if(isset($pandlCompanyData['c_'.$ledger]) && $pandlCompanyData['c_'.$ledger] !== 0){
                                !$isDetailed && $pandlData[$retailLedger->label][$company] +=  (-1 * $pandlCompanyData['c_'.$ledger]);
                                $isDetailed && $pandlData[$key.'_'.$retailLedger->label.'_'.$ledger][$company] = (-1 * $pandlCompanyData['c_'.$ledger]);
                                !$isDetailed && $totals[$retailLedger->label]  =  (isset($totals[$retailLedger->label]) ? $totals[$retailLedger->label] : 0) + (-1 * $pandlCompanyData['c_'.$ledger]);
                                $isDetailed && $totals[$key.'_'.$retailLedger->label.'_'.$ledger] = (isset($totals[$key.'_'.$retailLedger->label.'_'.$ledger]) ? $totals[$key.'_'.$retailLedger->label.'_'.$ledger] : 0) + (-1 * $pandlCompanyData['c_'.$ledger]);
                                $totalRetail += (-1 * $pandlCompanyData['c_'.$ledger]);
                            }
                        }
                    }
                    $pandlData['Total Retail'][$company] = $totalRetail;
                    $totals['Total Retail'] = (isset($totals['Total Retail']) ? $totals['Total Retail'] : 0) + $totalRetail;

                    $pandlData['Retail Percentage'][$company] = $totalIncome !== 0 ? ($totalRetail / $totalIncome * 100) : 0;
                    $totals['Retail Percentage'] = $totals['Total Revenue'] !== 0 ? ($totals['Total Retail'] / $totals['Total Revenue'] * 100) : 0;
                }



                $totalCOGS = $totalFoodPurchase + $totalLaborCost + $totalFranchiseFee + $totalRetail;
                
                $pandlData['Total COGS'][$company] = $totalCOGS;
                $totals['Total COGS'] = (isset($totals['Total COGS']) ? $totals['Total COGS'] : 0) + $totalCOGS;
                
                $pandlData['COGS Percentage'][$company] = $totalIncome !== 0 ? ($totalCOGS / $totalIncome * 100) : 0;
                $totals['COGS Percentage'] = $totals['Total Revenue'] !== 0 ? ($totals['Total COGS'] / $totals['Total Revenue'] * 100) : 0;


                $totalExpense = 0;
                foreach($expenseLegdgers as $key => $expenseLedger){
                    !$isDetailed && $pandlData[$expenseLedger->label][$company] = 0;
                    $ledgers = explode(',', $expenseLedger->ledgers);
                    foreach($ledgers as $ledger){
                        if(isset($pandlCompanyData['e_'.$ledger]) && $pandlCompanyData['e_'.$ledger] !== 0){
                            !$isDetailed && $pandlData[$expenseLedger->label][$company] +=  (-1 * $pandlCompanyData['e_'.$ledger]);
                            $isDetailed && $pandlData[$key.'_'.$expenseLedger->label.'_'.$ledger][$company] = (-1 * $pandlCompanyData['e_'.$ledger]);
                            !$isDetailed && $totals[$expenseLedger->label]  =  (isset($totals[$expenseLedger->label]) ? $totals[$expenseLedger->label] : 0) + (-1 * $pandlCompanyData['e_'.$ledger]);
                            $isDetailed && $totals[$key.'_'.$expenseLedger->label.'_'.$ledger] = (isset($totals[$key.'_'.$expenseLedger->label.'_'.$ledger]) ? $totals[$key.'_'.$expenseLedger->label.'_'.$ledger] : 0) + (-1 * $pandlCompanyData['e_'.$ledger]);
                            $totalExpense += (-1 * $pandlCompanyData['e_'.$ledger]);
                        }
                    }
                }
                $pandlData['Total Expense'][$company] = $totalExpense;

                $totals['Total Expense'] = (isset($totals['Total Expense']) ? $totals['Total Expense'] : 0) + $totalExpense;
                $pandlData['Expense Percentage'][$company] = $totalIncome !== 0 ? ($totalExpense / $totalIncome * 100) : 0;
                $totals['Expense Percentage'] = $totals['Total Revenue'] !== 0 ? ($totals['Total Expense'] / $totals['Total Revenue'] * 100) : 0;
                
                $pandlData['Net Income'][$company] = $totalIncome - $totalCOGS - $totalExpense;
                $totals['Net Income'] = (isset($totals['Net Income']) ? $totals['Net Income'] : 0) + $totalIncome - $totalCOGS - $totalExpense;
                
                $pandlData['Net Income Percentage'][$company] = $totalIncome !== 0 ? ($pandlData['Net Income'][$company] / $totalIncome * 100) : 0;
                $totals['Net Income Percentage'] = $totals['Total Revenue'] !== 0 ? ($totals['Net Income'] / $totals['Total Revenue'] * 100) : 0;
            }


            $responseData =[];
            function addResponseDataStoreWise(&$responseData, $pandlData, $totals, $lables, $isDetailed, $ledgers_with_names) : int{
                $count = 0;
               foreach($lables as $key => $label){
                    if(!$isDetailed){
                        $nonZero = array_filter(isset($pandlData[$label['label']]) ? $pandlData[$label['label']] : [], function($value){
                            return $value !== 0;
                        });
                        if(count($nonZero) > 0){
                            $responseData[] = [
                                'label' => $label['label'],
                                'value' => array_merge(isset($pandlData[$label['label']]) ? $pandlData[$label['label']] : [], ['total' => isset($totals[$label['label']]) ? $totals[$label['label']] : 0]),
                            ];
                            $count++;
                        }
                    }else{
                        $ledgers = explode(',', $label['ledgers']);
                        foreach($ledgers as $ledger){
                            $nonZero = array_filter(isset($pandlData[$key.'_'.$label['label'].'_'.$ledger]) ? $pandlData[$key.'_'.$label['label'].'_'.$ledger] : [], function($value){
                                return $value !== 0;
                            });
                            if(count($nonZero) > 0){
                                $responseData[] = [
                                    'label' => isset($ledgers_with_names[$ledger]) ? $ledger.' - '.$ledgers_with_names[$ledger] : $ledger.' - '.$ledger,
                                    'value' => array_merge(isset($pandlData[$key.'_'.$label['label'].'_'.$ledger]) ? $pandlData[$key.'_'.$label['label'].'_'.$ledger] : [], ['total' => isset($totals[$key.'_'.$label['label'].'_'.$ledger]) ? $totals[$key.'_'.$label['label'].'_'.$ledger] : 0]),
                                ];
                                $count++;
                            }
                        }
                    }
               }
               return $count;
            }
            $count = addResponseDataStoreWise($responseData, $pandlData, $totals, $incomeLegdgers, $isDetailed, $ledgers_with_names);
            $responseData[] = ['label' => 'Total Revenue', 'value' => array_merge(isset($pandlData['Total Revenue']) ? $pandlData['Total Revenue'] : [], ['total' => isset($totals['Total Revenue']) ? $totals['Total Revenue'] : 0])];

            $count = addResponseDataStoreWise($responseData, $pandlData, $totals, $foodPurchaseLegdgers, $isDetailed, $ledgers_with_names);
            ($count > 0) &&  $responseData[] = ['label' => 'Total Food Purchase', 'value' => array_merge(isset($pandlData['Total Food Purchase']) ? $pandlData['Total Food Purchase'] : [], ['total' => isset($totals['Total Food Purchase']) ? $totals['Total Food Purchase'] : 0])     ];
            ($count > 0) && $responseData[] = ['label' => 'Food Percentage', 'value' => array_merge(isset($pandlData['Food Percentage']) ? $pandlData['Food Percentage'] : [], ['total' => isset($totals['Food Percentage']) ? $totals['Food Percentage'] : 0])];
            
            $count = addResponseDataStoreWise($responseData, $pandlData, $totals, $laborCostLegdgers, $isDetailed, $ledgers_with_names);
            ($count > 0) && $responseData[] = ['label' => 'Total Labor Cost', 'value' => array_merge(isset($pandlData['Total Labor Cost']) ? $pandlData['Total Labor Cost'] : [], ['total' => isset($totals['Total Labor Cost']) ? $totals['Total Labor Cost'] : 0])];
            ($count > 0) && $responseData[] = ['label' => 'Labor Cost Percentage', 'value' => array_merge(isset($pandlData['Labor Cost Percentage']) ? $pandlData['Labor Cost Percentage'] : [], ['total' => isset($totals['Labor Cost Percentage']) ? $totals['Labor Cost Percentage'] : 0])];
            
            $count = addResponseDataStoreWise($responseData, $pandlData, $totals, $franchiseFeeLegdgers, $isDetailed, $ledgers_with_names);
            ($count > 0) && $responseData[] = ['label' => 'Total Franchise Fee', 'value' => array_merge(isset($pandlData['Total Franchise Fee']) ? $pandlData['Total Franchise Fee'] : [], ['total' => isset($totals['Total Franchise Fee']) ? $totals['Total Franchise Fee'] : 0])];
            ($count > 0) && $responseData[] = ['label' => 'Franchise Fee Percentage', 'value' => array_merge(isset($pandlData['Franchise Fee Percentage']) ? $pandlData['Franchise Fee Percentage'] : [], ['total' => isset($totals['Franchise Fee Percentage']) ? $totals['Franchise Fee Percentage'] : 0])];


            $count = addResponseDataStoreWise($responseData, $pandlData, $totals, $retailLegdgers, $isDetailed, $ledgers_with_names);
            ($count > 0) && $responseData[] = ['label' => 'Total Retail', 'value' => array_merge(isset($pandlData['Total Retail']) ? $pandlData['Total Retail'] : [], ['total' => isset($totals['Total Retail']) ? $totals['Total Retail'] : 0])];
            ($count > 0) && $responseData[] = ['label' => 'Retail Percentage', 'value' => array_merge(isset($pandlData['Retail Percentage']) ? $pandlData['Retail Percentage'] : [], ['total' => isset($totals['Retail Percentage']) ? $totals['Retail Percentage'] : 0])];

            $responseData[] = ['label' => 'Total COGS', 'value' => array_merge(isset($pandlData['Total COGS']) ? $pandlData['Total COGS'] : [], ['total' => isset($totals['Total COGS']) ? $totals['Total COGS'] : 0])];
            $responseData[] = ['label' => 'COGS Percentage', 'value' => array_merge(isset($pandlData['COGS Percentage']) ? $pandlData['COGS Percentage'] : [], ['total' => isset($totals['COGS Percentage']) ? $totals['COGS Percentage'] : 0])];
            
            $count = addResponseDataStoreWise($responseData, $pandlData, $totals, $expenseLegdgers, $isDetailed, $ledgers_with_names);
             $responseData[] = ['label' => 'Total Expense', 'value' => array_merge(isset($pandlData['Total Expense']) ? $pandlData['Total Expense'] : [], ['total' => isset($totals['Total Expense']) ? $totals['Total Expense'] : 0])];
             $responseData[] = ['label' => 'Expense Percentage', 'value' => array_merge(isset($pandlData['Expense Percentage']) ? $pandlData['Expense Percentage'] : [], ['total' => isset($totals['Expense Percentage']) ? $totals['Expense Percentage'] : 0])];
           
            $responseData[] = ['label' => 'Net Income', 'value' => array_merge(isset($pandlData['Net Income']) ? $pandlData['Net Income'] : [], ['total' => isset($totals['Net Income']) ? $totals['Net Income'] : 0])];
            $responseData[] = ['label' => 'Net Income Percentage', 'value' => array_merge(isset($pandlData['Net Income Percentage']) ? $pandlData['Net Income Percentage'] : [], ['total' => isset($totals['Net Income Percentage']) ? $totals['Net Income Percentage'] : 0])];
            return response()->json([
                'success' => true,
                'companies' => $companies,
                'data' => $responseData,
                'missing_ledgers' => (array_values($missingLedgers)),
                'missing_quickqore_ledgers' => (array_values($missingQuickqoreLedgers)),
            ]);
        } catch (\Exception $e) { 
            info($e);  
            return response()->json([
                'success' => false,
                'message' => 'Something went wrong'
            ], 500);
        }
    }

    public function getStoreWiseProfitAndLossReport(Request $request)
    {
        $this->authorize('access', 'store-wise-finance-report.index');
        
        try {
            $year = $request->input('year', Carbon::now()->year);
            $month = $request->input('month', Carbon::now()->month);
            $quarter = $request->input('quarter',null);
            $user_id = $request->input('user_id');
            $company_ids = $request->input('company_ids', []);
            $pandl_configuration_id = $request->input('pandl_configuration_id');
            $workgroup_id = $request->input('workgroup_id');
            $report_type = $request->input('report_type', 'summary');
            $isDetailed = $report_type === 'detailed';
            $workgroup=null;
            $companyWiseIncome = [];
            
            // Determine store numbers based on company_ids or user_id
            if (!empty($company_ids)) {
                // Use company_ids if provided
                $store_numbers = Company::whereIn('id', $company_ids)->pluck('store_number')->toArray();
            } elseif ($user_id) {
                // Fall back to user's companies if user_id is provided
                $user = User::find($user_id);
                $userCompanyIds = $user->companies_array;
                $store_numbers = Company::whereIn('id', $userCompanyIds)
                ->when($workgroup_id, function ($query) use ($workgroup_id) {
                    return $query->where('workgroup_id', $workgroup_id);
                })
                ->pluck('store_number')->toArray();
            } else if($workgroup_id) {
                $store_numbers = Company::where('workgroup_id', $workgroup_id)->pluck('store_number')->toArray();
                $workgroup = Workgroup::where('id',$workgroup_id)->select('id','name')->first();
            } else {
                $store_numbers = null;
            }

            $quickqoreService = new QuickqoreService();
            $response = $quickqoreService->handleStoreWiseFinanceReport([
                'year' => $year,
                'quarter' => $quarter,
                'month' => $month,
                'store_numbers' => $store_numbers,
                'workgroup'=>  $workgroup ?  $workgroup->name :null
            ]);
            $ledger_codes_with_company = $response['data']['ledger_codes_with_company'] ?? [];

            $companies = $response['data']['companies'];
            $data = $response['data']['data'];
            $ledgers_with_names = $response['data']['ledger_codes'];
            $ledger_codes = array_keys($response['data']['ledger_codes']);
            

            $pandlData =[];
            $pandlConfiguration = PandlConfiguration::with('details')->where('id', $pandl_configuration_id)->first();
            $incomeLegdgers = $pandlConfiguration->details->where('type', 'Income');
            $foodPurchaseLegdgers = $pandlConfiguration->details->where('type', 'COGS')->where('cogs_type', 'Food Purchase');
            $laborCostLegdgers = $pandlConfiguration->details->where('type', 'COGS')->where('cogs_type', 'Labor Cost');
            $franchiseFeeLegdgers = $pandlConfiguration->details->where('type', 'COGS')->where('cogs_type', 'Franchise fee');
            $retailLegdgers = $pandlConfiguration->details->where('type', 'COGS')->where('cogs_type', 'Retail');

            $expenseLegdgers = $pandlConfiguration->details->where('type', 'Expense');
            $pandlConfigurationLedgers =  $pandlConfiguration->details->pluck('ledgers')->toArray();
            $allLedgers = [];
            foreach($pandlConfigurationLedgers as $ledgers){
                $ledgers = explode(',', $ledgers);
                foreach($ledgers as $ledger){
                    if(!in_array($ledger, $allLedgers)){
                        $allLedgers[] = $ledger;
                    }
                }
            }

            


            $missingLedgers = [];
            // $pandlData['income'] = [];
            // $pandlData['cogs'] = [];
            // $pandlData['expense'] = [];
            $missingQuickqoreLedgers = [];
            $totals=[];

            foreach($ledger_codes as $ledger_code){
                if(!in_array($ledger_code, $allLedgers)){
                    $missingLedgers[$ledger_code] = $ledger_code . ' - ' . $ledger_codes_with_company[$ledger_code];
                }
            }
            foreach($allLedgers as $ledger){
                if(!in_array($ledger, $ledger_codes)){
                    $missingQuickqoreLedgers[$ledger] = $ledger;
                }
            }
            ksort($missingLedgers, SORT_NATURAL);
            ksort($missingQuickqoreLedgers, SORT_NATURAL);
            
            foreach($companies as $company){
                if(isset($data[$company])){
                    $pandlCompanyData = $data[$company];
                }else{
                    continue;
                }
                $totalIncome = 0;
                foreach($incomeLegdgers as $key => $incomeLedger){
                    !$isDetailed && $pandlData[$incomeLedger->label][$company] = 0;
                    $ledgers = explode(',', $incomeLedger->ledgers);
                    foreach($ledgers as $ledger){
                        if(isset($pandlCompanyData['i_'.$ledger])){
                            !$isDetailed && $pandlData[$incomeLedger->label][$company] += $pandlCompanyData['i_'.$ledger];
                            $isDetailed && $pandlData[$key.'_'.$incomeLedger->label.'_'.$ledger][$company] = $pandlCompanyData['i_'.$ledger];

                            !$isDetailed && $totals[$incomeLedger->label]  =  (isset($totals[$incomeLedger->label]) ? $totals[$incomeLedger->label] : 0) + $pandlCompanyData['i_'.$ledger];
                            $isDetailed && $totals[$key.'_'.$incomeLedger->label.'_'.$ledger] = (isset($totals[$key.'_'.$incomeLedger->label.'_'.$ledger]) ? $totals[$key.'_'.$incomeLedger->label.'_'.$ledger] : 0) + $pandlCompanyData['i_'.$ledger];

                            $totalIncome += $pandlCompanyData['i_'.$ledger];
                        }
                    }
                }
                $pandlData['Sales'][$company] = $totalIncome;

                $totals['Sales'] = (isset($totals['Sales']) ? $totals['Sales'] : 0) + $totalIncome;
                $totalFoodPurchase = 0;
                if(count($foodPurchaseLegdgers) > 0){
                    foreach($foodPurchaseLegdgers as $key => $foodPurchaseLedger){
                        !$isDetailed && $pandlData[$foodPurchaseLedger->label][$company] = 0;
                        $ledgers = explode(',', $foodPurchaseLedger->ledgers);
                        foreach($ledgers as $ledger){
                            if(isset($pandlCompanyData['c_'.$ledger]) && $pandlCompanyData['c_'.$ledger] !== 0){
                                !$isDetailed && $pandlData[$foodPurchaseLedger->label][$company] +=  (-1 * $pandlCompanyData['c_'.$ledger]);
                                $isDetailed && $pandlData[$key.'_'.$foodPurchaseLedger->label.'_'.$ledger][$company] = (-1 * $pandlCompanyData['c_'.$ledger]);
                                !$isDetailed && $totals[$foodPurchaseLedger->label]  =  (isset($totals[$foodPurchaseLedger->label]) ? $totals[$foodPurchaseLedger->label] : 0) + (-1 * $pandlCompanyData['c_'.$ledger]);
                                $isDetailed && $totals[$key.'_'.$foodPurchaseLedger->label.'_'.$ledger] = (isset($totals[$key.'_'.$foodPurchaseLedger->label.'_'.$ledger]) ? $totals[$key.'_'.$foodPurchaseLedger->label.'_'.$ledger] : 0) + (-1 * $pandlCompanyData['c_'.$ledger]);
                                $totalFoodPurchase += (-1 * $pandlCompanyData['c_'.$ledger]);
                            }
                        }
                    }
                    $pandlData['Total Food Purchase'][$company] = $totalFoodPurchase;
                    $totals['Total Food Purchase'] = (isset($totals['Total Food Purchase']) ? $totals['Total Food Purchase'] : 0) + $totalFoodPurchase;
                    $pandlData['Food %'][$company] = $totalIncome !== 0 ? ($totalFoodPurchase / $totalIncome * 100) : 0;
                    $totals['Food %'] = $totals['Sales'] !== 0 ? ($totals['Total Food Purchase'] / $totals['Sales'] * 100) : 0;
                
                }
                $totalLaborCost = 0;
                if(count($laborCostLegdgers) > 0){      
                    foreach($laborCostLegdgers as $key => $laborCostLedger){
                        !$isDetailed && $pandlData[$laborCostLedger->label][$company] = 0;
                        $ledgers = explode(',', $laborCostLedger->ledgers);
                        foreach($ledgers as $ledger){
                            if(isset($pandlCompanyData['c_'.$ledger]) && $pandlCompanyData['c_'.$ledger] !== 0){
                                !$isDetailed && $pandlData[$laborCostLedger->label][$company] +=  (-1 * $pandlCompanyData['c_'.$ledger]);
                                $isDetailed && $pandlData[$key.'_'.$laborCostLedger->label.'_'.$ledger][$company] = (-1 * $pandlCompanyData['c_'.$ledger]);
                                !$isDetailed && $totals[$laborCostLedger->label]  =  (isset($totals[$laborCostLedger->label]) ? $totals[$laborCostLedger->label] : 0) + (-1 * $pandlCompanyData['c_'.$ledger]);
                                $isDetailed && $totals[$key.'_'.$laborCostLedger->label.'_'.$ledger] = (isset($totals[$key.'_'.$laborCostLedger->label.'_'.$ledger]) ? $totals[$key.'_'.$laborCostLedger->label.'_'.$ledger] : 0) + (-1 * $pandlCompanyData['c_'.$ledger]);
                                $totalLaborCost += (-1 * $pandlCompanyData['c_'.$ledger]);
                            }
                        }
                    }
                    $pandlData['Total Labor Cost'][$company] = $totalLaborCost;
                    $totals['Total Labor Cost'] = (isset($totals['Total Labor Cost']) ? $totals['Total Labor Cost'] : 0) + $totalLaborCost;
                    $pandlData['Labour %'][$company] = $totalIncome !== 0 ? ($totalLaborCost / $totalIncome * 100) : 0;
                    $totals['Labour %'] = $totals['Sales'] !== 0 ? ($totals['Total Labor Cost'] / $totals['Sales'] * 100) : 0;
                }
                
                $totalFranchiseFee = 0;
                if(count($franchiseFeeLegdgers) > 0){
                    foreach($franchiseFeeLegdgers as $key => $franchiseFeeLedger){
                        !$isDetailed && $pandlData[$franchiseFeeLedger->label][$company] = 0;
                        $ledgers = explode(',', $franchiseFeeLedger->ledgers);
                        foreach($ledgers as $ledger){
                            if(isset($pandlCompanyData['c_'.$ledger]) && $pandlCompanyData['c_'.$ledger] !== 0){
                                !$isDetailed && $pandlData[$franchiseFeeLedger->label][$company] +=  (-1 * $pandlCompanyData['c_'.$ledger]);
                                $isDetailed && $pandlData[$key.'_'.$franchiseFeeLedger->label.'_'.$ledger][$company] = (-1 * $pandlCompanyData['c_'.$ledger]);
                                !$isDetailed && $totals[$franchiseFeeLedger->label]  =  (isset($totals[$franchiseFeeLedger->label]) ? $totals[$franchiseFeeLedger->label] : 0) + (-1 * $pandlCompanyData['c_'.$ledger]);
                                $isDetailed && $totals[$key.'_'.$franchiseFeeLedger->label.'_'.$ledger] = (isset($totals[$key.'_'.$franchiseFeeLedger->label.'_'.$ledger]) ? $totals[$key.'_'.$franchiseFeeLedger->label.'_'.$ledger] : 0) + (-1 * $pandlCompanyData['c_'.$ledger]);
                                $totalFranchiseFee += (-1 * $pandlCompanyData['c_'.$ledger]);
                            }
                        }
                    }
                    $pandlData['Total Franchise Fee'][$company] = $totalFranchiseFee;
                    $totals['Total Franchise Fee'] = (isset($totals['Total Franchise Fee']) ? $totals['Total Franchise Fee'] : 0) + $totalFranchiseFee;

                    $pandlData['Fees %'][$company] = $totalIncome !== 0 ? ($totalFranchiseFee / $totalIncome * 100) : 0;
                    $totals['Fees %'] = $totals['Sales'] !== 0 ? ($totals['Total Franchise Fee'] / $totals['Sales'] * 100) : 0;
                }


                $totalRetail = 0;
                if(count($retailLegdgers) > 0){
                    foreach($retailLegdgers as $key => $retailLedger){
                        !$isDetailed && $pandlData[$retailLedger->label][$company] = 0;
                        $ledgers = explode(',', $retailLedger->ledgers);
                        foreach($ledgers as $ledger){
                            if(isset($pandlCompanyData['c_'.$ledger]) && $pandlCompanyData['c_'.$ledger] !== 0){
                                !$isDetailed && $pandlData[$retailLedger->label][$company] +=  (-1 * $pandlCompanyData['c_'.$ledger]);
                                $isDetailed && $pandlData[$key.'_'.$retailLedger->label.'_'.$ledger][$company] = (-1 * $pandlCompanyData['c_'.$ledger]);
                                !$isDetailed && $totals[$retailLedger->label]  =  (isset($totals[$retailLedger->label]) ? $totals[$retailLedger->label] : 0) + (-1 * $pandlCompanyData['c_'.$ledger]);
                                $isDetailed && $totals[$key.'_'.$retailLedger->label.'_'.$ledger] = (isset($totals[$key.'_'.$retailLedger->label.'_'.$ledger]) ? $totals[$key.'_'.$retailLedger->label.'_'.$ledger] : 0) + (-1 * $pandlCompanyData['c_'.$ledger]);
                                $totalRetail += (-1 * $pandlCompanyData['c_'.$ledger]);
                            }
                        }
                    }
                    $pandlData['Total Retail'][$company] = $totalRetail;
                    $totals['Total Retail'] = (isset($totals['Total Retail']) ? $totals['Total Retail'] : 0) + $totalRetail;

                    $pandlData['Retail %'][$company] = $totalIncome !== 0 ? ($totalRetail / $totalIncome * 100) : 0;
                    $totals['Retail %'] = $totals['Sales'] !== 0 ? ($totals['Total Retail'] / $totals['Sales'] * 100) : 0;
                }



                $totalCOGS = $totalFoodPurchase + $totalLaborCost + $totalFranchiseFee + $totalRetail;
                
                $pandlData['Total COGS'][$company] = $totalCOGS;
                $totals['Total COGS'] = (isset($totals['Total COGS']) ? $totals['Total COGS'] : 0) + $totalCOGS;
                
                $pandlData['COGS %'][$company] = $totalIncome !== 0 ? ($totalCOGS / $totalIncome * 100) : 0;
                $totals['COGS %'] = $totals['Sales'] !== 0 ? ($totals['Total COGS'] / $totals['Sales'] * 100) : 0;

                $totalGP = $totalIncome - $totalCOGS;

                $pandlData['GP'][$company] = $totalGP;
                $totals['GP'] = (isset($totals['GP']) ? $totals['GP'] : 0) + $totalGP;
                
                $pandlData['GP %'][$company] = $totalIncome !== 0 ? ($totalGP / $totalIncome * 100) : 0;
                $totals['GP %'] = $totals['Sales'] !== 0 ? ($totals['GP'] / $totals['Sales'] * 100) : 0;



                $totalExpense = 0;
                foreach($expenseLegdgers as $key => $expenseLedger){
                    !$isDetailed && $pandlData[$expenseLedger->label][$company] = 0;
                    $ledgers = explode(',', $expenseLedger->ledgers);
                    foreach($ledgers as $ledger){
                        if(isset($pandlCompanyData['e_'.$ledger]) && $pandlCompanyData['e_'.$ledger] !== 0){
                            !$isDetailed && $pandlData[$expenseLedger->label][$company] +=  (-1 * $pandlCompanyData['e_'.$ledger]);
                            $isDetailed && $pandlData[$key.'_'.$expenseLedger->label.'_'.$ledger][$company] = (-1 * $pandlCompanyData['e_'.$ledger]);
                            !$isDetailed && $totals[$expenseLedger->label]  =  (isset($totals[$expenseLedger->label]) ? $totals[$expenseLedger->label] : 0) + (-1 * $pandlCompanyData['e_'.$ledger]);
                            $isDetailed && $totals[$key.'_'.$expenseLedger->label.'_'.$ledger] = (isset($totals[$key.'_'.$expenseLedger->label.'_'.$ledger]) ? $totals[$key.'_'.$expenseLedger->label.'_'.$ledger] : 0) + (-1 * $pandlCompanyData['e_'.$ledger]);
                            $totalExpense += (-1 * $pandlCompanyData['e_'.$ledger]);
                        }
                    }
                }
                $pandlData['Total Expense'][$company] = $totalExpense;

                $totals['Total Expense'] = (isset($totals['Total Expense']) ? $totals['Total Expense'] : 0) + $totalExpense;
                $pandlData['Expense %'][$company] = $totalIncome !== 0 ? ($totalExpense / $totalIncome * 100) : 0;
                $totals['Expense %'] = $totals['Sales'] !== 0 ? ($totals['Total Expense'] / $totals['Sales'] * 100) : 0;
                
                $pandlData['Net Income'][$company] = $totalIncome - $totalCOGS - $totalExpense;
                $totals['Net Income'] = (isset($totals['Net Income']) ? $totals['Net Income'] : 0) + $totalIncome - $totalCOGS - $totalExpense;
                
                $pandlData['Net Income %'][$company] = $totalIncome !== 0 ? ($pandlData['Net Income'][$company] / $totalIncome * 100) : 0;
                $totals['Net Income %'] = $totals['Sales'] !== 0 ? ($totals['Net Income'] / $totals['Sales'] * 100) : 0;
            }


            $responseData =[];
            function addResponseDataStoreWiseProfitAndLoss(&$responseData, $pandlData, $totals, $lables, $isDetailed, $ledgers_with_names, $isIncome = false) : int{
                $count = 0;
               foreach($lables as $key => $label){
                    if(!$isDetailed){
                        $nonZero = array_filter(isset($pandlData[$label['label']]) ? $pandlData[$label['label']] : [], function($value){
                            return $value !== 0;
                        });
                        if(count($nonZero) > 0){
                            $responseData[] = [
                                'label' => $label['label'],
                                'value' => array_merge(isset($pandlData[$label['label']]) ? $pandlData[$label['label']] : [], ['total' => isset($totals[$label['label']]) ? $totals[$label['label']] : 0]),
                            ];
                            if(!$isIncome){
                                $percentageValues=[];
                                foreach($pandlData[$label['label']] as $company => $value){
                                    $sales = $pandlData['Sales'][$company];
                                    $percentageValues[$company] = $sales !== 0 ? ($value / $sales * 100) : 0;
                                }
                                $totalPercentage = $totals['Sales'] !== 0 ? ($totals[$label['label']] / $totals['Sales'] * 100) : 0;
                                $responseData[] = [
                                    'label' => $label['label'] . ' %',
                                    'value' => array_merge($percentageValues, ['total' => $totalPercentage]),
                                ];
                            }
                            $count++;
                        }
                    }else{
                        $ledgers = explode(',', $label['ledgers']);
                        foreach($ledgers as $ledger){
                            $nonZero = array_filter(isset($pandlData[$key.'_'.$label['label'].'_'.$ledger]) ? $pandlData[$key.'_'.$label['label'].'_'.$ledger] : [], function($value){
                                return $value !== 0;
                            });
                            if(count($nonZero) > 0){
                                $responseData[] = [
                                    'label' => isset($ledgers_with_names[$ledger]) ? $ledger.' - '.$ledgers_with_names[$ledger] : $ledger.' - '.$ledger,
                                    'value' => array_merge(isset($pandlData[$key.'_'.$label['label'].'_'.$ledger]) ? $pandlData[$key.'_'.$label['label'].'_'.$ledger] : [], ['total' => isset($totals[$key.'_'.$label['label'].'_'.$ledger]) ? $totals[$key.'_'.$label['label'].'_'.$ledger] : 0]),
                                ];
                                $count++;
                            }
                        }
                    }
               }
               return $count;
            }
            $count = addResponseDataStoreWiseProfitAndLoss($responseData, $pandlData, $totals, $incomeLegdgers, $isDetailed, $ledgers_with_names,true);
            if($count == 1){
                $responseData=[];
            }
            $responseData[] = ['label' => 'Sales', 'value' => array_merge(isset($pandlData['Sales']) ? $pandlData['Sales'] : [], ['total' => isset($totals['Sales']) ? $totals['Sales'] : 0])];

            $count = addResponseDataStoreWiseProfitAndLoss($responseData, $pandlData, $totals, $foodPurchaseLegdgers, $isDetailed, $ledgers_with_names);
            ($count > 1) &&  $responseData[] = ['label' => 'Total Food Purchase', 'value' => array_merge(isset($pandlData['Total Food Purchase']) ? $pandlData['Total Food Purchase'] : [], ['total' => isset($totals['Total Food Purchase']) ? $totals['Total Food Purchase'] : 0])     ];
            ($count > 1) && $responseData[] = ['label' => 'Food %', 'value' => array_merge(isset($pandlData['Food %']) ? $pandlData['Food Percentage'] : [], ['total' => isset($totals['Food Percentage']) ? $totals['Food Percentage'] : 0])];
            
            $count = addResponseDataStoreWiseProfitAndLoss($responseData, $pandlData, $totals, $laborCostLegdgers, $isDetailed, $ledgers_with_names);
            ($count > 1) && $responseData[] = ['label' => 'Total Labor Cost', 'value' => array_merge(isset($pandlData['Total Labor Cost']) ? $pandlData['Total Labor Cost'] : [], ['total' => isset($totals['Total Labor Cost']) ? $totals['Total Labor Cost'] : 0])];
            ($count > 1) && $responseData[] = ['label' => 'Labour %', 'value' => array_merge(isset($pandlData['Labour %']) ? $pandlData['Labour %'] : [], ['total' => isset($totals['Labour %']) ? $totals['Labour %'] : 0])];
            
            $count = addResponseDataStoreWiseProfitAndLoss($responseData, $pandlData, $totals, $franchiseFeeLegdgers, $isDetailed, $ledgers_with_names);
            ($count > 1) && $responseData[] = ['label' => 'Total Franchise Fee', 'value' => array_merge(isset($pandlData['Total Franchise Fee']) ? $pandlData['Total Franchise Fee'] : [], ['total' => isset($totals['Total Franchise Fee']) ? $totals['Total Franchise Fee'] : 0])];
            ($count > 1) && $responseData[] = ['label' => 'Fees %', 'value' => array_merge(isset($pandlData['Fees %']) ? $pandlData['Fees %'] : [], ['total' => isset($totals['Fees %']) ? $totals['Fees %'] : 0])];


            $count = addResponseDataStoreWiseProfitAndLoss($responseData, $pandlData, $totals, $retailLegdgers, $isDetailed, $ledgers_with_names);
            ($count > 1) && $responseData[] = ['label' => 'Total Retail', 'value' => array_merge(isset($pandlData['Total Retail']) ? $pandlData['Total Retail'] : [], ['total' => isset($totals['Total Retail']) ? $totals['Total Retail'] : 0])];
            ($count > 1) && $responseData[] = ['label' => 'Retail %', 'value' => array_merge(isset($pandlData['Retail %']) ? $pandlData['Retail %'] : [], ['total' => isset($totals['Retail %']) ? $totals['Retail %'] : 0])];

            $responseData[] = ['label' => 'Total COGS', 'value' => array_merge(isset($pandlData['Total COGS']) ? $pandlData['Total COGS'] : [], ['total' => isset($totals['Total COGS']) ? $totals['Total COGS'] : 0])];
            $responseData[] = ['label' => 'COGS %', 'value' => array_merge(isset($pandlData['COGS %']) ? $pandlData['COGS %'] : [], ['total' => isset($totals['COGS %']) ? $totals['COGS %'] : 0])];
            $responseData[] = ['label' => 'GP', 'value' => array_merge(isset($pandlData['GP']) ? $pandlData['GP'] : [], ['total' => isset($totals['GP']) ? $totals['GP'] : 0])];
            $responseData[] = ['label' => 'GP %', 'value' => array_merge(isset($pandlData['GP %']) ? $pandlData['GP %'] : [], ['total' => isset($totals['GP %']) ? $totals['GP %'] : 0])];
            
            $count = addResponseDataStoreWiseProfitAndLoss($responseData, $pandlData, $totals, $expenseLegdgers, $isDetailed, $ledgers_with_names);
             $responseData[] = ['label' => 'Total Expense', 'value' => array_merge(isset($pandlData['Total Expense']) ? $pandlData['Total Expense'] : [], ['total' => isset($totals['Total Expense']) ? $totals['Total Expense'] : 0])];
             $responseData[] = ['label' => 'Expense %', 'value' => array_merge(isset($pandlData['Expense %']) ? $pandlData['Expense %'] : [], ['total' => isset($totals['Expense %']) ? $totals['Expense %'] : 0])];
           
            $responseData[] = ['label' => 'Net Income', 'value' => array_merge(isset($pandlData['Net Income']) ? $pandlData['Net Income'] : [], ['total' => isset($totals['Net Income']) ? $totals['Net Income'] : 0])];
            $responseData[] = ['label' => 'Net Income %', 'value' => array_merge(isset($pandlData['Net Income %']) ? $pandlData['Net Income %'] : [], ['total' => isset($totals['Net Income %']) ? $totals['Net Income %'] : 0])];
            return response()->json([
                'success' => true,
                'companies' => $companies,
                'data' => $responseData,
                'missing_ledgers' => (array_values($missingLedgers)),
                'missing_quickqore_ledgers' => (array_values($missingQuickqoreLedgers)),
            ]);
        } catch (\Exception $e) { 
            info($e);  
            return response()->json([
                'success' => false,
                'message' => 'Something went wrong'
            ], 500);
        }
    }

    
}
