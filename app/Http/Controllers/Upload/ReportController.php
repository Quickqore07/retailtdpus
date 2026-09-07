<?php

namespace App\Http\Controllers\Upload;

use App\Http\Controllers\Controller;
use App\Models\AR\CustomerPaymentItem;
use App\Models\AR\SalesInvoice;
use App\Models\Upload\UploadPortalCustomer;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class ReportController extends Controller
{
    private function getAuthenticatedUploadUser()
    {
        $user = Auth::guard('upload-portal')->user();
        if (! $user) {
            return null;
        }

        return User::with('role')->find($user->id);
    }

    private function ensurePermission($user, string $permission): bool
    {
        return Gate::forUser($user)->allows('sp-access', $permission)
            || Gate::forUser($user)->allows('access', $permission);
    }

    public function arAgingReport(Request $request)
    {
        $user = $this->getAuthenticatedUploadUser();
        if (! $user) {
            return response()->json(['success' => false, 'message' => 'User not authenticated'], 401);
        }

        if (! $this->ensurePermission($user, 'upload-portal-ar-aging-report.index')) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to view the AR aging report',
            ], 403);
        }

        $validated = $request->validate([
            'as_of_date' => 'nullable|date',
            'search' => 'nullable|string|max:255',
            'include_zero' => 'nullable|boolean',
        ]);

        $asOf = isset($validated['as_of_date'])
            ? Carbon::parse($validated['as_of_date'])->startOfDay()
            : now()->startOfDay();

        $includeZero = filter_var($request->input('include_zero', false), FILTER_VALIDATE_BOOLEAN);
        $search = trim((string) ($validated['search'] ?? ''));

        $customerQuery = UploadPortalCustomer::query()->orderBy('name');
        if ($search !== '') {
            $customerQuery->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('mobile', 'like', "%{$search}%")
                    ->orWhere('primary_person_name', 'like', "%{$search}%");
            });
        }

        $customers = $customerQuery->get(['id', 'name', 'email', 'mobile', 'primary_person_name']);
        if ($customers->isEmpty()) {
            return $this->emptyResponse($asOf);
        }

        $customerIds = $customers->pluck('id')->all();

        $invoices = SalesInvoice::query()
            ->whereIn('customer_id', $customerIds)
            ->where('status', '!=', 'cancelled')
            ->whereDate('invoice_date', '<=', $asOf->toDateString())
            ->select([
                'sales_invoices.id',
                'sales_invoices.customer_id',
                'sales_invoices.invoice_number',
                'sales_invoices.invoice_date',
                'sales_invoices.due_date',
                'sales_invoices.amount',
                DB::raw('COALESCE((SELECT SUM(cpi.amount_applied + cpi.discount) FROM customer_payment_items cpi WHERE cpi.sales_invoice_id = sales_invoices.id), 0) AS applied_amount'),
            ])
            ->get();

        $rows = [];
        foreach ($customers as $customer) {
            $rows[$customer->id] = [
                'customer_id' => (int) $customer->id,
                'customer_name' => $customer->name,
                'email' => $customer->email,
                'mobile' => $customer->mobile,
                'primary_person_name' => $customer->primary_person_name,
                'current' => 0.0,
                'days_31_60' => 0.0,
                'days_61_90' => 0.0,
                'days_over_90' => 0.0,
                'total' => 0.0,
                'invoice_count' => 0,
            ];
        }

        $asOfDay = $asOf->copy()->startOfDay();

        foreach ($invoices as $inv) {
            $balance = round((float) $inv->amount - (float) $inv->applied_amount, 2);
            if ($balance <= 0.009) {
                continue;
            }

            $anchor = $inv->due_date ?? $inv->invoice_date;
            if (! $anchor) {
                continue;
            }
            $anchorDay = $anchor->copy()->startOfDay();

            if (! isset($rows[$inv->customer_id])) {
                continue;
            }

            if ($anchorDay->copy()->addDays(31)->isAfter($asOfDay)) {
                $bucket = 'current';
            } elseif ($anchorDay->copy()->addDays(61)->isAfter($asOfDay)) {
                $bucket = 'days_31_60';
            } elseif ($anchorDay->copy()->addDays(91)->isAfter($asOfDay)) {
                $bucket = 'days_61_90';
            } else {
                $bucket = 'days_over_90';
            }

            $rows[$inv->customer_id][$bucket] += $balance;
            $rows[$inv->customer_id]['total'] += $balance;
            $rows[$inv->customer_id]['invoice_count']++;
        }

        $list = array_values($rows);
        if (! $includeZero) {
            $list = array_values(array_filter($list, static fn ($r) => $r['total'] > 0.009));
        }

        foreach ($list as &$row) {
            $row['current'] = round($row['current'], 2);
            $row['days_31_60'] = round($row['days_31_60'], 2);
            $row['days_61_90'] = round($row['days_61_90'], 2);
            $row['days_over_90'] = round($row['days_over_90'], 2);
            $row['total'] = round($row['total'], 2);
        }
        unset($row);

        usort($list, static function (array $a, array $b): int {
            if ($a['total'] === $b['total']) {
                return strcasecmp((string) $a['customer_name'], (string) $b['customer_name']);
            }

            return $b['total'] <=> $a['total'];
        });

        $totals = [
            'current' => round(array_sum(array_column($list, 'current')), 2),
            'days_31_60' => round(array_sum(array_column($list, 'days_31_60')), 2),
            'days_61_90' => round(array_sum(array_column($list, 'days_61_90')), 2),
            'days_over_90' => round(array_sum(array_column($list, 'days_over_90')), 2),
            'total' => round(array_sum(array_column($list, 'total')), 2),
        ];

        return response()->json([
            'success' => true,
            'as_of_date' => $asOf->toDateString(),
            'rows' => $list,
            'totals' => $totals,
        ]);
    }

    public function arCustomerBalanceReport(Request $request)
    {
        $user = $this->getAuthenticatedUploadUser();
        if (! $user) {
            return response()->json(['success' => false, 'message' => 'User not authenticated'], 401);
        }

        if (! $this->ensurePermission($user, 'upload-portal-ar-customer-balance-report.index')) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to view the AR customer balance report',
            ], 403);
        }
        
        $validated = $request->validate([
            'from_date' => 'nullable|date',
            'to_date' => 'nullable|date|after_or_equal:from_date',
            'search' => 'nullable|string|max:255',
            'include_zero' => 'nullable|boolean',
        ]);

        $fromDate = isset($validated['from_date'])
            ? Carbon::parse($validated['from_date'])->startOfDay()
            : null;

        $toDate = isset($validated['to_date'])
            ? Carbon::parse($validated['to_date'])->endOfDay()
            : null;
            
        $search = trim((string) ($validated['search'] ?? ''));
        $includeZero = filter_var($request->input('include_zero', false), FILTER_VALIDATE_BOOLEAN);

        $customerQuery = UploadPortalCustomer::query()->orderBy('name');
        if ($search !== '') {
            $customerQuery->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('mobile', 'like', "%{$search}%")
                    ->orWhere('primary_person_name', 'like', "%{$search}%");
            });
        }

        $customers = $customerQuery->get(['id', 'name', 'email', 'mobile', 'primary_person_name']);
        if ($customers->isEmpty()) {
            return [
                'success' => true,
                'message' => 'No customers found',
                'data' => [],
            ];
        }

        $customerIds = $customers->pluck('id')->all();

        $invoices = SalesInvoice::query()
            ->whereIn('customer_id', $customerIds)
            ->when($fromDate, function ($query) use ($fromDate) {
                $query->whereDate('invoice_date', '>=', $fromDate->toDateString());
            })
            ->when($toDate, function ($query) use ($toDate) {
                $query->whereDate('invoice_date', '<=', $toDate->toDateString());
            })
            ->select([
                'sales_invoices.id',
                'sales_invoices.customer_id',
                'sales_invoices.invoice_number',
                'sales_invoices.invoice_date',
                'sales_invoices.due_date',
                'sales_invoices.amount',
                DB::raw('COALESCE((SELECT SUM(cpi.amount_applied + cpi.discount) FROM customer_payment_items cpi WHERE cpi.sales_invoice_id = sales_invoices.id), 0) AS applied_amount'),
            ])
            ->get()->groupBy('customer_id');

        $invoiceIds = $invoices->pluck('id')->all();

        $payments = CustomerPaymentItem::query()
            ->whereIn('sales_invoice_id', $invoiceIds)
            ->select([
                'customer_payment_items.sales_invoice_id',
                DB::raw('SUM(customer_payment_items.amount_applied + customer_payment_items.discount) AS total_amount'),
            ])
            ->groupBy('sales_invoice_id')
            ->get()->keyBy('sales_invoice_id');
            
        $rows = [];
        foreach ($customers as $customer) {
            $customerInvoices = $invoices[$customer->id] ?? [];
            $dueInvoices=[];
            $dueBalance = 0;

            foreach ($customerInvoices as $invoice) {
                $payments = isset($payments[$invoice->id]) ?$payments[$invoice->id]->total_amount : 0;
                $balance = round((float) $invoice->amount - (float) $payments, 2);
                if ($balance <= 0.009) {
                    continue;
                }
                $dueInvoices[] = [
                    'invoice_number' => $invoice->invoice_number,
                    'invoice_date' => $invoice->invoice_date,
                    'due_date' => $invoice->due_date,
                    'amount' => $invoice->amount,
                    'applied_amount' => $payments,
                    'balance' => $balance,
                ];
                $dueBalance += $balance;
            }
            if($dueBalance > 0 || $includeZero) {

                $rows[$customer->id] = [
                    'customer_id' => (int) $customer->id,
                    'customer_name' => $customer->name,
                    'email' => $customer->email,
                    'mobile' => $customer->mobile,
                    'primary_person_name' => $customer->primary_person_name,
                    'invoices' => $dueInvoices,
                    'due_balance' => $dueBalance,
                ];
            }
        }

        return [
            'success' => true,
            'message' => 'Customer balance report generated successfully',
            'data' => array_values($rows),
        ];
    }

    private function emptyResponse(Carbon $asOf)
    {
        return response()->json([
            'success' => true,
            'as_of_date' => $asOf->toDateString(),
            'rows' => [],
            'totals' => [
                'current' => 0,
                'days_31_60' => 0,
                'days_61_90' => 0,
                'days_over_90' => 0,
                'total' => 0,
            ],
        ]);
    }
}
