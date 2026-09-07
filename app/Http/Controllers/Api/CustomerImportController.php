<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Upload\UploadPortalCustomer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

/**
 * Inbound sync when Quickqore creates or updates a customer ledger.
 * Authenticate with the same X-Security-Key used for outbound Quickqore calls.
 *
 * TDPUS customer names are stored as "{name} - {store_no}".
 */
class CustomerImportController extends Controller
{
    private const INVOICE_CONDITIONS = [
        'of current month',
        'of the following month',
        'day(s) after the invoice date',
        'day(s) after the end of the invoice month',
    ];

    public function store(Request $request): JsonResponse
    {
        return $this->upsert($request, false);
    }

    public function update(Request $request): JsonResponse
    {
        return $this->upsert($request, true);
    }

    private function upsert(Request $request, bool $isUpdate): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'qq_id' => 'required|integer|min:1',
            'qq_company_id' => 'nullable|integer|min:1',
            'store_no' => 'required|string|max:50',
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'mobile' => 'nullable|string|max:50',
            'fax' => 'nullable|string|max:50',
            'primary_person_name' => 'nullable|string|max:255',
            'email_notes' => 'nullable|string',
            'address_line_1' => 'nullable|string|max:255',
            'address_line_2' => 'nullable|string|max:255',
            'address_line_3' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:255',
            'state' => 'nullable|string|max:255',
            'country' => 'nullable|string|max:255',
            'zip_code' => 'nullable|string|max:32',
            'bank_name' => 'nullable|string|max:255',
            'account_number' => 'nullable|string|max:255',
            'routing_number' => 'nullable|string|max:255',
            'invoice_due' => 'nullable|integer|min:0|max:366',
            'invoice_condition' => ['nullable', 'string', Rule::in(self::INVOICE_CONDITIONS)],
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
        $storeNo = trim((string) $validated['store_no']);
        $displayName = trim((string) $validated['name']);
        $fullName = $this->customerMatchName($displayName, $storeNo);

        $attributes = [
            'qq_id' => $qqId,
            'qq_company_id' => isset($validated['qq_company_id']) ? (int) $validated['qq_company_id'] : null,
            'name' => $fullName,
            'email' => $this->nullableString($validated['email'] ?? null),
            'mobile' => $this->nullableString($validated['mobile'] ?? null),
            'fax' => $this->nullableString($validated['fax'] ?? null),
            'primary_person_name' => $this->nullableString($validated['primary_person_name'] ?? null),
            'email_notes' => $this->nullableString($validated['email_notes'] ?? null),
            'address_line_1' => $this->nullableString($validated['address_line_1'] ?? null),
            'address_line_2' => $this->nullableString($validated['address_line_2'] ?? null),
            'address_line_3' => $this->nullableString($validated['address_line_3'] ?? null),
            'city' => $this->nullableString($validated['city'] ?? null),
            'state' => $this->nullableString($validated['state'] ?? null),
            'country' => $this->nullableString($validated['country'] ?? null),
            'zip_code' => $this->nullableString($validated['zip_code'] ?? null),
            'bank_name' => $this->nullableString($validated['bank_name'] ?? null),
            'account_number' => $this->nullableString($validated['account_number'] ?? null),
            'routing_number' => $this->nullableString($validated['routing_number'] ?? null),
            'invoice_due' => (int) ($validated['invoice_due'] ?? 0),
            'invoice_condition' => $validated['invoice_condition'] ?? null,
        ];

        try {
            $customer = UploadPortalCustomer::query()->where('qq_id', $qqId)->first();
            if (! $customer) {
                $customer = $this->findCustomerByNameAndStore($displayName, $storeNo);
            }

            if (! $customer && $isUpdate) {
                return $this->upsert($request, false);
            }

            if ($customer) {
                if ($customer->qq_id) {
                    unset($attributes['qq_id']);
                }
                if ($customer->qq_company_id || empty($attributes['qq_company_id'])) {
                    unset($attributes['qq_company_id']);
                }
                $customer->fill($attributes)->save();
                $message = 'Customer updated successfully';
            } else {
                $customer = UploadPortalCustomer::create($attributes);
                $message = 'Customer created successfully';
            }

            return response()->json([
                'success' => true,
                'message' => $message,
                'customer' => $customer->fresh(),
            ]);
        } catch (\Throwable $e) {
            info('Quickqore customer sync error: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to sync customer',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function destroy(Request $request): JsonResponse
    {
        $qqIds = $request->input('qq_ids');
        if (! is_array($qqIds) && $request->filled('qq_id')) {
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
        $deleted = [];
        $skippedNotFound = [];
        $skippedHasInvoices = [];

        foreach ($qqIds as $qqId) {
            $customer = UploadPortalCustomer::query()->where('qq_id', $qqId)->first();
            if (! $customer) {
                $skippedNotFound[] = $qqId;
                continue;
            }

            if ($customer->salesInvoices()->exists()) {
                $skippedHasInvoices[] = [
                    'qq_id' => $qqId,
                    'id' => $customer->id,
                ];
                continue;
            }

            $customer->delete();
            $deleted[] = $qqId;
        }

        $blocked = $skippedHasInvoices !== [];

        return response()->json([
            'success' => ! $blocked || $deleted !== [],
            'message' => $blocked
                ? 'Some customers could not be deleted because they have sales invoices'
                : 'Customer delete completed',
            'deleted' => $deleted,
            'skipped_not_found' => $skippedNotFound,
            'skipped_has_invoices' => $skippedHasInvoices,
        ], $blocked && $deleted === [] ? 400 : 200);
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
        $storeNo = trim($storeNo);
        if ($storeNo === '') {
            return [];
        }

        return array_values(array_unique(array_filter([
            $storeNo,
            ltrim($storeNo, '0'),
        ], fn ($key) => $key !== '')));
    }

    private function nullableString(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value !== '' ? $value : null;
    }
}
