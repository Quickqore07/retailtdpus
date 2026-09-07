<?php

namespace App\Http\Controllers\Reports;

use App\Models\DataEntry\DailySale;
use App\Models\DataEntry\FoodPurchase;
use App\Models\DataEntry\IdealCost;
use App\Models\DataEntry\SalesProjection;
use App\Models\Settings\Company;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\DataEntry\FoodPurchaseItems;
use App\Models\DataEntry\IdealCostItem;
use App\Models\Settings\PjCalendarItem;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
class IdealCostReportController extends Controller
{
    /**
     * Ideal cost report: year-wise, weekly totals only.
     * Returns weeks (week-ending dates), one row per company with total + value per week, week totals, grand total.
     */
    public function getIdealCostReport(Request $request)
    {
        $this->authorize('access', 'ideal-cost-report.index');
        $request->validate([
            'year' => 'required|integer|min:2000|max:2100',
            'workgroup_ids' => 'nullable|array',
            'workgroup_ids.*' => 'integer|exists:workgroup,id',
        ]);

        $year = (int) $request->year;
        $companyId = $request->company_id ? (int) $request->company_id : null;
        $workgroupIds = collect($request->workgroup_ids ?? [])->map(fn ($id) => (int) $id)->filter()->values();

        $selectedCompanyIds = Company::query()
            ->authorizedCompanies('id',false)
            ->when($workgroupIds->isNotEmpty(), function ($query) use ($workgroupIds) {
                return $query->whereIn('workgroup_id', $workgroupIds->all());
            })
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $start = Carbon::create($year, 1, 1)->startOfDay();
        $end   = Carbon::create($year, 12, 31)->endOfDay();

        $startWeek = $start->copy()->startOfWeek(Carbon::MONDAY);
        $weeks = [];
        $cursor = $start->copy()->endOfWeek(Carbon::SUNDAY);
        if ($cursor->year < $year) {
            $cursor->addWeek();
        }
        while ($cursor->year <= $year && $cursor->lte($end)) {
            $weeks[] = $cursor->format('Y-m-d');
            $cursor->addWeek();
        }

        $idealCosts = IdealCost::with('items.company')
            ->whereBetween('date', [$startWeek->toDateString(), $end->toDateString()])
            ->whereHas('items', function ($query) use ($selectedCompanyIds) {
                $query->whereIn('company_id', $selectedCompanyIds);
            })
            ->orderBy('date')
            ->get();

        $byCompany = [];
        $weekTotals = array_fill_keys($weeks, 0.0);
        $grandTotal = 0.0;

        foreach ($idealCosts as $idealCost) {
            $weekEnd = Carbon::parse($idealCost->date)->endOfWeek(Carbon::SUNDAY)->format('Y-m-d');
            if (!in_array($weekEnd, $weeks, true)) {
                continue;
            }
            foreach ($idealCost->items as $item) {
                if ($companyId && (int) $item->company_id !== $companyId) {
                    continue;
                }
                if (!empty($selectedCompanyIds) && !in_array((int) $item->company_id, $selectedCompanyIds, true)) {
                    continue;
                }
                $amount = (float) $item->ideal_cost;
                $cid = $item->company_id;
                $cname = $item->company ? $item->company->name : (string) $cid;
                if (!isset($byCompany[$cid])) {
                    $byCompany[$cid] = [
                        'company_id'   => $cid,
                        'company_name' => $cname,
                        'total'        => 0.0,
                        'weeks'        => array_fill_keys($weeks, 0.0),
                    ];
                }
                $byCompany[$cid]['weeks'][$weekEnd] += $amount;
                $byCompany[$cid]['total'] += $amount;
                $weekTotals[$weekEnd] += $amount;
                $grandTotal += $amount;
            }
        }

        $rows = array_values($byCompany);
        usort($rows, fn ($a, $b) => strcasecmp($a['company_name'], $b['company_name']));

        return response()->json([
            'weeks'        => $weeks,
            'week_totals'  => $weekTotals,
            'rows'         => $rows,
            'grand_total'  => $grandTotal,
        ]);
    }

