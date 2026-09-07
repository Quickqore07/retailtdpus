<?php

namespace App\Http\Controllers\Upload;

use App\Http\Controllers\Controller;
use App\Models\AR\CustomerPaymentItem;
use App\Models\AR\SalesInvoice;
use App\Models\AR\SalesInvoiceItem;
use App\Models\Settings\Setting;
use App\Models\Upload\UploadPortalCustomer;
use App\Models\User;
use App\Services\AR\ArDocumentEmailComposer;
use App\Services\MailService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class SalesInvoiceController extends Controller
{
    private function getAuthenticatedUploadUser()
    {
        $user = Auth::guard('upload-portal')->user();
        if (!$user) {
            return null;
        }
        return User::with('role')->find($user->id);
    }

    private function ensurePermission($user, string $permission): bool
    {
        return Gate::forUser($user)->allows('sp-access', $permission)
            || Gate::forUser($user)->allows('access', $permission);
    }

    private function applySorting($query, Request $request)
    {
        $sortBy = $request->get('sort_by', 'invoice_date');
        $sortDirection = $request->get('sort_direction', 'desc');

        // Validate sort direction
        if (!in_array(strtolower($sortDirection), ['asc', 'desc'])) {
            $sortDirection = 'desc';
        }

        switch ($sortBy) {
            case 'invoice_number':
                $query->orderBy('sales_invoices.invoice_number', $sortDirection);
                break;
            
            case 'customer_id':
                $query->join('upload_portal_customers as customer_sort', 'sales_invoices.customer_id', '=', 'customer_sort.id')
                    ->orderBy('customer_sort.name', $sortDirection)
                    ->select('sales_invoices.*');
                break;
            
            case 'invoice_date':
                $query->orderBy('sales_invoices.invoice_date', $sortDirection);
                break;
            
            case 'due_date':
                $query->orderByRaw("COALESCE(sales_invoices.due_date, '9999-12-31') $sortDirection");
                break;
            
            case 'overdue_days':
                $query->orderByRaw("CASE 
                    WHEN sales_invoices.status = 'paid' THEN 0 
                    WHEN sales_invoices.due_date IS NULL THEN 0 
                    ELSE GREATEST(0, DATEDIFF(CURDATE(), sales_invoices.due_date)) 
                END $sortDirection");
                break;
            
            case 'amount':
                $query->orderBy('sales_invoices.amount', $sortDirection);
                break;
            
            case 'balance_due':
                // Add subquery for calculating balance due
                $query->selectRaw('sales_invoices.*, 
                    (sales_invoices.amount - COALESCE((
                        SELECT SUM(cpi.amount_applied + cpi.discount) 
                        FROM customer_payment_items cpi 
                        WHERE cpi.sales_invoice_id = sales_invoices.id
                    ), 0)) as calculated_balance')
                    ->orderBy('calculated_balance', $sortDirection);
                break;
            
            case 'status':
                $query->orderBy('sales_invoices.status', $sortDirection);
                break;
            
            case 'id':
            default:
                $query->orderBy('sales_invoices.id', $sortDirection);
                break;
        }

        return $query;
    }

    public function index(Request $request)
    {
        $user = $this->getAuthenticatedUploadUser();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'User not authenticated'], 401);
        }

        $customerArList = $request->filled('customer_id')
            && (
                $request->boolean('outstanding')
                || $request->boolean('settled')
                || $request->boolean('customer_ar')
            );
        $canListInvoices = $this->ensurePermission($user, 'upload-portal-invoice.index')
            || ($customerArList && $this->ensurePermission($user, 'upload-portal-customer-payment.add'))
            || ($customerArList && $this->ensurePermission($user, 'upload-portal-customer-payment.edit'))
            || ($customerArList && $this->ensurePermission($user, 'upload-portal-customer.view'))
            || ($customerArList && $this->ensurePermission($user, 'upload-portal-invoice.view'));

        if (! $canListInvoices) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to view invoices',
            ], 403);
        }

        $query = SalesInvoice::query()
            ->with(['customer:id,name,email,mobile,invoice_due,invoice_condition', 'createdBy', 'updatedBy']);

        if ($search = trim((string) $request->input('search'))) {
            $query->where(function ($q) use ($search) {
                $q->where('invoice_number', 'like', "%{$search}%")
                    ->orWhere('remarks', 'like', "%{$search}%")
                    ->orWhereHas('customer', function ($cq) use ($search) {
                        $cq->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    });
            });
        }

        if ($customerId = $request->input('customer_id')) {
            $query->where('customer_id', $customerId);
        }

        if ($request->boolean('outstanding')) {
            $includeIds = array_values(array_filter(array_map('intval', (array) $request->input('include_invoice_ids', []))));
            $query->where(function ($q) use ($includeIds) {
                $q->where(function ($q2) {
                    $q2->whereNotIn('status', ['paid', 'cancelled'])
                        ->whereRaw(
                            'sales_invoices.amount > COALESCE((SELECT SUM(cpi.amount_applied) FROM customer_payment_items cpi WHERE cpi.sales_invoice_id = sales_invoices.id), 0) + 0.005'
                        );
                });
                if ($includeIds !== []) {
                    $q->orWhereIn('sales_invoices.id', $includeIds);
                }
            });
        }elseif ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        if ($from = $request->input('date_from')) {
            $query->whereDate('invoice_date', '>=', $from);
        }

        if ($to = $request->input('date_to')) {
            $query->whereDate('invoice_date', '<=', $to);
        }

        if ($dueFrom = $request->input('due_date_from')) {
            $query->whereDate('due_date', '>=', $dueFrom);
        }

        if ($dueTo = $request->input('due_date_to')) {
            $query->whereDate('due_date', '<=', $dueTo);
        }

        $this->applySorting($query, $request);

        $perPage = (int) $request->input('per_page', 25);
        $invoices = $query->paginate($perPage);

        $this->appendInvoicePaidAmounts($invoices);

        return response()->json([
            'success' => true,
            'invoices' => $invoices,
        ]);
    }

    private function appendInvoicePaidAmounts($paginatorOrInvoice): void
    {
        $collection = $paginatorOrInvoice instanceof SalesInvoice
            ? collect([$paginatorOrInvoice])
            : $paginatorOrInvoice->getCollection();
        if ($collection->isEmpty()) {
            return;
        }

        $ids = $collection->pluck('id')->all();
        $sums = CustomerPaymentItem::query()
            ->whereIn('sales_invoice_id', $ids)
            ->selectRaw('sales_invoice_id, SUM(amount_applied + discount) as s')
            ->groupBy('sales_invoice_id')
            ->pluck('s', 'sales_invoice_id');

        foreach ($collection as $inv) {
            $paid = (float) ($sums[$inv->id] ?? 0);
            $inv->setAttribute('amount_paid', round($paid, 2));
            $inv->setAttribute('balance_due', round(max(0, (float) $inv->amount - $paid), 2));
        }
    }

    private function appendSingleInvoicePaymentSummary(SalesInvoice $invoice): void
    {
        $rows = DB::table('customer_payment_items')
            ->where('customer_payment_items.sales_invoice_id', $invoice->id)
            ->join('customer_payments', 'customer_payments.id', '=', 'customer_payment_items.customer_payment_id')
            ->select('customer_payment_items.amount_applied', 'customer_payments.payment_date','customer_payments.payment_type','customer_payments.id','customer_payment_items.discount')
            ->get();
        $due_balance = (float) array_sum(array_column($rows->toArray(), 'amount_applied')) + (float) array_sum(array_column($rows->toArray(), 'discount'));
        $invoice->setAttribute('balance_due', round(max(0, (float) $invoice->amount - $due_balance), 2));
        $invoice->setAttribute('payment_summary', $rows->toArray());
    }

    public function show($id)
    {
        $user = $this->getAuthenticatedUploadUser();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'User not authenticated'], 401);
        }

        if (
            !$this->ensurePermission($user, 'upload-portal-invoice.view')
            && !$this->ensurePermission($user, 'upload-portal-invoice.edit')
        ) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to view invoices',
            ], 403);
        }

        $invoice = SalesInvoice::with(['items', 'customer', 'createdBy', 'updatedBy'])->find($id);
        if (!$invoice) {
            return response()->json(['success' => false, 'message' => 'Invoice not found'], 404);
        }


        $this->appendSingleInvoicePaymentSummary($invoice);

        $settings = Setting::whereIn('key', ['invoice-company-name', 'invoice-company-address'])->pluck('value', 'key')->toArray();
        $invoice->company_name = isset($settings['invoice-company-name']) ? $settings['invoice-company-name'] : '';
        $invoice->company_address = isset($settings['invoice-company-address']) ? $settings['invoice-company-address'] : '';

        return response()->json([
            'success' => true,
            'invoice' => $invoice,
        ]);
    }

    public function downloadPdf($id)
    {
        $user = $this->getAuthenticatedUploadUser();
        if (! $user) {
            return response()->json(['success' => false, 'message' => 'User not authenticated'], 401);
        }

        if (
            ! $this->ensurePermission($user, 'upload-portal-invoice.view')
            && ! $this->ensurePermission($user, 'upload-portal-invoice.edit')
        ) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to view invoices',
            ], 403);
        }

        $invoice = SalesInvoice::with(['items', 'customer'])->find($id);
        if (! $invoice) {
            return response()->json(['success' => false, 'message' => 'Invoice not found'], 404);
        }

        $this->appendSingleInvoicePaymentSummary($invoice);

        $pdfPath = $this->buildInvoicePdfTempPath($invoice);
        if (! $pdfPath) {
            return response()->json([
                'success' => false,
                'message' => 'Could not generate invoice PDF',
            ], 500);
        }

        $filename = 'invoice-'.preg_replace('/[^A-Za-z0-9._-]+/', '-', (string) $invoice->invoice_number).'.pdf';

        return response()->download($pdfPath, $filename)->deleteFileAfterSend(true);
    }

    public function store(Request $request)
    {
        $user = $this->getAuthenticatedUploadUser();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'User not authenticated'], 401);
        }

        if (!$this->ensurePermission($user, 'upload-portal-invoice.add')) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to add invoices',
            ], 403);
        }

        $validated = $this->validateInvoice($request);

        try {
            DB::beginTransaction();

            $totals = $this->computeTotals($validated['items']);

            $invoice = SalesInvoice::create([
                'customer_id' => $validated['customer_id'],
                'invoice_number' => $validated['invoice_number'],
                'invoice_date' => $validated['invoice_date'],
                'due_date' => $validated['due_date'] ?? null,
                'subtotal' => $totals['subtotal'],
                'discount_total' => $totals['discount_total'],
                'tax_total' => $totals['tax_total'],
                'amount' => $totals['amount'],
                'remarks' => $validated['remarks'] ?? null,
                'status' => $validated['status'] ?? 'draft',
                'created_by' => $user->id,
                'updated_by' => $user->id,
            ]);

            foreach ($validated['items'] as $row) {
                SalesInvoiceItem::create([
                    'sales_invoice_id' => $invoice->id,
                    'item_name' => $row['item_name'],
                    'qty' => $row['qty'],
                    'unit_price' => $row['unit_price'],
                    'discount' => $row['discount'] ?? 0,
                    'tax_amount' => $row['tax_amount'] ?? 0,
                    'total' => $row['total'],
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Invoice created successfully',
                'invoice' => $invoice->fresh()->load(['items', 'customer', 'createdBy', 'updatedBy']),
            ]);
        } catch (\Throwable $th) {
            if (DB::transactionLevel() > 0) {
                DB::rollBack();
            }
            return response()->json([
                'success' => false,
                'message' => 'Failed to save invoice',
                'error' => $th->getMessage(),
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        $user = $this->getAuthenticatedUploadUser();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'User not authenticated'], 401);
        }

        if (!$this->ensurePermission($user, 'upload-portal-invoice.edit')) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to edit invoices',
            ], 403);
        }

        $invoice = SalesInvoice::find($id);
        $old_invoice = $invoice->toArray();
        if (!$invoice) {
            return response()->json(['success' => false, 'message' => 'Invoice not found'], 404);
        }

        $validated = $this->validateInvoice($request, $invoice->id);

        $this->appendInvoicePaidAmounts($invoice);

        try {
            DB::beginTransaction();

            if ($invoice->balance_due != $invoice->amount && ($old_invoice['amount'] != $validated['amount'] || $old_invoice['customer_id'] != $validated['customer_id'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Amount cannot be changed or customer cannot be changed after payment',
                ], 400);
            }

            $totals = $this->computeTotals($validated['items']);

            $invoice->fill([
                'customer_id' => $validated['customer_id'],
                'invoice_number' => $validated['invoice_number'],
                'invoice_date' => $validated['invoice_date'],
                'due_date' => $validated['due_date'] ?? null,
                'subtotal' => $totals['subtotal'],
                'discount_total' => $totals['discount_total'],
                'tax_total' => $totals['tax_total'],
                'amount' => $totals['amount'],
                'remarks' => $validated['remarks'] ?? null,
                'status' => $validated['status'] ?? $invoice->status,
                'updated_by' => $user->id,
            ])->save();

            SalesInvoiceItem::where('sales_invoice_id', $invoice->id)->delete();
            foreach ($validated['items'] as $row) {
                SalesInvoiceItem::create([
                    'sales_invoice_id' => $invoice->id,
                    'item_name' => $row['item_name'],
                    'qty' => $row['qty'],
                    'unit_price' => $row['unit_price'],
                    'discount' => $row['discount'] ?? 0,
                    'tax_amount' => $row['tax_amount'] ?? 0,
                    'total' => $row['total'],
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Invoice updated successfully',
                'invoice' => $invoice->fresh()->load(['items', 'customer', 'createdBy', 'updatedBy']),
            ]);
        } catch (\Throwable $th) {
            if (DB::transactionLevel() > 0) {
                DB::rollBack();
            }
            return response()->json([
                'success' => false,
                'message' => 'Failed to update invoice',
                'error' => $th->getMessage(),
            ], 500);
        }
    }

    public function destroy($id)
    {
        $user = $this->getAuthenticatedUploadUser();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'User not authenticated'], 401);
        }

        if (!$this->ensurePermission($user, 'upload-portal-invoice.delete')) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to delete invoices',
            ], 403);
        }

        $invoice = SalesInvoice::find($id);
        $paymentItems = CustomerPaymentItem::where('sales_invoice_id', $id)->get();
        if ($paymentItems->count() > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Invoice has payment items and cannot be deleted',
            ], 400);
        }
        if (!$invoice) {
            return response()->json(['success' => false, 'message' => 'Invoice not found'], 404);
        }

        $invoice->delete();

        return response()->json([
            'success' => true,
            'message' => 'Invoice deleted successfully',
        ]);
    }

    public function nextInvoiceNumber()
    {
        $user = $this->getAuthenticatedUploadUser();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'User not authenticated'], 401);
        }
        $settings = Setting::whereIn('key', ['invoice-prefix', 'invoice-year-(0/1)', 'invoice-starting-number', 'invoice-length'])->pluck('value', 'key')->toArray();
        $prefix = isset($settings['invoice-prefix']) ? $settings['invoice-prefix'] : 'INV';
        $year = isset($settings['invoice-year-(0/1)']) ? ($settings['invoice-year-(0/1)'] == 1 ? now()->format('Y') : '') : now()->format('Y');
        $starting_number = isset($settings['invoice-starting-number']) ? $settings['invoice-starting-number'] : 1;
        $length = isset($settings['invoice-length']) ? $settings['invoice-length'] : 5;

        $prefix = $prefix .  ($year != '' ? '-' . $year : '') . '-';
        $latest = SalesInvoice::query()
            ->where('invoice_number', 'like', $prefix . '%')
            ->orderByDesc('id')
            ->value('invoice_number');

        $next = 1;
        if ($latest && preg_match('/(\d+)$/', $latest, $m)) {
            $next = (int) $m[1] + 1;
        }

        if ($next < $starting_number) {
            $next = $starting_number;
        }

        return response()->json([
            'success' => true,
            'invoice_number' => $prefix . str_pad((string) $next, $length, '0', STR_PAD_LEFT),
        ]);
    }

    public function computeDueDate(Request $request)
    {
        $validated = $request->validate([
            'customer_id' => 'required|exists:upload_portal_customers,id',
            'invoice_date' => 'required|date',
        ]);

        $customer = UploadPortalCustomer::find($validated['customer_id']);
        $due = $this->computeDueDateFromCustomerTerms($customer, $validated['invoice_date']);

        return response()->json([
            'success' => true,
            'due_date' => $due,
        ]);
    }

    private function validateInvoice(Request $request, $invoiceId = null): array
    {
        $rules = [
            'customer_id' => 'required|exists:upload_portal_customers,id',
            'invoice_number' => [
                'required',
                'string',
                'max:64',
                Rule::unique('sales_invoices', 'invoice_number')
                    ->ignore($invoiceId),
            ],
            'invoice_date' => 'required|date',
            'due_date' => 'nullable|date|after_or_equal:invoice_date',
            'remarks' => 'nullable|string|max:5000',
            'status' => ['nullable', Rule::in(['draft', 'sent', 'paid', 'cancelled'])],
            'items' => 'required|array|min:1',
            'items.*.item_name' => 'required|string|max:255',
            'items.*.qty' => 'required|numeric|min:0',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.discount' => 'nullable|numeric|min:0',
            'items.*.tax_amount' => 'nullable|numeric|min:0',
            'items.*.total' => 'required|numeric|min:0',
        ];

        $data = $request->validate($rules);

        foreach ($data['items'] as $index => $row) {
            $expected = $this->lineTotal($row);
            $given = round((float) ($row['total'] ?? 0), 2);
            if (abs($expected - $given) > 0.01) {
                throw ValidationException::withMessages([
                    "items.$index.total" => 'Line total does not match qty * unit_price - discount + tax_amount',
                ]);
            }
        }

        return $data;
    }

    private function lineTotal(array $row): float
    {
        $qty = (float) ($row['qty'] ?? 0);
        $price = (float) ($row['unit_price'] ?? 0);
        $discount = (float) ($row['discount'] ?? 0);
        $tax = (float) ($row['tax_amount'] ?? 0);
        return round(max(0, ($qty * $price) - $discount) + $tax, 2);
    }

    private function computeTotals(array $items): array
    {
        $subtotal = 0;
        $discount = 0;
        $tax = 0;
        $amount = 0;

        foreach ($items as $row) {
            $qty = (float) ($row['qty'] ?? 0);
            $price = (float) ($row['unit_price'] ?? 0);
            $subtotal += $qty * $price;
            $discount += (float) ($row['discount'] ?? 0);
            $tax += (float) ($row['tax_amount'] ?? 0);
            $amount += (float) ($row['total'] ?? 0);
        }

        return [
            'subtotal' => round($subtotal, 2),
            'discount_total' => round($discount, 2),
            'tax_total' => round($tax, 2),
            'amount' => round($amount, 2),
        ];
    }

    private function computeDueDateFromCustomerTerms(?UploadPortalCustomer $customer, string $invoiceDate): ?string
    {
        if (!$customer) {
            return null;
        }

        $days = (int) ($customer->invoice_due ?? 0);
        $condition = strtolower(trim((string) ($customer->invoice_condition ?? '')));

        try {
            $base = Carbon::parse($invoiceDate)->startOfDay();
        } catch (\Throwable) {
            return null;
        }

        if ($condition === '' || $condition === 'of current month') {
            $endOfMonth = $base->copy()->endOfMonth();
            $day = max(1, min($days > 0 ? $days : $base->day, $endOfMonth->day));
            return $base->copy()->setDay($day)->toDateString();
        }

        if ($condition === 'of the following month') {
            $next = $base->copy()->addMonthNoOverflow()->endOfMonth();
            $day = max(1, min($days > 0 ? $days : 1, $next->day));
            return $next->copy()->setDay($day)->toDateString();
        }

        if ($condition === 'day(s) after the invoice date') {
            return $base->copy()->addDays($days)->toDateString();
        }

        if ($condition === 'day(s) after the end of the invoice month') {
            return $base->copy()->endOfMonth()->addDays($days)->toDateString();
        }

        return null;
    }

    public function markAsSent($id)
    {
        $user = $this->getAuthenticatedUploadUser();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'User not authenticated'], 401);
        }
        
        if (!$this->ensurePermission($user, 'upload-portal-invoice.edit')) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to edit invoices',
            ], 403);
        }
        
        $invoice = SalesInvoice::find($id);
        if (!$invoice) {
            return response()->json(['success' => false, 'message' => 'Invoice not found'], 404);
        }
        
        $invoice->status = 'sent';
        $invoice->updated_by = $user->id;
        $invoice->save();
        
        return response()->json([
            'success' => true,
            'message' => 'Invoice marked as sent successfully',
        ]);
    }

    public function emailCompose($id)
    {
        $user = $this->getAuthenticatedUploadUser();
        if (! $user) {
            return response()->json(['success' => false, 'message' => 'User not authenticated'], 401);
        }

        if (! $this->ensurePermission($user, 'upload-portal-invoice.mail-sent')) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to send invoices',
            ], 403);
        }

        $invoice = SalesInvoice::with(['customer'])->find($id);
        if (! $invoice) {
            return response()->json(['success' => false, 'message' => 'Invoice not found'], 404);
        }

        $values = ArDocumentEmailComposer::invoicePlaceholderValues($invoice);
        $templateId = ArDocumentEmailComposer::resolveTemplateId(null, 'invoice');
        $preview = ArDocumentEmailComposer::preview($templateId, 'invoice', $values);

        return response()->json([
            'success' => true,
            'to_email' => trim((string) ($invoice->customer?->email ?? '')),
            'templates' => ArDocumentEmailComposer::templatesList('invoice'),
            'template_id' => $templateId,
            'subject' => $preview['subject'],
            'body' => $preview['body'],
        ]);
    }

    public function emailPreview(Request $request, $id)
    {
        $user = $this->getAuthenticatedUploadUser();
        if (! $user) {
            return response()->json(['success' => false, 'message' => 'User not authenticated'], 401);
        }

        if (! $this->ensurePermission($user, 'upload-portal-invoice.mail-sent')) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to send invoices',
            ], 403);
        }

        $validated = $request->validate([
            'template_id' => 'sometimes|nullable|integer|exists:ar_email_templates,id',
        ]);

        $invoice = SalesInvoice::with(['customer'])->find($id);
        if (! $invoice) {
            return response()->json(['success' => false, 'message' => 'Invoice not found'], 404);
        }

        $values = ArDocumentEmailComposer::invoicePlaceholderValues($invoice);
        $tid = isset($validated['template_id']) ? (int) $validated['template_id'] : null;
        $preview = ArDocumentEmailComposer::preview($tid, 'invoice', $values);

        return response()->json([
            'success' => true,
            'subject' => $preview['subject'],
            'body' => $preview['body'],
        ]);
    }

    public function emailSend(Request $request, $id)
    {
        $user = $this->getAuthenticatedUploadUser();
        if (! $user) {
            return response()->json(['success' => false, 'message' => 'User not authenticated'], 401);
        }

        if (! $this->ensurePermission($user, 'upload-portal-invoice.mail-sent')) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to send invoices',
            ], 403);
        }

        $validated = $request->validate([
            'to_email' => 'required|email|max:255',
            'subject' => 'required|string|max:500',
            'body' => 'nullable|string|max:65535',
        ]);

        $invoice = SalesInvoice::with(['items', 'customer'])->find($id);
        if (! $invoice) {
            return response()->json(['success' => false, 'message' => 'Invoice not found'], 404);
        }

        $this->appendSingleInvoicePaymentSummary($invoice);

        $pdfPath = $this->buildInvoicePdfTempPath($invoice);
        if (! $pdfPath) {
            return response()->json([
                'success' => false,
                'message' => 'Could not generate invoice PDF',
            ], 500);
        }

        $html = $this->plainEmailHtml($validated['body'] ?? '');
        $attachments = [[
            'path' => $pdfPath,
            'name' => 'invoice-'.preg_replace('/[^A-Za-z0-9._-]+/', '-', (string) $invoice->invoice_number).'.pdf',
        ]];

        try {
            $result = MailService::sendMail(
                $validated['to_email'],
                $validated['subject'],
                $html,
                [],
                null,
                $attachments
            );
        } finally {
            if (is_file($pdfPath)) {
                @unlink($pdfPath);
            }
        }

        if (! $this->brevoMailSucceeded($result)) {
            $msg = is_array($result) ? ($result['message'] ?? json_encode($result)) : (string) $result;

            return response()->json([
                'success' => false,
                'message' => 'Email could not be sent'.($msg ? ': '.$msg : ''),
            ], 502);
        }
        unset($invoice->balance_due);
        unset($invoice->payment_summary);
        $invoice->status = 'sent';
        $invoice->updated_by = $user->id;
        $invoice->save();

        return response()->json([
            'success' => true,
            'message' => 'Invoice emailed successfully',
        ]);
    }

    private function plainEmailHtml(string $body): string
    {
        $escaped = trim($body) === ''
            ? "\u{00A0}"
            : htmlspecialchars($body, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

        return '<div style="font-family:Segoe UI,Roboto,Helvetica,Arial,sans-serif;font-size:14px;line-height:1.5;color:#111">'
            .nl2br($escaped, false)
            .'</div>';
    }

    private function brevoMailSucceeded(mixed $result): bool
    {
        return is_array($result) && isset($result['messageId']);
    }

    private function buildInvoicePdfTempPath(SalesInvoice $invoice): ?string
    {
        $customer = $invoice->customer;
        if (! $customer) {
            return null;
        }

        $branding = config('upload_portal.ar_account_statement', []);
        $companyAddress = $branding['company_address_lines'] ?? [];
        if (! is_array($companyAddress)) {
            $companyAddress = [];
        }

        $cityState = collect([$customer->city, $customer->state])->filter()->implode(', ');
        $zipPart = $customer->zip_code ? ' '.$customer->zip_code : '';
        $cityLine = trim($cityState.$zipPart);

        $fmt = static fn (float $n): string => '$'.number_format($n, 2);

        $fmtQty = static function (float $q): string {
            $r = round($q, 4);

            return abs($r - round($r)) < 0.0001 ? (string) (int) round($r) : number_format($q, 2, '.', '');
        };

        $items = [];
        foreach ($invoice->items as $row) {
            $items[] = [
                'item_name' => (string) $row->item_name,
                'qty' => $fmtQty((float) $row->qty),
                'unit_price' => $fmt((float) $row->unit_price),
                'discount' => $fmt((float) $row->discount),
                'tax' => $fmt((float) $row->tax_amount),
                'total' => $fmt((float) $row->total),
            ];
        }

        $paymentSummary = [];
        foreach ($invoice->getAttribute('payment_summary') ?? [] as $p) {
            $p = (array) $p;
            $paidOn = isset($p['payment_date']) && $p['payment_date']
                ? \Carbon\Carbon::parse($p['payment_date'])->format('m/d/Y')
                : '';
            $ptype = (string) ($p['payment_type'] ?? '');
            $applied = (float) ($p['amount_applied'] ?? 0);
            $disc = (float) ($p['discount'] ?? 0);
            $paymentSummary[] = [
                'label' => 'Paid ('.$ptype.') on '.$paidOn,
                'amount' => $fmt($applied + $disc),
            ];
        }

        $invoiceDate = $invoice->invoice_date ? $invoice->invoice_date->format('m/d/Y') : '';
        $dueDate = $invoice->due_date ? $invoice->due_date->format('m/d/Y') : '';
        $balanceDue = $fmt((float) ($invoice->getAttribute('balance_due') ?? $invoice->amount));

        $settings = Setting::whereIn('key', ['invoice-company-name', 'invoice-company-address'])->pluck('value', 'key')->toArray();
        $companyName = isset($settings['invoice-company-name']) ? $settings['invoice-company-name'] : '';
        $companyAddress = isset($settings['invoice-company-address']) ? $settings['invoice-company-address'] : '';

        $pdf = Pdf::loadView('pdfs.upload-portal-ar-sales-invoice', [
            'companyLegalName' => $companyName,
            'companyAddressLines' => $companyAddress,
            'invoiceNumber' => (string) $invoice->invoice_number,
            'invoiceDate' => $invoiceDate,
            'dueDate' => $dueDate,
            'balanceDue' => $balanceDue,
            'customerName' => $customer->documentDisplayName(),
            'customerPrimaryPerson' => trim((string) ($customer->primary_person_name ?? '')),
            'customerAddressLine1' => trim((string) ($customer->address_line_1 ?? '')),
            'customerAddressLine2' => trim((string) ($customer->address_line_2 ?? '')),
            'customerAddressLine3' => trim((string) ($customer->address_line_3 ?? '')),
            'customerCityStateZip' => $cityLine,
            'customerCountry' => trim((string) ($customer->country ?? '')),
            'customerEmail' => trim((string) ($customer->email ?? '')),
            'customerMobile' => trim((string) ($customer->mobile ?? '')),
            'items' => $items,
            'subtotal' => $fmt((float) $invoice->subtotal),
            'discountTotal' => $fmt((float) $invoice->discount_total),
            'taxTotal' => $fmt((float) $invoice->tax_total),
            'amountTotal' => $fmt((float) $invoice->amount),
            'remarks' => trim((string) ($invoice->remarks ?? '')),
            'paymentSummary' => $paymentSummary,
        ]);

        $tmp = tempnam(sys_get_temp_dir(), 'inv-pdf-');
        if ($tmp === false) {
            return null;
        }
        $path = $tmp.'.pdf';
        if (! @rename($tmp, $path)) {
            @unlink($tmp);

            return null;
        }
        file_put_contents($path, $pdf->output());

        return $path;
    }
}
