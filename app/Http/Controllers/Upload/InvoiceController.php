<?php

namespace App\Http\Controllers\Upload;

use App\Http\Controllers\Controller;
use App\Models\AP\ApInvoice;
use App\Models\AP\ApItem;
use App\Models\AP\ApPayment;
use App\Models\AP\ApPaymentItem;
use App\Models\Settings\Company;
use App\Models\Settings\Workgroup;
use App\Models\Upload\UploadDocument;
use App\Models\Upload\UploadFolder;
use App\Models\User;
use App\Services\Quickqore\QuickqoreInvoiceService;
use App\Services\Quickqore\QuickqoreService;
use App\Services\FileUploadService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class InvoiceController extends Controller
{
    private function getAuthenticatedUploadUser()
    {
        $user = Auth::guard('upload-portal')->user();
        if (!$user) {
            return null;
        }
        return User::with('role')->find($user->id);
    }

    private function ensurePermission($user, string $permission)
    {
        return Gate::forUser($user)->allows('sp-access', $permission) || Gate::forUser($user)->allows('access', $permission);
    }
    public function getVendors(Request $request)
    {
        try {
            $vendors = (new QuickqoreInvoiceService())->getVendors($request->all());
            foreach($vendors['data'] as $key => $vendor){
                if(isset($vendor['expense_ledger'])){
                    $vendors['data'][$key]['expense_ledger'] = [
                        'id' => $vendor['expense_ledger']['id'],
                        'name' => $vendor['expense_ledger']['code'] . ' - ' . $vendor['expense_ledger']['name'],
                    ];
                }
            }
            return to_json($vendors['data']);
        } catch (\Throwable $e) {
            return to_json([
                'success' => false,
                'message' => $e->getMessage(),
                'vendors' => [],
            ], 400);
        }
    }

    public function storeVendor(Request $request)
    {
        $user = $this->getAuthenticatedUploadUser();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'User not authenticated'], 401);
        }

        if (!$this->ensurePermission($user, 'upload-portal.add')) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to add vendors',
            ], 403);
        }

        $billConditions = [
            'of current month',
            'of the following month',
            'day(s) after the invoice date',
            'day(s) after the end of the invoice month',
        ];

        $validated = $request->validate([
            'store_number' => 'required',
            'workgroup' => 'required|string|max:255',
            'name' => 'required|string|max:255',
            'expense_ledger_id' => 'required|integer',
            'email' => 'nullable|email|max:255',
            'mobile' => 'nullable|string|max:50',
            'billing_address_line_1' => 'nullable|string|max:255',
            'billing_address_line_2' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:255',
            'state' => 'nullable|string|max:255',
            'country' => 'nullable|string|max:255',
            'zip_code' => 'nullable|string|max:32',
            'bill_day' => 'required|integer|min:0|max:366',
            'bill_condition' => ['required', 'string', Rule::in($billConditions)],
        ]);

        $payload = [
            'store_number' => $validated['store_number'],
            'workgroup' => $validated['workgroup'],
            'name' => $validated['name'],
            'expense_ledgerid' => (int) $validated['expense_ledger_id'],
            'email' => $validated['email'] ?? '',
            'mobile' => $validated['mobile'] ?? '',
            'address1' => $validated['billing_address_line_1'] ?? '',
            'address2' => $validated['billing_address_line_2'] ?? '',
            'city' => $validated['city'] ?? '',
            'state' => $validated['state'] ?? '',
            'country' => $validated['country'] ?? '',
            'zipCode' => $validated['zip_code'] ?? '',
            'billDay' => (int) $validated['bill_day'],
            'billCondition' => $validated['bill_condition'],
        ];

        try {
            $result = (new QuickqoreInvoiceService())->storeVendor($payload);
            $vendor = is_array($result['data'] ?? null) ? $result['data'] : $result;
            if (! is_array($vendor)) {
                throw new \RuntimeException('Unexpected response from Quickqore when creating vendor');
            }

            if (isset($vendor['expense_ledger'])) {
                $vendor['expense_ledger'] = [
                    'id' => $vendor['expense_ledger']['id'],
                    'name' => $vendor['expense_ledger']['code'].' - '.$vendor['expense_ledger']['name'],
                ];
            }

            return response()->json([
                'success' => true,
                'vendor' => $vendor,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    public function getSigns(Request $request)
    {
        try {
            $signs = (new QuickqoreInvoiceService())->getSigns($request->all());
            return to_json($signs['data']);
        } catch (\Throwable $e) {
            return to_json([
                'success' => false,
                'message' => $e->getMessage(),
                'signs' => [],
            ], 400);
        }
    }   
    public function getExpenseLedgers(Request $request)
    {
        try {
            $expenseLedgers = (new QuickqoreInvoiceService())->getExpenseLedgers($request->all());
            foreach($expenseLedgers['data'] as $key => $expenseLedger){
                $expenseLedgers['data'][$key]['name'] = $expenseLedger['code'] . ' - ' . $expenseLedger['name'];
            }
            return to_json($expenseLedgers['data']);
        } catch (\Throwable $e) {
            return to_json([
                'success' => false,
                'message' => $e->getMessage(),
                'expenseLedgers' => [],
            ], 400);
        }
    }
    public function getBanks(Request $request)
    {
        $banks = (new QuickqoreInvoiceService())->getBanks($request->all());
        return to_json($banks['data']);
    }
    public function getLatestCheckNumber(Request $request)
    {
        $latestCheckNumber = (new QuickqoreInvoiceService())->getLatestCheckNumber($request->all());
        return to_json($latestCheckNumber);
    }
    public function getBusinessUnits(Request $request)
    {
        $businessUnits = (new QuickqoreInvoiceService())->getBusinessUnits($request->all());
        $company = Company::whereIn('store_number', $businessUnits['data'])->selectRaw('id, concat(store_number, " - ", name) as name, store_number')->get();

        return to_json($company);
    }

    /**
     * Create upload document (S3/public disk), ap_invoices row, and ap_items in one transaction.
     */
    public function storeApInvoice(Request $request)
    {
        $user = $this->getAuthenticatedUploadUser();
        $bill_id=null;
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'User not authenticated'], 401);
        }

        if (!$this->ensurePermission($user, 'upload-portal.add')) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to add documents',
            ], 403);
        }

        $request->validate([
            'invoice_type' => 'required|string',
            'folder_id' => 'required|exists:upload_folders,id',
            'company_id' => 'required|exists:company,id', 
            'qq_vendor_id' => 'required_if:invoice_type,check|integer',
            'date' => 'required_if:invoice_type,check|date',
            'bill_number' => 'required_if:invoice_type,check|string|max:255',
            'due_date' => 'required_if:invoice_type,check|date',
            'remarks' => 'nullable|string',
            'invoice_name' => 'required_if:invoice_type,!check|string|max:255',
            'items' => 'required_if:invoice_type,check|string',
            'file' => 'required|file|' . upload_max_file_size_rule(),
        ]);

        $items = $request->invoice_type === 'check' ? json_decode($request->input('items'), true) : [];
        if ($request->invoice_type === 'check' && (!is_array($items) || count($items) < 1)) {
            return response()->json([
                'success' => false,
                'message' => 'At least one line item is required',
            ], 422);
        }

        foreach ($items as $index => $row) {
            $v = Validator::make($row, [
                'company_id' => 'required|exists:company,id',
                'qq_ledger_id' => 'required|integer',
                'amount' => 'required|numeric|min:0',
                'description' => 'nullable|string|max:2000',
            ]);
            if ($v->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid items payload at row ' . ($index + 1),
                    'errors' => $v->errors(),
                ], 422);
            }
        }

        $isAdmin = isset($user->role) && in_array($user->role->name, ['admin', 'superadmin'], true);
        $allowedCompanyIds = $isAdmin ? Company::pluck('id')->all() : ($user->companiesArray ?? []);

        if (!$isAdmin && !in_array((int) $request->company_id, $allowedCompanyIds, true)) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have access to the selected company',
            ], 403);
        }

        foreach ($items as $row) {
            if (!$isAdmin && !in_array((int) $row['company_id'], $allowedCompanyIds, true)) {
                return response()->json([
                    'success' => false,
                    'message' => 'You do not have access to one or more selected companies on line items',
                ], 403);
            }
        }

        $total = round(array_sum(array_map(fn ($r) => (float) ($r['amount'] ?? 0), $items)), 2);
        if ($total <= 0 && $request->invoice_type === 'check') {
            return response()->json([
                'success' => false,
                'message' => 'Invoice total must be greater than zero',
            ], 422);
        }

        $folder = UploadFolder::find($request->folder_id);
        if (!$folder) {
            return response()->json(['success' => false, 'message' => 'Folder not found'], 404);
        }

        $fileUploadService = new FileUploadService();
        $storedPath = null;

        try {
            if (!$request->hasFile('file')) {
                return response()->json(['success' => false, 'message' => 'No file uploaded'], 400);
            }

            $file = $request->file('file');
            $fileName = time() . '_' . $file->getClientOriginalName();
            $result = $fileUploadService->store(
                $file,
                'uploaded-documents/'.$folder->name,
            );
            $storedPath = $result['path'];

            DB::beginTransaction();

            $docName = $request->invoice_type === 'check' ? 'Invoice '.$request->bill_number . ' - ' . $request->vendor_name : $request->invoice_name;

            $document = UploadDocument::create([
                'folder_id' => $request->folder_id,
                'company_id' => $request->company_id,
                'name' => $docName,
                'file_path' => $result['path'],
                'file_name' => $fileName,
                'file_type' => $file->getClientMimeType(),
                'file_size' => $file->getSize(),
                'uploaded_by' => $user->id,
                'is_invoice' => 1,
                'approved_amount' => $total,
                'invoice_status' => 'draft',
            ]);

            $apInvoice = ApInvoice::create([
                'document_id' => $document->id,
                'company_id' => $request->company_id,
                'qq_vendor_id' => $request->qq_vendor_id,
                'date' => $request->date,
                'bill_number' => $request->bill_number,
                'qq_ledger_id' => $request->qq_ledger_id,
                'due_date' => $request->due_date,
                'amount' => $total,
                'approved_amount' => $total,
                'remarks' => $request->remarks,
                'status' => 'draft',
                'created_by' => $user->id,
                'updated_by' => $user->id,
                'invoice_type' => $request->invoice_type,
            ]);

            if($request->invoice_type === 'check') {
                $quickqoreInvoice = (new QuickqoreInvoiceService())->storeApInvoice([
                    'vendor_id' => $request->qq_vendor_id,
                    'date' => $request->date,
                    'bill_number' => $request->bill_number,
                    'due_date' => $request->due_date,
                    'amount' => $total,
                    'remarks' => $request->remarks,
                    'company_store_number' => $request->company_store_number,
                    'workgroup' => $request->workgroup,
                    'items' => $items,
                    'invoice_id' => $apInvoice->id,
                    'document_id' => $document->id,
                ]);
                if(!$quickqoreInvoice['success']) {
                    throw new \RuntimeException($quickqoreInvoice['message']);
                }
                $bill_id = $quickqoreInvoice['id'];
                $apInvoice->qq_purchase_id = $quickqoreInvoice['id'];
                $apInvoice->qq_company_id = $quickqoreInvoice['company_id'];
                $apInvoice->qq_business_unit_id = $quickqoreInvoice['bu_id'];
                $apInvoice->save();
            }

            foreach ($items as $row) {
                ApItem::create([
                    'ap_invoice_id' => $apInvoice->id,
                    'document_id' => $document->id,
                    'company_id' => $row['company_id'],
                    'qq_ledger_id' => $row['qq_ledger_id'],
                    'description' => $row['description'] ?? null,
                    'amount' => $row['amount'],
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Invoice uploaded successfully',
                'document' => $document,
                'ap_invoice' => $apInvoice->load('items'),
            ]);
        } catch (\Throwable $th) {
            if (DB::transactionLevel() > 0) {
                DB::rollBack();
            }
            if ($bill_id) {
                (new QuickqoreInvoiceService())->deleteApInvoice(['qq_purchase_id' => $bill_id, 'company_store_number' => $request->company_store_number]);
            }
            if ($storedPath) {
                try {
                    $fileUploadService->delete($storedPath);
                } catch (\Throwable) {
                    // ignore cleanup failure
                }
            }
            return response()->json([
                'success' => false,
                'message' => $th->getMessage(),
                'error' => 'Failed to save invoice',
            ], 500);
        }
    }

    public function showApInvoice($documentId)
    {
        $user = $this->getAuthenticatedUploadUser();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'User not authenticated'], 401);
        }

        if (
            !$this->ensurePermission($user, 'upload-portal.view')
            && !$this->ensurePermission($user, 'upload-portal.edit')
        ) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to view invoices',
            ], 403);
        }

        $document = UploadDocument::with(['folder', 'createdBy'])->find($documentId);
        if (!$document) {
            return response()->json(['success' => false, 'message' => 'Document not found'], 404);
        }

        $invoice = ApInvoice::with(['items', 'company', 'createdBy', 'updatedBy', 'approvedBy'])
            ->where('document_id', $document->id)
            ->first();

        if (!$invoice) {
            return response()->json(['success' => false, 'message' => 'Invoice not found'], 404);
        }

        $company = Company::with('workgroup:id,name')
            ->selectRaw('id, concat(store_number, " - ", name) as name, store_number, workgroup_id')
            ->find($document->company_id);

        return response()->json([
            'success' => true,
            'invoice' => $invoice,
            'document' => $document,
            'company' => $company,
        ]);
    }

    public function updateApInvoice(Request $request, $documentId)
    {
        $user = $this->getAuthenticatedUploadUser();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'User not authenticated'], 401);
        }


        $request->validate([
            'invoice_type' => 'required|string',
            'company_id' => 'required|exists:company,id',
            'qq_vendor_id' => 'required_if:invoice_type,check|integer',
            'date' => 'required_if:invoice_type,check|date',
            'bill_number' => 'required_if:invoice_type,check|string|max:255',
            'due_date' => 'required_if:invoice_type,check|date',
            'remarks' => 'nullable|string', 
            'invoice_name' => 'required_if:invoice_type,!check|string|max:255',
            'items' => 'required_if:invoice_type,check|string',
            'file' => 'nullable|file|' . upload_max_file_size_rule(),
            'approved_amount' => 'nullable|numeric|min:0',
        ]);

        $items = $request->invoice_type === 'check' ? json_decode($request->input('items'), true) : [];
        if ($request->invoice_type === 'check' && (!is_array($items) || count($items) < 1)) {
            return response()->json([
                'success' => false,
                'message' => 'At least one line item is required',
            ], 422);
        }

        foreach ($items as $index => $row) {
            $v = Validator::make($row, [
                'company_id' => 'required|exists:company,id',
                'qq_ledger_id' => 'required|integer',
                'amount' => 'required|numeric|min:0',
                'description' => 'nullable|string|max:2000',
            ]);
            if ($v->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid items payload at row ' . ($index + 1),
                    'errors' => $v->errors(),
                ], 422);
            }
        }

        $isAdmin = isset($user->role) && in_array($user->role->name, ['admin', 'superadmin'], true);
        $allowedCompanyIds = $isAdmin ? Company::pluck('id')->all() : ($user->companiesArray ?? []);

        if (!$isAdmin && !in_array((int) $request->company_id, $allowedCompanyIds, true)) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have access to the selected company',
            ], 403);
        }

        foreach ($items as $row) {
            if (!$isAdmin && !in_array((int) $row['company_id'], $allowedCompanyIds, true)) {
                return response()->json([
                    'success' => false,
                    'message' => 'You do not have access to one or more selected companies on line items',
                ], 403);
            }
        }

        $total = round(array_sum(array_map(fn ($r) => (float) ($r['amount'] ?? 0), $items)), 2);
        if ($total <= 0 && $request->invoice_type === 'check') {
            return response()->json([
                'success' => false,
                'message' => 'Invoice total must be greater than zero',
            ], 422);
        }

        $document = UploadDocument::with('folder')->find($documentId);
        if (!$document) {
            return response()->json(['success' => false, 'message' => 'Document not found'], 404);
        }

        $invoice = ApInvoice::where('document_id', $document->id)->first();
        if (!$invoice) {
            return response()->json(['success' => false, 'message' => 'Invoice not found'], 404);
        }

        if($invoice->status == 'approved' && !$this->ensurePermission($user, 'upload-portal.edit-after-approval')) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to edit approved invoices',
            ], 403);
        }else if($invoice->status == 'draft' && !$this->ensurePermission($user, 'upload-portal.edit')) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to edit draft invoices',
            ], 403);
        }


        if($invoice->status == 'approved' && ((float)($request->approved_amount) > (float)($total) || (float)($request->approved_amount) < 0)){
            return response()->json(['success' => false, 'message' => 'Approved amount must be greater than zero and less than total amount'], 422);
        }

        $fileUploadService = new FileUploadService();
        $oldPath = $document->file_path;
        $newPath = null;
        $newFileName = null;
        $newMime = null;
        $newSize = null;

        try {
            if ($request->hasFile('file')) {
                $file = $request->file('file');
                $newFileName = time() . '_' . $file->getClientOriginalName();
                $result = $fileUploadService->store($file, 'uploaded-documents/'.$document->folder->name);
                $newPath = $result['path'];
                $newMime = $file->getClientMimeType();
                $newSize = $file->getSize();
            }

            DB::beginTransaction();

            $docName = $request->invoice_type === 'check' ? 'Invoice '.$request->bill_number . ' - ' . $request->vendor_name : $request->invoice_name;
            $document->name = $docName;
            $document->company_id = $request->company_id;
            if ($newPath) {
                $document->file_path = $newPath;
                $document->file_name = $newFileName;
                $document->file_type = $newMime;
                $document->file_size = $newSize;
            }
            $document->is_invoice = 1;
            $document->invoice_status = $document->invoice_status == 'paid' ? 'approved' : $document->invoice_status;

            $document->save();

            $invoice->company_id = $request->company_id;
            $invoice->qq_vendor_id = $request->qq_vendor_id;
            $invoice->date = $request->date;
            $invoice->bill_number = $request->bill_number;
            $invoice->due_date = $request->due_date;
            $invoice->remarks = $request->remarks;
            $invoice->amount = $total;
            $invoice->updated_by = $user->id;
            $invoice->status = $invoice->status == 'paid' ? 'approved' : $invoice->status;
            $invoice->invoice_type = $request->invoice_type;
            if($invoice->status == 'approved'){
                $invoice->approved_amount = $request->approved_amount;
            }

            if($request->invoice_type === 'check' && $invoice->qq_purchase_id) {
                $quickqoreInvoice = (new QuickqoreInvoiceService())->updateApInvoice([
                    'vendor_id' => $request->qq_vendor_id,
                    'date' => $request->date,
                    'bill_number' => $request->bill_number,
                    'due_date' => $request->due_date,
                    'amount' => $total,
                    'remarks' => $request->remarks,
                    'company_store_number' => $request->company_store_number,
                    'workgroup' => $request->workgroup,
                    'invoice_id' => $invoice->id,
                    'items' => $items,
                    'document_id' => $document->id,
                    'qq_company_id' => $request->qq_company_id,
                    'qq_business_unit_id' => $request->qq_business_unit_id,
                    'qq_purchase_id' => $invoice->qq_purchase_id,
                ]);
                if(!$quickqoreInvoice['success']) {
                    throw new \RuntimeException($quickqoreInvoice['message']);
                }
                $invoice->qq_purchase_id = $quickqoreInvoice['id'];
                $invoice->qq_company_id = $quickqoreInvoice['company_id'];
                $invoice->qq_business_unit_id = $quickqoreInvoice['bu_id'];
                $invoice->save();
            }else if($request->invoice_type === 'check') {
                    $quickqoreInvoice = (new QuickqoreInvoiceService())->storeApInvoice([
                        'vendor_id' => $request->qq_vendor_id,
                        'date' => $request->date,
                        'bill_number' => $request->bill_number,
                        'due_date' => $request->due_date,
                        'amount' => $total,
                        'remarks' => $request->remarks,
                        'company_store_number' => $request->company_store_number,
                        'workgroup' => $request->workgroup,
                        'items' => $items,
                        'invoice_id' => $invoice->id,
                        'document_id' => $document->id,
                    ]);
                    $invoice->qq_purchase_id = $quickqoreInvoice['id'];
                    $invoice->qq_company_id = $quickqoreInvoice['company_id'];
                    $invoice->qq_business_unit_id = $quickqoreInvoice['bu_id'];
                    $invoice->save();
            }

            ApItem::where('ap_invoice_id', $invoice->id)->delete();
            foreach ($items as $row) {
                ApItem::create([
                    'ap_invoice_id' => $invoice->id,
                    'document_id' => $document->id,
                    'company_id' => $row['company_id'],
                    'qq_ledger_id' => $row['qq_ledger_id'],
                    'description' => $row['description'] ?? null,
                    'amount' => $row['amount'],
                ]);
            }

            DB::commit();

            if ($newPath && $oldPath && $oldPath !== $newPath) {
                try {
                    $fileUploadService->delete($oldPath);
                } catch (\Throwable) {
                    // ignore old file cleanup failure
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Invoice updated successfully',
                'document' => $document->fresh(),
                'ap_invoice' => $invoice->fresh()->load('items'),
            ]);
        } catch (\Throwable $th) {
            if (DB::transactionLevel() > 0) {
                DB::rollBack();
            }

            if ($newPath) {
                try {
                    $fileUploadService->delete($newPath);
                } catch (\Throwable) {
                    // ignore cleanup failure
                }
            }
            return response()->json([
                'success' => false,
                'message' => 'Failed to update invoice',
                'error' => $th->getMessage(),
            ], 500);
        }
    }

    public function approveApInvoice(Request $request, $documentId)
    {
        try {
            $user = $this->getAuthenticatedUploadUser();
            if (!$user) {
                return response()->json(['success' => false, 'message' => 'User not authenticated'], 401);
            }
            if (!$this->ensurePermission($user, 'upload-portal.approve')) {
                return response()->json([
                    'success' => false,
                    'message' => 'You do not have permission to approve invoices',
                ], 403);
            }

            $validated = $request->validate([
                'acknowledged' => 'required|accepted',
                'approval_remarks' => 'required|string|max:2000',
                'approved_amount' => 'required|numeric|min:0',
            ]);

            $invoice = ApInvoice::where('document_id', $documentId)->first();
            // if (!$invoice) {
            //     return response()->json(['success' => false, 'message' => 'Invoice not found'], 404);
            // }
            $document = UploadDocument::find($documentId);
            if($invoice && $invoice->invoice_type === 'check'){
                $workgroup = Workgroup::where('id', $invoice->company->workgroup_id)->first();

                (new QuickqoreInvoiceService())->approveApInvoice([
                    'qq_purchase_id' => $invoice->qq_purchase_id,
                    'qq_company_id' => $invoice->qq_company_id,
                    'company_store_number' => $invoice->company->store_number,
                    'workgroup' => $workgroup ? $workgroup->name : null,
                ]);
                $invoice->status = 'approved';
                $invoice->approval_remarks = $validated['approval_remarks'];
                $invoice->approved_amount = $validated['approved_amount'];
                $invoice->approved_by = $user->id;
                $invoice->approved_at = now();
                $invoice->save();

                $document->invoice_status = 'approved';
                $document->invoice_approved_by = $user->id;
                $document->invoice_approved_at = now();
                $document->approved_amount = $validated['approved_amount'];
                $document->save();
            }else{
                $invoice->status = 'paid';
                $invoice->approval_remarks = $validated['approval_remarks'];
                $invoice->approved_by = $user->id;
                $invoice->approved_at = now();
                $invoice->paid_by = $user->id;
                $invoice->paid_at = now();
                $invoice->save();

                $document->invoice_status = 'paid';
                $document->invoice_approved_by = $user->id;
                $document->invoice_approved_at = now();
                $document->save();
            }

            return response()->json(['success' => true, 'message' => 'Invoice approved successfully']);
        } catch (\Throwable $th) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to approve invoice',
                'error' => $th->getMessage(),
            ], 500);
        }
    }

    public function markApInvoicePaid($documentId)
    {
        try {
            $user = $this->getAuthenticatedUploadUser();
            if (!$user) {
                return response()->json(['success' => false, 'message' => 'User not authenticated'], 401);
            }
            if (!$this->ensurePermission($user, 'upload-portal.approve')) {
                return response()->json([
                    'success' => false,
                    'message' => 'You do not have permission to update invoice status',
                ], 403);
            }

            $invoice = ApInvoice::where('document_id', $documentId)->first();
            // if (!$invoice) {
            //     return response()->json(['success' => false, 'message' => 'Invoice not found'], 404);
            // }
            if($invoice){
                
                if ($invoice->status !== 'approved') {
                    return response()->json([
                        'success' => false,
                        'message' => 'Only approved invoices can be moved to paid',
                    ], 422);
                }
    
                $invoice->status = 'paid';
                $invoice->paid_by = $user->id;
                $invoice->paid_at = now();
                $invoice->save();
            }

            UploadDocument::where('id', $documentId)->update([
                'invoice_status' => 'paid',
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Invoice moved to paid successfully',
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update invoice status',
                'error' => $th->getMessage(),
            ], 500);
        }
    }

    public function getVendorBills(Request $request)
    {
        $request->validate([
            'company_id' => 'required|integer|exists:company,id',
            'qq_vendor_id' => 'required|integer',
        ]);

        $paidByBill = ApPaymentItem::query()
            ->selectRaw('bill_id, SUM(amount) as paid_amount')
            ->groupBy('bill_id')
            ->pluck('paid_amount', 'bill_id');

        $bills = ApInvoice::query()
            ->where('company_id', $request->integer('company_id'))
            ->where('qq_vendor_id', $request->integer('qq_vendor_id'))
            ->whereIn('status', ['approved'])
            ->orderByDesc('date')
            ->get()
            ->map(function (ApInvoice $invoice) use ($paidByBill) {
                $originalAmount = (float) $invoice->approved_amount;
                $paidAmount = (float) ($paidByBill[$invoice->id] ?? 0);
                $dueAmount = round(max(0, $originalAmount - $paidAmount), 2);
                return [
                    'id' => $invoice->id,
                    'date' => optional($invoice->date)->toDateString(),
                    'bill_number' => $invoice->bill_number,
                    'original_amount' => round($originalAmount, 2),
                    'due_amount' => $dueAmount,
                    'qq_purchase_id' => $invoice->qq_purchase_id,
                ];
            })
            ->filter(fn ($row) => $row['due_amount'] > 0)
            ->values();

        return to_json($bills);
    }

    public function showApPayment($documentId)
    {
        $payment = ApPayment::with([
            'company',
            'createdBy',
            'updatedBy',
            'items.bill:id,bill_number,date,amount,document_id',
        ])
            ->where('check_document_id', $documentId)
            ->first();

        if (!$payment) {
            return response()->json(['success' => false, 'message' => 'Payment not found'], 404);
        }

        return response()->json([
            'success' => true,
            'payment' => $payment,
        ]);
    }

    public function storeApPayment(Request $request)
    {
        $user = $this->getAuthenticatedUploadUser();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'User not authenticated'], 401);
        }
        if (!$this->ensurePermission($user, 'upload-portal.add')) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to add payments',
            ], 403);
        }

        $request->validate([
            'folder_id' => 'required|exists:upload_folders,id',
            'company_id' => 'required|exists:company,id',
            'company_store_number' => 'required',
            'workgroup' => 'required|string|max:50',
            'qq_vendor_id' => 'required|integer',
            'date' => 'required|date',
            'qq_bank_id' => 'required|integer',
            'payment_type' => 'required|string',
            'check_no' => 'nullable|string|max:255',
            'memo' => 'nullable|string|max:5000',
            'file' => 'nullable|file|mimes:pdf,jpg,jpeg,png|' . upload_max_file_size_rule(),
            'items' => 'required|string',
        ]);
        if (strtolower((string) $request->payment_type) !== 'check' && !$request->hasFile('file')) {
            return response()->json([
                'success' => false,
                'message' => 'File is required for non-check payments',
            ], 422);
        }

        $items = json_decode($request->input('items'), true);
        if (!is_array($items) || count($items) < 1) {
            return response()->json([
                'success' => false,
                'message' => 'At least one bill must be selected',
            ], 422);
        }

        foreach ($items as $idx => $row) {
            $v = Validator::make($row, [
                'bill_id' => 'required|integer|exists:ap_invoices,id',
                'due_amount' => 'required|numeric|min:0',
                'amount' => 'required|numeric|min:0.01',
            ]);
            if ($v->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid bill payload at row ' . ($idx + 1),
                    'errors' => $v->errors(),
                ], 422);
            }
            if ((float) $row['amount'] > (float) $row['due_amount']) {
                return response()->json([
                    'success' => false,
                    'message' => 'Payment amount cannot exceed due amount',
                ], 422);
            }
        }

        $totalAmount = round(array_sum(array_map(fn ($r) => (float) $r['amount'], $items)), 2);
        $apiPayload = [
            'store_number' => (int) $request->company_store_number,
            'workgroup' => $request->workgroup,
            'checkno' => (int) ($request->check_no ?? 0),
            'vendor_id' => (int) $request->qq_vendor_id,
            'payment_type' => $request->payment_type === 'check' ? 'Check' : ucfirst(strtolower($request->payment_type)),
            'date' => $request->date,
            'bank_id' => (int) $request->qq_bank_id,
            'qq_sign_id' => (int) $request->qq_sign_id,
            'memo' => $request->memo,
            'bills' => array_map(fn ($r) => [
                'qq_purchase_id' => (int) $r['qq_purchase_id'],
                'invoice_id' => (int) $r['bill_id'],
                'due_amount' => (float) $r['due_amount'],
                'amount' => (float) $r['amount'],

            ], $items),
        ];

        $folder = UploadFolder::find($request->folder_id);
        $fileUploadService = new FileUploadService();
        $uploadedPdfPath = null;
        DB::beginTransaction();
        try {
            $payment = ApPayment::create([
                'payment_id' => $request->payment_id,
                'company_id' => $request->company_id,
                'date' => $request->date,
                'qq_vendor_id' => $request->qq_vendor_id,
                'qq_bank_id' => $request->qq_bank_id,
                'payment_type' => strtolower($request->payment_type),
                'check_no' => $request->check_no ?? 0,
                'memo' => $request->memo,
                'amount' => $totalAmount,
                'created_by' => $user->id,
                'updated_by' => $user->id,
            ]);
            $apiPayload['payment_id'] = $payment->id;
            $quickqoreResponse = (new QuickqoreInvoiceService())->handleApPayment($apiPayload);
            if(!isset($quickqoreResponse['success']) || !$quickqoreResponse['success']) {
                throw new \RuntimeException($quickqoreResponse['message']);
            }
            $paymentType = strtolower((string) $request->payment_type);
            $fileType = 'application/pdf';
            $fileSize = 0;
            $fileName = null;

            if ($paymentType === 'check') {
                $pdfPath = $quickqoreResponse['pdfpath'] ?? null;
                if (!$pdfPath) {
                    throw new \RuntimeException('Quickqore did not return payment PDF path');
                }

                $pdfUrl = preg_match('/^https?:\/\//', $pdfPath)
                    ? $pdfPath
                    : rtrim((string) env('QUICKQORE_API_URL'), '/') . '/' . ltrim($pdfPath, '/');

                $pdfResponse = Http::get($pdfUrl);
                if (!$pdfResponse->successful()) {
                    throw new \RuntimeException('Failed to download payment PDF from Quickqore');
                }

                $disk = config('filesystems.default') === 's3' ? 's3' : 'public';
                $fileName = basename($pdfPath);
                $uploadedPdfPath = 'uploaded-documents/' . $folder->name . '/' . uniqid() . '_' . $fileName;
                Storage::disk($disk)->put($uploadedPdfPath, $pdfResponse->body(), 'public');
                $fileSize = strlen($pdfResponse->body());
            } else {
                if (!$request->hasFile('file')) {
                    throw new \RuntimeException('File is required for non-check payments');
                }
                $uploadResult = $fileUploadService->store($request->file('file'), 'uploaded-documents/' . $folder->name);
                $uploadedPdfPath = $uploadResult['path'];
                $fileName = $request->file('file')->getClientOriginalName();
                $fileType = $request->file('file')->getClientMimeType() ?: 'application/pdf';
                $fileSize = $request->file('file')->getSize();
            }

            $checkDocument = UploadDocument::create([
                'folder_id' => $request->folder_id,
                'company_id' => $request->company_id,
                'name' => 'AP Payment - ' . ($request->qq_vendor_name ?: ($request->check_no ?: null)),
                'file_path' => $uploadedPdfPath,
                'file_name' => $fileName,
                'file_type' => $fileType,
                'file_size' => $fileSize,
                'uploaded_by' => $user->id,
                'is_check' => 1,
                'check_status' => 'paid',
                'check_paid_at' => now(),
                'check_paid_by' => $user->id,
            ]);

            $payment->qq_paybill_id = $quickqoreResponse['id'] ?? null;
            $payment->qq_company_id = $quickqoreResponse['company_id'] ?? null;
            $payment->qq_business_unit_id = $quickqoreResponse['bu_id'] ?? null;
            $payment->check_document_id = $checkDocument->id;
            $payment->save();


            $billsId = collect($items)->pluck('bill_id')->map(fn ($id) => (int) $id)->unique()->values();
            ApInvoice::whereIn('id', $billsId)->update([
                'check_document_id' => $checkDocument->id,
            ]);

            foreach ($items as $row) {

                if($row['due_amount']  == $row['amount']) {
                    $inv =ApInvoice::where('id', $row['bill_id'])->first();
                    if($inv) {
                        $inv->status = 'paid';
                        $inv->paid_by = $user->id;
                        $inv->paid_at = now();
                        $inv->save();
                    }
                    $document = UploadDocument::where('id', $inv->document_id)->first();
                    if($document) {
                        $document->invoice_status = 'paid';
                        $document->save();
                    }
                }
                ApPaymentItem::create([
                    'ap_payment_id' => $payment->id,
                    'bill_id' => $row['bill_id'],
                    'amount' => $row['amount'],
                ]);
            }

            $billIds = collect($items)->pluck('bill_id')->map(fn ($id) => (int) $id)->unique()->values();
            $paidByBill = ApPaymentItem::query()
                ->selectRaw('bill_id, SUM(amount) as paid_amount')
                ->whereIn('bill_id', $billIds)
                ->groupBy('bill_id')
                ->pluck('paid_amount', 'bill_id');

            ApInvoice::whereIn('id', $billIds)->get()->each(function (ApInvoice $invoice) use ($paidByBill) {
                $invoice->status = ((float) ($paidByBill[$invoice->id] ?? 0) >= (float) $invoice->amount) || ((float) ($paidByBill[$invoice->id] ?? 0) >= (float) $invoice->approved_amount) ? 'paid' : 'approved';
                $invoice->save();
            });

            DB::commit();
            return response()->json([
                'success' => true,
                'message' => 'Payment saved successfully',
                'payment' => $payment,
                'document' => $checkDocument->file_url,
            ]);
        } catch (\Throwable $th) {
            DB::rollBack();
            if ($uploadedPdfPath) {
                try {
                    $fileUploadService->delete($uploadedPdfPath);
                } catch (\Throwable) {
                }
            }
            return response()->json([
                'success' => false,
                'message' => $th->getMessage(),
            ], 500);
        }
    }

    public function  updateApPayment(Request $request, $documentId)
    {
        $user = $this->getAuthenticatedUploadUser();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'User not authenticated'], 401);
        }
        if (!$this->ensurePermission($user, 'upload-portal.edit')) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to edit payments',
            ], 403);
        }

        $request->validate([
            'date' => 'required|date',
            'qq_bank_id' => 'required|integer',
            'payment_type' => 'required|string',
            'check_no' => 'nullable|string|max:255',
            'memo' => 'nullable|string|max:5000',
            'file' => 'nullable|file|mimes:pdf,jpg,jpeg,png|' . upload_max_file_size_rule(),
            'items' => 'required|string',
        ]);

        $payment = ApPayment::where('check_document_id', $documentId)->first();
        if (!$payment) {
            return response()->json(['success' => false, 'message' => 'Payment not found'], 404);
        }

        $items = json_decode($request->input('items'), true);
        if (!is_array($items) || count($items) < 1) {
            return response()->json([
                'success' => false,
                'message' => 'At least one bill must be selected',
            ], 422);
        }

        foreach ($items as $row) {
            if ((float) ($row['amount'] ?? 0) > (float) ($row['due_amount'] ?? 0)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Payment amount cannot exceed due amount',
                ], 422);
            }
        }

        DB::beginTransaction();
        try {
            $payment->date = $request->date;
            $payment->qq_bank_id = $request->qq_bank_id;
            $payment->payment_type = strtolower($request->payment_type);
            $payment->check_no = $request->check_no ?? 0;
            $payment->memo = $request->memo;
            $payment->amount = round(array_sum(array_map(fn ($r) => (float) $r['amount'], $items)), 2);
            $payment->updated_by = $user->id;
            $payment->save();

            ApPaymentItem::where('ap_payment_id', $payment->id)->delete();
            foreach ($items as $row) {
                ApPaymentItem::create([
                    'ap_payment_id' => $payment->id,
                    'bill_id' => $row['bill_id'],
                    'amount' => $row['amount'],
                ]);
            }

            if (strtolower((string) $request->payment_type) !== 'check' && $request->hasFile('file')) {
                $document = UploadDocument::find($documentId);
                if ($document) {
                    $fileUploadService = new FileUploadService();
                    $oldPath = $document->file_path;
                    $folder = UploadFolder::find($document->folder_id);
                    $result = $fileUploadService->store($request->file('file'), 'uploaded-documents/' . $folder->name);
                    $document->file_path = $result['path'];
                    $document->file_name = $request->file('file')->getClientOriginalName();
                    $document->file_type = $request->file('file')->getClientMimeType() ?: $document->file_type;
                    $document->file_size = $request->file('file')->getSize();
                    $document->save();
                    if ($oldPath && $oldPath !== $result['path']) {
                        try {
                            $fileUploadService->delete($oldPath);
                        } catch (\Throwable) {
                        }
                    }
                }
            }

            DB::commit();
            return response()->json([
                'success' => true,
                'message' => 'Payment updated successfully',
                'payment' => $payment->fresh()->load('items'),
            ]);
        } catch (\Throwable $th) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to update payment',
                'error' => $th->getMessage(),
            ], 500);
        }
    }
}