    /**
     * Purchase report: year-wise, weekly totals only.
     * Returns weeks (week-ending dates), one row per company with total + value per week, week totals, grand total.
     */
    public function getPurchaseReport(Request $request)
    {
        $this->authorize('access', 'purchase-report.index');
        $request->validate([
            'year' => 'required|integer|min:2000|max:2100',
            'workgroup_ids' => 'nullable|array',
            'workgroup_ids.*' => 'integer|exists:workgroup,id',
        ]);

        $year = (int) $request->year;
        $companyId = $request->company_id ? (int) $request->company_id : null;
        $workgroupIds = collect($request->workgroup_ids ?? [])->map(fn ($id) => (int) $id)->filter()->values();
        $selectedCompanyIds = Company::query()
            ->authorizedCompanies('id', false)
            ->when($workgroupIds->isNotEmpty(), function ($query) use ($workgroupIds) {
                return $query->whereIn('workgroup_id', $workgroupIds->all());
            })
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        // Week ending Sunday for the year
        $start = Carbon::create($year, 1, 1)->startOfDay();
        $end   = Carbon::create($year, 12, 31)->endOfDay();
        $weeks = [];
        $cursor = $start->copy()->endOfWeek(Carbon::SUNDAY);
        $startWeek = $start->copy()->startOfWeek(Carbon::MONDAY);
        if ($cursor->year < $year) {
            $cursor->addWeek();
        }
        while ($cursor->year <= $year && $cursor->lte($end)) {
            $weeks[] = $cursor->format('Y-m-d');
            $cursor->addWeek();
        }

        $foodPurchases = FoodPurchase::with('items.company')->whereBetween('date', [$startWeek->toDateString(), $end->toDateString()])
            ->whereHas('items', function ($query) use ($selectedCompanyIds) {
                $query->whereIn('company_id', $selectedCompanyIds);
            })
            ->orderBy('date')
            ->get();

        $byCompany = [];
        $weekTotals = array_fill_keys($weeks, 0.0);
        $grandTotal = 0.0;

        foreach ($foodPurchases as $foodPurchase) {
            $weekEnd = Carbon::parse($foodPurchase->date)->endOfWeek(Carbon::SUNDAY)->format('Y-m-d');
            if (!in_array($weekEnd, $weeks, true)) {
                continue;
            }
            foreach ($foodPurchase->items as $item) {
                if ($companyId && (int) $item->company_id !== $companyId) {
                    continue;
                }
                if (!empty($selectedCompanyIds) && !in_array((int) $item->company_id, $selectedCompanyIds, true)) {
                    continue;
                }
                $amount = (float) $item->total_amount;
                $cid = $item->company_id;
                $cname = $item->company ? $item->company->name : (string) $cid;
                if (!isset($byCompany[$cid])) {
                    $byCompany[$cid] = [
                        'company_id'   => $cid,
                        'company_name' => $cname,
                        'total'        => 0.0,
                        'weeks'        => array_fill_keys($weeks, 0.0),
                    ];
                }
                $byCompany[$cid]['weeks'][$weekEnd] += $amount;
                $byCompany[$cid]['total'] += $amount;
                $weekTotals[$weekEnd] += $amount;
                $grandTotal += $amount;
            }
        }

        $rows = array_values($byCompany);
        usort($rows, fn ($a, $b) => strcasecmp($a['company_name'], $b['company_name']));

        return response()->json([
            'weeks'        => $weeks,
            'week_totals'  => $weekTotals,
            'rows'         => $rows,
            'grand_total'  => $grandTotal,
        ]);
    }

    /**
     * Sales report: year-wise, weekly totals by store from daily_sales.total_sales.
     * Returns weeks (week-ending dates), one row per company with total + value per week, week totals, grand total.
     */
    public function getSalesReport(Request $request)
    {
        $this->authorize('access', 'sales-report.index');
        $request->validate([
            'year' => 'required|integer|min:2000|max:2100',
            'workgroup_ids' => 'nullable|array',
            'workgroup_ids.*' => 'integer|exists:workgroup,id',
        ]);

        $year = (int) $request->year;
        $companyId = $request->company_id ? (int) $request->company_id : null;
        $workgroupIds = collect($request->workgroup_ids ?? [])->map(fn ($id) => (int) $id)->filter()->values();
        $selectedCompanyIds = Company::query()
            ->authorizedCompanies('id', false)
            ->when($workgroupIds->isNotEmpty(), function ($query) use ($workgroupIds) {
                return $query->whereIn('workgroup_id', $workgroupIds->all());
            })
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $start = Carbon::create($year, 1, 1)->startOfDay();
        $end   = Carbon::create($year, 12, 31)->endOfDay();
        $startWeek = $start->copy()->startOfWeek(Carbon::MONDAY);
        $weeks = [];
        $cursor = $start->copy()->endOfWeek(Carbon::SUNDAY);
        if ($cursor->year < $year) {
            $cursor->addWeek();
        }
        while ($cursor->year <= $year && $cursor->lte($end)) {
            $weeks[] = $cursor->format('Y-m-d');
            $cursor->addWeek();
        }

        $dailySales = DailySale::with('company')
            ->whereBetween('date', [$startWeek->toDateString(), $end->toDateString()])
            ->orderBy('date')
            ->whereIn('company_id', $selectedCompanyIds)
            ->get();

        $byCompany = [];
        $weekTotals = array_fill_keys($weeks, 0.0);
        $grandTotal = 0.0;

        foreach ($dailySales as $sale) {
            if ($companyId && (int) $sale->company_id !== $companyId) {
                continue;
            }
            $weekEnd = Carbon::parse($sale->date)->endOfWeek(Carbon::SUNDAY)->format('Y-m-d');
            if (!in_array($weekEnd, $weeks, true)) {
                continue;
            }
            $amount = (float) $sale->net_sales;
            $cid = $sale->company_id;
            $cname = $sale->company ? $sale->company->name : (string) $cid;
            if (!isset($byCompany[$cid])) {
                $byCompany[$cid] = [
                    'company_id'   => $cid,
                    'company_name' => $cname,
                    'total'        => 0.0,
                    'weeks'        => array_fill_keys($weeks, 0.0),
                ];
            }
            $byCompany[$cid]['weeks'][$weekEnd] += $amount;
            $byCompany[$cid]['total'] += $amount;
            $weekTotals[$weekEnd] += $amount;
            $grandTotal += $amount;
        }

        $rows = array_values($byCompany);
        usort($rows, fn ($a, $b) => strcasecmp($a['company_name'], $b['company_name']));

        return response()->json([
            'weeks'        => $weeks,
            'week_totals'  => $weekTotals,
            'rows'         => $rows,
            'grand_total'  => $grandTotal,
        ]);
    }

