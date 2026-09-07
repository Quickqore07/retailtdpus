<?php

namespace App\Http\Controllers\Upload;

use App\Http\Controllers\Controller;
use App\Models\AP\ApInvoice;
use App\Models\AP\ApItem;
use App\Models\AP\ApPayment;
use App\Models\AP\ApPaymentItem;
use App\Models\Upload\UploadDocument;
use App\Models\Upload\UploadFolder;
use App\Models\Settings\Company;
use App\Models\Settings\Workgroup;
use App\Models\User;
use App\Services\FileUploadService;
use App\Services\Quickqore\QuickqoreInvoiceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use setasign\Fpdi\Fpdi;

class UploadDocumentController extends Controller
{

    private function ensurePermission($user, string $permission)
    {
        return Gate::forUser($user)->allows('access', $permission) || Gate::forUser($user)->allows('sp-access', $permission);
    }

    private function applySorting($query, Request $request)
    {
        $sortBy = $request->get('sort_by', 'created_at');
        $sortDirection = $request->get('sort_direction', 'desc');

        // Validate sort direction
        if (!in_array(strtolower($sortDirection), ['asc', 'desc'])) {
            $sortDirection = 'desc';
        }

        switch ($sortBy) {
            case 'name':
                $query->orderBy('name', $sortDirection);
                break;
            
            case 'folder_id':
                $query->join('upload_folders as folder_sort', 'upload_documents.folder_id', '=', 'folder_sort.id')
                    ->orderBy('folder_sort.name', $sortDirection)
                    ->select('upload_documents.*');
                break;
            
            case 'company_id':
                $query->join('company as company_sort', 'upload_documents.company_id', '=', 'company_sort.id')
                    ->orderBy('company_sort.name', $sortDirection)
                    ->select('upload_documents.*');
                break;
            
            case 'file_type':
                $query->orderBy('file_type', $sortDirection);
                break;
            
            case 'file_size':
                $query->orderBy('file_size', $sortDirection);
                break;
            
            case 'invoice_date':
                $query->leftJoin('ap_invoices as inv_date_sort', 'upload_documents.id', '=', 'inv_date_sort.document_id')
                    ->orderByRaw("COALESCE(inv_date_sort.date, '9999-12-31') $sortDirection")
                    ->select('upload_documents.*');
                break;
            
            case 'due_date':
                $query->leftJoin('ap_invoices as inv_due_sort', 'upload_documents.id', '=', 'inv_due_sort.document_id')
                    ->orderByRaw("COALESCE(inv_due_sort.due_date, '9999-12-31') $sortDirection")
                    ->select('upload_documents.*');
                break;
            
            case 'overdue_days':
                $query->leftJoin('ap_invoices as inv_overdue_sort', 'upload_documents.id', '=', 'inv_overdue_sort.document_id')
                    ->orderByRaw("CASE 
                        WHEN inv_overdue_sort.status = 'paid' THEN 0 
                        WHEN inv_overdue_sort.due_date IS NULL THEN 0 
                        ELSE GREATEST(0, DATEDIFF(CURDATE(), inv_overdue_sort.due_date)) 
                    END $sortDirection")
                    ->select('upload_documents.*');
                break;
            
            case 'created_by':
                $query->join('users as user_sort', 'upload_documents.uploaded_by', '=', 'user_sort.id')
                    ->orderBy('user_sort.name', $sortDirection)
                    ->select('upload_documents.*');
                break;
            
            case 'created_at':
            default:
                $query->orderBy('upload_documents.created_at', $sortDirection);
                break;
        }

        return $query;
    }
    public function getAllDocuments(Request $request)
    {
        $user = Auth::guard('upload-portal')->user();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'User not authenticated'], 401);
        }
        
        $user = User::with('role')->find($user->id);
        
        // Check view permission using existing Gate
        if (!$this->ensurePermission($user, 'upload-portal.view')) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to view documents'
            ], 403);
        }
        
        if (isset($user->role) && in_array($user->role->name, ['admin', 'superadmin'])) {
            $companyIds = Company::pluck('id')->toArray();
            $folderIds = UploadFolder::pluck('id')->toArray();
        } else {
            $companyIds = $user->companiesArray ?? [];
            $folderIds = $user->folder_access && count($user->folder_access) > 0 ? $user->folder_access : $user->role->folder_access ?? [];
        }

        $query = UploadDocument::whereIn('upload_documents.company_id', $companyIds)
            ->whereIn('upload_documents.folder_id', $folderIds)
            ->with(['company', 'folder', 'createdBy', 'apInvoice']);

        if ($request->has('folder_id') && $request->folder_id) {
            $query->where('upload_documents.folder_id', $request->folder_id);
        }

        if ($request->has('company_id') && $request->company_id) {
            $query->where('upload_documents.company_id', $request->company_id);
        }

        if ($request->has('name') && $request->name) {
            $query->where('upload_documents.name', 'like', '%' . $request->name . '%');
        }

        if ($request->has('date_from') && $request->date_from) {
            $query->whereDate('upload_documents.created_at', '>=', $request->date_from);
        }

        if ($request->has('date_to') && $request->date_to) {
            $query->whereDate('upload_documents.created_at', '<=', $request->date_to);
        }

        if ($request->has('status') && $request->status) {
            $query->whereHas('apInvoice', function ($q) use ($request) {
                $q->where('ap_invoices.status', strtolower($request->status));
            });
        }

        $this->applySorting($query, $request);

        $perPage = $request->get('per_page', 25);
        $page = $request->get('page', 1);
        
        $documents = $query->paginate($perPage, ['*'], 'page', $page);

        return response()->json([
            'success' => true,
            'documents' => $documents->items(),
            'pagination' => [
                'current_page' => $documents->currentPage(),
                'last_page' => $documents->lastPage(),
                'per_page' => $documents->perPage(),
                'total' => $documents->total(),
                'from' => $documents->firstItem(),
                'to' => $documents->lastItem(),
                'has_prev' => $documents->currentPage() > 1,
                'has_next' => $documents->hasMorePages(),
            ]
        ]);
    }

    public function index(Request $request, $folderId)
    {
        $folder = UploadFolder::find($folderId);
        if (!$folder) {
            return response()->json(['success' => false, 'message' => 'Folder not found'], 404);
        }

        $user = Auth::guard('upload-portal')->user();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'User not authenticated'], 401);
        }
        
        $user = User::with('role')->find($user->id);
        
        // Check view permission using existing Gate
        if (!$this->ensurePermission($user, 'upload-portal.view')) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to view documents'
            ], 403);
        }
        
        if (isset($user->role) && in_array($user->role->name, ['admin', 'superadmin'])) {
            $companyIds = Company::pluck('id')->toArray();
        } else {
            $companyIds = $user->companiesArray ?? [];
        }

        $query = UploadDocument::where('upload_documents.folder_id', $folderId)
            ->whereIn('upload_documents.company_id', $companyIds)
            ->with(['company', 'createdBy', 'apInvoice', 'folder:id,name,is_invoice,is_check']);

        if ($request->has('company_id') && $request->company_id) {
            $query->where('upload_documents.company_id', $request->company_id);
        }

        if ($request->has('name') && $request->name) {
            $query->where('upload_documents.name', 'like', '%' . $request->name . '%');
        }

        if ($request->has('date_from') && $request->date_from) {
            $query->whereDate('upload_documents.created_at', '>=', $request->date_from);
        }

        if ($request->has('date_to') && $request->date_to) {
            $query->whereDate('upload_documents.created_at', '<=', $request->date_to);
        }

        if ($request->has('due_date_from') && $request->due_date_from) {
            $query->whereHas('apInvoice', function ($q) use ($request) {
                $q->whereDate('ap_invoices.due_date', '>=', $request->due_date_from);
            });
        }

        if ($request->has('due_date_to') && $request->due_date_to) {
            $query->whereHas('apInvoice', function ($q) use ($request) {
                $q->whereDate('ap_invoices.due_date', '<=', $request->due_date_to);
            });
        }

        if ($request->has('status') && $request->status) {
            $query->where(function ($q) use ($request) {
                $q->whereHas('apInvoice', function ($q) use ($request) {
                    $q->where('ap_invoices.status', strtolower($request->status));
                });
                $q->orWhere('upload_documents.invoice_status', strtolower($request->status));
            });
        }

        $this->applySorting($query, $request);

        $perPage = $request->get('per_page', 25);
        $page = $request->get('page', 1);
        
        $documents = $query->paginate($perPage, ['*'], 'page', $page);

        return response()->json([
            'success' => true,
            'documents' => $documents->items(),
            'folder' => $folder,
            'pagination' => [
                'current_page' => $documents->currentPage(),
                'last_page' => $documents->lastPage(),
                'per_page' => $documents->perPage(),
                'total' => $documents->total(),
                'from' => $documents->firstItem(),
                'to' => $documents->lastItem(),
                'has_prev' => $documents->currentPage() > 1,
                'has_next' => $documents->hasMorePages(),
            ]
        ]);
    }

    public function store(Request $request)
    {
        $user = Auth::guard('upload-portal')->user();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'User not authenticated'], 401);
        }

        $user = User::with('role')->find($user->id);
        
        // Check add permission using existing Gate
        if (!$this->ensurePermission($user, 'upload-portal.add')) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to add documents'
            ], 403);
        }

        $request->validate([
            'folder_id' => 'required|exists:upload_folders,id',
            'name' => 'required|string|max:255',
            'file' => 'required|file|' . upload_max_file_size_rule(),
        ]);
        
        DB::beginTransaction();
        try {
            if (!$request->has('company_id')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Please select a company'
                ], 400);
            }

            $userId = $user->id;

            if ($request->hasFile('file')) {
                $file = $request->file('file');
                $fileName = time() . '_' . $file->getClientOriginalName();
                $folder = UploadFolder::find($request->folder_id);

                $fileUploadService = new FileUploadService();
                $result = $fileUploadService->store(
                    $file,
                    'uploaded-documents/'.$folder->name,
                );

                $document = UploadDocument::create([
                    'folder_id' => $request->folder_id,
                    'company_id' => $request->company_id,
                    'name' => $request->name,
                    'file_path' => $result['path'],
                    'file_name' => $fileName,
                    'file_type' => $file->getClientMimeType(),
                    'file_size' => $file->getSize(),
                    'uploaded_by' => $userId,
                ]);
                
                DB::commit();
                return response()->json([
                    'success' => true,
                    'message' => 'Document uploaded successfully',
                    'document' => $document
                ]);
            }
            
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'No file uploaded'
            ], 400);
        
        } catch (\Throwable $th) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to upload document',
                'error' => $th->getMessage()
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        $user = Auth::guard('upload-portal')->user();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'User not authenticated'], 401);
        }

        $user = User::with('role')->find($user->id);
        
        // Check edit permission using existing Gate
        if (!$this->ensurePermission($user, 'upload-portal.edit')) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to edit documents'
            ], 403);
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'company_id' => 'required|exists:company,id',
        ]);

        DB::beginTransaction();
        try {
            $document = UploadDocument::find($id);
            
            if (!$document) {
                return response()->json([
                    'success' => false,
                    'message' => 'Document not found'
                ], 404);
            }

            // Only allow editing name and company, not the file
            $document->name = $request->name;
            $document->company_id = $request->company_id;
            $document->save();

            DB::commit();
            return response()->json([
                'success' => true,
                'message' => 'Document updated successfully',
                'document' => $document->load(['company', 'folder'])
            ]);
        
        } catch (\Throwable $th) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to update document',
                'error' => $th->getMessage()
            ], 500);
        }
    }

    public function destroy($id)
    {
        $user = Auth::guard('upload-portal')->user();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'User not authenticated'], 401);
        }

        $user = User::with('role')->find($user->id);
        
        // Check delete permission using existing Gate
        if (!$this->ensurePermission($user, 'upload-portal.delete')) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to delete documents'
            ], 403);
        }

        DB::beginTransaction();
        try {
            $apInvoice = ApInvoice::where('document_id', $id)->first();
            if ($apInvoice) {
                if($apInvoice->status == 'paid') {
                    return response()->json([
                        'success' => false,
                        'message' => 'Invoice is already paid'
                    ], 400);
                }
                $workgroup = Workgroup::where('id', $apInvoice->company->workgroup_id)->first();
                (new QuickqoreInvoiceService())->deleteApInvoice(['qq_company_id' => $apInvoice->qq_company_id, 'qq_purchase_id' => $apInvoice->qq_purchase_id, 'company_store_number' => $apInvoice->company->store_number, 'workgroup' => $workgroup ? $workgroup->name : null]);    
                ApItem::where('ap_invoice_id', $apInvoice->id)->delete();
                $apInvoice->delete();
            }
            $apPayment = ApPayment::where('check_document_id', $id)->first();
            if ($apPayment) {
                $workgroup = Workgroup::where('id', $apPayment->company->workgroup_id)->first();
                (new QuickqoreInvoiceService())->deleteApPayment(['qq_company_id' => $apPayment->qq_company_id, 'qq_paybill_id' => $apPayment->qq_paybill_id, 'company_store_number' => $apPayment->company->store_number, 'workgroup' => $workgroup ? $workgroup->name : null]);    
                $invoices = ApPaymentItem::where('ap_payment_id', $apPayment->id)->pluck('bill_id')->toArray();

                $ducumentIds = ApInvoice::whereIn('id', $invoices)->pluck('document_id')->toArray();
                UploadDocument::whereIn('id', $ducumentIds)->update(['invoice_status' => 'approved']);
                ApInvoice::whereIn('id', $invoices)->update(['check_document_id' => null,'status' => 'approved']);

                ApPaymentItem::where('ap_payment_id', $apPayment->id)->delete();
                $apPayment->delete();
            }

            $document = UploadDocument::find($id);
            
            if (!$document) {
                return response()->json([
                    'success' => false,
                    'message' => 'Document not found'
                ], 404);
            }

            // Delete file from storage
            if ($document->file_path) {
                $fileUploadService = new FileUploadService();
                $fileUploadService->delete($document->file_path);
            }

            $document->delete();

            DB::commit();
            return response()->json([
                'success' => true,
                'message' => 'Document deleted successfully'
            ]);
        
        } catch (\Throwable $th) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete document',
                'error' => $th->getMessage()
            ], 500);
        }
    }

    public function download($id)
    {
        $document = UploadDocument::find($id);
        if (!$document) {
            return response()->json(['success' => false, 'message' => 'Document not found'], 404);
        }
        return response()->json(['success' => true, 'url' => $document->file_url]);
    }

    public function getWorkgroups(Request $request)
    {
        $user = Auth::guard('upload-portal')->user();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'User not authenticated'], 401);
        }
        
        $user = User::with('role')->find($user->id);
        
        if (isset($user->role) && in_array($user->role->name, ['admin', 'superadmin'])) {
            $workgroups = Workgroup::onlyUpload()->where('active', true)->get();
        } else {
            $companyIds = $user->companiesArray ?? [];
            
            $workgroupIds = Company::whereIn('id', $companyIds)
                ->where('only_upload', true)
                ->pluck('workgroup_id')
                ->unique()
                ->toArray();
            
            $workgroups = Workgroup::whereIn('id', $workgroupIds)
                ->where('only_upload', true)
                ->where('active', true)
                ->get();
        }

        return response()->json([
            'success' => true,
            'workgroups' => $workgroups
        ]);
    }

    public function getCompanies(Request $request)
    {
        
        $workgroupId = $request->query('workgroup_id');
        
        if (!$workgroupId) {
            return response()->json(['success' => false, 'message' => 'Workgroup ID is required'], 400);
        }

        $user = Auth::guard('upload-portal')->user();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'User not authenticated'], 401);
        }
        
        $user = User::with('role')->find($user->id);
        
        if (isset($user->role) && in_array($user->role->name, ['admin', 'superadmin'])) {
            dd($workgroupId);
            $companies = Company::where('workgroup_id', $workgroupId)
                ->where('only_upload', true)
                ->get();
        } else {
            $companyIds = $user->companiesArray ?? [];
            
            $companies = Company::where('workgroup_id', $workgroupId)
                ->where('only_upload', true)
                ->whereIn('id', $companyIds)
                ->get();
        }

        return response()->json([
            'success' => true,
            'companies' => $companies
        ]);
    }

    public function getAllCompanies(Request $request)
    {
        $user = Auth::guard('upload-portal')->user();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'User not authenticated'], 401);
        }
        
        $user = User::with('role')->find($user->id);
        $companyIds=[];
        $admin = false;
        if (isset($user->role) && in_array($user->role->name, ['admin', 'superadmin'])) {
            $admin = true;
        } else {
            $companyIds = $user->companiesArray ?? [];
        }

        $companies = Company::when(!$admin, function ($query) use ($companyIds) {
                return $query->whereIn('id', $companyIds);
            })
            ->whereHas('workgroup', function ($query) {
                $query->where('active', true);
            })
            ->select('id', 'name', 'store_number', 'workgroup_id')
            ->get()
            ->map(function ($company) {
                return [
                    'id' => $company->id,
                    'name' => $company->store_number . ' - ' . $company->name,
                    'store_number' => $company->store_number,
                    'type' => 'company'
                ];
            });

        return response()->json([
            'success' => true,
            'companies' => $companies
        ]);
    }

    public function printChecksPdf(Request $request)
    {
        if (! class_exists(Fpdi::class)) {
            return response()->json([
                'success' => false,
                'message' => 'PDF merge is not available. Run composer require setasign/fpdi setasign/fpdf on the server.',
            ], 503);
        }

        $request->validate([
            'document_ids' => 'required|array|min:1|max:50',
            'document_ids.*' => 'integer|exists:upload_documents,id',
        ]);

        $user = Auth::guard('upload-portal')->user();
        if (! $user) {
            return response()->json(['success' => false, 'message' => 'User not authenticated'], 401);
        }

        $user = User::with('role')->find($user->id);

        if (!$this->ensurePermission($user, 'upload-portal.view')) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to view documents',
            ], 403);
        }

        if (isset($user->role) && in_array($user->role->name, ['admin', 'superadmin'])) {
            $companyIds = Company::pluck('id')->toArray();
            $folderIds = UploadFolder::pluck('id')->toArray();
        } else {
            $companyIds = $user->companiesArray ?? [];
            $folderIds = $user->folder_access ?? [];
        }

        $ids = array_values(array_unique($request->input('document_ids', [])));

        $documents = UploadDocument::query()
            ->whereIn('id', $ids)
            ->where('file_type', 'application/pdf')
            ->whereIn('company_id', $companyIds)
            ->whereIn('folder_id', $folderIds)
            ->get();

        if ($documents->count() !== count($ids)) {
            return response()->json([
                'success' => false,
                'message' => 'Some documents were not found, are not checks, or you do not have access to them.',
            ], 422);
        }

        $order = array_flip($ids);
        $documents = $documents->sortBy(fn (UploadDocument $d) => $order[$d->id] ?? 999)->values();

        try {
            $binary = $this->mergeCheckDocumentsToPdfBinary($documents);
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => 'Could not build the combined PDF. Ensure each check file is a valid PDF or image.',
            ], 422);
        }

        $filename = 'checks-'.now()->format('Y-m-d-His').'.pdf';

        return response($binary, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }

    /**
     * @param  \Illuminate\Support\Collection<int, UploadDocument>  $documents
     */
    private function mergeCheckDocumentsToPdfBinary($documents): string
    {
        $pdf = new Fpdi;
        $disk = config('filesystems.default') === 's3' ? 's3' : 'public';
        $storage = Storage::disk($disk);

        foreach ($documents as $document) {
            if (empty($document->file_path)) {
                throw new \RuntimeException('Document '.$document->id.' has no file');
            }

            $bytes = $storage->get($document->file_path);
            if ($bytes === null || $bytes === '') {
                throw new \RuntimeException('Empty file for document '.$document->id);
            }

            $mime = strtolower((string) $document->file_type);
            $name = strtolower((string) ($document->file_name ?? ''));
            $isPdf = str_contains($mime, 'pdf') || str_ends_with($name, '.pdf');

            if ($isPdf) {
                $tmp = tempnam(sys_get_temp_dir(), 'upchk');
                if ($tmp === false) {
                    throw new \RuntimeException('Could not create temp file');
                }
                try {
                    file_put_contents($tmp, $bytes);
                    $pageCount = $pdf->setSourceFile($tmp);
                    for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
                        $pdf->AddPage();
                        $tpl = $pdf->importPage($pageNo);
                        $pdf->useTemplate($tpl);
                    }
                } finally {
                    @unlink($tmp);
                }

                continue;
            }

            $ext = '.jpg';
            if (str_contains($mime, 'png') || str_ends_with($name, '.png')) {
                $ext = '.png';
            } elseif (str_contains($mime, 'gif') || str_ends_with($name, '.gif')) {
                $ext = '.gif';
            }

            $tmpBase = tempnam(sys_get_temp_dir(), 'upimg');
            if ($tmpBase === false) {
                throw new \RuntimeException('Could not create temp file');
            }
            @unlink($tmpBase);
            $imgPath = $tmpBase.$ext;
            try {
                file_put_contents($imgPath, $bytes);
                $pdf->AddPage();
                $pdf->Image($imgPath, 10, 10, 190);
            } finally {
                @unlink($imgPath);
            }
        }

        $out = $pdf->Output('S');
        if (! is_string($out) || $out === '') {
            throw new \RuntimeException('PDF output was empty');
        }

        return $out;
    }
    public function viewDocument(Request $request)
    {
        $document = UploadDocument::find($request->id);
        if (!$document) {
            return to_json([
                'success' => false,
                'message' => 'Document not found',
            ], 404);
        }
        return to_json([
            'success' => true,
            'url' => $document->file_url,
        ]);
    }
}
