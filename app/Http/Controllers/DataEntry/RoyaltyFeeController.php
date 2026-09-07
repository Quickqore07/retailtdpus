<?php

namespace App\Http\Controllers\DataEntry;

use App\Http\Controllers\Controller;
use App\Models\DataEntry\RoyaltyFee;
use App\Models\Settings\Company;
use App\Services\ActivityLogService;
use App\Services\FileUploadService;
use App\Services\Quickqore\QuickqoreService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Validation\Rule;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\Process\Process;

class RoyaltyFeeController extends Controller
{
    public function index()
    {
        $this->authorize('access', 'royalty-fees.index');

        $collection = RoyaltyFee::join('company', 'royalty_fees.company_id', '=', 'company.id')
            ->authorizedCompanies('company_id')
            ->select('royalty_fees.*')
            ->with('company', 'createdBy', 'updatedBy')
            ->filter();

        return to_json([
            'collection' => $collection,
        ]);
    }

    public function create(Request $request)
    {
        $this->authorize('access', 'royalty-fees.create');

        $companyId = $request->session()->get('company');
        $company = Company::select('id', 'name', 'store_number')
            ->where('id', $companyId)
            ->selectRaw('CONCAT(store_number, " - ", name) as name')
            ->first();

        return to_json([
            'form' => [
                'type' => null,
                'company_id' => $companyId,
                'company' => $company,
                'invoice_number' => '',
                'invoice_date' => now()->toDateString(),
                'due_date' => null,
                'amount' => 0,
                'description' => '',
                'invoice_pdf' => null,
            ],
        ]);
    }