    /**
     * Ideal Cost and Purchase Difference Report: year-wise, weekly totals by store
     * Shows ideal cost, purchase, and their difference.
     * If employee_id is provided, only shows companies accessible by that employee.
     */
    public function getIdealCostPurchaseDifferenceReport(Request $request)
    {
        $this->authorize('access', 'ideal-cost-purchase-difference-report.index');
        $request->validate([
            'year' => 'required|integer|min:2000|max:2100',
            'user_id' => 'nullable|integer|exists:users,id',
            'workgroup_ids' => 'nullable|array',
            'workgroup_ids.*' => 'integer|exists:workgroup,id',
        ]);

        $year = (int) $request->year;
        $userId = $request->user_id ? (int) $request->user_id : null;
        $workgroupIds = collect($request->workgroup_ids ?? [])->map(fn ($id) => (int) $id)->filter()->values();
        $user = User::find($userId);
        if($user){
            $companyIds = $user->getCompaniesArrayAttribute();
        }else{
            $companyIds = null;
        }
        $selectedCompanyIds = Company::query()
            ->authorizedCompanies('id', false)
            ->when($workgroupIds->isNotEmpty(), function ($query) use ($workgroupIds) {
                return $query->whereIn('workgroup_id', $workgroupIds->all());
            })
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();
        if ($companyIds !== null) {
            $selectedCompanyIds = array_values(array_intersect($selectedCompanyIds, array_map('intval', $companyIds)));
        }

        $start = Carbon::create($year, 1, 1)->startOfDay();
       
        $end = Carbon::now()->previous(Carbon::SUNDAY)->endOfDay();
        $startWeek = $start->copy()->startOfWeek(Carbon::MONDAY);
        
        $weeks = [];
        $cursor = $start->copy()->endOfWeek(Carbon::SUNDAY);
        if ($cursor->year < $year) {
            $cursor->addWeek();
        }
        while ($cursor->year <= $year && $cursor->lte($end)) {
            $weeks[] = $cursor->format('Y-m-d');
            $cursor->addWeek();
        }

        // Fetch ideal cost data
        $idealCosts = IdealCost::with('items.company')
            ->whereHas('items', function ($query) use ($selectedCompanyIds) {
                $query->whereIn('company_id', $selectedCompanyIds);
            })
            ->whereBetween('date', [$startWeek->toDateString(), $end->toDateString()])
            ->orderBy('date')
            ->get();

        // Fetch purchase data
        $foodPurchases = FoodPurchase::with('items.company')
            ->whereHas('items', function ($query) use ($selectedCompanyIds) {
                $query->whereIn('company_id', $selectedCompanyIds);
            })
            ->whereBetween('date', [$startWeek->toDateString(), $end->toDateString()])
            ->orderBy('date')
            ->get();

        $byCompany = [];
        $idealCostByCompanyWeek = [];
        $purchaseByCompanyWeek = [];

        // Process ideal costs
        foreach ($idealCosts as $idealCost) {
            $weekEnd = Carbon::parse($idealCost->date)->endOfWeek(Carbon::SUNDAY)->format('Y-m-d');
            if (!in_array($weekEnd, $weeks, true)) {
                continue;
            }
            foreach ($idealCost->items as $item) {
                if (!empty($selectedCompanyIds) && !in_array((int) $item->company_id, $selectedCompanyIds, true)) {
                    continue;
                }
                $cid = $item->company_id;
                

                $amount = (float) $item->ideal_cost;
                $cname = $item->company ? $item->company->name : (string) $cid;
                
                if (!isset($byCompany[$cid])) {
                    $byCompany[$cid] = [
                        'company_id'   => $cid,
                        'company_name' => $cname,
                        'total' => 0.0,
                        'weeks' => array_fill_keys($weeks, 0.0),
                    ];
                }
                
                if (!isset($idealCostByCompanyWeek[$cid])) {
                    $idealCostByCompanyWeek[$cid] = array_fill_keys($weeks, 0.0);
                }
                
                $idealCostByCompanyWeek[$cid][$weekEnd] += $amount;
            }
        }

        // Process purchases
        foreach ($foodPurchases as $foodPurchase) {
            $weekEnd = Carbon::parse($foodPurchase->date)->endOfWeek(Carbon::SUNDAY)->format('Y-m-d');
            if (!in_array($weekEnd, $weeks, true)) {
                continue;
            }
            foreach ($foodPurchase->items as $item) {
                if (!empty($selectedCompanyIds) && !in_array((int) $item->company_id, $selectedCompanyIds, true)) {
                    continue;
                }
                $cid = $item->company_id;

                $amount = (float) $item->total_amount;
                $cname = $item->company ? $item->company->name : (string) $cid;
                
                if (!isset($byCompany[$cid])) {
                    $byCompany[$cid] = [
                        'company_id'   => $cid,
                        'company_name' => $cname,
                        'total' => 0.0,
                        'weeks' => array_fill_keys($weeks, 0.0),
                    ];
                }
                
                if (!isset($purchaseByCompanyWeek[$cid])) {
                    $purchaseByCompanyWeek[$cid] = array_fill_keys($weeks, 0.0);
                }
                
                $purchaseByCompanyWeek[$cid][$weekEnd] += $amount;
            }
        }

        // Calculate differences
        $weekTotals = array_fill_keys($weeks, 0.0);
        $grandTotal = 0.0;

        foreach ($byCompany as $cid => &$company) {
            foreach ($weeks as $week) {
                $idealCost = $idealCostByCompanyWeek[$cid][$week] ?? 0.0;
                $purchase = $purchaseByCompanyWeek[$cid][$week] ?? 0.0;
                $difference = $idealCost - $purchase;
                $company['weeks'][$week] = $difference;
                $company['total'] += $difference;
                $weekTotals[$week] += $difference;
                $grandTotal += $difference;
            }
        }

        $rows = array_values($byCompany);
        usort($rows, fn ($a, $b) => strcasecmp($a['company_name'], $b['company_name']));

        return response()->json([
            'weeks' => $weeks,
            'week_totals' => $weekTotals,
            'rows' => $rows,
            'grand_total' => $grandTotal,
        ]);
    }

