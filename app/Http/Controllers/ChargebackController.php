<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\ChargeBack\Chargeback;
use App\Models\ChargeBack\ChargebackReasonCode;
use App\Models\ChargeBack\ChargebackEntryMode;
use Illuminate\Validation\Rule;
use App\Models\Upload\UploadDocument;
use App\Models\Upload\UploadFolder;
use App\Models\User;
use App\Services\ActivityLogService;
use App\Services\ChargebackExportService;
use App\Services\ChargebackNotificationService;
use App\Services\FileUploadService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ChargebackController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('access', 'charge-back.index');

        $companyIds = $this->resolveCompanyIdsForUser(
            $request->input('user_id') ? (int) $request->input('user_id') : null
        );

        $collection = Chargeback::join('company', 'chargebacks.company_id', '=', 'company.id')
            ->authorizedCompanies('company_id',false)
            ->when($companyIds !== null, function ($query) use ($companyIds) {
                if (empty($companyIds)) {
                    return $query->whereRaw('1 = 0');
                }

                return $query->whereIn('chargebacks.company_id', $companyIds);
            })
            ->select('chargebacks.*')
            ->with(
                'company',
                'document:id,file_path,file_name',
                'salesReceiptDocument:id,file_path,file_name',
                'createdBy:id,name',
                'updatedBy:id,name',
                'salesReceiptUploadedBy:id,name',
                'submittedBy:id,name',
                'markedAsReceivedBy:id,name',
            )
            ->filter();

        return to_json([
            'collection' => $collection,
        ]);
    }

    public function export(Request $request, ChargebackExportService $exportService)
    {
        $this->authorize('access', 'charge-back.index');

        return $exportService->exportChargebacks($request);
    }

    protected function resolveCompanyIdsForUser(?int $userId): ?array
    {
        if (!$userId) {
            return null;
        }

        $user = User::find($userId);

        if (!$user) {
            return [];
        }

        return $user->getCompaniesArrayAttribute();
    }

    public function create()
    {
        $this->authorize('access', 'charge-back.create');

        return to_json([
            'form' => $this->defaultForm(),
            'options' => $this->formOptions(),
        ]);
    }

    public function summary(Request $request)
    {
        $this->authorize('access', 'charge-back.index');

        $companyIds = $this->resolveCompanyIdsForUser(
            $request->input('user_id') ? (int) $request->input('user_id') : null
        );

        $rows = Chargeback::query()
            ->authorizedCompanies('company_id', false)
            ->when($companyIds !== null, function ($query) use ($companyIds) {
                if (empty($companyIds)) {
                    return $query->whereRaw('1 = 0');
                }

                return $query->whereIn('company_id', $companyIds);
            })
            ->with('company:id,name')
            ->orderBy('processor_due_date')
            ->get();

        $pendingItems = $rows
            ->filter(fn ($item) => data_get($item, 'display_status.key') === 'receipt_pending')
            ->sortBy('processor_due_date')
            ->values();

        $submittedItems = $rows->filter(fn ($item) => data_get($item, 'display_status.key') === 'submitted')->values();
        $creditedItems = $rows->filter(fn ($item) => data_get($item, 'display_status.key') === 'credited')->values();
        $expiredItems = $rows->filter(fn ($item) => data_get($item, 'display_status.key') === 'expired')->values();
        $uploadedLateItems = $rows->filter(fn ($item) => data_get($item, 'display_status.key') === 'uploaded_late')->values();

        $statusColorMap = [
            'receipt_pending' => 'bg-orange-500',
            'uploaded' => 'bg-emerald-500',
            'uploaded_late' => 'bg-orange-600',
            'submitted' => 'bg-purple-500',
            'credited' => 'bg-lime-500',
            'expired' => 'bg-red-500',
        ];

        $amountByStatus = $rows
            ->groupBy(fn ($item) => data_get($item, 'display_status.key', 'receipt_pending'))
            ->map(function ($group, $key) use ($statusColorMap) {
                $first = $group->first();

                return [
                    'key' => $key,
                    'label' => data_get($first, 'display_status.label', 'Receipt Pending'),
                    'count' => $group->count(),
                    'amount' => (float) $group->sum('amount'),
                    'color' => $statusColorMap[$key] ?? 'bg-blue-500',
                ];
            })
            ->sortByDesc('amount')
            ->values();

        return to_json([
            'summary' => [
                'scopeLabel' => 'All ' . $rows->pluck('company_id')->filter()->unique()->count() . ' stores shown',
                'productionCount' => $rows->count(),
                'metrics' => [
                    [
                        'key' => 'total',
                        'label' => 'TOTAL CHARGEBACKS',
                        'amount' => (float) $rows->sum('amount'),
                        'cases' => $rows->count(),
                        'accent' => 'emerald',
                    ],
                    [
                        'key' => 'receipts_pending',
                        'label' => 'RECEIPTS NOT UPLOADED',
                        'amount' => (float) $pendingItems->sum('amount'),
                        'cases' => $pendingItems->count(),
                        'accent' => 'orange',
                    ],
                    [
                        'key' => 'in_dispute',
                        'label' => 'IN DISPUTE / SUBMITTED',
                        'amount' => (float) $submittedItems->sum('amount'),
                        'cases' => $submittedItems->count(),
                        'accent' => 'purple',
                    ],
                    [
                        'key' => 'credit_recovered',
                        'label' => 'CREDIT RECOVERED',
                        'amount' => (float) $creditedItems->sum('amount'),
                        'cases' => $creditedItems->count(),
                        'accent' => 'green',
                    ],
                    [
                        'key' => 'expired_lost',
                        'label' => 'EXPIRED / LOST',
                        'amount' => (float) $expiredItems->sum('amount'),
                        'cases' => $expiredItems->count(),
                        'subLabel' => $expiredItems->count() ? $expiredItems->count() . ' expired' : null,
                        'accent' => 'red',
                    ],
                    [
                        'key' => 'uploaded_late',
                        'label' => 'UPLOADED LATE',
                        'amount' => null,
                        'cases' => $uploadedLateItems->count(),
                        'casesOnly' => true,
                        'accent' => 'blue',
                    ],
                ],
                'amountByStatus' => $amountByStatus,
                'pendingReceipts' => $pendingItems
                    ->take(10)
                    ->map(fn ($item) => [
                        'id' => $item->id,
                        'case_number' => $item->case_number ?: '-',
                        'reference_number' => $item->reference_number ?: '-',
                        'store_name' => data_get($item, 'company.name', '-'),
                        'reason_code' => $item->reason_code ?: '-',
                        'amount' => (float) $item->amount,
                        'card_network' => $item->card_network ?: '-',
                        'card_last_four' => $item->card_last_four ?: '----',
                        'chargeback_received_date' => $item->chargeback_received_date ? Carbon::parse($item->chargeback_received_date)->format('d F Y') : '-',
                        'processor_due_date' => $item->processor_due_date ? Carbon::parse($item->processor_due_date)->format('d F Y') : '-',
                        'status' => data_get($item, 'display_status.label', 'Receipt Pending'),
                        'status_tone' => data_get($item, 'display_status.tone', 'warning'),
                    ])
                    ->values(),
            ],
            'totals' => [
                'sales_receipts' => [
                    'label' => 'PENDING CLAIM',
                    'amount' => (float) $rows->filter(fn ($item) => !empty($item->sales_receipt_document_id) && !$item->submitted)->sum('amount'),
                    'cases' => $rows->filter(fn ($item) => !empty($item->sales_receipt_document_id) && !$item->submitted)->count(),
                ],
                'expired' => [
                    'label' => 'EXPIRED CLAIM',
                    'amount' => (float) $expiredItems->sum('amount'),
                    'cases' => $expiredItems->count(),
                ],
                'reimbursed' => [
                    'label' => 'REIMBURSED CLAIM',
                    'amount' => (float) $rows->filter(fn ($item) => (bool) $item->submitted)->sum('amount'),
                    'cases' => $rows->filter(fn ($item) => (bool) $item->submitted)->count(),
                    'pending_cases' => $rows->filter(fn ($item) => $item->submitted && !$item->marked_as_received)->count(),
                ],
            ],
        ]);
    }

    public function store(Request $request, FileUploadService $fileUploadService)
    {
        $this->authorize('access', 'charge-back.create');

        $validated = $request->validate([
            'case_number' => 'nullable|string|max:255',
            'network_case_number' => 'nullable|string|max:255',
            'reference_number' => 'nullable|string|max:255',
            'company_id' => 'required|integer|exists:company,id',
            'reason_code' => ['nullable', Rule::in(ChargebackReasonCode::validCodes())],
            'amount' => 'required|numeric|min:0',
            'card_network' => 'nullable|in:Visa,Mastercard,Amex,Discover',
            'card_last_four' => 'nullable|digits:4',
            'entry_mode' => ['nullable', Rule::in(ChargebackEntryMode::validNames())],
            'transaction_date' => 'nullable|date',
            'chargeback_received_date' => 'nullable|date',
            'processor_due_date' => 'nullable|date',
            'notes' => 'nullable|string',
            'dispute_document' => 'required|file|' . upload_max_file_size_rule() . '|mimes:pdf,jpg,jpeg,png,doc,docx,xls,xlsx',
        ],[
            'company_id.required' => 'The company field is required.',
        ]);

        DB::beginTransaction();

        try {
            $documentId = null;

            if ($request->hasFile('dispute_document')) {
                $folder = UploadFolder::whereRaw('LOWER(name) = ?', ['charge back'])->first();

                if (!$folder) {
                    $folder = UploadFolder::create([
                        'name' => 'Charge Back',
                        'hide' => true,
                    ]);
                }

                $file = $request->file('dispute_document');
                $fileName = time() . '_' . $file->getClientOriginalName();
                $result = $fileUploadService->store(
                    $file,
                    'uploaded-documents/' . $folder->name,
                );

                $document = UploadDocument::create([
                    'folder_id' => $folder->id,
                    'company_id' => $validated['company_id'],
                    'name' => 'Chargeback Dispute - ' . ($validated['case_number'] ?: $validated['reference_number'] ?: 'Document'),
                    'file_path' => $result['path'],
                    'file_name' => $fileName,
                    'file_type' => $file->getClientMimeType(),
                    'file_size' => $file->getSize(),
                    'uploaded_by' => Auth::id(),
                    'deletable' => false,
                ]);

                $documentId = $document->id;
            }

            $chargeback = new Chargeback([
                ...$validated,
                'document_id' => $documentId,
            ]);
            $chargeback->skipActivityLog = true;
            $chargeback->save();

            DB::commit();

            $chargeback = $this->loadChargebackRelations($chargeback);

            ActivityLogService::logCreate(
                'chargebacks',
                (int) $chargeback->id,
                $chargeback->getAttributes(),
                'Chargeback created'
            );
            ChargebackNotificationService::notifyCreated($chargeback);

            return to_json([
                'saved' => true,
                'message' => 'Chargeback saved successfully.',
                'model' => $chargeback,
                'form' => $this->defaultForm(),
            ]);
        } catch (\Throwable $th) {
            DB::rollBack();

            report($th);

            return to_json([
                'saved' => false,
                'message' => 'Failed to save chargeback.',
                'error' => $th->getMessage(),
            ], 500);
        }
    }

    public function update(Request $request, Chargeback $chargeback, FileUploadService $fileUploadService)
    {
        $this->authorize('access', 'charge-back.update');

        if ($chargeback->submitted || $chargeback->marked_as_received) {
            return to_json([
                'saved' => false,
                'message' => 'Only unsubmitted chargebacks can be edited.',
            ], 422);
        }

        $validated = $request->validate([
            'case_number' => 'nullable|string|max:255',
            'network_case_number' => 'nullable|string|max:255',
            'reference_number' => 'nullable|string|max:255',
            'company_id' => 'required|integer|exists:company,id',
            'reason_code' => ['nullable', Rule::in(ChargebackReasonCode::validCodes())],
            'amount' => 'required|numeric|min:0',
            'card_network' => 'nullable|in:Visa,Mastercard,Amex,Discover',
            'card_last_four' => 'nullable|digits:4',
            'entry_mode' => ['nullable', Rule::in(ChargebackEntryMode::validNames())],
            'transaction_date' => 'nullable|date',
            'chargeback_received_date' => 'nullable|date',
            'processor_due_date' => 'nullable|date',
            'notes' => 'nullable|string',
            'dispute_document' => 'nullable|file|' . upload_max_file_size_rule() . '|mimes:pdf,jpg,jpeg,png,doc,docx,xls,xlsx',
        ]);

        DB::beginTransaction();

        try {
            $documentId = $chargeback->document_id;

            if ($request->hasFile('dispute_document')) {
                $folder = UploadFolder::whereRaw('LOWER(name) = ?', ['charge back'])->first();

                if (!$folder) {
                    $folder = UploadFolder::create([
                        'name' => 'Charge Back',
                        'hide' => true,
                    ]);
                }

                $file = $request->file('dispute_document');
                $fileName = time() . '_' . $file->getClientOriginalName();
                $result = $fileUploadService->store(
                    $file,
                    'uploaded-documents/' . $folder->name,
                );

                $document = UploadDocument::create([
                    'folder_id' => $folder->id,
                    'company_id' => $validated['company_id'],
                    'name' => 'Chargeback Dispute - ' . ($validated['case_number'] ?: $validated['reference_number'] ?: 'Document'),
                    'file_path' => $result['path'],
                    'file_name' => $fileName,
                    'file_type' => $file->getClientMimeType(),
                    'file_size' => $file->getSize(),
                    'uploaded_by' => Auth::id(),
                    'deletable' => false,
                ]);

                $documentId = $document->id;
            }

            $oldValues = $chargeback->getAttributes();
            $chargeback->skipActivityLog = true;
            $chargeback->update([
                ...$validated,
                'document_id' => $documentId,
            ]);

            DB::commit();

            $chargeback = $this->loadChargebackRelations($chargeback->fresh());

            $this->logChargebackUpdate($chargeback, $oldValues, 'Chargeback updated');
            ChargebackNotificationService::notifyUpdated($chargeback);

            return to_json([
                'saved' => true,
                'message' => 'Chargeback updated successfully.',
                'model' => $chargeback,
            ]);
        } catch (\Throwable $th) {
            DB::rollBack();

            report($th);

            return to_json([
                'saved' => false,
                'message' => 'Failed to update chargeback.',
                'error' => $th->getMessage(),
            ], 500);
        }
    }

    public function uploadSalesReceipt(Request $request, Chargeback $chargeback, FileUploadService $fileUploadService)
    {
        $this->authorize('access', 'charge-back.upload-receipt');

        $validated = $request->validate([
            'sales_receipt' => 'required|file|' . upload_max_file_size_rule() . '|mimes:pdf,jpg,jpeg,png,doc,docx,xls,xlsx',
        ]);

        if ($chargeback->submitted || $chargeback->marked_as_received) {
            return to_json([
                'saved' => false,
                'message' => 'Sales receipt cannot be uploaded for a submitted or credited chargeback.',
            ], 422);
        }

        $isReupload = !empty($chargeback->sales_receipt_document_id);

        DB::beginTransaction();

        try {
            $folder = UploadFolder::whereRaw('LOWER(name) = ?', ['sales receipt'])->first();

            if (!$folder) {
                $folder = UploadFolder::create([
                    'name' => 'Sales Receipt',
                    'hide' => true,
                ]);
            }

            $file = $request->file('sales_receipt');
            $fileName = time() . '_' . $file->getClientOriginalName();
            $result = $fileUploadService->store(
                $file,
                'uploaded-documents/' . $folder->name,
            );

            $document = UploadDocument::create([
                'folder_id' => $folder->id,
                'company_id' => $chargeback->company_id,
                'name' => 'Chargeback Sales Receipt - ' . ($chargeback->case_number ?: $chargeback->reference_number ?: 'Document'),
                'file_path' => $result['path'],
                'file_name' => $fileName,
                'file_type' => $file->getClientMimeType(),
                'file_size' => $file->getSize(),
                'uploaded_by' => Auth::id(),
                'deletable' => false,
            ]);

            $oldValues = $chargeback->getAttributes();
            $chargeback->skipActivityLog = true;
            $chargeback->update([
                'sales_receipt_document_id' => $document->id,
                'upload_sales_receipt_date' => now()->toDateString(),
                'sales_receipt_uploaded_by' => Auth::id(),
            ]);

            DB::commit();

            $chargeback = $this->loadChargebackRelations($chargeback->fresh());

            $this->logChargebackUpdate(
                $chargeback,
                $oldValues,
                $isReupload ? 'Sales receipt reuploaded' : 'Sales receipt uploaded'
            );
            ChargebackNotificationService::notifySalesReceiptUploaded($chargeback, $isReupload);

            return to_json([
                'saved' => true,
                'message' => 'Sales receipt uploaded successfully.',
                'model' => $chargeback,
            ]);
        } catch (\Throwable $th) {
            DB::rollBack();

            report($th);

            return to_json([
                'saved' => false,
                'message' => 'Failed to upload sales receipt.',
                'error' => $th->getMessage(),
            ], 500);
        }
    }

    public function submit(Chargeback $chargeback)
    {
        $this->authorize('access', 'charge-back.submit');

        if ($chargeback->marked_as_received) {
            return to_json([
                'saved' => false,
                'message' => 'This chargeback has already been credited.',
            ], 422);
        }

        if (!$chargeback->sales_receipt_document_id) {
            return to_json([
                'saved' => false,
                'message' => 'Upload a sales receipt before submitting to the processor.',
            ], 422);
        }

        if ($chargeback->submitted) {
            return to_json([
                'saved' => false,
                'message' => 'This chargeback has already been submitted.',
            ], 422);
        }

        $oldValues = $chargeback->getAttributes();
        $chargeback->skipActivityLog = true;
        $chargeback->update([
            'submitted' => true,
            'submitted_by' => Auth::id(),
            'submitted_at' => now(),
        ]);

        $chargeback = $this->loadChargebackRelations($chargeback->fresh());

        $this->logChargebackUpdate($chargeback, $oldValues, 'Chargeback submitted to processor');
        ChargebackNotificationService::notifySubmitted($chargeback);

        return to_json([
            'saved' => true,
            'message' => 'Chargeback submitted to processor.',
            'model' => $chargeback,
        ]);
    }

    public function markAsCredited(Request $request, Chargeback $chargeback)
    {
        $this->authorize('access', 'charge-back.mark-as-credited');

        $validated = $request->validate([
            'credited_date' => 'nullable|date',
        ]);

        if (!$chargeback->submitted) {
            return to_json([
                'saved' => false,
                'message' => 'Only submitted chargebacks can be marked as credited.',
            ], 422);
        }

        if ($chargeback->marked_as_received) {
            return to_json([
                'saved' => false,
                'message' => 'This chargeback has already been credited.',
            ], 422);
        }

        $oldValues = $chargeback->getAttributes();
        $chargeback->skipActivityLog = true;
        $chargeback->update([
            'marked_as_received' => true,
            'marked_as_received_by' => Auth::id(),
            'marked_as_received_at' => now(),
            'credited_date' => $validated['credited_date'] ?? now()->toDateString(),
        ]);

        $chargeback = $this->loadChargebackRelations($chargeback->fresh());

        $this->logChargebackUpdate($chargeback, $oldValues, 'Chargeback marked as credited');

        return to_json([
            'saved' => true,
            'message' => 'Chargeback marked as credited.',
            'model' => $chargeback,
        ]);
    }

    protected function chargebackRelationList(): array
    {
        return [
            'company',
            'document:id,file_path,file_name',
            'salesReceiptDocument:id,file_path,file_name',
            'createdBy:id,name',
            'updatedBy:id,name',
            'salesReceiptUploadedBy:id,name',
            'submittedBy:id,name',
            'markedAsReceivedBy:id,name',
        ];
    }

    protected function loadChargebackRelations(Chargeback $chargeback): Chargeback
    {
        return $chargeback->load($this->chargebackRelationList());
    }

    protected function logChargebackUpdate(Chargeback $chargeback, array $oldValues, string $description): void
    {
        ActivityLogService::logUpdate(
            'chargebacks',
            (int) $chargeback->id,
            $oldValues,
            $chargeback->getAttributes(),
            $description
        );
    }

    protected function defaultForm(): array
    {
        return [
            'case_number' => '',
            'network_case_number' => '',
            'reference_number' => '',
            'company_id' => null,
            'workgroup' => null,
            'company' => null,
            'reason_code' => null,
            'amount' => '',
            'card_network' => null,
            'card_last_four' => '',
            'entry_mode' => null,
            'transaction_date' => '',
            'chargeback_received_date' => '',
            'processor_due_date' => '',
            'notes' => '',
        ];
    }

    protected function formOptions(): array
    {
        return [
            'reason_codes' => ChargebackReasonCode::query()
                ->orderBy('code')
                ->get()
                ->map(fn ($item) => [
                    'value' => $item->code,
                    'label' => "{$item->code} - {$item->description}",
                ])
                ->values(),
            'card_networks' => collect(Chargeback::CARD_NETWORKS)
                ->map(fn ($value) => ['value' => $value, 'label' => $value])
                ->values(),
            'entry_modes' => ChargebackEntryMode::query()
                ->orderBy('name')
                ->get()
                ->map(fn ($item) => ['value' => $item->name, 'label' => $item->name])
                ->values(),
        ];
    }
}
