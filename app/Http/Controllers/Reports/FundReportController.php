<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Models\DataEntry\FoodPurchaseItems;
use App\Models\Settings\Company;
use App\Models\Settings\FundRequirement;
use App\Models\Settings\FundRequirementCompany;
use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Models\DataEntry\BankUpload;
use App\Services\BankCategoryRule;

class FundReportController extends Controller
{
    /**
     * Get available fund requirement labels
     */
    public function getLabels()
    {
        $this->authorize('access', 'company-wise-fund-report.index');
        
        $labels = [
            ['value' => 'food_purchase', 'label' => 'Food Purchase']
        ];
        
        $fundRequirements = FundRequirement::where('active', true)
            ->where('type', '!=', 'fixed')
            ->orderBy('label')
            ->get(['label']);
            
        foreach ($fundRequirements as $fundRequirement) {
            $labels[] = [
                'value' => $fundRequirement->label,
                'label' => ucwords(str_replace('_', ' ', $fundRequirement->label))
            ];
        }
        
        return response()->json($labels);
    }

    /**
     * Company Wise Fund Report: Shows fund requirements by company.
     * Each company as a row, each upcoming day as a column.
     */
    public function getCompanyWiseFundReport(Request $request)
    {
        $this->authorize('access', 'company-wise-fund-report.index');
        try {
            $label = $request->input('label', 'food_purchase');

            // Get food purchase due days from settings (with caching)
            $foodPurchaseDueDays = getSettingValue('food-purchase-due-(days)', 6);

            $today = now();
            $dates = [];
            $days = 10;
            
            // Build dates array for the next 10 days
            for ($i = 0; $i < $days; $i++) {
                $currentDate = $today->copy()->addDays($i);
                $dates[] = [
                    'date' => $currentDate->format('Y-m-d'),
                    'day' => $currentDate->format('l'),
                ];
            }

            // Get all companies
            $companies = Company::selectRaw('id, store_number, name, CONCAT(store_number, " - ", name) as company_name')
                ->orderBy('company_name')
                ->get();

            $companyIds = $companies->pluck('id')->toArray();

            // Handle food purchase label
            if ($label === 'food_purchase') {
                $reportData = $this->processFoodPurchaseData($companies, $dates, $foodPurchaseDueDays, $companyIds);
            } else {
                // Handle fund requirement labels
                $reportData = $this->processFundRequirementData($companies, $dates, $label, $days);
            }

            return response()->json([
                'data' => $reportData,
                'dates' => array_map(function($d) {
                    return [
                        'date' => $d['date'],
                        'day' => $d['day']
                    ];
                }, $dates),
                'fund_due_days' => $foodPurchaseDueDays,
                'label' => $label,
            ]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    /**
     * Process food purchase data for report
     */
    private function processFoodPurchaseData($companies, $dates, $foodPurchaseDueDays, $companyIds)
    {
        $allTargetDates = [];
        $dateTargetMap = [];
        
        foreach ($dates as $dateInfo) {
            $currentDate = Carbon::parse($dateInfo['date']);
            $targetDate = $currentDate->copy()->subDays($foodPurchaseDueDays)->format('Y-m-d');
            $allTargetDates[] = $targetDate;
            $dateTargetMap[$dateInfo['date']] = $targetDate;
        }

        // Fetch all food purchases in one query
        $foodPurchases = FoodPurchaseItems::whereIn('company_id', $companyIds)
            ->join('food_purchase', 'food_purchase_items.food_purchase_id', '=', 'food_purchase.id')
            ->whereIn('food_purchase.date', array_unique($allTargetDates))
            ->selectRaw('company_id, food_purchase.date, SUM(food_purchase_items.total_amount) as total_amount')
            ->groupBy('company_id', 'food_purchase.date')
            ->get()
            ->groupBy('company_id')
            ->map(function ($items) {
                return $items->keyBy('date');
            });

        // Build report data
        $reportData = [];
        foreach ($companies as $index => $company) {
            $companyData = [
                'sr_no' => $index + 1,
                'store_number' => $company->store_number,
                'company_name' => $company->company_name,
                'company_id' => $company->id,
            ];

            $rowTotal = 0;
            $weekendDates = [];
            $companyPurchases = $foodPurchases->get($company->id, collect());

            foreach ($dates as $dateInfo) {
                $day = $dateInfo['day'];
                $targetDate = $dateTargetMap[$dateInfo['date']];
                $date = $dateInfo['date'];

                if ($day == 'Saturday' || $day == 'Sunday') {
                    $weekendDates[] = $targetDate;
                    $companyData['date_' . $date] = [
                        'amount' => 0,
                        'day' => $day,
                    ];
                } elseif ($day == 'Monday') {
                    // Calculate for Monday + accumulated weekend
                    $amount = 0;
                    foreach (array_merge($weekendDates, [$targetDate]) as $checkDate) {
                        $purchase = $companyPurchases->get($checkDate);
                        if ($purchase) {
                            $amount += (float) $purchase->total_amount;
                        }
                    }
                    
                    $companyData['date_' . $date] = [
                        'amount' => round($amount, 2),
                        'day' => $day,
                    ];
                    $rowTotal += $amount;
                    $weekendDates = [];
                } else {
                    // Regular weekday
                    $purchase = $companyPurchases->get($targetDate);
                    $amount = $purchase ? (float) $purchase->total_amount : 0;
                    
                    $companyData['date_' . $date] = [
                        'amount' => round($amount, 2),
                        'day' => $day,
                    ];
                    $rowTotal += $amount;
                }
            }

            $companyData['total'] = round($rowTotal, 2);
            $reportData[] = $companyData;
        }

        return $reportData;
    }

    /**
     * Process fund requirement data for report
     */
    private function processFundRequirementData($companies, $dates, $label, $days)
    {
        $today = now();
        
        // Get the fund requirement by label
        $fundRequirement = FundRequirement::where('active', true)
            ->where('label', $label)
            ->first();
        $companiesData = FundRequirementCompany::where('fund_requirement_id', $fundRequirement->id)->get()->keyBy('company_id');

        if (!$fundRequirement) {
            return [];
        }

        // Calculate payment dates based on fund requirement condition
        $paymentDates = $this->calculatePaymentDates($fundRequirement, $dates, $today, $days);

        // Build report data
        $reportData = [];
        foreach ($companies as $index => $company) {
            $companyData = [
                'sr_no' => $index + 1,
                'store_number' => $company->store_number,
                'company_name' => $company->company_name,
                'company_id' => $company->id,
            ];

            $rowTotal = 0;

            foreach ($dates as $dateInfo) {
                $date = $dateInfo['date'];
                $day = $dateInfo['day'];
                $amount = 0;

                // Check if this date is a payment date
                if (in_array($date, $paymentDates)) {
                    $amount = $companiesData[$company->id]->amount ?? 0;
                }

                $companyData['date_' . $date] = [
                    'amount' => round($amount, 2),
                    'day' => $day,
                ];
                $rowTotal += $amount;
            }

            $companyData['total'] = round($rowTotal, 2);
            $reportData[] = $companyData;
        }

        return $reportData;
    }

    /**
     * Calculate payment dates based on fund requirement conditions
     */
    private function calculatePaymentDates($fundRequirement, $dates, $today, $days)
    {
        $paymentDates = [];

        if ($fundRequirement->condition_type === 'monthly') {
            $date = Carbon::parse($fundRequirement->condition_value)->format('d');
            $monthDate = Carbon::createFromDate($today->year, $today->month, $date)->format('Y-m-d');
            $paymentDates[] = $this->getPaymentDate($monthDate, $dates);
        } elseif ($fundRequirement->condition_type === 'weekly') {
            $datesToCheck = $this->getActualDate($fundRequirement->condition_value, $days, 'weekly');
            foreach ($datesToCheck as $payrollDate) {
                $adjusted = $this->getPaymentDate($payrollDate, $dates);
                if ($adjusted) {
                    $paymentDates[] = $adjusted;
                }
            }
        } elseif ($fundRequirement->condition_type === 'bi-weekly') {
            $datesToCheck = $this->getActualDate($fundRequirement->condition_value, $days, 'bi-weekly');
            foreach ($datesToCheck as $payrollDate) {
                $adjusted = $this->getPaymentDate($payrollDate, $dates);
                if ($adjusted) {
                    $paymentDates[] = $adjusted;
                }
            }   
        }

        return array_filter($paymentDates);
    }

    /**
     * Get payroll dates based on start date and interval
     */
    private function getActualDate($startDate, $days, $type)
    {
        $dates = [];
        $lastDate = now()->addDays($days);
        $currentDate = Carbon::parse($startDate);

        while ($currentDate->isBefore($lastDate)) {
            $dates[] = $currentDate->format('Y-m-d');
            $currentDate = $currentDate->addDays($type == 'weekly' ? 7 : 14);
        }
        return $dates;
    }

    /**
     * Adjust payment date if it falls on weekend (move to next Monday)
     */
    private function getPaymentDate($paymentDate, $dates)
    {
        $dateInfo = collect($dates)->firstWhere('date', $paymentDate);
        
        if (!$dateInfo) {
            return null;
        }
        
        $day = $dateInfo['day'];
        if ($day != 'Friday' && $day != 'Saturday' && $day != 'Sunday') {
            return $paymentDate;
        }
        
        // Find next Monday
        foreach ($dates as $d) {
            if ($d['day'] == 'Monday' && Carbon::parse($d['date'])->isAfter($paymentDate)) {
                return $d['date'];
            }
        }
        
        return null;
    }

    public function getBankReport(Request $request)
    {
        $this->authorize('access', 'bank-report.index');
        
        try {
            $validated = $request->validate([
                'start_date' => 'required|date',
                'end_date' => 'required|date|after_or_equal:start_date',
                'bank_id' => 'nullable|integer',
                'report_type' => 'required|in:account_wise,description_wise',
            ]);

            $startDate = Carbon::parse($validated['start_date'])->startOfDay();
            $endDate = Carbon::parse($validated['end_date'])->endOfDay();
            $bankId = $request->input('bank_id');
            $reportType = $request->input('report_type', 'account_wise');

            $openingBalanceQuery = BankUpload::where('date', '<', $startDate);

            // Build the query for bank uploads
            $query = BankUpload::query()
                ->whereBetween('date', [$startDate, $endDate])
                ->with(['bank', 'company']);

            // Apply bank filter
            if ($bankId) {
                $query->where('bank_id', $bankId);
                $openingBalanceQuery->where('bank_id', $bankId);
            }

            // Get the data
            $bankUploads = $query->orderBy('date', 'asc')->get();

            // Group data based on report type
            $reportData = [];
            $totalCredit = 0;
            $totalDebit = 0;

            if ($reportType === 'account_wise') {
                // Group by account number
                $grouped = $bankUploads->groupBy('account_number');
                $openingBalance = $openingBalanceQuery->sum('amount');
                foreach ($grouped as $accountNumber => $entries) {
                    $credit = 0;
                    $debit = 0;
                    $accountName = '';
                    $bankName = '';
                    
                    foreach ($entries as $entry) {
                        if ($entry->amount > 0) {
                            $credit += (float) $entry->amount;
                        } else {
                            $debit += abs((float) $entry->amount);
                        }
                        
                        if (empty($accountName) && !empty($entry->account_name)) {
                            $accountName = $entry->account_name;
                        }
                        if (empty($bankName) && $entry->bank) {
                            $bankName = $entry->bank->name;
                        }
                    }
                    
                    $reportData[] = [
                        'group_by' => $accountNumber,
                        'group_name' => $accountNumber .  ($accountName ? ' - ' . $accountName : ''),
                        'bank_name' => $bankName,
                        'credit' => round($credit, 2),
                        'debit' => round($debit, 2),
                        'net' => round($credit - $debit, 2),
                        'count' => count($entries),
                    ];
                    
                    $totalCredit += $credit;
                    $totalDebit += $debit;
                }
            } else {
                // Group by categorized description
                $bankCategoryRule = new BankCategoryRule();
                $openingBalance = $openingBalanceQuery->sum('amount');
                
                // Categorize each entry and group by category
                $categorizedEntries = [];
                foreach ($bankUploads as $entry) {
                    $category = $bankCategoryRule->getCategory($entry->description);
                    $category = $category ?: ($entry->description ?: '(No Description)');
                    
                    if (!isset($categorizedEntries[$category])) {
                        $categorizedEntries[$category] = [];
                    }
                    $categorizedEntries[$category][] = $entry;
                }
                
                // Build report data from categorized entries
                foreach ($categorizedEntries as $category => $entries) {
                    $credit = 0;
                    $debit = 0;
                    $bankName = '';
                    
                    foreach ($entries as $entry) {
                        if ($entry->amount > 0) {
                            $credit += (float) $entry->amount;
                        } else {
                            $debit += abs((float) $entry->amount);
                        }
                        
                        if (empty($bankName) && $entry->bank) {
                            $bankName = $entry->bank->name;
                        }
                    }
                    
                    $reportData[] = [
                        'group_by' => $category,
                        'group_name' => $category,
                        'bank_name' => $bankName,
                        'credit' => round($credit, 2),
                        'debit' => round($debit, 2),
                        'net' => round($credit - $debit, 2),
                        'count' => count($entries),
                    ];
                    
                    $totalCredit += $credit;
                    $totalDebit += $debit;
                }
            }

            $openingBalance = $openingBalanceQuery->sum('amount');

            // Get summary statistics
            $summary = [
                'total_entries' => $bankUploads->count(),
                'total_groups' => count($reportData),
                'total_credit' => round($totalCredit, 2),
                'total_debit' => round($totalDebit, 2),
                'net_amount' => round($totalCredit - $totalDebit, 2),
                'start_date' => $startDate->format('Y-m-d'),
                'end_date' => $endDate->format('Y-m-d'),
                'report_type' => $reportType,
                'opening_balance' => round($openingBalance, 2),
            ];

            return response()->json([
                'success' => true,
                'data' => $reportData,
                'summary' => $summary,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