    /**
     * Ideal Cost Company Totals Report: one row per company for a date range.
     * Columns: company, network_sales, ideal_cost, actual_purchase, diff (IC - AP),
     * std_ic (IC/NS), act_pur (AP/NS), diff_ns (diff/NS). Optional user filter.
     */
    public function getIdealCostSummaryReport(Request $request)
    {
        $this->authorize('access', 'ideal-cost-summary-report.index');
        $request->validate([
            'start_date' => 'nullable|date',
            'end_date'   => 'nullable|date|after_or_equal:start_date',
            'user_id'    => 'nullable|integer|exists:users,id',
            'workgroup_ids' => 'nullable|array',
            'workgroup_ids.*' => 'integer|exists:workgroup,id',
            'selected_period' => 'nullable|integer|exists:pj_calendar_items,id',
        ]);

        if(!$request->selected_period && (!$request->start_date || !$request->end_date)) {
            return response()->json([
                'message' => 'Please select a week or period',
            ], 422);
        }

        if($request->selected_period) {
            $period = PjCalendarItem::where('id', $request->selected_period)->first();
            $weeks = $period->weeks;
            $start = Carbon::parse($weeks[0])->startOfWeek(Carbon::MONDAY);
            $end = Carbon::parse($weeks[count($weeks) - 1])->endOfWeek(Carbon::SUNDAY);
        }
        else{
            $start = Carbon::parse($request->start_date)->startOfDay();
            $end   = Carbon::parse($request->end_date)->endOfDay();
        }
        $userId = $request->user_id ? (int) $request->user_id : null;
        $workgroupIds = collect($request->workgroup_ids ?? [])->map(fn ($id) => (int) $id)->filter()->values();

        $user = $userId ? User::find($userId) : null;
        $companyIds = $user ? $user->getCompaniesArrayAttribute() : null;
        $selectedCompanyIds = Company::query()
            ->authorizedCompanies('id', false)
            ->when($workgroupIds->isNotEmpty(), function ($query) use ($workgroupIds) {
                return $query->whereIn('workgroup_id', $workgroupIds->all());
            })
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();
        if ($companyIds !== null) {
            $selectedCompanyIds = array_values(array_intersect($selectedCompanyIds, array_map('intval', $companyIds)));
        }
        $startStr = $start->toDateString();
        $endStr   = $end->toDateString();
        // Network sales by company (from daily_sales.net_sales)
        $dailySales = DailySale::with('company')
            ->whereIn('company_id', $selectedCompanyIds)
            ->whereBetween('date', [$startStr, $endStr])
            ->get();

        $networkSalesByCompany = [];
        foreach ($dailySales as $sale) {
            $cid = $sale->company_id;
            $cname = $sale->company ? $sale->company->name : (string) $cid;
            if (!isset($networkSalesByCompany[$cid])) {
                $networkSalesByCompany[$cid] = ['company_id' => $cid, 'company_name' => $cname, 'total' => 0.0];
            }
            $networkSalesByCompany[$cid]['total'] += (float) $sale->net_sales;
        }

        // Ideal cost by company
        $idealCosts = IdealCost::with('items.company')
            ->whereBetween('date', [$startStr, $endStr])
            ->whereHas('items', function ($query) use ($selectedCompanyIds) {
                $query->whereIn('company_id', $selectedCompanyIds);
            })
            ->get();
        $idealCostByCompany = [];
        foreach ($idealCosts as $idealCost) {
            foreach ($idealCost->items as $item) {
                if (!empty($selectedCompanyIds) && !in_array((int) $item->company_id, $selectedCompanyIds, true)) {
                    continue;
                }
                $cid = $item->company_id;
                $cname = $item->company ? $item->company->name : (string) $cid;
                if (!isset($idealCostByCompany[$cid])) {
                    $idealCostByCompany[$cid] = ['company_id' => $cid, 'company_name' => $cname, 'total' => 0.0];
                }
                $idealCostByCompany[$cid]['total'] += (float) $item->ideal_cost;
            }
        }

        // Actual purchase by company
        $foodPurchases = FoodPurchase::with('items.company')
            ->whereBetween('date', [$startStr, $endStr])
            ->whereHas('items', function ($query) use ($selectedCompanyIds) {
                $query->whereIn('company_id', $selectedCompanyIds);
            })
            ->get();
        $actualPurchaseByCompany = [];
        foreach ($foodPurchases as $fp) {
            foreach ($fp->items as $item) {
                if (!empty($selectedCompanyIds) && !in_array((int) $item->company_id, $selectedCompanyIds, true)) {
                    continue;
                }
                $cid = $item->company_id;
                $cname = $item->company ? $item->company->name : (string) $cid;
                if (!isset($actualPurchaseByCompany[$cid])) {
                    $actualPurchaseByCompany[$cid] = ['company_id' => $cid, 'company_name' => $cname, 'total' => 0.0];
                }
                $actualPurchaseByCompany[$cid]['total'] += (float) $item->total_amount;
            }
        }

        // Merge all companies (from any of the three sources)
        $allCompanyIds = array_unique(array_merge(
            array_keys($networkSalesByCompany),
            array_keys($idealCostByCompany),
            array_keys($actualPurchaseByCompany)
        ));

        $rows = [];
        $totals = [
            'network_sales' => 0.0,
            'ideal_cost'    => 0.0,
            'actual_purchase' => 0.0,
            'diff'          => 0.0,
        ];

        foreach ($allCompanyIds as $cid) {
            $ns  = $networkSalesByCompany[$cid]['total'] ?? 0.0;
            $ic  = $idealCostByCompany[$cid]['total'] ?? 0.0;
            $ap  = $actualPurchaseByCompany[$cid]['total'] ?? 0.0;
            $name = $networkSalesByCompany[$cid]['company_name']
                ?? $idealCostByCompany[$cid]['company_name']
                ?? $actualPurchaseByCompany[$cid]['company_name']
                ?? (string) $cid;

            $diff = $ic - $ap;
            $stdIc   = $ns > 0 ? $ic / $ns : null;
            $actPur  = $ns > 0 ? $ap / $ns : null;
            $diffNs   = $ns > 0 ? $diff / $ns : null;

            $rows[] = [
                'company_id'      => $cid,
                'company_name'    => $name,
                'network_sales'   => round($ns, 2),
                'ideal_cost'      => round($ic, 2),
                'actual_purchase' => round($ap, 2),
                'diff'            => round($diff, 2),
                'std_ic'          => $stdIc !== null ? round($stdIc, 4) : null,
                'act_pur'         => $actPur !== null ? round($actPur, 4) : null,
                'diff_ns'         => $diffNs !== null ? round($diffNs, 4) : null,
            ];

            $totals['network_sales']   += $ns;
            $totals['ideal_cost']      += $ic;
            $totals['actual_purchase'] += $ap;
            $totals['diff']            += $diff;
        }

        $totals['std_ic']   = $totals['network_sales'] > 0 ? $totals['ideal_cost'] / $totals['network_sales'] : null;
        $totals['act_pur']  = $totals['network_sales'] > 0 ? $totals['actual_purchase'] / $totals['network_sales'] : null;
        $totals['diff_ns']  = $totals['network_sales'] > 0 ? $totals['diff'] / $totals['network_sales'] : null;

        usort($rows, fn ($a, $b) => strcasecmp($a['company_name'], $b['company_name']));

        return response()->json([
            'rows'   => $rows,
            'totals' => $totals,
        ]);
    }