    public function store(Request $request, FileUploadService $fileUploadService)
    {
        $this->authorize('access', 'royalty-fees.create');

        $validated = $this->validatePayload($request, true);

        DB::beginTransaction();
        try {
            $file = $request->file('invoice_pdf');
            if ($file) {
                $result = $fileUploadService->store(
                    $file,
                    RoyaltyFee::storageDirectory($validated['type']),
                    'public',
                    $this->invoicePdfFilename($validated['invoice_number'] ?: null, $file)
                );
                $validated['invoice_pdf'] = $result['path'];
            } else {
                $validated['invoice_pdf'] = null;
            }

            $fee = RoyaltyFee::create($validated);

            /* Quickqore API */
            $company = Company::where('id', $fee->company_id)->first();
            (new QuickqoreService())->handleRoyaltyFee([
                [
                    'store' => $company->store_number,
                    'type' => $fee->type,
                    'amount' => (string) $fee->amount,
                    'date' => $fee->invoice_date,
                    'ref_id' => $fee->id,
                    'workgroup_name' => $company->workgroup->name ?? '',
                ],
            ]);
            /* End Quickqore API */

            DB::commit();

            return to_json([
                'saved' => true,
                'id' => $fee->id,
                'message' => 'Royalty Fee created successfully',
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            info($e);
            return to_json([
                'saved' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function show($id)
    {
        $this->authorize('access', 'royalty-fees.show');

        $model = RoyaltyFee::with('company', 'createdBy', 'updatedBy')
            ->authorizedCompanies('company_id')
            ->findOrFail($id);

        return to_json([
            'model' => $model,
        ]);
    }

    public function edit($id)
    {
        $this->authorize('access', 'royalty-fees.update');

        $form = RoyaltyFee::with('company')
            ->authorizedCompanies('company_id')
            ->findOrFail($id);

        return to_json([
            'form' => $form,
        ]);
    }

    public function update(Request $request, $id, FileUploadService $fileUploadService)
    {
        $this->authorize('access', 'royalty-fees.update');

        $validated = $this->validatePayload($request, false);

        DB::beginTransaction();
        try {
            $fee = RoyaltyFee::authorizedCompanies('company_id')->findOrFail($id);
            $oldPath = $fee->invoice_pdf;

            if ($request->hasFile('invoice_pdf')) {
                $file = $request->file('invoice_pdf');
                $result = $fileUploadService->store(
                    $file,
                    RoyaltyFee::storageDirectory($validated['type']),
                    'public',
                    $this->invoicePdfFilename($validated['invoice_number'] ?: null, $file)
                );
                $validated['invoice_pdf'] = $result['path'];
            }

            $fee->update($validated);

            if (!empty($validated['invoice_pdf']) && $oldPath && $oldPath !== $validated['invoice_pdf']) {
                $fileUploadService->delete($oldPath);
            }

            /* Quickqore API */
            $company = Company::with('workgroup')->where('id', $fee->company_id)->first();
            (new QuickqoreService())->handleRoyaltyFee([
                [
                    'store' => $company->store_number,
                    'type' => $fee->type,
                    'amount' => (string) $fee->amount,
                    'date' => $fee->invoice_date,
                    'ref_id' => $fee->id,
                    'workgroup_name' => $company->workgroup->name ?? '',
                ],
            ]);
            /* End Quickqore API */

            DB::commit();

            return to_json([
                'saved' => true,
                'id' => $fee->id,
                'message' => 'Royalty Fee updated successfully',
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            return to_json([
                'saved' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function destroy($id, FileUploadService $fileUploadService)
    {
        $this->authorize('access', 'royalty-fees.delete');

        DB::beginTransaction();
        try {
            $fee = RoyaltyFee::authorizedCompanies('company_id')->findOrFail($id);
            $path = $fee->invoice_pdf;
            $fee->delete();

            if ($path) {
                $fileUploadService->delete($path);
            }

            DB::commit();

            return to_json([
                'deleted' => true,
                'id' => $id,
                'message' => 'Royalty Fee deleted successfully',
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();

            return to_json([
                'deleted' => false,
                'message' => 'Royalty Fee deletion failed',
            ], 500);
        }
    }

    public function export(Request $request)
    {
        $this->authorize('access', 'royalty-fees.index');

        $collection = RoyaltyFee::join('company', 'royalty_fees.company_id', '=', 'company.id')
            ->authorizedCompanies('company_id')
            ->select('royalty_fees.*')
            ->with('company', 'createdBy')
            ->export($request->all());

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $headers = [
            'Type',
            'Company',
            'Invoice Date',
            'Due Date',
            'Amount',
            'Invoice Number',
            'Description',
            'Created At',
        ];
        $sheet->fromArray($headers, null, 'A1');
        $sheet->getStyle('A1:H1')->getFont()->setBold(true);
        $sheet->getStyle('A1:H1')->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setARGB('FF4472C4');
        $sheet->getStyle('A1:H1')->getFont()->getColor()->setARGB('FFFFFFFF');

        $row = 2;
        foreach ($collection as $fee) {
            $sheet->fromArray([
                $fee->type ?? '',
                $fee->company->name ?? '',
                $fee->invoice_date ? Carbon::parse($fee->invoice_date)->format('m/d/Y') : '',
                $fee->due_date ? Carbon::parse($fee->due_date)->format('m/d/Y') : '',
                $fee->amount ?? 0,
                $fee->invoice_number ?? '',
                $fee->description ?? '',
                $fee->created_at ? Carbon::parse($fee->created_at)->format('m/d/Y H:i:s') : '',
            ], null, 'A' . $row);
            $row++;
        }

        foreach (range('A', 'I') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $writer = new Xlsx($spreadsheet);
        $fileName = 'royalty_fees_export_' . date('Y-m-d_His') . '.xlsx';
        $tempFile = tempnam(sys_get_temp_dir(), $fileName);
        $writer->save($tempFile);

        return response()->download($tempFile, $fileName)->deleteFileAfterSend(true);
    }

    public function downloadExcelSample()
    {
        $this->authorize('access', 'royalty-fees.create');

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Royalty Fees');
        $headers = [
            'Type',
            'Company',
            'Invoice Date',
            'Due Date',
            'Amount',
            'Invoice Number',
            'Description',
            'Created At',
        ];
        $sheet->fromArray($headers, null, 'A1');
        $sheet->getStyle('A1:H1')->getFont()->setBold(true);
        $sheet->getStyle('A1:H1')->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setARGB('FF4472C4');
        $sheet->getStyle('A1:H1')->getFont()->getColor()->setARGB('FFFFFFFF');
        $sheet->fromArray([
            RoyaltyFee::TYPE_ROYALTY,
            '123 - Sample Store',
            now()->format('m/d/Y'),
            now()->addDays(7)->format('m/d/Y'),
            '100.00',
            'INV-1001',
            'Sample royalty fee',
            '',
        ], null, 'A2');

        foreach (range('A', 'H') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $instructionSheet = $spreadsheet->createSheet();
        $instructionSheet->setTitle('Instructions');
        $instructionSheet->fromArray([
            ['ROYALTY FEE EXCEL IMPORT - INSTRUCTIONS'],
            [''],
            ['Use the same columns as the exported file. Created At is ignored.'],
            [''],
            ['Type', 'Required. Advertisement or Royalty.'],
            ['Company', 'Required. Store number, or "store number - company name" (same as export).'],
            ['Invoice Date', 'Required. Format: MM/DD/YYYY'],
            ['Due Date', 'Required. Format: MM/DD/YYYY, on or after invoice date.'],
            ['Amount', 'Required. Numeric amount (0 or greater).'],
            ['Invoice Number', 'Optional. Must be unique when provided.'],
            ['Description', 'Optional.'],
            ['Created At', 'Ignored on import.'],
            [''],
            ['Remove the sample data row before importing your data.'],
        ], null, 'A1');
        $instructionSheet->getColumnDimension('A')->setWidth(20);
        $instructionSheet->getColumnDimension('B')->setWidth(80);
        $instructionSheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);

        $spreadsheet->setActiveSheetIndex(0);

        $writer = new Xlsx($spreadsheet);
        $fileName = 'royalty_fees_import_sample.xlsx';
        $tempFile = tempnam(sys_get_temp_dir(), $fileName);
        $writer->save($tempFile);

        return response()->download($tempFile, $fileName)->deleteFileAfterSend(true);
    }

    public function importExcel(Request $request)
    {
        $this->authorize('access', 'royalty-fees.create');

        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv', upload_max_file_size_rule()],
        ]);

        $spreadsheet = IOFactory::load($request->file('file')->getRealPath());
        $sheet = $spreadsheet->getActiveSheet();
        $highestRow = (int) $sheet->getHighestDataRow();
        $highestColumn = $sheet->getHighestDataColumn();
        $highestColumnIndex = Coordinate::columnIndexFromString($highestColumn);

        if ($highestRow < 2) {
            return to_json([
                'saved' => false,
                'message' => 'The Excel file has no data rows.',
            ], 422);
        }

        $headerMap = [];
        for ($col = 1; $col <= $highestColumnIndex; $col++) {
            $header = strtolower(trim((string) $sheet->getCell(Coordinate::stringFromColumnIndex($col) . '1')->getFormattedValue()));
            $header = preg_replace('/\s+/', ' ', $header);
            if ($header !== '') {
                $headerMap[$header] = $col;
            }
        }

        $requiredHeaders = ['type', 'company', 'invoice date', 'due date', 'amount'];
        $missingHeaders = array_values(array_filter(
            $requiredHeaders,
            fn ($header) => !isset($headerMap[$header])
        ));
        if ($missingHeaders) {
            return to_json([
                'saved' => false,
                'message' => 'Invalid Excel format. Missing columns: ' . implode(', ', $missingHeaders) . '.',
            ], 422);
        }

        $companies = Company::with('workgroup')->get()->keyBy('store_number');
        $authorizedIds = array_flip(authorizedCompanies());
        $imported = 0;
        $messages = [];
        $seenInvoiceNumbers = [];
        $importedIds = [];
        $importedDates = [];
        $qqData = [];
        $rowsToInsert = [];

        for ($row = 2; $row <= $highestRow; $row++) {
            $type = trim((string) $this->excelCellValue($sheet, $headerMap['type'], $row));
            $companyValue = trim((string) $this->excelCellValue($sheet, $headerMap['company'], $row));
            $invoiceDateValue = $this->excelCellValue($sheet, $headerMap['invoice date'], $row);
            $dueDateValue = $this->excelCellValue($sheet, $headerMap['due date'], $row);
            $amountValue = $this->excelCellValue($sheet, $headerMap['amount'], $row);
            $invoiceNumber = RoyaltyFee::normalizeInvoiceNumber((string) ($this->excelCellValue(
                $sheet,
                $headerMap['invoice number'] ?? null,
                $row
            ) ?? ''));
            $description = trim((string) ($this->excelCellValue(
                $sheet,
                $headerMap['description'] ?? null,
                $row
            ) ?? ''));

            if ($type === '' && $companyValue === '' && trim((string) $invoiceDateValue) === '' && trim((string) $amountValue) === '') {
                continue;
            }

            $resolvedType = $this->resolveExcelType($type);
            if (!$resolvedType) {
                $messages[] = "Row {$row}: type must be Advertisement or Royalty.";
                continue;
            }

            $storeNumber = $this->storeNumberFromCompanyCell($companyValue);
            if ($storeNumber === '') {
                $messages[] = "Row {$row}: company / store number is required.";
                continue;
            }

            $company = $companies[$storeNumber] ?? null;
            if (!$company) {
                $messages[] = "Row {$row}: no company found for store number {$storeNumber}.";
                continue;
            }
            if (!isset($authorizedIds[$company->id])) {
                $messages[] = "Row {$row}: you are not authorized for store number {$storeNumber}.";
                continue;
            }

            $invoiceDate = $this->parseExcelDate($invoiceDateValue);
            $dueDate = $this->parseExcelDate($dueDateValue) ?: $invoiceDate;
            if (!$invoiceDate) {
                $messages[] = "Row {$row}: invoice date was not found or is invalid.";
                continue;
            }
            if (!$dueDate) {
                $messages[] = "Row {$row}: due date was not found or is invalid.";
                continue;
            }
            if ($dueDate < $invoiceDate) {
                $messages[] = "Row {$row}: due date must be on or after invoice date.";
                continue;
            }

            $amount = $this->parseExcelAmount($amountValue);
            if ($amount === null) {
                $messages[] = "Row {$row}: amount is required and must be a number.";
                continue;
            }

            if ($invoiceNumber !== '') {
                $invoiceKey = strtolower($invoiceNumber);
                if (isset($seenInvoiceNumbers[$invoiceKey]) || $this->invoiceNumberExists($invoiceNumber)) {
                    $messages[] = "Row {$row}: invoice {$invoiceNumber} already exists.";
                    continue;
                }
                $seenInvoiceNumbers[$invoiceKey] = true;
            }

            $payload = [
                'type' => $resolvedType,
                'company_id' => $company->id,
                'invoice_number' => $invoiceNumber,
                'invoice_date' => $invoiceDate,
                'due_date' => $dueDate,
                'amount' => $amount,
                'description' => $description,
            ];
            $rowsToInsert[] = [
                'payload' => $payload,
                'company' => $company,
            ];
        }

        if (!$rowsToInsert) {
            return to_json([
                'saved' => false,
                'imported' => 0,
                'messages' => $messages,
                'message' => $messages[0] ?? 'No royalty fees were imported.',
            ], 422);
        }

        $isBulk = count($rowsToInsert) > 1;

        DB::beginTransaction();
        try {
            foreach ($rowsToInsert as $rowData) {
                $fee = new RoyaltyFee($rowData['payload']);
                if ($isBulk) {
                    $fee->skipActivityLog = true;
                }
                $fee->save();

                $imported++;
                $importedIds[] = $fee->id;
                $importedDates[] = $rowData['payload']['invoice_date'];
                $qqData[] = [
                    'store' => $rowData['company']->store_number,
                    'type' => $fee->type,
                    'amount' => (string) $fee->amount,
                    'date' => $rowData['payload']['invoice_date'],
                    'ref_id' => $fee->id,
                    'workgroup_name' => $rowData['company']->workgroup->name ?? '',
                ];
            }

            if ($isBulk) {
                $importedDates = array_values(array_unique(array_filter($importedDates)));
                sort($importedDates);
                ActivityLogService::logBulkImport(
                    'royalty_fees',
                    [
                        'ids' => $importedIds,
                        'count' => $imported,
                        'dates' => $importedDates,
                        'date_from' => $importedDates[0] ?? null,
                        'date_to' => $importedDates[count($importedDates) - 1] ?? null,
                        'source' => 'excel',
                        'imported_at' => now()->toDateTimeString(),
                    ],
                    $imported . ' royalty fee(s) imported from Excel'
                );
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            info($e);

            return to_json([
                'saved' => false,
                'message' => 'Royalty fee Excel import failed.',
            ], 500);
        }

        (new QuickqoreService())->handleRoyaltyFee($qqData);

        return to_json([
            'saved' => true,
            'imported' => $imported,
            'messages' => $messages,
            'message' => $imported . ' royalty fee(s) imported successfully'
                . (count($messages) ? '. Some rows were skipped.' : '.'),
        ]);
    }

    public function importPdf(Request $request, FileUploadService $fileUploadService)
    {
        $this->authorize('access', 'royalty-fees.create');
        set_time_limit(900);

        $request->validate([
            'file' => ['required_without:files', 'nullable', 'file', 'mimes:pdf', upload_max_file_size_rule()],
            'files' => ['required_without:file', 'nullable', 'array', 'max:14'],
            'files.*' => ['required', 'file', 'mimes:pdf', upload_max_file_size_rule()],
        ]);

        $files = $request->file('files', []);
        if ($files instanceof \Illuminate\Http\UploadedFile) {
            $files = [$files];
        }
        if (!is_array($files)) {
            $files = [];
        }
        if ($request->hasFile('file')) {
            $files[] = $request->file('file');
        }
        if (count($files) > 14) {
            return to_json([
                'saved' => false,
                'message' => 'You can upload a maximum of 14 PDFs at a time.',
            ], 422);
        }

        $imported = 0;
        $messages = [];
        $seenInvoiceNumbers = [];
        $importedIds = [];
        $importedDates = [];
        $qqData = [];
        $isBulk = count($files) > 1;
        $workDir = storage_path('app/tmp/royalty-fees/' . uniqid('import_', true));
        File::ensureDirectoryExists($workDir);
        $companies = Company::with('workgroup')->get()->keyBy('store_number');

        try {
            foreach ($files as $file) {
                $originalName = $file->getClientOriginalName();
                $localName = uniqid() . '.pdf';
                $localPath = $workDir . DIRECTORY_SEPARATOR . $localName;
                $source = $file->getRealPath();
                if (!$source || !copy($source, $localPath)) {
                    $messages[] = "{$originalName}: could not save uploaded PDF.";
                    continue;
                }

                try {
                    $parsed = $this->extractInvoiceFromPdf($localPath);
                    $storeNumber = $this->normalizeStoreNumber($parsed['store_number'] ?? '');
                    if ($storeNumber === '') {
                        $messages[] = "{$originalName}: could not find store number in Bill To.";
                        continue;
                    }

                    $company = $companies[$storeNumber] ?? null;
                    if (!$company) {
                        $messages[] = "{$originalName}: no company found for store number {$storeNumber}.";
                        continue;
                    }

                    $invoiceNumber = RoyaltyFee::normalizeInvoiceNumber((string) ($parsed['invoice_number'] ?? ''));
                    if ($invoiceNumber === '') {
                        $messages[] = "{$originalName}: invoice number was not found.";
                        continue;
                    }

                    $invoiceKey = strtolower($invoiceNumber);
                    if (isset($seenInvoiceNumbers[$invoiceKey]) || $this->invoiceNumberExists($invoiceNumber)) {
                        $messages[] = "Invoice {$invoiceNumber} already exists.";
                        continue;
                    }
                    $seenInvoiceNumbers[$invoiceKey] = true;

                    $invoiceDate = $this->invoiceDateFromPurchaseOrder($parsed['purchase_order'] ?? null)
                        ?: $this->parseImportDate($parsed['invoice_date'] ?? null);
                    $dueDate = $this->parseImportDate($parsed['due_date'] ?? null) ?: $invoiceDate;
                    if (!$invoiceDate) {
                        $messages[] = "{$originalName}: invoice date was not found.";
                        continue;
                    }

                    $type = $this->resolveImportType($parsed);
                    if (!$type) {
                        $messages[] = "{$originalName}: could not determine type from purchase order or description.";
                        continue;
                    }

                    $payload = [
                        'type' => $type,
                        'company_id' => $company->id,
                        'invoice_number' => $invoiceNumber,
                        'invoice_date' => $invoiceDate,
                        'due_date' => $dueDate,
                        'amount' => (float) ($parsed['amount'] ?? 0),
                        'description' => trim((string) ($parsed['description'] ?? '')),
                    ];

                    $stored = $fileUploadService->store(
                        $file,
                        RoyaltyFee::storageDirectory($type),
                        'public',
                        $this->invoicePdfFilename($invoiceNumber)
                    );
                    $payload['invoice_pdf'] = $stored['path'];

                    try {
                        $fee = new RoyaltyFee($payload);
                        if ($isBulk) {
                            $fee->skipActivityLog = true;
                        }
                        $fee->save();
                        $importedIds[] = $fee->id;
                        $importedDates[] = $invoiceDate;
                        $qqData[] = [
                            'store' => $company->store_number,
                            'type' => $type,
                            'amount' => (string) $payload['amount'],
                            'date' => $invoiceDate,
                            'ref_id' => $fee->id,
                            'workgroup_name' => $company->workgroup->name,
                        ];
                        $imported++;
                    } catch (\Throwable $e) {
                        $fileUploadService->delete($stored['path'] ?? '');
                        throw $e;
                    }
                } catch (\Throwable $e) {
                    $messages[] = "{$originalName}: " . $e->getMessage();
                }
            }
        } finally {
            File::deleteDirectory($workDir);
        }

        if ($imported === 0) {
            return to_json([
                'saved' => false,
                'imported' => 0,
                'messages' => $messages,
                'message' => $messages[0] ?? 'No royalty fees were imported.',
            ], 422);
        }

        if ($isBulk) {
            $importedDates = array_values(array_unique(array_filter($importedDates)));
            sort($importedDates);
            ActivityLogService::logBulkImport(
                'royalty_fees',
                [
                    'ids' => $importedIds,
                    'count' => $imported,
                    'dates' => $importedDates,
                    'date_from' => $importedDates[0] ?? null,
                    'date_to' => $importedDates[count($importedDates) - 1] ?? null,
                    'source' => 'pdf',
                    'imported_at' => now()->toDateTimeString(),
                ],
                $imported . ' royalty fee(s) imported from PDF'
            );
        }

        /* Quickqore API */
        (new QuickqoreService())->handleRoyaltyFee($qqData);
        /* End Quickqore API */

        return to_json([
            'saved' => true,
            'imported' => $imported,
            'messages' => $messages,
            'message' => $imported . ' royalty fee(s) imported successfully'
                . (count($messages) ? '. Some files were skipped.' : '.'),
        ]);
    }

    private function extractInvoiceFromPdf(string $pdfPath): array
    {
        $script = base_path('scripts/royaltyFeeImport.py');
        $process = new Process([$this->pythonBinary(), $script, 'ocr', $pdfPath]);
        $process->setTimeout(180);
        $process->setEnv(['PYTHONIOENCODING' => 'utf-8']);
        $process->run();

        if (!$process->isSuccessful()) {
            $error = trim($process->getErrorOutput() . "\n" . $process->getOutput());
            $lower = strtolower($error);
            info($error);
            if (str_contains($error, 'pdfplumber') || str_contains($error, 'Missing dependency')) {
                throw new \RuntimeException('Python dependency pdfplumber is missing. Run: pip install -r scripts/requirements.txt');
            }
            if (str_contains($lower, 'not found') || str_contains($error, 'WinError 2')) {
                throw new \RuntimeException('Python was not found. Install Python or set PYTHON_BINARY in .env.');
            }
            $short = strlen($error) > 400 ? substr($error, 0, 400) . '…' : $error;
            throw new \RuntimeException($short !== '' ? $short : 'PDF processing failed.');
        }

        $jsonPath = dirname($pdfPath) . DIRECTORY_SEPARATOR
            . pathinfo($pdfPath, PATHINFO_FILENAME)
            . DIRECTORY_SEPARATOR
            . 'invoice_data.json';

        if (is_file($jsonPath)) {
            $data = json_decode(File::get($jsonPath), true);
            if (is_array($data)) {
                return $data;
            }
        }

        $output = $process->getOutput();
        if (preg_match('/__INVOICE_JSON__\s*(\{.*\})/s', $output, $match)) {
            $data = json_decode($match[1], true);
            if (is_array($data)) {
                return $data;
            }
        }

        throw new \RuntimeException('Failed to process PDF. Invoice data was not created.');
    }

    private function pythonBinary(): string
    {
        if (env('APP_ENV') === 'local') {
            return 'python';
        } else {
            return base_path('venv/bin/python');
        }
    }

    private function normalizeStoreNumber(?string $value): string
    {
        $digits = preg_replace('/\D/', '', (string) $value);
        if ($digits === null || $digits === '') {
            return '';
        }

        $normalized = ltrim($digits, '0');

        return $normalized === '' ? '0' : $normalized;
    }

    private function parseImportDate(?string $value): ?string
    {
        if (!$value) {
            return null;
        }

        $value = trim($value);
        foreach (['Y-m-d', 'm/d/Y', 'm-d-Y', 'n/j/Y', 'm/d/y', 'n/j/y'] as $format) {
            try {
                return Carbon::createFromFormat($format, $value)->toDateString();
            } catch (\Throwable $e) {
                continue;
            }
        }

        try {
            return Carbon::parse($value)->toDateString();
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Prefer invoice date from Purchase Order range end date.
     * Examples:
     * - "Roy 6/29 - 7/26" => 7/26 (year inferred from today)
     * - "Roy 12/29 - 1/29" with today 1/30/2027 => end 1/29/2027 (start year 2026 for 12/29)
     */
    private function invoiceDateFromPurchaseOrder(?string $purchaseOrder, ?Carbon $reference = null): ?string
    {
        $purchaseOrder = trim((string) $purchaseOrder);
        if ($purchaseOrder === '') {
            return null;
        }

        if (!preg_match(
            '/(?:Roy|Mktg)\s*(\d{1,2}[\/\-]\d{1,2})\s*[-–]\s*(\d{1,2}[\/\-]\d{1,2})/i',
            $purchaseOrder,
            $matches
        )) {
            return null;
        }

        $startMd = $this->parseMonthDay($matches[1]);
        $endMd = $this->parseMonthDay($matches[2]);
        if (!$startMd || !$endMd) {
            return null;
        }

        $ref = $reference?->copy()->startOfDay() ?? now()->startOfDay();
        $crossesYear = ($startMd['month'] > $endMd['month'])
            || ($startMd['month'] === $endMd['month'] && $startMd['day'] > $endMd['day']);

        try {
            $endThisYear = Carbon::create($ref->year, $endMd['month'], $endMd['day'])->startOfDay();
        } catch (\Throwable $e) {
            return null;
        }

        $endYear = $endThisYear->lte($ref) ? $ref->year : $ref->year - 1;
        $startYear = $crossesYear ? $endYear - 1 : $endYear;

        try {
            // Validate start date year assignment (e.g. 12/29 => previous year on wrap).
            Carbon::create($startYear, $startMd['month'], $startMd['day']);
            return Carbon::create($endYear, $endMd['month'], $endMd['day'])->toDateString();
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function parseMonthDay(?string $value): ?array
    {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }

        foreach (['n/j', 'm/d', 'n-j', 'm-d'] as $format) {
            try {
                $date = Carbon::createFromFormat($format, $value);
                return ['month' => (int) $date->month, 'day' => (int) $date->day];
            } catch (\Throwable $e) {
                continue;
            }
        }

        if (preg_match('/^(\d{1,2})[\/\-](\d{1,2})$/', $value, $matches)) {
            $month = (int) $matches[1];
            $day = (int) $matches[2];
            if ($month >= 1 && $month <= 12 && $day >= 1 && $day <= 31) {
                return ['month' => $month, 'day' => $day];
            }
        }

        return null;
    }

    private function resolveImportType(array $parsed): ?string
    {
        $type = trim((string) ($parsed['type'] ?? ''));
        if (in_array($type, RoyaltyFee::types(), true)) {
            return $type;
        }

        $purchaseOrder = strtolower(trim((string) ($parsed['purchase_order'] ?? '')));
        if (str_starts_with($purchaseOrder, 'roy')) {
            return RoyaltyFee::TYPE_ROYALTY;
        }
        if (str_starts_with($purchaseOrder, 'mktg')) {
            return RoyaltyFee::TYPE_ADVERTISEMENT;
        }

        $description = strtolower(trim((string) ($parsed['description'] ?? '')));
        if (str_contains($description, 'marketing fund')) {
            return RoyaltyFee::TYPE_ADVERTISEMENT;
        }
        if (str_contains($description, 'royalt')) {
            return RoyaltyFee::TYPE_ROYALTY;
        }

        return null;
    }

    private function excelCellValue($sheet, ?int $column, int $row)
    {
        if (!$column) {
            return '';
        }

        $cell = $sheet->getCell(Coordinate::stringFromColumnIndex($column) . $row);
        $value = $cell->getValue();
        if ($value === null || $value === '') {
            return trim((string) $cell->getFormattedValue());
        }

        return $value;
    }

    private function resolveExcelType(?string $type): ?string
    {
        $normalized = strtolower(trim((string) $type));
        if ($normalized === '') {
            return null;
        }

        foreach (RoyaltyFee::types() as $allowed) {
            if (strtolower($allowed) === $normalized) {
                return $allowed;
            }
        }

        return $this->resolveImportType(['type' => $type, 'purchase_order' => $type, 'description' => $type]);
    }

    private function storeNumberFromCompanyCell(?string $value): string
    {
        $value = trim((string) $value);
        if ($value === '') {
            return '';
        }

        if (preg_match('/^\s*(\d+)/', $value, $matches)) {
            return $this->normalizeStoreNumber($matches[1]);
        }

        return $this->normalizeStoreNumber($value);
    }

    private function parseExcelDate($value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if ($value instanceof \DateTimeInterface) {
            return Carbon::parse($value)->toDateString();
        }

        if (is_numeric($value)) {
            try {
                return Carbon::instance(ExcelDate::excelToDateTimeObject((float) $value))->toDateString();
            } catch (\Throwable $e) {
                // fall through to string parsing
            }
        }

        return $this->parseImportDate(trim((string) $value));
    }

    private function parseExcelAmount($value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_numeric($value)) {
            return round((float) $value, 2);
        }

        $cleaned = str_replace([',', '$', ' '], '', trim((string) $value));
        if ($cleaned === '' || !is_numeric($cleaned)) {
            return null;
        }

        return round((float) $cleaned, 2);
    }

    private function invoiceNumberExists(string $invoiceNumber, ?int $ignoreId = null): bool
    {
        return RoyaltyFee::query()
            ->where(function ($query) use ($invoiceNumber) {
                $normalized = RoyaltyFee::normalizeInvoiceNumber($invoiceNumber);
                $query->where('invoice_number', $invoiceNumber)
                    ->orWhere('invoice_number', $normalized)
                    ->orWhereRaw("TRIM(LEADING '0' FROM invoice_number) = ?", [$normalized]);
            })
            ->when($ignoreId, fn($query) => $query->where('id', '!=', $ignoreId))
            ->exists();
    }

    private function invoicePdfFilename(?string $invoiceNumber, $file = null): string
    {
        $extension = 'pdf';
        if ($file) {
            $extension = strtolower($file->getClientOriginalExtension() ?: $file->extension() ?: 'pdf');
        }

        $safe = $invoiceNumber ? (preg_replace('/[^A-Za-z0-9._-]/', '_', $invoiceNumber) ?: '') : '';
        if ($safe === '') {
            $safe = 'invoice_' . uniqid();
        }

        return $safe . '.' . $extension;
    }

    private function validatePayload(Request $request, bool $isCreate): array
    {
        $pdfRule = [
            'nullable',
            'file',
            upload_max_file_size_rule(),
            'mimes:pdf,jpg,jpeg,png',
        ];

        if ($request->filled('invoice_number')) {
            $request->merge([
                'invoice_number' => RoyaltyFee::normalizeInvoiceNumber($request->input('invoice_number')),
            ]);
        }

        $invoiceNumberRules = ['nullable', 'string', 'max:255'];
        if ($request->filled('invoice_number')) {
            $invoiceNumberRules[] = Rule::unique('royalty_fees', 'invoice_number')
                ->ignore($isCreate ? null : $request->route('royalty_fee'));
        }

        $validated = $request->validate([
            'type' => ['required', 'string', Rule::in(RoyaltyFee::types())],
            'company_id' => ['required', 'integer', 'exists:company,id'],
            'invoice_number' => $invoiceNumberRules,
            'invoice_date' => ['required', 'date'],
            'due_date' => ['required', 'date', 'after_or_equal:invoice_date'],
            'amount' => ['required', 'numeric', 'min:0'],
            'description' => ['nullable', 'string'],
            'invoice_pdf' => $pdfRule,
        ], [
            'invoice_number.unique' => 'Invoice is already exist.',
        ]);

        unset($validated['invoice_pdf']);
        $validated['invoice_number'] = $validated['invoice_number'] ?? '';
        $validated['amount'] = (float) $validated['amount'];

        if ($validated['invoice_number'] !== '') {
            $ignoreId = $isCreate ? null : $request->route('royalty_fee');
            if ($this->invoiceNumberExists($validated['invoice_number'], $ignoreId ? (int) $ignoreId : null)) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'invoice_number' => ['Invoice is already exist.'],
                ]);
            }
        }

        return $validated;
    }
}
