<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Models\DataEntry\OldDailySale;
use App\Models\Settings\Company;
use App\Models\Settings\CompanyGroup;
use Illuminate\Http\Request;
use Carbon\Carbon;

class SalesReportController extends Controller
{
    
    /**
     * Network weekly sales report: all stores as rows, one column per week for the selected year.
     * Only net sales per company per week.
     */
    public function getNetworkWeeklySalesReport(Request $request)
    {
        $this->authorize('access', 'network-weekly-sales-report.index');
        $request->validate([
            'year' => 'required|integer|min:2020|max:2100',
            'workgroup_ids' => 'nullable|array',
            'workgroup_ids.*' => 'integer|exists:workgroup,id',
        ]);

        $year = (int) $request->input('year');
        $workgroupIds = collect($request->input('workgroup_ids', []))
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->values();

        $yearStart = Carbon::parse($year . '-01-01');
        $yearEnd = Carbon::parse($year . '-12-31');
        $dayOfWeek = (int) $yearStart->format('N');
        $daysToMonday = $dayOfWeek === 7 ? -6 : 1 - $dayOfWeek;
        $firstMonday = $yearStart->copy()->addDays($daysToMonday);

        $eows = [];
        $current = $firstMonday->copy();
        for ($i = 0; $i < 54; $i++) {
            $weekEnd = $current->copy()->addDays(6);
            if ($weekEnd->lt($yearStart)) {
                $current->addDays(7);
                continue;
            }
            if ($current->gt($yearEnd)) {
                break;
            }
            $eows[] = $weekEnd->format('Y-m-d');
            $current->addDays(7);
        }

        $companies = Company::selectRaw('id, store_number, name, CONCAT(store_number, " - ", name) as company_name')
            ->authorizedCompanies('id',false)
            ->whereIn('workgroup_id', $workgroupIds->all())
            ->orderBy('company_name')
            ->get();

        // Get company groups
        $companyGroups = CompanyGroup::active()->get();
        
        // Create a mapping of company_id to group info
        $companyToGroup = [];
        foreach ($companyGroups as $group) {
            if ($group->companies) {
                foreach ($group->company_array as $companyId) {
                    $companyToGroup[$companyId] = [
                        'id' => $group->id,
                        'name' => $group->name,
                    ];
                }
            }
        }

        $reportData = [];
        foreach ($companies as $index => $company) {
            $groupInfo = $companyToGroup[$company->id] ?? null;
            
            $companyData = [
                'sr_no' => $index + 1,
                'company_name' => $company->company_name,
                'store_number' => $company->store_number,
                'company_id' => $company->id,
                'group_id' => $groupInfo ? $groupInfo['id'] : null,
                'group_name' => $groupInfo ? $groupInfo['name'] : 'Ungrouped',
            ];

            if (count($eows) > 0) {
                $startDate = Carbon::parse($eows[0])->subDays(6)->format('Y-m-d');
                $endDate = $eows[count($eows) - 1];

                // Week = Mon–Sun; week_ending = Sunday (DAYOFWEEK: 1=Sun .. 7=Sat)
                $weeklySales = OldDailySale::where('company_id', $company->id)
                    ->whereBetween('date', [$startDate, $endDate])
                    ->selectRaw('DATE(DATE_ADD(date, INTERVAL (8 - DAYOFWEEK(date)) % 7 DAY)) as week_ending, SUM(net_sales) as total_sales')
                    ->groupBy('week_ending')
                    ->get()
                    ->keyBy('week_ending');

                foreach ($eows as $eow) {
                    $companyData['pp_' . $eow] = isset($weeklySales[$eow])
                        ? round((float) $weeklySales[$eow]->total_sales, 2)
                        : 0;
                }
            } else {
                foreach ($eows as $eow) {
                    $companyData['pp_' . $eow] = 0;
                }
            }

            $reportData[] = $companyData;
        }

        return response()->json([
            'data' => $reportData,
            'eows' => $eows,
            'year' => $year,
            'company_groups' => $companyGroups->map(function ($group) {
                return [
                    'id' => $group->id,
                    'name' => $group->name,
                ];
            }),
        ]);
    }

    /**
     * Network monthly sales report: all stores as rows, one column per month for the selected year.
     * Only net sales per company per month.
     */
    public function getNetworkMonthlySalesReport(Request $request)
    {
        $this->authorize('access', 'network-monthly-sales-report.index');
        $request->validate([
            'year' => 'required|integer|min:2020|max:2100',
            'workgroup_ids' => 'nullable|array',
            'workgroup_ids.*' => 'integer|exists:workgroup,id',
        ]);

        $year = (int) $request->input('year');
        $workgroupIds = collect($request->input('workgroup_ids', []))
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->values();

        $monthEnds = [];
        for ($m = 1; $m <= 12; $m++) {
            $monthEnds[] = Carbon::create($year, $m)->endOfMonth()->format('Y-m-d');
        }

        $companies = Company::selectRaw('id, store_number, name, CONCAT(store_number, " - ", name) as company_name')
            ->authorizedCompanies('id',false)
            ->whereIn('workgroup_id', $workgroupIds->all())
            ->orderBy('company_name')
            ->get();

        $reportData = [];
        foreach ($companies as $index => $company) {
            $companyData = [
                'sr_no' => $index + 1,
                'company_name' => $company->company_name,
                'company_id' => $company->id,
                'store_number' => $company->store_number,
            ];

            $monthlySales = OldDailySale::where('company_id', $company->id)
                ->whereYear('date', $year)
                ->selectRaw('LAST_DAY(date) as month_end, SUM(net_sales) as total_sales')
                ->groupBy('month_end')
                ->get()
                ->keyBy('month_end');

            foreach ($monthEnds as $monthEnd) {
                $companyData['pp_' . $monthEnd] = isset($monthlySales[$monthEnd])
                    ? round((float) $monthlySales[$monthEnd]->total_sales, 2)
                    : 0;
            }

            $reportData[] = $companyData;
        }

        return response()->json([
            'data' => $reportData,
            'months' => $monthEnds,
            'year' => $year,
        ]);
    }
}