    /**
     * Food Truck Report (stub): returns all stores with zero values.
     */
    public function getFoodTruckReport(Request $request)
    {
        $request->validate([
            'year' => 'required|integer|min:2000|max:2100',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'user_id' => 'nullable|integer|exists:users,id',
            'workgroup_ids' => 'nullable|array',
            'workgroup_ids.*' => 'integer|exists:workgroup,id',
        ]);
        $startDate = Carbon::parse($request->start_date)->startOfDay();
        $endDate = Carbon::parse($request->end_date)->endOfDay();
        $workgroupIds = collect($request->workgroup_ids ?? [])->map(fn ($id) => (int) $id)->filter()->values();

        $user = $request->user_id ? User::find($request->user_id) : null;
        $companyIds = $user ? $user->getCompaniesArrayAttribute() : null;


        $selectedCompanyIds = Company::query()
            ->authorizedCompanies('id', false)
            ->when($workgroupIds->isNotEmpty(), function ($query) use ($workgroupIds) {
                return $query->whereIn('workgroup_id', $workgroupIds->all());
            })
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        if ($companyIds !== null) {
            $selectedCompanyIds = array_values(array_intersect($selectedCompanyIds, array_map('intval', $companyIds)));
        }
        $companies = Company::query()
            ->select('id', 'name', 'store_number')
            ->orderBy('store_number')
            ->orderBy('name')
            ->whereIn('id', $selectedCompanyIds)
            ->get();
        $companyIds = $companies->pluck('id');

        $salesProjection = SalesProjection::query()
            ->where('start_date', $startDate->toDateString())
            ->where('end_date', $endDate->toDateString())
            ->whereIn('company_id', $companyIds)
            ->get()
            ->keyBy('company_id');

        $lastweekStartDate = Carbon::parse($request->start_date)->subWeek()->startOfDay();
        $lastweekEndDate = Carbon::parse($request->end_date)->subWeek()->endOfDay();

        $lastWeekIdealCost = IdealCostItem::query()
            ->join('ideal_cost', 'ideal_cost_items.ideal_cost_id', '=', 'ideal_cost.id')
            ->whereBetween('ideal_cost.date', [$lastweekStartDate->toDateString(), $lastweekEndDate->toDateString()])
            ->whereIn('ideal_cost_items.company_id', $companyIds)
            ->groupBy('ideal_cost_items.company_id')
            ->selectRaw('ideal_cost_items.company_id, SUM(ideal_cost_items.ideal_cost) as total_ideal_cost')
            ->get()
            ->keyBy('company_id');

        $lastWeekFoodPurchase = FoodPurchaseItems::query()
            ->join('food_purchase', 'food_purchase_items.food_purchase_id', '=', 'food_purchase.id')
            ->whereBetween('food_purchase.date', [$lastweekStartDate->toDateString(), $lastweekEndDate->toDateString()])
            ->whereIn('food_purchase_items.company_id', $companyIds)
            ->groupBy('food_purchase_items.company_id')
            ->selectRaw('food_purchase_items.company_id, SUM(food_purchase_items.total_amount) as total_amount')
            ->get()
            ->keyBy('company_id');


        
        if(count($salesProjection) == 0) {
            $salesProjection = [];
            $last2weekStartDate = Carbon::parse($request->start_date)->subWeeks(2)->startOfDay();
            $last2weekEndDate = Carbon::parse($request->end_date)->subWeeks(2)->endOfDay();

            $salesWindowStart = $last2weekStartDate->toDateString();
            $salesWindowEnd = $last2weekEndDate->toDateString();


            $dailySales = DailySale::with('company')
                ->whereBetween('date', [$salesWindowStart, $salesWindowEnd])
                ->whereIn('company_id', $companyIds)
                ->groupBy('company_id')
                ->selectRaw('company_id, SUM(net_sales) as total_sales')
                ->get()
                ->keyBy('company_id');

            $idealCosts = IdealCostItem::with('company')
                ->join('ideal_cost', 'ideal_cost_items.ideal_cost_id', '=', 'ideal_cost.id')
                ->whereBetween('ideal_cost.date', [$salesWindowStart, $salesWindowEnd])
                ->whereIn('ideal_cost_items.company_id', $companyIds)
                ->groupBy('ideal_cost_items.company_id')
                ->selectRaw('ideal_cost_items.company_id, SUM(ideal_cost_items.ideal_cost) as total_ideal_cost')
                ->get()
                ->keyBy('company_id');
            
            $allCompanyIds = array_unique(array_merge(
                array_keys($dailySales->toArray()),
                array_keys($idealCosts->toArray())
            ));
            foreach ($allCompanyIds as $cid) {
                if(isset($dailySales[$cid]) && isset($idealCosts[$cid])) {
                    $sales_projection_var = round($dailySales[$cid]['total_sales'] / 100) * 100;
                    $ideal_cost_percent_var = round($idealCosts[$cid]['total_ideal_cost'] / $dailySales[$cid]['total_sales'], 4);
                    $ideal_food_projection_var = round($sales_projection_var * $ideal_cost_percent_var, 2);
                    $salesProjection[$cid] = [
                        'sales_projection' => $sales_projection_var,
                        'ideal_cost_percent' => round($ideal_cost_percent_var*100, 2),
                        'ideal_food_projection' => $ideal_food_projection_var,
                    ];
                }
            }
            
        }
        $foodPurchase = FoodPurchase::query()
            ->join('food_purchase_items', 'food_purchase.id', '=', 'food_purchase_items.food_purchase_id')
            ->selectRaw('food_purchase_items.company_id, food_purchase.date, SUM(food_purchase_items.total_amount) as total_amount')
            ->whereBetween('food_purchase.date', [$startDate, $endDate])
            ->whereIn('food_purchase_items.company_id', $companyIds)
            ->groupBy('food_purchase_items.company_id', 'food_purchase.date')
            ->orderBy('food_purchase.date')
            ->get();

        $trucks=0;
        $truckData=[];
        foreach ($foodPurchase as $fp) {
            if(!isset($truckData[$fp->company_id])) {
                $truckData[$fp->company_id] = [];
            }
            $truckData[$fp->company_id][] = [
                'date' => $fp->date,
                'total_amount' => $fp->total_amount,
            ];
            if(count($truckData[$fp->company_id]) > $trucks) {
                $trucks = count($truckData[$fp->company_id]);
            }
        }

        $rows = $companies->map(function ($company) use ($salesProjection, $truckData, $trucks, $lastWeekIdealCost, $lastWeekFoodPurchase) {
            $storeLabel = trim(($company->store_number ? $company->store_number . ' - ' : '') . $company->name);
            $array=[
                'company_id' => $company->id,
                'store' => $storeLabel,
                'sales_projection' => 0,
                'ideal_cost_percent' => 0,
                'ideal_food_projection' => 0,
                'pre_wk_extra' => 0,
                'net_order' => 0,
                'truck_1' => 0,
                'diff_after_truck_1' => 0,
                'truck_2' => 0,
                'diff_after_truck_2' => 0,
                'total' => 0,
                'diff_after_total' => 0,
            ];

            $array['pre_wk_extra'] = isset($lastWeekIdealCost[$company->id]) ? $lastWeekIdealCost[$company->id]['total_ideal_cost'] : 0;
            $array['pre_wk_extra'] -= isset($lastWeekFoodPurchase[$company->id]) ? $lastWeekFoodPurchase[$company->id]['total_amount'] : 0;

            if($array['pre_wk_extra']>0){
                $array['pre_wk_extra'] = 0;
            }
            if(isset($salesProjection[$company->id])) {
                $array['sales_projection'] = $salesProjection[$company->id]['sales_projection'];
                $array['ideal_cost_percent'] = $salesProjection[$company->id]['ideal_cost_percent'];
                $array['ideal_food_projection'] = $salesProjection[$company->id]['ideal_food_projection'];
            }
            $array['net_order'] = $array['ideal_food_projection'] + $array['pre_wk_extra'];
            for($i=1; $i<=$trucks; $i++) {
                if(isset($truckData[$company->id][$i-1])) {
                    $array['truck_'.$i] = $truckData[$company->id][$i-1]['total_amount'];
                    if($i < $trucks) {
                        $array['diff_after_truck_'.$i] = $array['net_order'] - $array['truck_'.$i];
                    }
                }
            }
            $array['total'] =  $array['truck_1'] + $array['truck_2'];
            $array['diff_after_total'] = $array['net_order'] - $array['total'];

           for($i=1; $i<=$trucks; $i++) {
            if(isset($truckData[$company->id][$i-1])) {
                $array['truck_'.$i] = $truckData[$company->id][$i-1]['total_amount'];
            }
           }
           return $array;
           
        })->values();

        return response()->json([
            'rows' => $rows,
            'trucks' => $trucks,
        ]);
    }

