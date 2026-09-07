<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Models\AP\ExpenseType;
use App\Models\AP\PurchaseInvoice;
use App\Models\Settings\Company;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class APReportController extends Controller
{
    public function getBillWiseReport(Request $request)
    {
        $this->authorize('access', 'bill-wise-report.index');

        $filters = $this->validateReportFilters($request);
        $expenseType = ExpenseType::query()->findOrFail($filters['expense_id']);

        $year = $filters['year'];
        $startDate = Carbon::create($year, 1, 1)->startOfDay();
        $endDate = Carbon::create($year, 12, 31)->endOfDay();

        $companyIds = $this->filteredCompanyQuery('id, CONCAT(store_number, " - ", name) as name', $filters)
            ->pluck('id');

        $invoices = PurchaseInvoice::query()
            ->leftJoin('company', 'ap_purchase_invoices.company_id', '=', 'company.id')
            ->leftJoin('ap_vendors', 'ap_purchase_invoices.vendor_id', '=', 'ap_vendors.id')
            ->selectRaw('ap_purchase_invoices.company_id as company_id, ap_purchase_invoices.id as id, ap_purchase_invoices.invoice_no as invoice_no, ap_purchase_invoices.invoice_date as invoice_date, ap_purchase_invoices.amount as amount, ap_purchase_invoices.other_amount as other_amount, ap_purchase_invoices.total_amount as total_amount, concat(company.store_number, " - ", company.name) as company_name, ap_vendors.name as vendor_name, ap_vendors.code as vendor_code')
            ->where('ap_purchase_invoices.expense_id', $expenseType->id)
            ->authorizedCompanies('ap_purchase_invoices.company_id', false)
            ->whereIn('ap_purchase_invoices.company_id', $companyIds)
            ->whereBetween('ap_purchase_invoices.invoice_date', [$startDate, $endDate])
            ->get()
            ->groupBy('company_id');

        $allCompanies = $this->filteredCompanyQuery('id, CONCAT(store_number, " - ", name) as name', $filters)->get();

        $maxBills = $invoices->map(fn ($group) => $group->count())->max() ?? 0;

        $companyInvoices = $allCompanies->map(function ($company) use ($invoices) {
            $companyInvoiceGroup = $invoices->get($company->id);

            return [
                'company_name' => $company->name,
                'bills' => $companyInvoiceGroup ? $companyInvoiceGroup->map(function ($invoice) {
                    return [
                        'id' => $invoice->id,
                        'invoice_no' => $invoice->invoice_no,
                        'invoice_date' => $invoice->invoice_date,
                        'amount' => $invoice->amount,
                        'other_amount' => $invoice->other_amount,
                        'total_amount' => $invoice->total_amount,
                        'vendor_name' => $invoice->vendor_name,
                        'vendor_code' => $invoice->vendor_code,
                    ];
                }) : [],
            ];
        });

        return response()->json([
            'data' => $companyInvoices,
            'max_bills' => $maxBills < 5 ? 5 : $maxBills,
            'expense_type' => $this->expenseTypePayload($expenseType),
        ]);
    }

    public function getMonthlyReport(Request $request)
    {
        $this->authorize('access', 'monthly-report.index');

        $filters = $this->validateReportFilters($request);
        $expenseType = ExpenseType::query()->findOrFail($filters['expense_id']);

        return response()->json(
            $this->buildMonthlyExpenseReport($filters, $expenseType)
        );
    }

    private function validateReportFilters(Request $request): array
    {
        $request->validate([
            'year' => 'required|integer|min:2020',
            'expense_id' => 'required|integer|exists:ap_expense_types,id',
            'user_id' => 'nullable|integer|exists:users,id',
            'workgroup_ids' => 'nullable|array',
            'workgroup_ids.*' => 'integer|exists:workgroup,id',
        ]);

        return [
            'year' => (int) $request->year,
            'expense_id' => (int) $request->expense_id,
            'user_id' => $request->user_id ? (int) $request->user_id : null,
            'workgroup_ids' => collect($request->input('workgroup_ids', []))
                ->map(fn ($id) => (int) $id)
                ->filter()
                ->values()
                ->all(),
        ];
    }

    private function filteredCompanyQuery(string $companySelect, array $filters): Builder
    {
        $userCompanyIds = null;
        if ($filters['user_id']) {
            $user = User::find($filters['user_id']);
            $userCompanyIds = $user?->getCompaniesArrayAttribute();
        }

        $workgroupIds = collect($filters['workgroup_ids'] ?? []);

        return Company::authorizedCompanies('id', false)
            ->selectRaw($companySelect)
            ->when($workgroupIds->isNotEmpty(), fn ($query) => $query->whereIn('workgroup_id', $workgroupIds->all()))
            ->when($userCompanyIds !== null, fn ($query) => $query->whereIn('id', $userCompanyIds))
            ->orderByRaw('CAST(store_number AS UNSIGNED)');
    }

    private function buildMonthlyExpenseReport(array $filters, ExpenseType $expenseType): array
    {
        $year = $filters['year'];
        $startDate = Carbon::create($year, 1, 1)->startOfDay();
        $endDate = Carbon::create($year, 12, 31)->endOfDay();
        $includeFrequency = $expenseType->name === 'Trash Tickets';

        $companySelect = $includeFrequency
            ? 'id, store_number, trash_frequency, CONCAT(store_number, " - ", name) as name'
            : 'id, store_number, CONCAT(store_number, " - ", name) as name';

        $companyIds = $this->filteredCompanyQuery($companySelect, $filters)->pluck('id');

        $invoices = PurchaseInvoice::query()
            ->authorizedCompanies('company_id', false)
            ->with([
                'company' => fn ($query) => $query->selectRaw($companySelect),
                'vendor:id,name',
            ])
            ->whereIn('company_id', $companyIds)
            ->where('expense_id', $expenseType->id)
            ->whereBetween('invoice_date', [$startDate, $endDate])
            ->get()
            ->groupBy('company_id');

        $allCompanies = $this->filteredCompanyQuery($companySelect, $filters)->get();

        $monthLabels = $this->monthLabels();
        $grandTotals = [
            'amount' => 0,
            'other_amount' => 0,
            'total_amount' => 0,
            'months' => array_fill_keys(array_keys($monthLabels), [
                'amount' => 0,
                'other_amount' => 0,
                'total' => 0,
            ]),
        ];

        $reportData = $allCompanies->map(function ($company) use ($invoices, $monthLabels, $includeFrequency, &$grandTotals) {
            $companyInvoices = $invoices->get($company->id, collect());

            $providers = $companyInvoices
                ->pluck('vendor.name')
                ->filter()
                ->unique()
                ->sort()
                ->values()
                ->implode(', ');

            $months = [];
            $rowAmount = 0;
            $rowOtherAmount = 0;
            $rowTotalAmount = 0;

            foreach ($monthLabels as $monthKey => $monthLabel) {
                $monthInvoices = $companyInvoices->filter(
                    fn ($invoice) => (int) Carbon::parse($invoice->invoice_date)->month === (int) $monthKey
                );

                $amount = round((float) $monthInvoices->sum('amount'), 2);
                $otherAmount = round((float) $monthInvoices->sum('other_amount'), 2);
                $total = round((float) $monthInvoices->sum(
                    fn ($invoice) => $invoice->total_amount ?? $invoice->amount
                ), 2);

                $months[$monthKey] = [
                    'amount' => $amount,
                    'other_amount' => $otherAmount,
                    'total' => $total,
                ];

                $rowAmount += $amount;
                $rowOtherAmount += $otherAmount;
                $rowTotalAmount += $total;

                $grandTotals['months'][$monthKey]['amount'] += $amount;
                $grandTotals['months'][$monthKey]['other_amount'] += $otherAmount;
                $grandTotals['months'][$monthKey]['total'] += $total;
            }

            $grandTotals['amount'] += $rowAmount;
            $grandTotals['other_amount'] += $rowOtherAmount;
            $grandTotals['total_amount'] += $rowTotalAmount;

            $row = [
                'company_name' => $company->name,
                'providers' => $providers ?: '-',
                'total_amount' => round($rowTotalAmount, 2),
                'months' => $months,
            ];

            if ($includeFrequency) {
                $row['freq'] = $company->trash_frequency ?: '-';
            }

            return $row;
        })->values();

        $grandTotals['amount'] = round($grandTotals['amount'], 2);
        $grandTotals['other_amount'] = round($grandTotals['other_amount'], 2);
        $grandTotals['total_amount'] = round($grandTotals['total_amount'], 2);

        foreach ($grandTotals['months'] as $monthKey => $monthTotals) {
            $grandTotals['months'][$monthKey] = [
                'amount' => round($monthTotals['amount'], 2),
                'other_amount' => round($monthTotals['other_amount'], 2),
                'total' => round($monthTotals['total'], 2),
            ];
        }

        return [
            'data' => $reportData,
            'months' => $monthLabels,
            'year' => $year,
            'totals' => $grandTotals,
            'expense_type' => $this->expenseTypePayload($expenseType),
        ];
    }

    private function expenseTypePayload(ExpenseType $expenseType): array
    {
        return [
            'id' => $expenseType->id,
            'name' => $expenseType->name,
            'amount_label' => $expenseType->amount_label,
            'other_amount_label' => $expenseType->other_amount_label,
            'show_other_amount' => $expenseType->show_other_amount,
        ];
    }

    private function monthLabels(): array
    {
        return [
            1 => 'January',
            2 => 'February',
            3 => 'March',
            4 => 'April',
            5 => 'May',
            6 => 'June',
            7 => 'July',
            8 => 'August',
            9 => 'September',
            10 => 'October',
            11 => 'November',
            12 => 'December',
        ];
    }
}
