<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AR\SalesInvoice;
use App\Models\AR\SalesInvoiceItem;
use App\Models\Upload\UploadPortalCustomer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

/**
 * Inbound sync when Quickqore creates a sales invoice (pizza order sheet import).
 * Authenticate with the same X-Security-Key used for outbound Quickqore calls.
 *
 * Customer names in TDPUS are stored as "{name} - {store_no}"
 * e.g. payload customer_name "Shining Light" + store_no "700" → "Shining Light - 700".
 */
class SalesInvoiceImportController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'qq_id' => 'nullable|integer|min:1',
            'customer_name' => 'required|string|max:255',
            'store_no' => 'required|string|max:50',
            'invoice_number' => 'required|string|max:64',
            'date' => 'required|date',
            'due_date' => 'nullable|date',
            'amount' => 'required|numeric|min:0',
            'customer' => 'nullable|array',
            'customer.qq_id' => 'nullable|integer|min:1',
            'customer.qq_company_id' => 'nullable|integer|min:1',
            'customer.is_new' => 'nullable|boolean',
            'customer.name' => 'nullable|string|max:255',
            'customer.name1' => 'nullable|string|max:255',
            'customer.name2' => 'nullable|string|max:255',
            'customer.primary_person' => 'nullable|string|max:255',
            'customer.phone' => 'nullable|string|max:50',
            'customer.address' => 'nullable|string|max:255',
            'customer.city' => 'nullable|string|max:255',
            'customer.state' => 'nullable|string|max:255',
            'customer.zip' => 'nullable|string|max:32',
            'customer.country' => 'nullable|string|max:255',
            'customer.invoice_day' => 'nullable|integer|min:0|max:366',
            'customer.invoice_condition' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $validated = $validator->validated();
        $invoiceNumber = trim((string) $validated['invoice_number']);
        $qqId = isset($validated['qq_id']) ? (int) $validated['qq_id'] : null;

        $existing = SalesInvoice::query()
            ->where('invoice_number', $invoiceNumber)
            ->when($qqId, fn ($query) => $query->orWhere('qq_id', $qqId))
            ->first();

        if ($existing) {
            return response()->json([
                'success' => true,
                'skipped' => true,
                'message' => 'Sales invoice already exists',
                'sales_invoice' => $existing->load('customer:id,name'),
            ]);
        }

        $amount = round((float) $validated['amount'], 2);
        $customerCreated = false;

        try {
            DB::beginTransaction();

            $customer = $this->findCustomerByNameAndStore(
                (string) $validated['customer_name'],
                (string) $validated['store_no']
            );

            if (!$customer) {
                $customer = $this->createCustomerFromPayload($validated);
                $customerCreated = true;
            } else {
                $this->backfillCustomerQuickqoreIds($customer, $validated['customer'] ?? []);
            }

            $invoice = SalesInvoice::create([
                'qq_id' => $qqId,
                'customer_id' => $customer->id,
                'invoice_number' => $invoiceNumber,
                'invoice_date' => $validated['date'],
                'due_date' => $validated['due_date'] ?? null,
                'subtotal' => $amount,
                'discount_total' => 0,
                'tax_total' => 0,
                'amount' => $amount,
                'status' => 'sent',
            ]);

            SalesInvoiceItem::create([
                'sales_invoice_id' => $invoice->id,
                'item_name' => 'Pizza Order',
                'qty' => 1,
                'unit_price' => $amount,
                'discount' => 0,
                'tax_amount' => 0,
                'total' => $amount,
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => $customerCreated
                    ? 'Customer and sales invoice created successfully'
                    : 'Sales invoice created successfully',
                'customer_created' => $customerCreated,
                'sales_invoice' => $invoice->fresh()->load(['items', 'customer:id,name']),
            ]);
        } catch (\Throwable $e) {
            if (DB::transactionLevel() > 0) {
                DB::rollBack();
            }
            info('Quickqore sales invoice sync error: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to create sales invoice',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function update(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'qq_id' => 'required|integer|min:1',
            'customer_name' => 'required|string|max:255',
            'store_no' => 'nullable|string|max:50',
            'invoice_number' => 'required|string|max:64',
            'date' => 'required|date',
            'due_date' => 'nullable|date',
            'amount' => 'required|numeric|min:0',
            'status' => 'nullable|string|max:64',
            'customer' => 'nullable|array',
            'customer.qq_id' => 'nullable|integer|min:1',
            'customer.qq_company_id' => 'nullable|integer|min:1',
            'customer.is_new' => 'nullable|boolean',
            'customer.name' => 'nullable|string|max:255',
            'customer.name1' => 'nullable|string|max:255',
            'customer.name2' => 'nullable|string|max:255',
            'customer.primary_person' => 'nullable|string|max:255',
            'customer.phone' => 'nullable|string|max:50',
            'customer.address' => 'nullable|string|max:255',
            'customer.city' => 'nullable|string|max:255',
            'customer.state' => 'nullable|string|max:255',
            'customer.zip' => 'nullable|string|max:32',
            'customer.country' => 'nullable|string|max:255',
            'customer.invoice_day' => 'nullable|integer|min:0|max:366',
            'customer.invoice_condition' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $validated = $validator->validated();
        $qqId = (int) $validated['qq_id'];
        $invoiceNumber = trim((string) $validated['invoice_number']);
        $amount = round((float) $validated['amount'], 2);

        $invoice = SalesInvoice::query()->where('qq_id', $qqId)->first();
        if (!$invoice) {
            return response()->json([
                'success' => false,
                'message' => 'Sales invoice not found for qq_id: '.$qqId,
            ], 404);
        }

        $duplicateNumber = SalesInvoice::query()
            ->where('invoice_number', $invoiceNumber)
            ->where('id', '!=', $invoice->id)
            ->exists();
        if ($duplicateNumber) {
            return response()->json([
                'success' => false,
                'message' => 'Invoice number already exists: '.$invoiceNumber,
            ], 422);
        }

        $customerCreated = false;

        try {
            DB::beginTransaction();

            $customer = $this->resolveCustomerForUpdate($validated, $invoice);
            if (!$customer) {
                $customer = $this->createCustomerFromPayload($validated);
                $customerCreated = true;
            } else {
                $this->backfillCustomerQuickqoreIds($customer, $validated['customer'] ?? []);
            }

            $paidAmount = (float) $invoice->paymentItems()->sum(DB::raw('amount_applied + discount'));
            $hasPayments = $paidAmount > 0.005;
            $amountChanged = abs((float) $invoice->amount - $amount) > 0.01;
            $customerChanged = (int) $invoice->customer_id !== (int) $customer->id;

            if ($hasPayments && ($amountChanged || $customerChanged)) {
                DB::rollBack();

                return response()->json([
                    'success' => false,
                    'message' => 'Amount cannot be changed or customer cannot be changed after payment',
                ], 400);
            }

            $status = $this->mapQuickqoreStatus($validated['status'] ?? null) ?? $invoice->status;

            $invoice->fill([
                'customer_id' => $customer->id,
                'invoice_number' => $invoiceNumber,
                'invoice_date' => $validated['date'],
                'due_date' => $validated['due_date'] ?? null,
                'subtotal' => $amount,
                'discount_total' => 0,
                'tax_total' => 0,
                'amount' => $amount,
                'status' => $status,
            ])->save();

            SalesInvoiceItem::where('sales_invoice_id', $invoice->id)->delete();
            SalesInvoiceItem::create([
                'sales_invoice_id' => $invoice->id,
                'item_name' => 'Pizza Order',
                'qty' => 1,
                'unit_price' => $amount,
                'discount' => 0,
                'tax_amount' => 0,
                'total' => $amount,
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => $customerCreated
                    ? 'Customer created and sales invoice updated successfully'
                    : 'Sales invoice updated successfully',
                'customer_created' => $customerCreated,
                'sales_invoice' => $invoice->fresh()->load(['items', 'customer:id,name']),
            ]);
        } catch (\Throwable $e) {
            if (DB::transactionLevel() > 0) {
                DB::rollBack();
            }
            info('Quickqore sales invoice update error: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to update sales invoice',
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

        $invoices = SalesInvoice::query()
            ->whereIn('qq_id', $qqIds)
            ->withCount('paymentItems')
            ->get()
            ->keyBy(fn (SalesInvoice $invoice) => (int) $invoice->qq_id);

        $deleted = [];
        $skippedNotFound = [];
        $skippedHasPayments = [];

        try {
            DB::beginTransaction();

            foreach ($qqIds as $qqId) {
                $invoice = $invoices->get($qqId);
                if (!$invoice) {
                    $skippedNotFound[] = $qqId;
                    continue;
                }

                if ((int) $invoice->payment_items_count > 0) {
                    $skippedHasPayments[] = [
                        'qq_id' => $qqId,
                        'invoice_number' => $invoice->invoice_number,
                    ];
                    continue;
                }

                SalesInvoiceItem::where('sales_invoice_id', $invoice->id)->delete();
                $invoice->delete();
                $deleted[] = $qqId;
            }

            DB::commit();
        } catch (\Throwable $e) {
            if (DB::transactionLevel() > 0) {
                DB::rollBack();
            }
            info('Quickqore sales invoice delete error: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete sales invoice',
                'error' => $e->getMessage(),
            ], 500);
        }

        $blocked = count($skippedHasPayments) > 0;
        $messageParts = [];
        if ($deleted !== []) {
            $messageParts[] = count($deleted).' sales invoice(s) deleted';
        }
        if ($skippedNotFound !== []) {
            $messageParts[] = count($skippedNotFound).' invoice(s) not found';
        }
        if ($blocked) {
            $messageParts[] = count($skippedHasPayments).' invoice(s) have payments and cannot be deleted';
        }

        return response()->json([
            'success' => !$blocked,
            'message' => $messageParts !== [] ? implode('. ', $messageParts) : 'No sales invoices to delete',
            'deleted' => $deleted,
            'skipped_not_found' => $skippedNotFound,
            'skipped_has_payments' => $skippedHasPayments,
        ], $blocked ? 400 : 200);
    }

    private function resolveCustomerForUpdate(array $validated, SalesInvoice $invoice): ?UploadPortalCustomer
    {
        $storeNo = trim((string) ($validated['store_no'] ?? ''));
        $customerName = (string) $validated['customer_name'];

        if ($storeNo !== '') {
            return $this->findCustomerByNameAndStore($customerName, $storeNo);
        }

        $existing = $invoice->customer;
        if ($existing && strcasecmp(
            UploadPortalCustomer::stripInternalNameSuffix($existing->name),
            trim($customerName)
        ) === 0) {
            return $existing;
        }

        return UploadPortalCustomer::query()
            ->whereRaw('LOWER(TRIM(name)) = ?', [strtolower(trim($customerName))])
            ->first();
    }

    private function mapQuickqoreStatus(?string $status): ?string
    {
        $map = [
            'draft' => 'draft',
            'awaiting approval' => 'draft',
            'awaiting payment' => 'sent',
            'sent' => 'sent',
            'paid' => 'paid',
            'cancelled' => 'cancelled',
            'canceled' => 'cancelled',
        ];

        $key = strtolower(trim((string) $status));

        return $map[$key] ?? null;
    }

    private function findCustomerByNameAndStore(string $customerName, string $storeNo): ?UploadPortalCustomer
    {
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

    private function createCustomerFromPayload(array $validated): UploadPortalCustomer
    {
        $data = $validated['customer'] ?? [];
        $customerName = trim((string) ($data['name'] ?? $validated['customer_name']));
        $storeNo = (string) $validated['store_no'];
        $name = $this->customerMatchName($customerName, $storeNo);

        $primaryPerson = trim((string) ($data['primary_person'] ?? ''));
        if ($primaryPerson === '') {
            $name1 = trim((string) ($data['name1'] ?? ''));
            $name2 = trim((string) ($data['name2'] ?? ''));
            $primaryPerson = ($name2 !== '' && strcasecmp($name2, $name1) !== 0) ? $name2 : $name1;
        }

        $invoiceDay = (int) ($data['invoice_day'] ?? 30);
        if ($invoiceDay <= 0) {
            $invoiceDay = 30;
        }

        $invoiceCondition = trim((string) ($data['invoice_condition'] ?? 'of the following month'));
        $allowedConditions = [
            'of current month',
            'of the following month',
            'day(s) after the invoice date',
            'day(s) after the end of the invoice month',
        ];
        if (!in_array($invoiceCondition, $allowedConditions, true)) {
            $invoiceCondition = 'of the following month';
        }

        return UploadPortalCustomer::create([
            'qq_id' => isset($data['qq_id']) ? (int) $data['qq_id'] : null,
            'qq_company_id' => isset($data['qq_company_id']) ? (int) $data['qq_company_id'] : null,
            'name' => $name,
            'primary_person_name' => $primaryPerson !== '' ? $primaryPerson : null,
            'mobile' => $this->nullableString($data['phone'] ?? null),
            'address_line_1' => $this->nullableString($data['address'] ?? null),
            'city' => $this->nullableString($data['city'] ?? null),
            'state' => $this->nullableString($data['state'] ?? null),
            'zip_code' => $this->nullableString($data['zip'] ?? null),
            'country' => $this->nullableString($data['country'] ?? 'US') ?: 'US',
            'invoice_due' => $invoiceDay,
            'invoice_condition' => $invoiceCondition,
        ]);
    }

    private function backfillCustomerQuickqoreIds(UploadPortalCustomer $customer, array $data): void
    {
        $dirty = false;
        $qqId = isset($data['qq_id']) ? (int) $data['qq_id'] : 0;
        $qqCompanyId = isset($data['qq_company_id']) ? (int) $data['qq_company_id'] : 0;

        if ($qqId > 0 && ! $customer->qq_id) {
            $customer->qq_id = $qqId;
            $dirty = true;
        }
        if ($qqCompanyId > 0 && ! $customer->qq_company_id) {
            $customer->qq_company_id = $qqCompanyId;
            $dirty = true;
        }

        if ($dirty) {
            $customer->save();
        }
    }

    private function nullableString(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value !== '' ? $value : null;
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