    /**
     * Sales Projection Report:
     * - Filter by year + selected week
     * - Shows previous 3 weekly sales for each store
     * - Shows previous week ideal cost %
     * - Returns default sales projection (avg of previous 3 weeks)
     * - Returns ideal food projection and pre week diff
     */
    public function getSalesProjectionReport(Request $request)
    {
        $this->authorize('access', 'sales-projection-report.index');
        $request->validate([
            'year' => 'required|integer|min:2000|max:2100',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'user_id' => 'nullable|integer|exists:users,id',
            'workgroup_ids' => 'nullable|array',
            'workgroup_ids.*' => 'integer|exists:workgroup,id',
        ]);

        $year = (int) $request->year;
        $selectedStart = Carbon::parse($request->start_date)->startOfDay();
        $selectedEnd = Carbon::parse($request->end_date)->endOfDay();
        $userId = $request->user_id ? (int) $request->user_id : null;
        $workgroupIds = collect($request->workgroup_ids ?? [])->map(fn ($id) => (int) $id)->filter()->values();
        $user = $userId ? User::find($userId) : null;
        $companyIds = $user ? $user->getCompaniesArrayAttribute() : null;
        $selectedCompanyIds = Company::query()
            ->authorizedCompanies('id', false)
            ->when($workgroupIds->isNotEmpty(), function ($query) use ($workgroupIds) {
                return $query->whereIn('workgroup_id', $workgroupIds->all());
            })
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();
        if ($companyIds !== null) {
            $selectedCompanyIds = array_values(array_intersect($selectedCompanyIds, array_map('intval', $companyIds)));
        }

        if ($selectedStart->year !== $year && $selectedEnd->year !== $year) {
            return response()->json([
                'message' => 'Selected week does not belong to requested year.',
            ], 422);
        }

        $weekRanges = [];
        for ($i = 2; $i <= 4; $i++) {
            $start = $selectedStart->copy()->subWeeks($i)->startOfDay();
            $end = $selectedEnd->copy()->subWeeks($i)->endOfDay();
            $weekRanges[$i-1] = [$start, $end];
        }


        $salesWindowStart = $weekRanges[3][0]->toDateString();
        $salesWindowEnd = $weekRanges[1][1]->toDateString();

        $dailySales = DailySale::with('company')
            ->whereBetween('date', [$salesWindowStart, $salesWindowEnd])
            ->orderBy('date')
            ->whereIn('company_id', $selectedCompanyIds)
            ->orderBy('company_id')
            ->get();

        $idealCosts = IdealCost::with('items.company')
            ->whereBetween('date', [$weekRanges[1][0]->toDateString(), $weekRanges[1][1]->toDateString()])
            ->orderBy('date')
            ->whereHas('items', function ($query) use ($selectedCompanyIds) {
                $query->whereIn('company_id', $selectedCompanyIds);
            })
            ->get();
        $savedSalesProjections = SalesProjection::query()
            ->where('start_date', $selectedStart->toDateString())
            ->where('end_date', $selectedEnd->toDateString())
            ->whereIn('company_id', $selectedCompanyIds)
            ->orderBy('company_id')
            ->get()
            ->keyBy('company_id');


        $salesByCompanyWeek = [];
        $companyLabels = [];
        foreach ($dailySales as $sale) {
            if (!empty($selectedCompanyIds) && !in_array((int) $sale->company_id, $selectedCompanyIds, true)) {
                continue;
            }

            $saleDate = Carbon::parse($sale->date)->startOfDay();
            $bucket = null;
            for ($i = 1; $i <= 3; $i++) {
                if ($saleDate->betweenIncluded($weekRanges[$i][0], $weekRanges[$i][1])) {
                    $bucket = $i;
                    break;
                }
            }
            if ($bucket === null) {
                continue;
            }

            $cid = (int) $sale->company_id;
            $salesByCompanyWeek[$cid] = $salesByCompanyWeek[$cid] ?? [1 => 0.0, 2 => 0.0, 3 => 0.0];
            $salesByCompanyWeek[$cid][$bucket] += (float) $sale->net_sales;
            if (!isset($companyLabels[$cid])) {
                $companyLabels[$cid] = $sale->company ? $sale->company->name : (string) $cid;
            }
        }

        $idealWeek1ByCompany = [];
        foreach ($idealCosts as $idealCost) {
            foreach ($idealCost->items as $item) {
                if (!empty($selectedCompanyIds) && !in_array((int) $item->company_id, $selectedCompanyIds, true)) {
                    continue;
                }
                $cid = (int) $item->company_id;
                $idealWeek1ByCompany[$cid] = ($idealWeek1ByCompany[$cid] ?? 0.0) + (float) $item->ideal_cost;
                if (!isset($companyLabels[$cid])) {
                    $companyLabels[$cid] = $item->company ? $item->company->name : (string) $cid;
                }
            }
        }

        $allCompanyIds = array_unique(array_merge(
            array_keys($salesByCompanyWeek),
            array_keys($idealWeek1ByCompany),
            array_keys($savedSalesProjections->toArray())
        ));

        if (!empty($allCompanyIds)) {
            $savedProjectionCompanies = Company::query()
                ->whereIn('id', $allCompanyIds)
                ->pluck('name', 'id');

            foreach ($savedProjectionCompanies as $companyId => $companyName) {
                if (!isset($companyLabels[(int) $companyId])) {
                    $companyLabels[(int) $companyId] = $companyName;
                }
            }
        }

        $lastWeekFoodPurchase = FoodPurchaseItems::join('food_purchase', 'food_purchase_items.food_purchase_id', '=', 'food_purchase.id')
            ->whereBetween('food_purchase.date', [$weekRanges[1][0]->toDateString(), $weekRanges[1][1]->toDateString()])
            ->groupBy('food_purchase_items.company_id')
            ->whereIn('food_purchase_items.company_id', $allCompanyIds)
            ->select('food_purchase_items.company_id', DB::raw('SUM(food_purchase_items.total_amount) as total_amount'))
            ->get()
            ->keyBy('company_id');

        $lastWeekFoodPurchaseByCompany = [];
        foreach ($lastWeekFoodPurchase as $cid => $item) {
            $lastWeekFoodPurchaseByCompany[$cid] = (float) $item->total_amount;
            if (!isset($companyLabels[$cid])) {
                $companyLabels[$cid] = $item->company ? $item->company->name : (string) $cid;
            }
        }

        $rows = [];
        foreach ($allCompanyIds as $cid) {
            $pre1 = (float) ($salesByCompanyWeek[$cid][1] ?? 0.0);
            $pre2 = (float) ($salesByCompanyWeek[$cid][2] ?? 0.0);
            $pre3 = (float) ($salesByCompanyWeek[$cid][3] ?? 0.0);
            $idealWeek1 = (float) ($idealWeek1ByCompany[$cid] ?? 0.0);

            // Default projection uses last week sales rounded to nearest 100.
            $projection = round($pre1 / 100) * 100;
            if (isset($savedSalesProjections[$cid])) {
                $projection = (float) $savedSalesProjections[$cid]->sales_projection;
            }
            $idealCostPercent = $pre1 > 0 ? ($idealWeek1 / $pre1) : 0.0;
            $idealCostPercent=round($idealCostPercent, 4);
            $idealFoodProjection = $projection * $idealCostPercent;
            $preWeekDiff = ($lastWeekFoodPurchaseByCompany[$cid] ?? 0.0) - $idealFoodProjection;

            $rows[] = [
                'company_id' => (int) $cid,
                'store' => $companyLabels[$cid] ?? (string) $cid,
                'pre_week_1' => round($pre1, 2),
                'pre_week_2' => round($pre2, 2),
                'pre_week_3' => round($pre3, 2),
                'sales_projection' => round($projection, 2),
                'pre_week_ideal_cost_percent' => round($idealCostPercent, 4),
                'ideal_food_projection' => round($idealFoodProjection, 2),
                'pre_week_diff' => round($preWeekDiff, 2),
                'last_week_food_purchase' => round($lastWeekFoodPurchaseByCompany[$cid] ?? 0.0, 2),
            ];
        }

        usort($rows, fn ($a, $b) => strcasecmp($a['store'], $b['store']));

        return response()->json([
            'rows' => $rows,
            'week_ranges' => [
                'pre_week_1' => [
                    'start_date' => $weekRanges[1][0]->toDateString(),
                    'end_date' => $weekRanges[1][1]->toDateString(),
                ],
                'pre_week_2' => [
                    'start_date' => $weekRanges[2][0]->toDateString(),
                    'end_date' => $weekRanges[2][1]->toDateString(),
                ],
                'pre_week_3' => [
                    'start_date' => $weekRanges[3][0]->toDateString(),
                    'end_date' => $weekRanges[3][1]->toDateString(),
                ],
            ],
        ]);
    }

