<?php

namespace App\Http\Controllers\AP;

use App\Http\Controllers\Controller;
use App\Models\AP\ExpenseType;
use App\Models\AP\PurchaseInvoice;
use App\Models\Upload\UploadDocument;
use App\Models\Upload\UploadFolder;
use App\Services\FileUploadService;
use App\Services\PurchaseInvoiceNotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PurchaseInvoiceController extends Controller
{
    public function index()
    {
        $this->authorize('access', 'purchase-invoice.index');

        $baseQuery = PurchaseInvoice::query()
            ->authorizedCompanies('company_id', false);

        $statusCounts = (clone $baseQuery)
            ->selectRaw('status, count(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->all();

        $statusCounts = collect(PurchaseInvoice::STATUSES)
            ->mapWithKeys(fn (string $status) => [$status => (int) ($statusCounts[$status] ?? 0)])
            ->all();

        $collection = (clone $baseQuery)
            ->when(request('status'), function ($query) {
                $query->where('status', request('status'));
            })
            ->join('ap_vendors', 'ap_purchase_invoices.vendor_id', '=', 'ap_vendors.id')
            ->join('company', 'ap_purchase_invoices.company_id', '=', 'company.id')
            ->select('ap_purchase_invoices.*', 'ap_vendors.name as vendor_name', 'company.name as company_name')
            ->with([
                'workgroup',
                'company',
                'vendor',
                'expense',
                'document:id,file_path,file_name',
                'createdBy',
                'updatedBy',
                'payments:id,purchase_invoice_id,amount,check_number,payment_date,payment_method,remarks',
            ])
            ->withSum('payments', 'amount')
            ->filter();

        return to_json([
            'collection' => $collection->through(fn (PurchaseInvoice $item) => $this->appendPaymentSummary($item)),
            'status_counts' => $statusCounts,
        ]);
    }

    public function create()
    {
        $this->authorize('access', 'purchase-invoice.create');

        return to_json([
            'form' => $this->defaultForm(),
        ]);
    }

    public function store(Request $request, FileUploadService $fileUploadService)
    {
        $this->authorize('access', 'purchase-invoice.create');

        $validated = $this->prepareValidatedData($request->validate($this->validationRules($request), [
            'company_id.required' => 'Company is required.',
            'expense_id.required' => 'Expense type is required.',
            'workgroup_id.required' => 'Workgroup is required.',
        ]));

        DB::beginTransaction();

        try {
            $documentId = $this->storeDocument(
                $request,
                $fileUploadService,
                (int) $validated['company_id'],
                $validated['invoice_no'],
                (float) $validated['total_amount']
            );

            $item = PurchaseInvoice::create([
                ...$validated,
                'document_id' => $documentId,
            ]);

            DB::commit();

            PurchaseInvoiceNotificationService::notifyCreated($item);

            return to_json([
                'saved' => true,
                'id' => $item->id,
                'message' => 'Purchase invoice created successfully',
            ]);
        } catch (\Throwable $th) {
            info($th);
            DB::rollBack();
            report($th);

            return to_json([
                'saved' => false,
                'message' => 'Failed to create purchase invoice.',
                'error' => $th->getMessage(),
            ], 500);
        }
    }

    public function show($id)
    {
        $this->authorize('access', 'purchase-invoice.show');

        $item = PurchaseInvoice::query()
            ->authorizedCompanies('company_id', false)
            ->with([
                'workgroup',
                'company',
                'vendor',
                'expense',
                'document',
                'createdBy',
                'updatedBy',
                'payments.createdBy',
            ])
            ->withSum('payments', 'amount')
            ->findOrFail($id);

        return to_json([
            'model' => $this->appendPaymentSummary($item),
        ]);
    }

    public function edit($id)
    {
        $this->authorize('access', 'purchase-invoice.update');

        $item = PurchaseInvoice::query()
            ->authorizedCompanies('company_id', false)
            ->with(['workgroup', 'company', 'vendor', 'expense', 'document'])
            ->findOrFail($id);

        return to_json([
            'form' => $item,
        ]);
    }

    public function update($id, Request $request, FileUploadService $fileUploadService)
    {
        $this->authorize('access', 'purchase-invoice.update');

        $item = PurchaseInvoice::query()
            ->authorizedCompanies('company_id', false)
            ->findOrFail($id);

        if ($item->status !== 'draft') {
            return to_json([
                'saved' => false,
                'message' => 'Only draft purchase invoices can be edited.',
            ], 422);
        }

        $validated = $this->prepareValidatedData($request->validate($this->validationRulesForUpdate($request)));

        DB::beginTransaction();

        try {
            $documentId = $item->document_id;
            $oldPath = $item->document->file_path;

            if ($request->hasFile('document')) {

                $documentId = $this->storeDocument(
                    $request,
                    $fileUploadService,
                    (int) $validated['company_id'],
                    $validated['invoice_no'],
                    (float) $validated['total_amount']
                );
            }

            $item->fill([
                ...$validated,
                'document_id' => $documentId,
            ]);
            $item->save();

            if ($request->hasFile('document')) {
                $fileUploadService = new FileUploadService();
                $fileUploadService->delete($oldPath);
            }
            
            
            if ($item->document) {


                $item->document->update([
                    'company_id' => $validated['company_id'],
                    'name' => $this->documentName($validated['invoice_no']),
                    'approved_amount' => $validated['total_amount'],
                    'invoice_status' => $validated['status'],
                    'editable' => false,
                ]);
            }

            DB::commit();

            return to_json([
                'saved' => true,
                'id' => $item->id,
                'message' => 'Purchase invoice updated successfully',
            ]);
        } catch (\Throwable $th) {
            DB::rollBack();
            report($th);

            return to_json([
                'saved' => false,
                'message' => 'Failed to update purchase invoice.',
                'error' => $th->getMessage(),
            ], 500);
        }
    }

    public function destroy($id)
    {
        $this->authorize('access', 'purchase-invoice.delete');

        $item = PurchaseInvoice::query()
            ->authorizedCompanies('company_id', false)
            ->findOrFail($id);

            
            if ($item->status !== 'draft') {
                return to_json([
                    'deleted' => false,
                    'message' => 'Only draft purchase invoices can be deleted.',
                ], 422);
            }
            
            $item->delete();
        if($item->document) {
            $fileUploadService = new FileUploadService();
            $fileUploadService->delete($item->document->file_path);
            $item->document->delete();
        }

        return to_json([
            'deleted' => true,
            'message' => 'Purchase invoice deleted successfully',
        ]);
    }

    public function approve($id)
    {
        $this->authorize('access', 'purchase-invoice.update');

        $item = PurchaseInvoice::query()
            ->authorizedCompanies('company_id', false)
            ->findOrFail($id);

        if ($item->status !== 'draft') {
            return to_json([
                'success' => false,
                'message' => 'Only draft purchase invoices can be approved.',
            ], 422);
        }

        $item->update(['status' => 'approved']);

        PurchaseInvoiceNotificationService::notifyApproved($item);

        return to_json([
            'success' => true,
            'message' => 'Purchase invoice approved successfully',
        ]);
    }

    public function cancel($id)
    {
        $this->authorize('access', 'purchase-invoice.update');

        $item = PurchaseInvoice::query()
            ->authorizedCompanies('company_id', false)
            ->findOrFail($id);

        if (!in_array($item->status, ['draft', 'approved'], true)) {
            return to_json([
                'success' => false,
                'message' => 'Paid purchase invoices cannot be cancelled.',
            ], 422);
        }

        $item->update(['status' => 'cancelled']);

        return to_json([
            'success' => true,
            'message' => 'Purchase invoice cancelled successfully',
        ]);
    }

    public function moveToDraft($id)
    {
        $this->authorize('access', 'purchase-invoice.update');

        $item = PurchaseInvoice::query()
            ->authorizedCompanies('company_id', false)
            ->findOrFail($id);

        if ($item->status !== 'cancelled') {
            return to_json([
                'success' => false,
                'message' => 'Only cancelled purchase invoices can be moved to draft.',
            ], 422);
        }

        $item->update(['status' => 'draft']);

        return to_json([
            'success' => true,
            'message' => 'Purchase invoice moved to draft successfully',
        ]);
    }

    private function defaultForm(): array
    {
        $defaultExpense = ExpenseType::where('name', 'Repair')->first();

        return [
            'workgroup_id' => null,
            'company_id' => null,
            'vendor_id' => null,
            'workgroup' => null,
            'company' => null,
            'vendor' => null,
            'invoice_date' => now()->toDateString(),
            'invoice_no' => '',
            'due_date' => '',
            'expense_id' => $defaultExpense?->id,
            'expense' => $defaultExpense,
            'amount' => '',
            'other_amount' => 0,
            'total_amount' => 0,
            'remarks' => '',
            'status' => 'draft',
        ];
    }

    private function validationRules(Request $request): array
    {
        $expenseType = ExpenseType::find($request->input('expense_id'));
        $amountRules = $expenseType?->isAmountOptional()
            ? 'nullable|numeric|min:0'
            : 'required|numeric|min:0';

        return [
            'workgroup_id' => 'required|integer|exists:workgroup,id',
            'company_id' => 'required|integer|exists:company,id',
            'vendor_id' => 'required|integer|exists:ap_vendors,id',
            'invoice_date' => 'required|date',
            'invoice_no' => 'required|string|max:255',
            'due_date' => 'nullable|date',
            'expense_id' => 'required|integer|exists:ap_expense_types,id',
            'amount' => $amountRules,
            'other_amount' => 'nullable|numeric|min:0',
            'remarks' => 'nullable|string',
            'status' => 'required|in:' . implode(',', PurchaseInvoice::STATUSES),
            'document' => 'nullable|file|' . upload_max_file_size_rule() . '|mimes:pdf,jpg,jpeg,png,doc,docx,xls,xlsx',
        ];
    }

    private function validationRulesForUpdate(Request $request): array
    {
        return array_merge($this->validationRules($request), [
            'status' => 'required|in:draft',
        ]);
    }

    private function storeDocument(
        Request $request,
        FileUploadService $fileUploadService,
        int $companyId,
        string $invoiceNo,
        float $amount,
    ): ?int {
        if (!$request->hasFile('document')) {
            return null;
        }

        $folder = UploadFolder::whereRaw('LOWER(name) = ?', ['purchase invoice'])->first();

        if (!$folder) {
            $folder = UploadFolder::create([
                'name' => 'Purchase Invoice',
                'hide' => true,
            ]);
        }

        $file = $request->file('document');
        $fileName = time() . '_' . $file->getClientOriginalName();
        $result = $fileUploadService->store(
            $file,
            'uploaded-documents/' . $folder->name,
        );

        $document = UploadDocument::create([
            'folder_id' => $folder->id,
            'company_id' => $companyId,
            'name' => $this->documentName($invoiceNo),
            'file_path' => $result['path'],
            'file_name' => $fileName,
            'file_type' => $file->getClientMimeType(),
            'file_size' => $file->getSize(),
            'uploaded_by' => Auth::id(),
            'approved_amount' => $amount,
            'editable' => false,
        ]);

        return $document->id;
    }

    private function prepareValidatedData(array $validated): array
    {
        $expenseType = ExpenseType::findOrFail($validated['expense_id']);

        if (!$expenseType->show_other_amount) {
            $validated['other_amount'] = 0;
        } else {
            $validated['other_amount'] = $validated['other_amount'] ?? 0;
        }

        $validated['amount'] = $validated['amount'] ?? 0;

        $validated['total_amount'] = round(
            (float) $validated['amount'] + (float) $validated['other_amount'],
            2
        );

        return $validated;
    }

    private function documentName(string $invoiceNo): string
    {
        return 'Purchase Invoice - ' . ($invoiceNo ?: 'Document');
    }

    private function appendPaymentSummary(PurchaseInvoice $item): PurchaseInvoice
    {
        $item->setAttribute('paid_amount', $item->paid_amount);
        $item->setAttribute('due_amount', $item->due_amount);

        return $item;
    }
}
