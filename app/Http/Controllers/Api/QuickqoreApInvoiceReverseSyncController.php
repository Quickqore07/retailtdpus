<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AP\ApInvoice;
use App\Models\AP\ApItem;
use App\Models\Settings\Company;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

/**
 * Inbound sync when Quickqore updates an AP bill after export from this app.
 * Authenticate with the same X-Security-Key used for outbound Quickqore calls.
 */
class QuickqoreApInvoiceReverseSyncController extends Controller
{
    public function sync(Request $request): JsonResponse
    {
        $expectedKey = (string) config('app.quickqore_security_key', '');
        if ($expectedKey === '') {
            return response()->json(['success' => false, 'message' => 'Quickqore inbound sync is not configured'], 503);
        }
        $givenKey = (string) $request->header('X-Security-Key', '');
        if (!hash_equals($expectedKey, $givenKey)) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $invoice_id = $request->input('invoice_id');
        if (!$invoice_id) {
            return response()->json([
                'success' => false,
                'message' => 'invoice_id is required',
            ], 422);
        }
        $invoice_id = (int) $invoice_id;
        $validator = Validator::make($request->all(), [
            'invoice_id' => 'required|integer',
            'company_store_number' => 'nullable|string|max:50',
            'vendor_id' => 'nullable|integer',
            'date' => 'nullable|date',
            'bill_number' => 'nullable|string|max:255',
            'due_date' => 'nullable|date',
            'amount' => 'nullable|numeric|min:0',
            'remarks' => 'nullable|string|max:5000',
            'qq_company_id' => 'nullable|integer',
            'qq_business_unit_id' => 'nullable|integer',
            'items' => 'nullable|array',
            'items.*.company_store_number' => 'required_with:items|string|max:50',
            'items.*.qq_ledger_id' => 'required_with:items|integer',
            'items.*.amount' => 'required_with:items|numeric|min:0',
            'items.*.description' => 'nullable|string|max:2000',
            
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $invoiceQuery = ApInvoice::query()->where('id', $invoice_id);
        $storeNumber = (string) $request->input('company_store_number');
        $compnies = Company::pluck('id', 'store_number');
        if (!isset($compnies[$storeNumber])) {
            return response()->json([
                'success' => false,
                'message' => 'Company not found for the given store number: '.$storeNumber .' in TDPUS',
            ], 404);
        }
        $companyId = $compnies[$storeNumber];
        /** @var ApInvoice|null $invoice */
        $invoice = $invoiceQuery->first();
        if (!$invoice) {
            return response()->json([
                'success' => false,
                'message' => 'AP invoice not found for the given purchase bill',
            ], 404);
        }

        $items = $request->input('items');

        try {
            DB::beginTransaction();

            $invoice->qq_vendor_id = (int) $request->input('vendor_id');
            $invoice->date = $request->input('date');
            $invoice->bill_number = $request->input('bill_number');
            $invoice->due_date = $request->input('due_date');
            $invoice->remarks = $request->input('remarks');
            $invoice->amount = $request->input('amount');
            $invoice->qq_company_id = (int) $request->input('qq_company_id');
            $invoice->qq_business_unit_id = (int) $request->input('qq_business_unit_id');
            $invoice->company_id = (int) $companyId;

            if (is_array($items) && count($items) > 0) {
                $invoice->amount = round(array_sum(array_map(fn ($r) => (float) ($r['amount'] ?? 0), $items)), 2);

                ApItem::where('ap_invoice_id', $invoice->id)->delete();
                foreach ($items as $row) {
                    $rowCompanyId = isset($compnies[$row['company_store_number']]) ? $compnies[$row['company_store_number']] : null;
                    if(!$rowCompanyId) {
                        return response()->json([
                            'success' => false,
                            'message' => 'Company not found for the given store number: '.$row['company_store_number'] .' in TDPUS',
                        ], 404);
                    }
                    ApItem::create([
                        'ap_invoice_id' => $invoice->id,
                        'document_id' => $invoice->document_id,
                        'company_id' => (int) $rowCompanyId,
                        'qq_ledger_id' => (int) $row['qq_ledger_id'],
                        'description' => $row['description'] ?? null,
                        'amount' => $row['amount'],
                    ]);
                }
            }


            $invoice->save();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'AP invoice updated successfully',
                'ap_invoice' => $invoice->fresh()->load('items'),
            ]);
        } catch (\Throwable $e) {
            if (DB::transactionLevel() > 0) {
                DB::rollBack();
            }
            info('Quickqore AP invoice reverse sync error: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to update AP invoice',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