    public function submitSalesProjectionReport(Request $request)
    {
        $this->authorize('access', 'sales-projection-report.submit');

        $request->validate([
            'year' => 'required|integer|min:2000|max:2100',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'workgroup_ids' => 'nullable|array',
            'workgroup_ids.*' => 'integer|exists:workgroup,id',
            'rows' => 'required|array|min:1',
            'rows.*.company_id' => 'required|integer',
            'rows.*.sales_projection' => 'required|numeric|min:0',
        ]);

        $year = (int) $request->year;
        $startDate = Carbon::parse($request->start_date)->toDateString();
        $endDate = Carbon::parse($request->end_date)->toDateString();
        $rows = $request->rows;
        $workgroupIds = collect($request->workgroup_ids ?? [])->map(fn ($id) => (int) $id)->filter()->values();
        $selectedCompanyIds = Company::query()
            ->authorizedCompanies('id', false)
            ->when($workgroupIds->isNotEmpty(), function ($query) use ($workgroupIds) {
                return $query->whereIn('workgroup_id', $workgroupIds->all());
            })
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $submittedCompanyIds = collect($rows)
            ->pluck('company_id')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();
        $invalidCompanyIds = array_values(array_diff($submittedCompanyIds, $selectedCompanyIds));
        if (!empty($invalidCompanyIds)) {
            return response()->json([
                'message' => 'One or more submitted companies are not allowed for the selected workgroups.',
                'invalid_company_ids' => $invalidCompanyIds,
            ], 422);
        }

        DB::transaction(function () use ($rows, $year, $startDate, $endDate) {
            foreach ($rows as $row) {
                SalesProjection::updateOrCreate(
                    [
                        'start_date' => $startDate,
                        'end_date' => $endDate,
                        'company_id' => (int) $row['company_id'],
                    ],
                    [
                        'year' => $year,
                        'sales_projection' => round((float) $row['sales_projection'], 2),
                        'ideal_food_projection' => round((float) $row['ideal_food_projection'], 2),
                        'ideal_cost_percent' => round((float) $row['ideal_cost_percent'], 2),
                        'updated_by' => Auth::id(),
                    ]
                );
            }
        });

        return response()->json([
            'message' => 'Sales projection submitted successfully.',
        ]);
    }
}
