<?php

namespace App\Http\Controllers\Upload;

use App\Http\Controllers\Controller;
use App\Models\AR\CustomerPayment;
use App\Models\AR\CustomerPaymentCreditItem;
use App\Models\AR\CustomerPaymentItem;
use App\Models\AR\SalesInvoice;
use App\Models\User;
use App\Services\Quickqore\QuickqoreInvoiceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class CustomerPaymentController extends Controller
{
    private const PAYMENT_TYPES = ['cash', 'check', 'credit_debit', 'echeck_ach', 'customer_credit'];

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

    public function index(Request $request)
    {
        $user = $this->getAuthenticatedUploadUser();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'User not authenticated'], 401);
        }

        if (!$this->ensurePermission($user, 'upload-portal-customer-payment.index')) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to view payments',
            ], 403);
        }

        $query = CustomerPayment::query()
            ->with(['customer:id,name', 'createdBy']);

        if ($customerId = $request->input('customer_id')) {
            $query->where('customer_id', $customerId);
        }

        if ($search = trim((string) $request->input('search'))) {
            $query->whereHas('customer', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%");
            });
        }

        $perPage = (int) $request->input('per_page', 25);
        $payments = $query->orderByDesc('id')->paginate($perPage);

        return response()->json([
            'success' => true,
            'payments' => $payments,
        ]);
    }

    public function customerCreditBalance(Request $request, $customerId)
    {
        $user = $this->getAuthenticatedUploadUser();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'User not authenticated'], 401);
        }

        if (
            ! $this->ensurePermission($user, 'upload-portal-customer-payment.add')
            && ! $this->ensurePermission($user, 'upload-portal-customer-payment.edit')
            && ! $this->ensurePermission($user, 'upload-portal-customer-payment.view')
        ) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to view customer credit',
            ], 403);
        }

        $excludePaymentId = $request->input('payment_id') ? (int) $request->input('payment_id') : null;
        $balance = self::computeCustomerCreditBalance((int) $customerId, $excludePaymentId);

        if ($excludePaymentId) {
            $payment = CustomerPayment::query()->find($excludePaymentId);
            if ($payment && (int) $payment->customer_id === (int) $customerId) {
                $balance = round($balance + (float) $payment->credit_applied, 2);
            }
        }

        return response()->json([
            'success' => true,
            'credit_balance' => $balance,
        ]);
    }

    public function show($id)
    {
        $user = $this->getAuthenticatedUploadUser();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'User not authenticated'], 401);
        }

        if (!$this->ensurePermission($user, 'upload-portal-customer-payment.view')) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to view this payment',
            ], 403);
        }

        $payment = CustomerPayment::with([
            'customer:id,name,email,mobile',
            'items.salesInvoice:id,customer_id,invoice_number,invoice_date,amount,status',
            'creditItems.sourcePayment:id,payment_date,amount_received,unapplied_amount',
            'createdBy',
            'updatedBy',
        ])->find($id);

        if (!$payment) {
            return response()->json(['success' => false, 'message' => 'Payment not found'], 404);
        }

        return response()->json([
            'success' => true,
            'payment' => $payment,
        ]);
    }

    public function store(Request $request)
    {
        $user = $this->getAuthenticatedUploadUser();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'User not authenticated'], 401);
        }

        if (!$this->ensurePermission($user, 'upload-portal-customer-payment.add')) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to record payments',
            ], 403);
        }

        $validated = $this->validatePaymentPayload($request);

        try {
            DB::beginTransaction();

            $result = $this->persistPayment($validated, null, $user->id);
            $this->syncPaymentToQuickqore($result);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Payment recorded successfully',
                'payment' => $result->load(['customer:id,name', 'items.salesInvoice:id,invoice_number', 'creditItems', 'createdBy']),
            ]);
        } catch (\InvalidArgumentException $e) {
            if (DB::transactionLevel() > 0) {
                DB::rollBack();
            }

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        } catch (\Throwable $th) {
            if (DB::transactionLevel() > 0) {
                DB::rollBack();
            }

            return response()->json([
                'success' => false,
                'message' => 'Failed to record payment',
                'error' => $th->getMessage(),
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        $user = $this->getAuthenticatedUploadUser();
        if (! $user) {
            return response()->json(['success' => false, 'message' => 'User not authenticated'], 401);
        }

        if (! $this->ensurePermission($user, 'upload-portal-customer-payment.edit')) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to edit payments',
            ], 403);
        }

        $validated = $this->validatePaymentPayload($request);

        try {
            DB::beginTransaction();

            $payment = CustomerPayment::query()->lockForUpdate()->find($id);
            if (! $payment) {
                DB::rollBack();

                return response()->json(['success' => false, 'message' => 'Payment not found'], 404);
            }

            if ((int) $validated['customer_id'] !== (int) $payment->customer_id) {
                DB::rollBack();

                return response()->json([
                    'success' => false,
                    'message' => 'Customer cannot be changed for an existing payment.',
                ], 422);
            }

            $oldInvoiceIds = CustomerPaymentItem::query()
                ->where('customer_payment_id', $payment->id)
                ->pluck('sales_invoice_id')
                ->map(fn ($v) => (int) $v)
                ->all();

            $this->reverseCreditApplications($payment);

            $result = $this->persistPayment($validated, $payment, $user->id);

            $syncIds = array_values(array_unique(array_merge($oldInvoiceIds, collect($validated['items'])->pluck('sales_invoice_id')->map(fn ($v) => (int) $v)->all())));

            $this->syncInvoicePaidStatus($syncIds);
            $this->syncPaymentToQuickqore($result);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Payment updated successfully',
                'payment' => $result->fresh()->load([
                    'customer:id,name',
                    'items.salesInvoice:id,invoice_number',
                    'creditItems',
                    'createdBy',
                    'updatedBy',
                ]),
            ]);
        } catch (\InvalidArgumentException $e) {
            if (DB::transactionLevel() > 0) {
                DB::rollBack();
            }

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        } catch (\Throwable $th) {
            if (DB::transactionLevel() > 0) {
                DB::rollBack();
            }

            return response()->json([
                'success' => false,
                'message' => 'Failed to update payment',
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

        if (!$this->ensurePermission($user, 'upload-portal-customer-payment.delete')) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to delete payments',
            ], 403);
        }

        try {
            DB::beginTransaction();

            $payment = CustomerPayment::query()->lockForUpdate()->find($id);
            if (!$payment) {
                DB::rollBack();

                return response()->json(['success' => false, 'message' => 'Payment not found'], 404);
            }

            if (CustomerPaymentCreditItem::query()->where('source_customer_payment_id', $payment->id)->exists()) {
                DB::rollBack();

                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete this payment because its credit has been applied to other payments.',
                ], 422);
            }

            $invoiceIds = CustomerPaymentItem::query()
                ->where('customer_payment_id', $payment->id)
                ->pluck('sales_invoice_id')
                ->map(fn ($v) => (int) $v)
                ->all();

            $this->reverseCreditApplications($payment);

            $payment->loadMissing(['customer:id,name']);
            $payment->delete();

            $this->syncInvoicePaidStatus($invoiceIds);
            $this->deletePaymentFromQuickqore($payment);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Payment deleted successfully',
            ]);
        } catch (\InvalidArgumentException $e) {
            if (DB::transactionLevel() > 0) {
                DB::rollBack();
            }

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        } catch (\Throwable $th) {
            if (DB::transactionLevel() > 0) {
                DB::rollBack();
            }

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete payment',
                'error' => $th->getMessage(),
            ], 500);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function validatePaymentPayload(Request $request): array
    {
        $validated = $request->validate([
            'customer_id' => ['required', Rule::exists('upload_portal_customers', 'id')->whereNull('deleted_at')],
            'payment_date' => 'required|date',
            'payment_type' => ['nullable', Rule::in(self::PAYMENT_TYPES)],
            'amount_received' => 'required|numeric|min:0',
            'credit_applied' => 'nullable|numeric|min:0',
            'remarks' => 'nullable|string|max:5000',
            'items' => 'present|array',
            'items.*.sales_invoice_id' => ['required', Rule::exists('sales_invoices', 'id')],
            'items.*.amount_applied' => 'required|numeric|min:0',
            'items.*.discount' => 'required|numeric|min:0',
        ]);

        $validated['credit_applied'] = round((float) ($validated['credit_applied'] ?? 0), 2);
        $validated['amount_received'] = round((float) $validated['amount_received'], 2);

        $itemsInput = array_values(array_filter($validated['items'], function (array $row) {
            return round((float) $row['amount_applied'] + (float) $row['discount'], 2) > 0;
        }));

        if ($itemsInput === [] && $validated['amount_received'] <= 0 && $validated['credit_applied'] <= 0) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'items' => ['Enter at least one invoice amount, amount received, or credit to apply.'],
            ]);
        }

        $validated['items'] = $itemsInput;

        $amountReceived = $validated['amount_received'];
        $creditApplied = $validated['credit_applied'];

        if ($amountReceived > 0 && empty($validated['payment_type'])) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'payment_type' => ['Payment type is required when amount received is greater than zero.'],
            ]);
        }

        if ($amountReceived <= 0 && $creditApplied <= 0) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'amount_received' => ['Amount received or credit applied must be greater than zero.'],
            ]);
        }

        if ($amountReceived <= 0) {
            $validated['payment_type'] = 'customer_credit';
        }

        return $validated;
    }

    private function persistPayment(array $validated, ?CustomerPayment $existing, int $userId): CustomerPayment
    {
        $itemsInput = $validated['items'];
        $invoiceIds = collect($itemsInput)->pluck('sales_invoice_id')->unique()->values()->all();
        $amountReceived = $validated['amount_received'];
        $creditApplied = $validated['credit_applied'];

        $amountsThisPayment = [];
        foreach ($itemsInput as $row) {
            $invId = (int) $row['sales_invoice_id'];
            $amountsThisPayment[$invId] = round(
                ($amountsThisPayment[$invId] ?? 0) + (float) $row['amount_applied'] + (float) $row['discount'],
                2
            );
        }

        $lineTotal = 0;
        foreach ($itemsInput as $row) {
            $lineTotal = round($lineTotal + (float) $row['amount_applied'], 2);
        }

        if ($lineTotal - ($amountReceived + $creditApplied) > 0.009) {
            throw new \InvalidArgumentException('Amount received plus credit applied cannot be less than the total applied to invoices.');
        }

        if ($lineTotal <= 0 && $creditApplied > 0) {
            throw new \InvalidArgumentException('Customer credit can only be applied toward invoices.');
        }

        $totalSurplus = round($amountReceived - max(0, $lineTotal - $creditApplied), 2);
        if ($totalSurplus < -0.009) {
            throw new \InvalidArgumentException('Amount received is not enough to cover the cash portion of this payment.');
        }
        $totalSurplus = max(0, $totalSurplus);
        $unappliedAmount = $totalSurplus;

        if ($creditApplied > 0) {
            $availableCredit = self::computeCustomerCreditBalance(
                (int) $validated['customer_id'],
                $existing?->id
            );
            if ($existing) {
                $availableCredit = round($availableCredit + (float) $existing->credit_applied, 2);
            }
            if ($creditApplied - $availableCredit > 0.009) {
                throw new \InvalidArgumentException("Customer only has {$availableCredit} in available credit.");
            }
        }

        if ($invoiceIds !== []) {
            $invoices = SalesInvoice::query()
                ->whereIn('id', $invoiceIds)
                ->where('customer_id', $validated['customer_id'])
                ->when($existing === null, function ($q) {
                    $q->whereNotIn('status', ['paid', 'cancelled']);
                }, function ($q) use ($existing) {
                    $q->where(function ($q2) use ($existing) {
                        $q2->whereNotIn('status', ['cancelled'])
                            ->where(function ($q3) use ($existing) {
                                $q3->whereNotIn('status', ['paid'])
                                    ->orWhereExists(function ($sub) use ($existing) {
                                        $sub->selectRaw('1')
                                            ->from('customer_payment_items as cpi')
                                            ->whereColumn('cpi.sales_invoice_id', 'sales_invoices.id')
                                            ->where('cpi.customer_payment_id', $existing->id);
                                    });
                            });
                    });
                })
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            if ($invoices->count() !== count($invoiceIds)) {
                throw new \InvalidArgumentException('One or more invoices are invalid, not for this customer, or already closed.');
            }

            $this->assertInvoicesNotMixingQuickqoreLink($invoices);

            $paidSoFar = $this->sumPaidPerInvoice($invoiceIds, $existing?->id);

            foreach ($amountsThisPayment as $invId => $sumApply) {
                $invoice = $invoices->get($invId);
                $already = round((float) ($paidSoFar[$invId] ?? 0), 2);
                $balance = round(max(0, (float) $invoice->amount - $already), 2);
                if ($sumApply - $balance > 0.009) {
                    throw new \InvalidArgumentException("Amount for invoice {$invoice->invoice_number} exceeds balance due ({$balance}).");
                }
            }
        }

        if ($existing !== null) {
            $consumedFromThis = round((float) CustomerPaymentCreditItem::query()
                ->where('source_customer_payment_id', $existing->id)
                ->sum('amount'), 2);
            if ($totalSurplus + 0.009 < $consumedFromThis) {
                throw new \InvalidArgumentException('Cannot reduce this payment below the credit already applied to other payments.');
            }
            $unappliedAmount = round($totalSurplus - $consumedFromThis, 2);

            CustomerPaymentItem::query()->where('customer_payment_id', $existing->id)->delete();
            CustomerPaymentCreditItem::query()->where('customer_payment_id', $existing->id)->delete();

            $existing->update([
                'payment_date' => $validated['payment_date'],
                'payment_type' => $validated['payment_type'],
                'total_amount' => $lineTotal,
                'amount_received' => $amountReceived,
                'credit_applied' => $creditApplied,
                'unapplied_amount' => $unappliedAmount,
                'remarks' => $validated['remarks'] ?? null,
                'updated_by' => $userId,
            ]);
            $payment = $existing;
        } else {
            $payment = CustomerPayment::create([
                'customer_id' => $validated['customer_id'],
                'payment_date' => $validated['payment_date'],
                'payment_type' => $validated['payment_type'],
                'total_amount' => $lineTotal,
                'amount_received' => $amountReceived,
                'credit_applied' => $creditApplied,
                'unapplied_amount' => $unappliedAmount,
                'remarks' => $validated['remarks'] ?? null,
                'created_by' => $userId,
                'updated_by' => $userId,
            ]);
        }

        foreach ($itemsInput as $row) {
            CustomerPaymentItem::create([
                'customer_payment_id' => $payment->id,
                'sales_invoice_id' => (int) $row['sales_invoice_id'],
                'amount_applied' => round((float) $row['amount_applied'], 2),
                'discount' => round((float) $row['discount'], 2),
            ]);
        }

        if ($creditApplied > 0) {
            $this->allocateCustomerCredit($payment, (int) $validated['customer_id'], $creditApplied);
        }

        if ($existing === null) {
            $this->syncInvoicePaidStatus($invoiceIds);
        }

        return $payment;
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

    private function allocateCustomerCredit(CustomerPayment $payment, int $customerId, float $amount): void
    {
        $remaining = round($amount, 2);

        $sources = CustomerPayment::query()
            ->where('customer_id', $customerId)
            ->where('unapplied_amount', '>', 0)
            ->when($payment->id, fn ($q) => $q->where('id', '!=', $payment->id))
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

    public static function computeCustomerCreditBalance(int $customerId, ?int $excludePaymentId = null): float
    {
        return round((float) CustomerPayment::query()
            ->where('customer_id', $customerId)
            ->when($excludePaymentId !== null, fn ($q) => $q->where('id', '!=', $excludePaymentId))
            ->sum('unapplied_amount'), 2);
    }

    /**
     * @param  array<int>  $invoiceIds
     * @return \Illuminate\Support\Collection<string|int, float>
     */
    private function sumPaidPerInvoice(array $invoiceIds, ?int $excludePaymentId = null)
    {
        return CustomerPaymentItem::query()
            ->whereIn('sales_invoice_id', $invoiceIds)
            ->when($excludePaymentId !== null, function ($q) use ($excludePaymentId) {
                $q->where('customer_payment_id', '!=', $excludePaymentId);
            })
            ->selectRaw('sales_invoice_id, (SUM(amount_applied) + SUM(discount)) as s')
            ->groupBy('sales_invoice_id')
            ->pluck('s', 'sales_invoice_id');
    }

    /**
     * Quickqore-linked invoices cannot share a payment with invoices that have no qq_id.
     *
     * @param  \Illuminate\Support\Collection<int, SalesInvoice>  $invoices
     */
    private function assertInvoicesNotMixingQuickqoreLink($invoices): void
    {
        $hasLinked = false;
        $hasUnlinked = false;

        foreach ($invoices as $invoice) {
            if ((int) ($invoice->qq_id ?? 0) > 0) {
                $hasLinked = true;
            } else {
                $hasUnlinked = true;
            }

            if ($hasLinked && $hasUnlinked) {
                throw new \InvalidArgumentException(
                    'You cannot add a Quickqore-linked invoice together with an invoice that is not linked to Quickqore.'
                );
            }
        }
    }

    private function syncPaymentToQuickqore(CustomerPayment $payment): void
    {
        $payment->loadMissing(['items.salesInvoice', 'customer:id,name,qq_company_id']);

        $pizzaItems = $payment->items->filter(function ($item) {
            return $item->salesInvoice && $item->salesInvoice->qq_id;
        });

        if ($pizzaItems->isEmpty() && ! $payment->qq_payment_id) {
            return;
        }

        $storeNo = $this->storeNoFromCustomerName($payment->customer?->name);
        if ($storeNo === null || $storeNo === '') {
            throw new \InvalidArgumentException('Unable to determine store number for Quickqore payment sync.');
        }

        $invoices = $pizzaItems->map(function ($item) {
            return [
                'qq_id' => (int) $item->salesInvoice->qq_id,
                'invoice_number' => $item->salesInvoice->invoice_number,
                'amount' => round((float) $item->amount_applied, 2),
                'discount' => round((float) $item->discount, 2),
            ];
        })->filter(function ($row) {
            return $row['amount'] > 0 || $row['discount'] > 0;
        })->values()->all();

        $totalDiscount = round(collect($invoices)->sum('discount'), 2);

        $payload = [
            'tdpus_id' => $payment->id,
            'qq_payment_id' => $payment->qq_payment_id ? (int) $payment->qq_payment_id : null,
            'qq_company_id' => $payment->customer?->qq_company_id ? (int) $payment->customer->qq_company_id : null,
            'store_no' => $storeNo,
            'date' => $payment->payment_date?->format('Y-m-d') ?? $payment->payment_date,
            'amount' => round((float) $payment->amount_received + $totalDiscount, 2),
            'discount' => $totalDiscount,
            'payment_type' => $this->quickqorePaymentType($payment->payment_type),
            'memo' => $payment->remarks,
            'invoices' => $invoices,
        ];

        $service = new QuickqoreInvoiceService();
        try {
            if ($payment->qq_payment_id) {
                try {
                    $service->updateArPayment($payload);
                } catch (\RuntimeException $e) {
                    if (! str_contains(strtolower($e->getMessage()), 'not found')) {
                        throw $e;
                    }
                    $response = $service->storeArPayment($payload);
                    $this->rememberQuickqorePaymentId($payment, $response);
                }

                return;
            }

            $response = $service->storeArPayment($payload);
            $this->rememberQuickqorePaymentId($payment, $response);
        } catch (\RuntimeException $e) {
            throw new \InvalidArgumentException($e->getMessage());
        }
    }

    private function deletePaymentFromQuickqore(CustomerPayment $payment): void
    {
        if (! $payment->qq_payment_id) {
            return;
        }

        $payment->loadMissing('customer:id,name,qq_company_id');

        $storeNo = $this->storeNoFromCustomerName($payment->customer?->name);
        if ($storeNo === null || $storeNo === '') {
            throw new \InvalidArgumentException('Unable to determine store number for Quickqore payment delete.');
        }

        try {
            (new QuickqoreInvoiceService())->deleteArPayment([
                'tdpus_id' => $payment->id,
                'qq_payment_id' => (int) $payment->qq_payment_id,
                'qq_company_id' => $payment->customer?->qq_company_id ? (int) $payment->customer->qq_company_id : null,
                'store_no' => $storeNo,
            ]);
        } catch (\RuntimeException $e) {
            if (str_contains(strtolower($e->getMessage()), 'not found')) {
                return;
            }

            throw new \InvalidArgumentException($e->getMessage());
        }
    }

    private function rememberQuickqorePaymentId(CustomerPayment $payment, array $response): void
    {
        $qqId = isset($response['id']) ? (int) $response['id'] : 0;
        if ($qqId <= 0) {
            throw new \InvalidArgumentException('Quickqore did not return a payment id.');
        }

        $payment->qq_payment_id = $qqId;
        $payment->save();
    }

    private function storeNoFromCustomerName(?string $name): ?string
    {
        $name = trim((string) $name);
        if ($name === '') {
            return null;
        }

        if (preg_match('/\s-\s*(\d+)\s*$/', $name, $matches)) {
            return $matches[1];
        }

        return null;
    }

    private function quickqorePaymentType(?string $type): string
    {
        $map = [
            'cash' => 'Cash',
            'check' => 'Check',
            'echeck_ach' => 'Wire Transfer',
            'credit_debit' => 'Cash',
            'customer_credit' => 'Cash',
        ];

        $key = strtolower(trim((string) $type));

        return $map[$key] ?? 'Cash';
    }

    /**
     * @param  array<int>  $invoiceIds
     */
    private function syncInvoicePaidStatus(array $invoiceIds): void
    {
        $invoices = SalesInvoice::query()->whereIn('id', $invoiceIds)->get();
        foreach ($invoices as $invoice) {
            $paid = (float) (CustomerPaymentItem::query()
                ->where('sales_invoice_id', $invoice->id)
                ->selectRaw('COALESCE(SUM(amount_applied) + SUM(discount), 0) as s')
                ->value('s') ?? 0);
            $balance = round(max(0, (float) $invoice->amount - $paid), 2);
            if ($balance <= 0.009 && ! in_array($invoice->status, ['cancelled'], true)) {
                if ($invoice->status !== 'paid') {
                    $invoice->status = 'paid';
                    $invoice->updated_by = Auth::guard('upload-portal')->id();
                    $invoice->save();
                }
            } elseif ($balance > 0.009 && $invoice->status === 'paid') {
                $invoice->status = 'sent';
                $invoice->updated_by = Auth::guard('upload-portal')->id();
                $invoice->save();
            }
        }
    }
}
