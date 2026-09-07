<?php

namespace App\Http\Controllers\DataEntry;

use App\Http\Controllers\Controller;
use App\Models\DataEntry\BankUpload;
use App\Models\Settings\LedgerDetails;
use App\Services\ActivityLogService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;

class BankUploadController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('access', 'bank-upload.index');

        $collection = BankUpload::leftjoin('company', 'bank_uploads.company_id', '=', 'company.id')
            ->select('bank_uploads.date')
            // ->authorizedCompanies('company_id')
            ->addSelect('bank_uploads.bank_id')
            ->selectRaw('SUM(CASE WHEN bank_uploads.amount > 0 THEN bank_uploads.amount ELSE 0 END) as total_credit')
            ->selectRaw('SUM(CASE WHEN bank_uploads.amount < 0 THEN ABS(bank_uploads.amount) ELSE 0 END) as total_debit')
            ->selectRaw('COUNT(*) as transaction_count')
            ->selectRaw('MAX(bank_uploads.created_at) as created_at')
            ->selectRaw('MAX(CASE WHEN bank_uploads.description = "Opening Balance" THEN 1 ELSE 0 END) as is_opening_balance')
            ->groupBy('bank_uploads.date', 'bank_uploads.bank_id')
            ->with('bank')
            ->filter();

        return to_json([
            'collection' => $collection,
        ]);
    }

    public function show($id, Request $request)
    {
        $this->authorize('access', 'bank-upload.show');

        $model = BankUpload::with(['company', 'bank'])
            ->authorizedCompanies('company_id')
            ->findOrFail($id);

        return to_json([
            'model' => $model,
        ]);
    }

    public function detailRecords(Request $request)
    {
        $this->authorize('access', 'bank-upload.index');
        
        $request->validate([
            'date' => 'required|date',
            'bank_id' => 'nullable|integer',
        ]);


        $records = BankUpload::
            where('date', $request->date)
            ->when($request->bank_id && $request->bank_id != 0, function ($query) use ($request) {
                $query->where('bank_id', $request->bank_id);
            })
            ->when($request->bank_id == 0, function ($query) use ($request) {
                $query->whereNull('bank_id');
            })
            // ->authorizedCompanies('company_id')
            ->with(['bank', 'company'])
            ->orderBy('account_number')
            ->orderBy('description')
            ->get();

        return to_json([
            'records' => $records,
        ]);
    }

    public function storeOpeningBalance(Request $request)
    {
        $this->authorize('access', 'bank-upload.create');

        $request->validate([
            'date' => 'required|date',
            'bank_id' => 'required|integer|exists:ledgers,id',
            'amount' => 'required|numeric|not_in:0',
        ]);

        DB::beginTransaction();
        try {
            $bank = \App\Models\Settings\Ledger::findOrFail($request->bank_id);
            
            // Get ledger details for this bank to find account number
            // $ledgerDetail = LedgerDetails::where('ledger_id', $request->bank_id)->first();
            
            // if (!$ledgerDetail) {
            //     return to_json([
            //         'saved' => false,
            //         'message' => 'Bank account details not found',
            //     ], 422);
            // }

            $user = Auth::user();
            
            // Check if Opening Balance already exists for this date and bank
            $existing = BankUpload::where('date', $request->date)
                ->where('bank_id', $request->bank_id)
                ->where('description', 'Opening Balance')
                ->first();

            if ($existing) {
                $existing->update([
                    'amount' => $request->amount,
                    'is_opening_balance' => 1,
                    'updated_by' => $user->id,
                    'updated_at' => now(),
                ]);
                $message = 'Opening Balance updated successfully';
            } else {
                BankUpload::create([
                    'company_id' => 0,
                    'account_number' =>'Opening Balance',
                    'account_name' => $bank->ledger_name ?? '', 
                    'bank_id' => $request->bank_id,
                    'description' => 'Opening Balance',
                    'amount' => $request->amount,
                    'is_opening_balance' => 1,
                    'date' => $request->date,
                    'created_by' => $user->id,
                    'updated_by' => $user->id,
                ]);
                $message = 'Opening Balance saved successfully';
            }

            DB::commit();
            return to_json([
                'saved' => true,
                'message' => $message,
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            return to_json([
                'saved' => false,
                'message' => 'Failed to save Opening Balance: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        $this->authorize('access', 'bank-upload.update');

        $request->validate([
            'amount' => 'required|numeric|not_in:0',
            'description' => 'nullable|string',
        ]);

        DB::beginTransaction();
        try {
            $user = Auth::user();
            
            // Find the Opening Balance record
            $entryRecord = BankUpload::where('id', $id)
                ->first();

            $input = [
                'updated_by' => $user->id,
                'updated_at' => now(),
            ];
            if($request->description && !$entryRecord->is_opening_balance) {
                $input['description'] = $request->description;
            }

            if($entryRecord->is_opening_balance) {
                $input['amount'] = $request->amount;
            }

            $entryRecord->update($input);

            DB::commit();
            return to_json([
                'saved' => true,
                'message' => 'Record updated successfully',
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            return to_json([
                'saved' => false,
                'message' => 'Failed to update record: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function destroy($id)
    {
        $this->authorize('access', 'bank-upload.delete');

        DB::beginTransaction();
        try {
            $upload = BankUpload::authorizedCompanies('company_id')->findOrFail($id);
            $upload->delete();

            DB::commit();
            return to_json([
                'deleted' => true,
                'id' => $id,
                'message' => 'Bank Upload deleted successfully',
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            return to_json([
                'deleted' => false,
                'message' => 'Bank Upload deletion failed',
            ], 500);
        }
    }

    public function destroyMultiple(Request $request)
    {
        $this->authorize('access', 'bank-upload.delete');

        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'required|integer|exists:bank_uploads,id',
        ]);

        DB::beginTransaction();
        try {
            $uploads = BankUpload::authorizedCompanies('company_id')
                ->whereIn('id', $request->ids)
                ->pluck('id')
                ->toArray();
            
            BankUpload::whereIn('id', $uploads)->delete();
            
            DB::commit();
            return to_json([
                'deleted' => true,
                'message' => 'Bank uploads deleted successfully',
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            return to_json([
                'deleted' => false,
                'message' => 'Bank upload deletion failed',
            ], 500);
        }
    }

    /**
     * Delete all bank upload rows for one grouped index row (same date + bank as list/detail views).
     */
    public function destroyGroup(Request $request)
    {
        $this->authorize('access', 'bank-upload.delete');

        $request->validate([
            'date' => 'required|date',
            'bank_id' => 'nullable|integer',
        ]);

        DB::beginTransaction();
        try {
            $query = BankUpload::where('date', $request->date)
                ->when($request->bank_id && $request->bank_id != 0, function ($q) use ($request) {
                    $q->where('bank_id', $request->bank_id);
                })
                ->when($request->bank_id == 0, function ($q) {
                    $q->whereNull('bank_id');
                });

            if (! $query->exists()) {
                DB::rollBack();

                return to_json([
                    'deleted' => false,
                    'message' => 'No matching bank upload records found',
                ], 404);
            }

            $query->delete();

            DB::commit();

            return to_json([
                'deleted' => true,
                'message' => 'Bank upload deleted successfully',
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();

            return to_json([
                'deleted' => false,
                'message' => 'Bank upload deletion failed',
            ], 500);
        }
    }

    public function delete(Request $request, $id)
    {
        $this->authorize('access', 'bank-upload.delete');

        DB::beginTransaction();
        try {
            $deleted = BankUpload::where('id', $id)->first();

            if ($deleted && $deleted->is_opening_balance) {
                $deleted->delete();
                DB::commit();
                return to_json([
                    'deleted' => true,
                    'message' => 'Opening Balance deleted successfully',
                ]);
            } else {
                DB::rollBack();
                return to_json([
                    'deleted' => false,
                    'message' => 'Opening Balance not found',
                ], 404);
            }
        } catch (\Throwable $e) {
            DB::rollBack();
            return to_json([
                'deleted' => false,
                'message' => 'Opening Balance deletion failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function upload(Request $request)
    {
        $this->authorize('access', 'bank-upload.upload');

        $request->validate([
            'file' => 'required_without:files|file|' . upload_max_file_size_rule(),
            'files' => 'required_without:file|nullable|array',
            'files.*' => 'required|file|' . upload_max_file_size_rule(),
            'confirmed' => 'nullable|boolean',
        ]);

        $files = $request->hasFile('files')
            ? (array) $request->file('files')
            : [$request->file('file')];
        $files = array_values(array_filter($files));

        if (empty($files)) {
            $debugInfo = [
                'hasFile_files' => $request->hasFile('files') ? 'yes' : 'no',
                'hasFile_file' => $request->hasFile('file') ? 'yes' : 'no',
                'content_length' => $request->header('Content-Length'),
                'post_max_size' => ini_get('post_max_size'),
                'upload_max_filesize' => ini_get('upload_max_filesize'),
            ];

            info('No files received - Debug Info:', $debugInfo);

            return to_json([
                'success' => false,
                'message' => 'No file(s) provided',
                'debug' => config('app.debug') ? $debugInfo : null,
            ], 422);
        }

        // First pass: return dates for user confirmation before importing
        if (! $request->boolean('confirmed')) {
            try {
                $confirmationDates = [];
                $message = [];

                foreach ($files as $the_file) {
                    try {
                        $excelfile = $this->loadSpreadsheetFromUpload($the_file);
                        $cell_details = $this->getCellDetails($excelfile->getActiveSheet());
                    } catch (\Throwable $e) {
                        $message[] = "Failed to process file {$the_file->getClientOriginalName()}: " . $e->getMessage();
                        continue;
                    }

                    foreach ($cell_details as $key => $cell_detail) {
                        $parsedDate = $this->parseBankUploadDate($cell_detail['date'] ?? null);
                        if ($parsedDate) {
                            $confirmationDates[$parsedDate] = Carbon::parse($parsedDate)->format('m/d/Y');
                        } else {
                            $row = $key + 2;
                            $message[] = "Invalid date format in row {$row}: {$cell_detail['date']}";
                        }
                    }
                }

                ksort($confirmationDates);
                $confirmationDates = array_values($confirmationDates);

                if (empty($confirmationDates)) {
                    return to_json([
                        'saved' => false,
                        'message' => ! empty($message)
                            ? implode('; ', array_values(array_unique($message)))
                            : 'No valid dates found in the uploaded sheet(s).',
                    ], 422);
                }

                return to_json([
                    'needs_confirmation' => true,
                    'dates' => $confirmationDates,
                    'message' => 'Please confirm the dates found in the uploaded sheet(s).',
                    'messages' => array_values(array_unique($message)),
                ]);
            } catch (\Throwable $e) {
                info($e);
                return to_json([
                    'saved' => false,
                    'message' => 'Bank Upload failed: ' . $e->getMessage(),
                ], 500);
            }
        }

        DB::beginTransaction();
        try {
            $ledgerDetails = LedgerDetails::select('account_no', 'company_id', 'ledger_id')->get()->keyBy('account_no');

            $groupedData = [];
            $message = [];
            $dates = [];
            $bank_ids = [];
            $account_numbers = [];

            foreach ($files as $the_file) {
                try {
                    $excelfile = $this->loadSpreadsheetFromUpload($the_file);
                    $sheet = $excelfile->getActiveSheet();
                    $cell_details = $this->getCellDetails($sheet);
                } catch (\Throwable $e) {
                    $message[] = "Failed to process file {$the_file->getClientOriginalName()}: " . $e->getMessage();
                    continue;
                }

                foreach ($cell_details as $key => $cell_detail) {
                    $row = $key + 2;
                    $account_number = $cell_detail['account_number'];

                    $ledgerDetail = $ledgerDetails[$account_number] ?? null;
                    if (is_null($ledgerDetail)) {
                        $message[] = "Account number {$account_number} not found in any company";
                    }

                    $company_id = isset($ledgerDetail->company_id) ? $ledgerDetail->company_id : null;
                    $bank_id = isset($ledgerDetail->ledger_id) ? $ledgerDetail->ledger_id : null;

                    if (is_null($company_id) || is_null($bank_id)) {
                        $message[] = "Account number {$account_number} not found in any company";
                    }

                    $date = $this->parseBankUploadDate($cell_detail['date'] ?? null);

                    if (! $date) {
                        $message[] = "Invalid date format in row {$row}: {$cell_detail['date']}";
                        continue;
                    }

                    $dates[$date] = $date;
                    $bank_ids[$bank_id] = $bank_id;
                    $credit_amount = isset($cell_detail['credit_amount'])
                        ? floatval(str_replace(['$', ',', '₹', '?', ' '], '', $cell_detail['credit_amount']))
                        : 0;

                    $debit_amount = isset($cell_detail['debit_amount'])
                        ? floatval(str_replace(['$', ',', '₹', '?', ' '], '', $cell_detail['debit_amount']))
                        : 0;

                    $amount_field = isset($cell_detail['amount'])
                        ? floatval(str_replace(['$', ',', '₹', '?', ' '], '', $cell_detail['amount']))
                        : 0;

                    $description = $cell_detail['description'] ?? '';

                    // Determine final amount: credit is positive, debit is negative
                    if ($amount_field != 0) {
                        $amount = $amount_field;
                    } elseif ($credit_amount != 0) {
                        $amount = $credit_amount;
                    } elseif ($debit_amount != 0) {
                        $amount = -1 * $debit_amount;
                    } else {
                        continue;
                    }
                    $account_numbers[$account_number] = $account_number;

                    // Group by account number AND description
                    $groupKey = $account_number . '|' . $description;
                    if (isset($groupedData[$date][$groupKey])) {
                        $groupedData[$date][$groupKey]['amount'] += $amount;
                    } else {
                        $groupedData[$date][$groupKey] = [
                            'company_id' => $company_id ?? null,
                            'account_number' => $account_number,
                            'account_name' => $cell_detail['account_name'] ?? '',
                            'bank_id' => $bank_id ?? null,
                            'description' => $description,
                            'amount' => $amount,
                            'date' => $date,
                        ];
                    }
                }

                unset($cell_details, $excelfile, $sheet);
            }

            $user = Auth::user();
            $bankUploadMaxId = BankUpload::max('id') ?? 0;
            $totalInserted = 0;

            BankUpload::whereIn('date', $dates)->whereIn('account_number', $account_numbers)->delete();

            $insertData = [];
            foreach ($groupedData as $date => $groups) {
                foreach ($groups as $groupKey => $data) {
                    $insertData[] = [
                        'company_id' => $data['company_id'] ?? null,
                        'account_number' => $data['account_number'],
                        'account_name' => $data['account_name'],
                        'bank_id' => $data['bank_id'] ?? null,
                        'description' => $data['description'],
                        'amount' => $data['amount'],
                        'date' => $data['date'],
                        'created_by' => $user->id,
                        'updated_by' => $user->id,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];

                    if (count($insertData) >= 300) {
                        BankUpload::insert($insertData);
                        $totalInserted += count($insertData);
                        $insertData = [];
                    }
                }
            }

            if (! empty($insertData)) {
                BankUpload::insert($insertData);
                $totalInserted += count($insertData);
            }

            if ($totalInserted > 0) {
                $insertedIds = BankUpload::where('id', '>', $bankUploadMaxId)->pluck('id')->toArray();
                $uniqueDates = array_values(array_unique($dates));
                ActivityLogService::logBulkImport('bank_uploads', ['ids' => $insertedIds], "Dates: " . implode(', ', $uniqueDates));
                $message[] = $totalInserted . " bank transaction records uploaded successfully";
            } else {
                $message[] = "No valid records found to import";
            }

            unset($insertData, $groupedData, $ledgerDetails);

            DB::commit();
            return to_json([
                'saved' => true,
                'message' => 'Bank Upload completed successfully',
                'messages' => array_values(array_unique($message)),
            ]);

        } catch (\Throwable $e) {
            info($e);
            DB::rollBack();
            $message = $e->getMessage();
            if (str_contains($message, 'Allowed memory size')) {
                $message = 'File is too large. Please upload a smaller file.';
            }
            return to_json([
                'saved' => false,
                'message' => 'Bank Upload failed: ' . $message,
            ], 500);
        }
    }

    protected function loadSpreadsheetFromUpload($the_file)
    {
        $filePath = $the_file->getRealPath();
        $extension = strtolower($the_file->getClientOriginalExtension());

        if (in_array($extension, ['csv', 'txt'])) {
            $content = file_get_contents($filePath);
            $content = str_replace('\\"', '"', $content);
            $content = str_replace(["\r\n", "\r"], "\n", $content);
            $tempPath = storage_path('app/temp_clean_' . uniqid() . '.csv');
            file_put_contents($tempPath, $content);
            $excelfile = IOFactory::load($tempPath);
            @unlink($tempPath);

            return $excelfile;
        }

        return IOFactory::load($filePath);
    }

    /**
     * Parse a sheet date value into Y-m-d, or null if invalid.
     */
    protected function parseBankUploadDate($date): ?string
    {
        if ($date === null || $date === '') {
            return null;
        }

        $date = trim((string) $date);
        $dateFormats = ['m/d/Y', 'Y-m-d', 'm-d-Y', 'd/m/Y'];

        foreach ($dateFormats as $dateFormat) {
            try {
                $arr = preg_split('/[\/\-]/', $date);
                $format = $dateFormat;
                if (count($arr) == 3 && strlen($arr[2]) == 2 && str_contains($format, 'Y')) {
                    $format = str_replace('Y', 'y', $format);
                }

                return Carbon::createFromFormat($format, $date)->format('Y-m-d');
            } catch (\Throwable $e) {
                continue;
            }
        }

        try {
            return Carbon::parse($date)->format('Y-m-d');
        } catch (\Throwable $e) {
            return null;
        }
    }

    protected function getCellDetails($sheet)
    {
        $wantedHeaders = [
            'date' => ['date', 'txn date', 'transaction date', 'trans date', 'posting date', 'ledger date'],
            'account_number' => ['account', 'account number', 'a/c no', 'account #', 'account no'],
            'credit_amount' => ['cr amount', 'credit', 'credit amount', 'deposits'],
            'debit_amount' => ['db amount', 'debit', 'debit amount', 'withdrawals'],
            'description' => ['text field', 'description', 'narration', 'details', 'transaction details', 'transaction description'],  
            'fallback_description' => ['description'],
            'account_name' => ['account name', 'account name', 'account name', 'account name', 'account name'],
            'amount' => ['amount'],
            'dc' => ['debit / credit indicator'],
            'value' => ['value'],
        ];
        
        $headerMap = [];
        $highestColumn = $sheet->getHighestColumn();
        $highestRow = $sheet->getHighestRow();
        $highestColumnIndex = Coordinate::columnIndexFromString($highestColumn);
        
        for ($row = 1; $row <= $highestRow; $row++) {
            for ($col = 1; $col <= $highestColumnIndex; $col++) {
                $columnLetter = Coordinate::stringFromColumnIndex($col);
                try {
                    $header = strtolower(trim(
                        $sheet->getCell($columnLetter . $row)->getFormattedValue()
                    ));
                } catch (\Throwable $e) {
                    continue;
                }
                foreach ($wantedHeaders as $key => $aliases) {
                    if (in_array($header, $aliases)) {
                        $headerMap[$key] = $col;
                    }
                }
            }
            
            if (isset($headerMap['date']) && 
                isset($headerMap['account_number']) && 
                isset($headerMap['description']) && 
                (isset($headerMap['credit_amount']) || isset($headerMap['debit_amount']) || isset($headerMap['amount']) || (isset($headerMap['dc']) && isset($headerMap['value'])))
            ) {
                break;
            }
        }
        if (!isset($headerMap['date']) || !isset($headerMap['account_number']) || !isset($headerMap['description'])) {
            throw new \Exception('Required columns not found. Please ensure the file has Date, Account Number, and Description columns.');
        }

        $cell_details = [];
        for ($dataRow = $row + 1; $dataRow <= $highestRow; $dataRow++) {
            $rowData = [];

            foreach ($headerMap as $field => $colIndex) {
                $columnLetter = Coordinate::stringFromColumnIndex($colIndex);
                try {
                    $value =  $sheet
                    ->getCell($columnLetter . $dataRow)
                    ->getFormattedValue();
                    if (is_string($value)) {
                        $value = str_replace(["\r\n", "\r"], "\n", $value);
                        $value = trim($value);
                    }
                    $rowData[$field] = $value;
                } catch (\Throwable $e) {
                    $rowData[$field] = null;
                }
            }
            if(isset($headerMap['dc']) && isset($headerMap['value'])){
                $rowData['amount'] = $rowData['dc'] == 'C' ? $rowData['value'] : -1 * $rowData['value'];
            }
            if (isset($headerMap['fallback_description']) && $rowData['description'] == '') {
                try {
                    $value = $sheet
                        ->getCell(Coordinate::stringFromColumnIndex($headerMap['fallback_description']) . $dataRow)
                        ->getFormattedValue();
                        if (is_string($value)) {
                            $value = str_replace(["\r\n", "\r"], "\n", $value);
                            $value = trim($value);
                        }
                    $rowData['description'] = $value;
                } catch (\Throwable $e) {
                    $rowData['description'] = '';
                }
            }
            if (array_filter($rowData) && 
                !is_null($rowData['date']) && 
                !str_contains(strtolower($rowData['date']), 'total') && 
                !is_null($rowData['account_number']) && 
                !is_null($rowData['description'])) {
                $cell_details[] = $rowData;
            }
        }
        
        return $cell_details;
    }
}
