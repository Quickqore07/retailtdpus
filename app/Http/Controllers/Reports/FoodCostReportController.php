<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Models\AR\FeesUpload;
use App\Models\DataEntry\DailySale;
use Illuminate\Http\Request;
use App\Models\DataEntry\FoodPurchaseItems;
use App\Models\DataEntry\IdealCostItem;
use App\Models\DataEntry\PayrollJournal;
use App\Models\Payroll\EmployeeWeeklySummary;
use App\Models\Settings\Company;
use App\Models\User;
use Carbon\Carbon;

class FoodCostReportController extends Controller
{

    private $non_consider_company =['224455'];
    public function getFlmReport(Request $request)
    {
        $this->authorize('access', 'flm-report.index');

        return response()->json($this->buildFlmReportData($request, false));
    }

    public function getFlmTReport(Request $request)
    {
        $this->authorize('access', 'flm-t-report.index');

        return response()->json($this->buildFlmReportData($request, true));
    }

    private function buildFlmReportData(Request $request, bool $includeTpf): array
    {
        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date',
            'workgroup_ids' => 'nullable|array',
            'workgroup_ids.*' => 'integer|exists:workgroup,id',
            'report_type' => 'nullable|in:weekly,bi-weekly',
        ]);

        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $userId = $request->user_id ? (int) $request->user_id : null;

        $biWeeklyDate = $this->getBiWeeklyDate($endDate);

        if ($userId) {
            $user = User::find($userId);
            $userCompanyIds = $user->getCompaniesArrayAttribute();
        } else {
            $userCompanyIds = null;
        }

        $workgroupIds = collect($request->input('workgroup_ids', []))
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->values();

        $companies = Company::authorizedCompanies('id', false)->when($workgroupIds->isNotEmpty(), function ($query) use ($workgroupIds) {
            return $query->whereIn('workgroup_id', $workgroupIds->all());
        })->when($userCompanyIds !== null, function ($query) use ($userCompanyIds) {
            return $query->whereIn('id', $userCompanyIds);
        })->whereNotIn('store_number', $this->non_consider_company)->with('workgroup')->selectRaw('id, store_number,workgroup_id, name, CONCAT(store_number, " - ", name) as name')->orderBy('name')->get()->keyBy('id');

        $companyIds = $companies->keys()->all();

        $filteredCompanyIds = $companies->keys()->all();
        $dailySalesSelect = 'company_id, SUM(sales) as total_sales';

        $DailySales = DailySale::when($companyIds !== null, function ($query) use ($companyIds) {
            return $query->whereIn('company_id', $companyIds);
        })->when(!empty($filteredCompanyIds), function ($query) use ($filteredCompanyIds) {
            return $query->whereIn('company_id', $filteredCompanyIds);
        })->whereBetween('date', [$startDate, $endDate])->groupBy('company_id')->selectRaw($dailySalesSelect)->get()->keyBy('company_id');

        $FoodPurchases = FoodPurchaseItems::join('food_purchase', 'food_purchase_items.food_purchase_id', '=', 'food_purchase.id')->when($companyIds !== null, function ($query) use ($companyIds) {
            return $query->whereIn('food_purchase_items.company_id', $companyIds);
        })->when(!empty($filteredCompanyIds), function ($query) use ($filteredCompanyIds) {
            return $query->whereIn('food_purchase_items.company_id', $filteredCompanyIds);
        })->whereBetween('food_purchase.date', [$startDate, $endDate])->groupBy('food_purchase_items.company_id')->selectRaw('food_purchase_items.company_id, SUM(food_purchase_items.total_amount) as total_cost')->pluck('total_cost', 'company_id');

        $idealCost = IdealCostItem::join('ideal_cost', 'ideal_cost_items.ideal_cost_id', '=', 'ideal_cost.id')->when($companyIds !== null, function ($query) use ($companyIds) {
            return $query->whereIn('ideal_cost_items.company_id', $companyIds);
        })->when(!empty($filteredCompanyIds), function ($query) use ($filteredCompanyIds) {
            return $query->whereIn('ideal_cost_items.company_id', $filteredCompanyIds);
        })->whereBetween('ideal_cost.date', [$startDate, $endDate])->groupBy('ideal_cost_items.company_id')->selectRaw('ideal_cost_items.company_id, SUM(ideal_cost_items.mileage) as total_mileage')->pluck('total_mileage', 'company_id');

        $labourCostDC = PayrollJournal::when($companyIds !== null, function ($query) use ($companyIds) {
            return $query->whereIn('company_id', $companyIds);
        })->when(!empty($filteredCompanyIds), function ($query) use ($filteredCompanyIds) {
            return $query->whereIn('company_id', $filteredCompanyIds);
        })->whereBetween('payroll_journals.eow', [$startDate, $biWeeklyDate])->groupBy('payroll_journals.company_id')->selectRaw('payroll_journals.company_id, SUM(payroll_journals.total_earnings) + SUM(payroll_journals.er_withholdings) - SUM(payroll_journals.charge_tips_reimb) - SUM(payroll_journals.mileage_reimb) as ctc')->pluck('ctc', 'company_id');

        $labourCostPA = EmployeeWeeklySummary::when($companyIds !== null, function ($query) use ($companyIds) {
            return $query->whereIn('company_id', $companyIds);
        })->when(!empty($filteredCompanyIds), function ($query) use ($filteredCompanyIds) {
            return $query->whereIn('company_id', $filteredCompanyIds);
        })->whereBetween('eow', [$startDate, $endDate])->groupBy('company_id')->selectRaw('company_id, SUM(total_earnings) as total_earnings')->pluck('total_earnings', 'company_id');

        $tpfCost = collect();
        if ($includeTpf) {
            $tpfCost = FeesUpload::when($companyIds !== null, function ($query) use ($companyIds) {
                return $query->whereIn('company_id', $companyIds);
            })->when(!empty($filteredCompanyIds), function ($query) use ($filteredCompanyIds) {
                return $query->whereIn('company_id', $filteredCompanyIds);
            })->whereBetween('date', [$startDate, $biWeeklyDate])->groupBy('company_id')->selectRaw('company_id, SUM(total_amount) as total_amount')->pluck('total_amount', 'company_id');
        }

        $flmData = [];
        foreach ($companies as $companyId => $company) {
            $sales = isset($DailySales[$companyId]) ? $DailySales[$companyId]->total_sales : 0;
            $food = isset($FoodPurchases[$companyId]) ? $FoodPurchases[$companyId] : 0;
            $foodPercentage = ($sales > 0 ? $food / $sales : 0) * 100;

            if ($request->report_type === 'weekly') {
                if ($company->workgroup->name == 'DC') {
                    $labour = isset($labourCostDC[$companyId]) ? ($labourCostDC[$companyId] / 2) : 0;
                } else {
                    $labour = isset($labourCostPA[$companyId]) ? $labourCostPA[$companyId] : 0;
                }
            } else {
                if ($company->workgroup->name == 'DC') {
                    $labour = isset($labourCostDC[$companyId]) ? $labourCostDC[$companyId] : 0;
                } else {
                    $labour = isset($labourCostPA[$companyId]) ? $labourCostPA[$companyId] : 0;
                }
            }
            $labourPercentage = ($sales > 0 ? $labour / $sales : 0) * 100;

            $tpf = 0;
            $tpfPercentage = 0;
            if ($includeTpf) {
                $tpf = isset($tpfCost[$companyId]) ? (float) $tpfCost[$companyId] : 0;
                $tpfPercentage = ($sales > 0 ? $tpf / $sales : 0) * 100;
            }

            $flm = $food + $labour + $tpf;
            $flmPercentage = ($sales > 0 ? $flm / $sales : 0) * 100;

            $row = [
                'company_name' => $companies[$companyId]->name,
                'store_number' => $companies[$companyId]->store_number,
                'sales' => round($sales, 2),
                'food' => round($food, 2),
                'food_percentage' => round($foodPercentage, 2),
                'labour' => round($labour, 2),
                'labour_percentage' => round($labourPercentage, 2),
                'flm' => round($flm, 2),
                'flm_percentage' => round($flmPercentage, 2),
            ];

            if ($includeTpf) {
                $row['tpf'] = round($tpf, 2);
                $row['tpf_percentage'] = round($tpfPercentage, 2);
            }

            $flmData[] = $row;
        }

        return $flmData;
    }

    /**
     * Store-wise weekly FLM report: one store, one row per week for the selected year.
     */
    public function getFlmStoreSummaryReport(Request $request)
    {
        $this->authorize('access', 'store-wise-weekly-flm-report.index');
        $request->validate([
            'year' => 'required|integer|min:2020|max:2100',
            'company_id' => 'required|integer|exists:company,id',
            'report_type' => 'nullable|in:weekly,bi-weekly',
        ]);

        $year = (int) $request->input('year');
        $reportType = $request->input('report_type', 'weekly');
        $isBiWeekly = $reportType === 'bi-weekly';
        $requestedCompanyId = (int) $request->input('company_id');

        $company = Company::where('id', $requestedCompanyId)->with('workgroup')->first();

        if (!$company) {
            return response()->json([]);
        }

        // Build periods (weekly or bi-weekly) that overlap the selected year.
        $yearStart = Carbon::parse($year . '-01-01');
        $yearEnd = Carbon::parse($year . '-12-31');
        $dayOfWeek = (int) $yearStart->format('N'); // 1=Mon..7=Sun
        $daysToMonday = $company->workgroup->name == 'DC' ? ($dayOfWeek === 0 ? 0 : 1 - $dayOfWeek) : ($dayOfWeek === 0 ? -6 : 1 - $dayOfWeek - 7);
        $firstMonday = $yearStart->copy()->addDays($daysToMonday);

        $weeks = [];
        $current = $firstMonday->copy();
        for ($i = 0; $i < 54; $i++) {
            $weekEnd = $current->copy()->addDays($isBiWeekly ? 13 : 6);
            if ($weekEnd->lt($yearStart)) {
                $current->addDays($isBiWeekly ? 14 : 7);
                continue;
            }
            if ($current->gt($yearEnd)) {
                break;
            }
            $weeks[] = [
                'start' => $current->format('Y-m-d'),
                'end' => $weekEnd->format('Y-m-d'),
                'week_ending' => $weekEnd->format('m/d/Y'),
            ];
            $current->addDays($isBiWeekly ? 14 : 7);
        }

        $reportData = [];
        $companyId = $company->id;
        foreach ($weeks as $week) {
            $startDate = $week['start'];
            $endDate = $week['end'];

            $dailySales = DailySale::where('company_id', $companyId)
                ->whereBetween('date', [$startDate, $endDate])
                ->selectRaw('SUM(sales) as total_sales')
                ->first();

            $food = (float) FoodPurchaseItems::join('food_purchase', 'food_purchase_items.food_purchase_id', '=', 'food_purchase.id')
                ->where('food_purchase_items.company_id', $companyId)
                ->whereBetween('food_purchase.date', [$startDate, $endDate])
                ->sum('food_purchase_items.total_amount');

            $idealCostMileage = (float) IdealCostItem::join('ideal_cost', 'ideal_cost_items.ideal_cost_id', '=', 'ideal_cost.id')
                ->where('ideal_cost_items.company_id', $companyId)
                ->whereBetween('ideal_cost.date', [$startDate, $endDate])
                ->sum('ideal_cost_items.mileage');

            if ($company->workgroup->name == 'DC') {
                $biWeeklyEnd = $this->getBiWeeklyDate($endDate);
                $labourEnd = $biWeeklyEnd ? $biWeeklyEnd->format('Y-m-d') : $endDate;
                $labourData = PayrollJournal::where('company_id', $companyId)
                    ->whereBetween('payroll_journals.eow', [$startDate, $labourEnd])
                    ->selectRaw('SUM(payroll_journals.total_earnings) + SUM(payroll_journals.er_withholdings) - SUM(payroll_journals.charge_tips_reimb) - SUM(payroll_journals.mileage_reimb) as ctc')
                    ->first();
                if (!$isBiWeekly) {
                    $labour = $labourData->ctc / 2;
                }else{
                    $labour = $labourData->ctc;
                }
            } else {
                $labour = (float) EmployeeWeeklySummary::where('company_id', $companyId)
                    ->whereBetween('eow', [$startDate, $endDate])
                    ->sum('total_earnings');
            }

            $sales = $dailySales && $dailySales->total_sales ? (float) $dailySales->total_sales : 0;
            $foodPercentage = $sales > 0 ? ($food / $sales) * 100 : 0;
            $labourPercentage = $sales > 0 ? ($labour / $sales) * 100 : 0;
            $flm = $food + $labour;
            $flmPercentage = $sales > 0 ? ($flm / $sales) * 100 : 0;

            $reportData[] = [
                'week_ending' => $week['week_ending'],
                'eow' => $endDate,
                'sales' => round($sales, 2),
                'food' => round($food, 2),
                'food_percentage' => round($foodPercentage, 2),
                'labour' => round($labour, 2),
                'labour_percentage' => round($labourPercentage, 2),
                'flm' => round($flm, 2),
                'flm_percentage' => round($flmPercentage, 2),
            ];
        }

        return response()->json($reportData);
    }

    public function getBiWeeklyDate($endDate)
    {
        $endDate = Carbon::parse($endDate);
        $year = $endDate->year;
        $yearStart = Carbon::parse($year . '-01-01');
        $yearEnd = Carbon::parse($year . '-12-31');
        $dayOfWeek = (int) $yearStart->format('N'); // 1=Mon..7=Sun
        $daysToMonday = $dayOfWeek === 0 ? 0 : 1 - $dayOfWeek;
        $firstMonday = $yearStart->copy()->addDays($daysToMonday);

        $current = $firstMonday->copy();
        for ($i = 0; $i < 54; $i++) {
            $weekEnd = $current->copy()->addDays(13);
            if ($weekEnd->lt($yearStart)) {
                $current->addDays(14);
                continue;
            }
            if ($current->gt($yearEnd)) {
                break;
            }
            if($current < $endDate && $weekEnd >= $endDate){
                return $weekEnd;
            }
            $current->addDays(14);
        }

    }

}