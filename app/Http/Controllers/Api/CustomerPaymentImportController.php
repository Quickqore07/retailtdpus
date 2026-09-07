<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AR\CustomerPayment;
use App\Models\AR\CustomerPaymentCreditItem;
use App\Models\AR\CustomerPaymentItem;
use App\Models\AR\SalesInvoice;
use App\Models\Upload\UploadPortalCustomer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

/**
 * Inbound sync when Quickqore records a receive payment against pizza-imported invoices.
 * Authenticate with the same X-Security-Key used for outbound Quickqore calls.
 */
class CustomerPaymentImportController extends Controller
{
    private const PAYMENT_TYPE_MAP = [
        'cash' => 'cash',
        'check' => 'check',
        'mo' => 'cash',
        'wire transfer' => 'echeck_ach',
        'credit_debit' => 'credit_debit',
        'echeck_ach' => 'echeck_ach',
        'customer_credit' => 'customer_credit',
    ];

    public function store(Request $request): JsonResponse
    {
        $request->merge([
            'qq_id' => $request->input('qq_id', $request->input('qq_payment_id')),
        ]);

        $validator = Validator::make($request->all(), [
            'qq_id' => 'required|integer|min:1',
            'customer_name' => 'nullable|string|max:255',
            'customer_qq_id' => 'nullable|integer|min:1',
            'store_no' => 'nullable|string|max:50',
            'date' => 'required|date',
            'amount' => 'required|numeric|min:0',
            'payment_type' => 'nullable|string|max:64',
            'cheque' => 'nullable|string|max:255',
            'memo' => 'nullable|string|max:5000',
            'number' => 'nullable|string|max:255',
            'invoices' => 'required|array|min:1',
            'invoices.*.qq_id' => 'nullable|integer|min:1',
            'invoices.*.invoice_number' => 'required|string|max:64',
            'invoices.*.amount' => 'required|numeric|min:0',
            'invoices.*.discount' => 'nullable|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $validated = $validator->validated();
        $qqPaymentId = (int) $validated['qq_id'];

        $existing = CustomerPayment::query()->where('qq_payment_id', $qqPaymentId)->first();
        if ($existing) {
            return response()->json([
                'success' => true,
                'skipped' => true,
                'message' => 'Customer payment already exists',
                'customer_payment' => $existing->load(['customer:id,name', 'items.salesInvoice:id,invoice_number,qq_id']),
            ]);
        }

        try {
            DB::beginTransaction();

            $resolved = $this->resolveInvoiceAllocations($validated['invoices']);
            if ($resolved['missing'] !== []) {
                DB::rollBack();

                return response()->json([
                    'success' => false,
                    'message' => 'One or more invoices were not found',
                    'missing_invoices' => $resolved['missing'],
                ], 404);
            }

            $allocations = $resolved['allocations'];
            if ($allocations === []) {
                DB::rollBack();

                return response()->json([
                    'success' => false,
                    'message' => 'Payment must apply a positive amount to at least one invoice',
                ], 422);
            }

            $customerIds = array_unique(array_map(fn (array $row) => (int) $row['invoice']->customer_id, $allocations));
            if (count($customerIds) !== 1) {
                DB::rollBack();

                return response()->json([
                    'success' => false,
                    'message' => 'All invoices on a payment must belong to the same customer',
                ], 422);
            }

            $customerId = (int) $customerIds[0];
            $namedCustomer = $this->findCustomerFromPayload($validated);
            if ($namedCustomer && (int) $namedCustomer->id !== $customerId) {
                DB::rollBack();

                return response()->json([
                    'success' => false,
                    'message' => 'Invoices belong to a different customer than the payment payload',
                ], 422);
            }

            $lineTotal = 0.0;
            $totalDiscount = 0.0;
            $invoiceIds = [];
            $amountsThisPayment = [];
            foreach ($allocations as $row) {
                $invoiceId = (int) $row['invoice']->id;
                $applied = round((float) $row['amount'], 2);
                $discount = round((float) $row['discount'], 2);
                $lineTotal = round($lineTotal + $applied, 2);
                $totalDiscount = round($totalDiscount + $discount, 2);
                $invoiceIds[] = $invoiceId;
                $amountsThisPayment[$invoiceId] = round(($amountsThisPayment[$invoiceId] ?? 0) + $applied + $discount, 2);
            }

            $amountReceived = round(max(0, (float) $validated['amount'] - $totalDiscount), 2);
            $creditApplied = round(max(0, $lineTotal - $amountReceived), 2);
            $unappliedAmount = round(max(0, $amountReceived - $lineTotal), 2);
            $paymentType = $this->mapPaymentType($validated['payment_type'] ?? null, $amountReceived);

            $invoices = SalesInvoice::query()
                ->whereIn('id', $invoiceIds)
                ->whereNotIn('status', ['cancelled'])
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            if ($invoices->count() !== count(array_unique($invoiceIds))) {
                DB::rollBack();

                return response()->json([
                    'success' => false,
                    'message' => 'One or more invoices are invalid or cancelled',
                ], 422);
            }

            $paidSoFar = CustomerPaymentItem::query()
                ->whereIn('sales_invoice_id', $invoiceIds)
                ->selectRaw('sales_invoice_id, (SUM(amount_applied) + SUM(discount)) as s')
                ->groupBy('sales_invoice_id')
                ->pluck('s', 'sales_invoice_id');

            foreach ($amountsThisPayment as $invoiceId => $sumApply) {
                $invoice = $invoices->get($invoiceId);
                $already = round((float) ($paidSoFar[$invoiceId] ?? 0), 2);
                $balance = round(max(0, (float) $invoice->amount - $already), 2);
                if ($sumApply - $balance > 0.009) {
                    DB::rollBack();

                    return response()->json([
                        'success' => false,
                        'message' => "Amount for invoice {$invoice->invoice_number} exceeds balance due ({$balance}).",
                    ], 422);
                }
            }

            $payment = CustomerPayment::create([
                'qq_payment_id' => $qqPaymentId,
                'customer_id' => $customerId,
                'payment_date' => $validated['date'],
                'payment_type' => $paymentType,
                'total_amount' => $lineTotal,
                'amount_received' => $amountReceived,
                'credit_applied' => $creditApplied,
                'unapplied_amount' => $unappliedAmount,
                'remarks' => $this->buildRemarks($validated),
            ]);

            if ($creditApplied > 0.009) {
                $this->allocateCustomerCredit($payment, $customerId, $creditApplied);
            }

            foreach ($allocations as $row) {
                CustomerPaymentItem::create([
                    'customer_payment_id' => $payment->id,
                    'sales_invoice_id' => (int) $row['invoice']->id,
                    'amount_applied' => round((float) $row['amount'], 2),
                    'discount' => round((float) $row['discount'], 2),
                ]);
            }

            $this->syncInvoicePaidStatus($invoiceIds);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Customer payment created successfully',
                'customer_payment' => $payment->fresh()->load(['customer:id,name', 'items.salesInvoice:id,invoice_number,qq_id']),
            ]);
        } catch (\InvalidArgumentException $e) {
            if (DB::transactionLevel() > 0) {
                DB::rollBack();
            }

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        } catch (\Throwable $e) {
            if (DB::transactionLevel() > 0) {
                DB::rollBack();
            }
            info('Quickqore customer payment sync error: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to create customer payment',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function update(Request $request): JsonResponse
    {
        $request->merge([
            'qq_id' => $request->input('qq_id', $request->input('qq_payment_id')),
        ]);

        $validator = Validator::make($request->all(), [
            'qq_id' => 'required|integer|min:1',
            'customer_name' => 'nullable|string|max:255',
            'customer_qq_id' => 'nullable|integer|min:1',
            'store_no' => 'nullable|string|max:50',
            'date' => 'required|date',
            'amount' => 'required|numeric|min:0',
            'payment_type' => 'nullable|string|max:64',
            'cheque' => 'nullable|string|max:255',
            'memo' => 'nullable|string|max:5000',
            'number' => 'nullable|string|max:255',
            'invoices' => 'required|array|min:1',
            'invoices.*.qq_id' => 'nullable|integer|min:1',
            'invoices.*.invoice_number' => 'required|string|max:64',
            'invoices.*.amount' => 'required|numeric|min:0',
            'invoices.*.discount' => 'nullable|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $validated = $validator->validated();
        $qqPaymentId = (int) $validated['qq_id'];

        try {
            DB::beginTransaction();

            $payment = CustomerPayment::query()
                ->where('qq_payment_id', $qqPaymentId)
                ->lockForUpdate()
                ->first();

            if (!$payment) {
                DB::rollBack();

                return $this->store($request);
            }

            $resolved = $this->resolveInvoiceAllocations($validated['invoices']);
            if ($resolved['missing'] !== []) {
                DB::rollBack();

                return response()->json([
                    'success' => false,
                    'message' => 'One or more invoices were not found',
                    'missing_invoices' => $resolved['missing'],
                ], 404);
            }

            $allocations = $resolved['allocations'];
            if ($allocations === []) {
                DB::rollBack();

                return response()->json([
                    'success' => false,
                    'message' => 'Payment must apply a positive amount to at least one invoice',
                ], 422);
            }

            $customerIds = array_unique(array_map(fn (array $row) => (int) $row['invoice']->customer_id, $allocations));
            if (count($customerIds) !== 1) {
                DB::rollBack();

                return response()->json([
                    'success' => false,
                    'message' => 'All invoices on a payment must belong to the same customer',
                ], 422);
            }

            $customerId = (int) $customerIds[0];
            if ((int) $payment->customer_id !== $customerId) {
                DB::rollBack();

                return response()->json([
                    'success' => false,
                    'message' => 'Customer cannot be changed for an existing payment',
                ], 422);
            }

            $namedCustomer = $this->findCustomerFromPayload($validated);
            if ($namedCustomer && (int) $namedCustomer->id !== $customerId) {
                DB::rollBack();

                return response()->json([
                    'success' => false,
                    'message' => 'Invoices belong to a different customer than the payment payload',
                ], 422);
            }

            $lineTotal = 0.0;
            $totalDiscount = 0.0;
            $invoiceIds = [];
            $amountsThisPayment = [];
            foreach ($allocations as $row) {
                $invoiceId = (int) $row['invoice']->id;
                $applied = round((float) $row['amount'], 2);
                $discount = round((float) $row['discount'], 2);
                $lineTotal = round($lineTotal + $applied, 2);
                $totalDiscount = round($totalDiscount + $discount, 2);
                $invoiceIds[] = $invoiceId;
                $amountsThisPayment[$invoiceId] = round(($amountsThisPayment[$invoiceId] ?? 0) + $applied + $discount, 2);
            }

            $amountReceived = round(max(0, (float) $validated['amount'] - $totalDiscount), 2);
            $creditApplied = round(max(0, $lineTotal - $amountReceived), 2);
            $totalSurplus = round(max(0, $amountReceived - $lineTotal), 2);

            $consumedFromThis = round((float) CustomerPaymentCreditItem::query()
                ->where('source_customer_payment_id', $payment->id)
                ->sum('amount'), 2);
            if ($totalSurplus + 0.009 < $consumedFromThis) {
                DB::rollBack();

                return response()->json([
                    'success' => false,
                    'message' => 'Cannot reduce this payment below the credit already applied to other payments.',
                ], 422);
            }
            $unappliedAmount = round($totalSurplus - $consumedFromThis, 2);
            $paymentType = $this->mapPaymentType($validated['payment_type'] ?? null, $amountReceived);

            $oldInvoiceIds = CustomerPaymentItem::query()
                ->where('customer_payment_id', $payment->id)
                ->pluck('sales_invoice_id')
                ->map(fn ($id) => (int) $id)
                ->all();

            $this->reverseCreditApplications($payment);
            CustomerPaymentItem::query()->where('customer_payment_id', $payment->id)->delete();

            $invoices = SalesInvoice::query()
                ->whereIn('id', $invoiceIds)
                ->whereNotIn('status', ['cancelled'])
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            if ($invoices->count() !== count(array_unique($invoiceIds))) {
                DB::rollBack();

                return response()->json([
                    'success' => false,
                    'message' => 'One or more invoices are invalid or cancelled',
                ], 422);
            }

            $paidSoFar = CustomerPaymentItem::query()
                ->whereIn('sales_invoice_id', $invoiceIds)
                ->where('customer_payment_id', '!=', $payment->id)
                ->selectRaw('sales_invoice_id, (SUM(amount_applied) + SUM(discount)) as s')
                ->groupBy('sales_invoice_id')
                ->pluck('s', 'sales_invoice_id');

            foreach ($amountsThisPayment as $invoiceId => $sumApply) {
                $invoice = $invoices->get($invoiceId);
                $already = round((float) ($paidSoFar[$invoiceId] ?? 0), 2);
                $balance = round(max(0, (float) $invoice->amount - $already), 2);
                if ($sumApply - $balance > 0.009) {
                    DB::rollBack();

                    return response()->json([
                        'success' => false,
                        'message' => "Amount for invoice {$invoice->invoice_number} exceeds balance due ({$balance}).",
                    ], 422);
                }
            }

            $payment->update([
                'payment_date' => $validated['date'],
                'payment_type' => $paymentType,
                'total_amount' => $lineTotal,
                'amount_received' => $amountReceived,
                'credit_applied' => $creditApplied,
                'unapplied_amount' => $unappliedAmount,
                'remarks' => $this->buildRemarks($validated),
            ]);

            if ($creditApplied > 0.009) {
                $this->allocateCustomerCredit($payment, $customerId, $creditApplied);
            }

            foreach ($allocations as $row) {
                CustomerPaymentItem::create([
                    'customer_payment_id' => $payment->id,
                    'sales_invoice_id' => (int) $row['invoice']->id,
                    'amount_applied' => round((float) $row['amount'], 2),
                    'discount' => round((float) $row['discount'], 2),
                ]);
            }

            $this->syncInvoicePaidStatus(array_values(array_unique(array_merge($oldInvoiceIds, $invoiceIds))));

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Customer payment updated successfully',
                'customer_payment' => $payment->fresh()->load(['customer:id,name', 'items.salesInvoice:id,invoice_number,qq_id']),
            ]);
        } catch (\InvalidArgumentException $e) {
            if (DB::transactionLevel() > 0) {
                DB::rollBack();
            }

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        } catch (\Throwable $e) {
            if (DB::transactionLevel() > 0) {
                DB::rollBack();
            }
            info('Quickqore customer payment update error: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to update customer payment',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function destroy(Request $request): JsonResponse
    {
        $qqIds = $request->input('qq_ids');
        if (!is_array($qqIds) && $request->filled('qq_id')) {
            $qqIds = [$request->input('qq_id')];
        }
        if (!is_array($qqIds) && $request->filled('qq_payment_id')) {
            $qqIds = [$request->input('qq_payment_id')];
        }
        $request->merge(['qq_ids' => $qqIds]);

        $validator = Validator::make($request->all(), [
            'qq_ids' => 'required|array|min:1',
            'qq_ids.*' => 'required|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $qqIds = array_values(array_unique(array_map('intval', $validator->validated()['qq_ids'])));

        $payments = CustomerPayment::query()
            ->whereIn('qq_payment_id', $qqIds)
            ->get()
            ->keyBy(fn (CustomerPayment $payment) => (int) $payment->qq_payment_id);

        $deleted = [];
        $skippedNotFound = [];
        $skippedHasCreditApplied = [];

        try {
            DB::beginTransaction();

            foreach ($qqIds as $qqId) {
                $payment = $payments->get($qqId);
                if (!$payment) {
                    $skippedNotFound[] = $qqId;
                    continue;
                }

                $payment = CustomerPayment::query()->lockForUpdate()->find($payment->id);
                if (!$payment) {
                    $skippedNotFound[] = $qqId;
                    continue;
                }

                if (CustomerPaymentCreditItem::query()->where('source_customer_payment_id', $payment->id)->exists()) {
                    $skippedHasCreditApplied[] = [
                        'qq_payment_id' => $qqId,
                        'id' => $payment->id,
                    ];
                    continue;
                }

                $invoiceIds = CustomerPaymentItem::query()
                    ->where('customer_payment_id', $payment->id)
                    ->pluck('sales_invoice_id')
                    ->map(fn ($id) => (int) $id)
                    ->all();

                $this->reverseCreditApplications($payment);
                $payment->delete();
                $this->syncInvoicePaidStatus($invoiceIds);
                $deleted[] = $qqId;
            }

            DB::commit();
        } catch (\Throwable $e) {
            if (DB::transactionLevel() > 0) {
                DB::rollBack();
            }
            info('Quickqore customer payment delete error: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete customer payment',
                'error' => $e->getMessage(),
            ], 500);
        }

        $blocked = count($skippedHasCreditApplied) > 0;
        $messageParts = [];
        if ($deleted !== []) {
            $messageParts[] = count($deleted).' customer payment(s) deleted';
        }
        if ($skippedNotFound !== []) {
            $messageParts[] = count($skippedNotFound).' payment(s) not found';
        }
        if ($blocked) {
            $messageParts[] = count($skippedHasCreditApplied).' payment(s) have credit applied to other payments and cannot be deleted';
        }

        return response()->json([
            'success' => !$blocked,
            'message' => $messageParts !== [] ? implode('. ', $messageParts) : 'No customer payments to delete',
            'deleted' => $deleted,
            'skipped_not_found' => $skippedNotFound,
            'skipped_has_credit_applied' => $skippedHasCreditApplied,
        ], $blocked ? 400 : 200);
    }

    /**
     * @param  list<array<string, mixed>>  $invoiceRows
     * @return array{allocations: list<array{invoice: SalesInvoice, amount: float, discount: float}>, missing: list<array<string, mixed>>}
     */
    private function resolveInvoiceAllocations(array $invoiceRows): array
    {
        $allocations = [];
        $missing = [];

        foreach ($invoiceRows as $row) {
            $amount = round((float) ($row['amount'] ?? 0), 2);
            $discount = round((float) ($row['discount'] ?? 0), 2);
            if ($amount <= 0 && $discount <= 0) {
                continue;
            }

            $invoice = $this->findSalesInvoice($row);
            if (!$invoice) {
                $missing[] = [
                    'qq_id' => $row['qq_id'] ?? null,
                    'invoice_number' => $row['invoice_number'] ?? null,
                ];
                continue;
            }

            $allocations[] = [
                'invoice' => $invoice,
                'amount' => $amount,
                'discount' => $discount,
            ];
        }

        return [
            'allocations' => $allocations,
            'missing' => $missing,
        ];
    }

    private function findSalesInvoice(array $row): ?SalesInvoice
    {
        $qqId = isset($row['qq_id']) ? (int) $row['qq_id'] : 0;
        $invoiceNumber = trim((string) ($row['invoice_number'] ?? ''));

        if ($qqId > 0) {
            $invoice = SalesInvoice::query()->where('qq_id', $qqId)->first();
            if ($invoice) {
                return $invoice;
            }
        }

        if ($invoiceNumber !== '') {
            return SalesInvoice::query()->where('invoice_number', $invoiceNumber)->first();
        }

        return null;
    }

    private function findCustomerFromPayload(array $validated): ?UploadPortalCustomer
    {
        $customerName = trim((string) ($validated['customer_name'] ?? ''));
        $storeNo = trim((string) ($validated['store_no'] ?? ''));
        if ($customerName === '' || $storeNo === '') {
            return null;
        }

        $names = [];
        foreach ($this->storeLookupKeys($storeNo) as $store) {
            $names[] = strtolower($this->customerMatchName($customerName, $store));
        }

        if ($names === []) {
            return null;
        }

        return UploadPortalCustomer::query()
            ->where(function ($query) use ($names) {
                foreach ($names as $name) {
                    $query->orWhereRaw('LOWER(TRIM(name)) = ?', [$name]);
                }
            })
            ->first();
    }

    private function mapPaymentType(?string $paymentType, float $amountReceived): string
    {
        if ($amountReceived <= 0) {
            return 'customer_credit';
        }

        $key = strtolower(trim((string) $paymentType));

        return self::PAYMENT_TYPE_MAP[$key] ?? 'cash';
    }

    private function buildRemarks(array $validated): ?string
    {
        $parts = [];
        $memo = trim((string) ($validated['memo'] ?? ''));
        $cheque = trim((string) ($validated['cheque'] ?? ''));
        $number = trim((string) ($validated['number'] ?? ''));

        if ($memo !== '') {
            $parts[] = $memo;
        }
        if ($cheque !== '') {
            $parts[] = 'Cheque: '.$cheque;
        }
        if ($number !== '') {
            $parts[] = 'QQ#: '.$number;
        }

        $remarks = implode(' | ', $parts);

        return $remarks !== '' ? $remarks : null;
    }

    private function allocateCustomerCredit(CustomerPayment $payment, int $customerId, float $amount): void
    {
        $remaining = round($amount, 2);

        $sources = CustomerPayment::query()
            ->where('customer_id', $customerId)
            ->where('unapplied_amount', '>', 0)
            ->where('id', '!=', $payment->id)
            ->orderBy('payment_date')
            ->orderBy('id')
            ->lockForUpdate()
            ->get();

        foreach ($sources as $source) {
            if ($remaining <= 0.009) {
                break;
            }

            $available = round((float) $source->unapplied_amount, 2);
            if ($available <= 0) {
                continue;
            }

            $take = round(min($available, $remaining), 2);
            if ($take <= 0) {
                continue;
            }

            CustomerPaymentCreditItem::create([
                'customer_payment_id' => $payment->id,
                'source_customer_payment_id' => $source->id,
                'amount' => $take,
            ]);

            $source->unapplied_amount = round($available - $take, 2);
            $source->save();

            $remaining = round($remaining - $take, 2);
        }

        if ($remaining > 0.009) {
            throw new \InvalidArgumentException('Could not allocate the requested customer credit.');
        }
    }

    private function reverseCreditApplications(CustomerPayment $payment): void
    {
        $creditItems = CustomerPaymentCreditItem::query()
            ->where('customer_payment_id', $payment->id)
            ->lockForUpdate()
            ->get();

        foreach ($creditItems as $item) {
            $source = CustomerPayment::query()->lockForUpdate()->find($item->source_customer_payment_id);
            if ($source) {
                $source->unapplied_amount = round((float) $source->unapplied_amount + (float) $item->amount, 2);
                $source->save();
            }
            $item->delete();
        }
    }

    /**
     * @param  array<int>  $invoiceIds
     */
    private function syncInvoicePaidStatus(array $invoiceIds): void
    {
        if ($invoiceIds === []) {
            return;
        }

        $invoices = SalesInvoice::query()->whereIn('id', $invoiceIds)->get();
        foreach ($invoices as $invoice) {
            $paid = (float) (CustomerPaymentItem::query()
                ->where('sales_invoice_id', $invoice->id)
                ->selectRaw('COALESCE(SUM(amount_applied) + SUM(discount), 0) as s')
                ->value('s') ?? 0);
            $balance = round(max(0, (float) $invoice->amount - $paid), 2);
            if ($balance <= 0.009 && $invoice->status !== 'cancelled') {
                if ($invoice->status !== 'paid') {
                    $invoice->status = 'paid';
                    $invoice->save();
                }
            } elseif ($balance > 0.009 && $invoice->status === 'paid') {
                $invoice->status = 'sent';
                $invoice->save();
            }
        }
    }

    private function customerMatchName(string $customerName, string $storeNo): string
    {
        $name = trim($customerName);
        $store = trim($storeNo);
        $suffix = ' - '.$store;

        if ($store !== '' && str_ends_with(strtolower($name), strtolower($suffix))) {
            return $name;
        }

        return $name.' - '.$store;
    }

    /**
     * @return list<string>
     */
    private function storeLookupKeys(string $storeNo): array
    {
        $key = preg_replace('/\s+/', '', trim($storeNo));
        if ($key === '') {
            return [];
        }

        $keys = [$key];
        $trimmed = ltrim($key, '0');
        if ($trimmed !== '' && $trimmed !== $key) {
            $keys[] = $trimmed;
        }

        return array_values(array_unique($keys));
    }
}
