<?php

namespace App\Http\Controllers\AR;

use App\Http\Controllers\Controller;
use App\Models\AR\FeesUpload;
use App\Models\LedgerVouchers;
use App\Models\Settings\Company;
use App\Models\Settings\LedgerDetails;
use App\Services\ActivityLogService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class FeesUploadController extends Controller
{
    public function index()
    {
        $this->authorize('access', 'fees-upload.index');

        $collection = FeesUpload::join('company', 'fees_uploads.company_id', '=', 'company.id')
            ->authorizedCompanies('company_id')
            ->select('fees_uploads.*')
            ->with('createdBy', 'updatedBy', 'company')
            ->filter();

        return to_json([
            'collection' => $collection,
        ]);
    }

    public function create()
    {
        $this->authorize('access', 'fees-upload.create');

        $company_id = request()->session()->get('company');
        $company = Company::select('id', 'name', 'store_number')
            ->where('id', $company_id)
            ->selectRaw('CONCAT(store_number, " - ", name) as name')
            ->first();

        return to_json([
            'form' => [
                'company_id' => $company->id,
                'company' => $company,
                'date' => now()->toDateString(),
                'ddd_cash' => 0.00,
                'ez_cater' => 0.00,
                'meal_deal' => 0.00,
                'visa' => 0.00,
                'amex' => 0.00,
                'total_amount' => 0.00,
            ],
        ]);
    }

    public function store(Request $request)
    {
        $this->authorize('access', 'fees-upload.create');

        $validated = $request->validate([
            'company_id' => 'required|integer|exists:company,id',
            'date' => 'required|date',
            'visa' => 'required|numeric|min:0',
            'amex' => 'required|numeric|min:0',
            'doordash' => 'required|numeric|min:0',
            'ddc_doordash' => 'required|numeric|min:0',
            'uber' => 'required|numeric|min:0',
            'grubhub' => 'required|numeric|min:0',
            'ddd_cash' => 'required|numeric|min:0',
            'ez_cater' => 'required|numeric|min:0',
            'meal_deal' => 'required|numeric|min:0',
        ]);

        DB::beginTransaction();
        try {
            $feesUpload = FeesUpload::create($validated);
            $dddCashLedger = LedgerDetails::where('code', '1001.49')->where('company_id', $validated['company_id']) ->pluck('ledger_id', 'company_id')->toArray();
            $ezCaterLedger = LedgerDetails::where('code', '1001.53')->where('company_id', $validated['company_id'])->pluck('ledger_id', 'company_id')->toArray();
            $mealDealLedger = LedgerDetails::where('code', '1001.04')->where('company_id', $validated['company_id'])->pluck('ledger_id', 'company_id')->toArray();
            $visaLedger = LedgerDetails::where('code', '1001.01')->where('company_id', $validated['company_id'])->pluck('ledger_id', 'company_id')->toArray();
            $amexLedger = LedgerDetails::where('code', '1001.02')->where('company_id', $validated['company_id'])->pluck('ledger_id', 'company_id')->toArray();
            
            
            $doordashLedger = LedgerDetails::where('code', '1001.52')->where('company_id', $validated['company_id'])->pluck('ledger_id', 'company_id')->toArray();
            $uberLedger = LedgerDetails::where('code', '1001.50')->where('company_id', $validated['company_id'])->pluck('ledger_id', 'company_id')->toArray();
            $grubhubLedger = LedgerDetails::where('code', '1001.51')->where('company_id', $validated['company_id'])->pluck('ledger_id', 'company_id')->toArray();

            $ledgerVouchersArray = [];
            isset($dddCashLedger[$validated['company_id']]) && $ledgerVouchersArray[] = [
                'company_id' => $validated['company_id'],
                'ledger_id' => $dddCashLedger[$validated['company_id']],
                'amount' =>  floatval($validated['ddd_cash'] ?? 0),
                'debit' => 0,
                'credit' => floatval($validated['ddd_cash'] ?? 0),
                'voucher_id' => $feesUpload->id,
            ];
            isset($ezCaterLedger[$validated['company_id']]) && $ledgerVouchersArray[] = [
                'company_id' => $validated['company_id'],
                'ledger_id' => $ezCaterLedger[$validated['company_id']],
                'amount' =>  floatval($validated['ez_cater'] ?? 0),
                'debit' => 0,
                'credit' => floatval($validated['ez_cater'] ?? 0),
                'voucher_id' => $feesUpload->id,
            ];
            isset($mealDealLedger[$validated['company_id']]) && $ledgerVouchersArray[] = [
                'company_id' => $validated['company_id'],
                'ledger_id' => $mealDealLedger[$validated['company_id']],
                'amount' =>  floatval($validated['meal_deal'] ?? 0),
                'debit' => 0,
                'credit' => floatval($validated['meal_deal'] ?? 0),
                'voucher_id' => $feesUpload->id,
            ];
            isset($visaLedger[$validated['company_id']]) && $ledgerVouchersArray[] = [
                'company_id' => $validated['company_id'],
                'ledger_id' => $visaLedger[$validated['company_id']],
                'amount' =>  floatval($validated['visa'] ?? 0),
                'debit' => 0,
                'credit' => floatval($validated['visa'] ?? 0),
                'voucher_id' => $feesUpload->id,
            ];
            isset($amexLedger[$validated['company_id']]) && $ledgerVouchersArray[] = [
                'company_id' => $validated['company_id'],
                'ledger_id' => $amexLedger[$validated['company_id']],
                'amount' =>  floatval($validated['amex'] ?? 0),
                'debit' => 0,
                'credit' => floatval($validated['amex'] ?? 0),
                'voucher_id' => $feesUpload->id,
            ];
            isset($doordashLedger[$validated['company_id']]) && $ledgerVouchersArray[] = [
                'company_id' => $validated['company_id'],
                'ledger_id' => $doordashLedger[$validated['company_id']],
                'amount' =>  floatval($validated['doordash'] ?? 0),
                'debit' => 0,
                'credit' => floatval($validated['doordash'] ?? 0),
                'voucher_id' => $feesUpload->id,
            ];
            isset($doordashLedger[$validated['company_id']]) && $ledgerVouchersArray[] = [
                'company_id' => $validated['company_id'],
                'ledger_id' => $doordashLedger[$validated['company_id']],
                'amount' =>  floatval($validated['ddc_doordash'] ?? 0),
                'debit' => 0,
                'credit' => floatval($validated['ddc_doordash'] ?? 0),
                'voucher_id' => $feesUpload->id,
                'voucher_type' => 'ddc_doordash',
            ];
            isset($uberLedger[$validated['company_id']]) && $ledgerVouchersArray[] = [  
                'company_id' => $validated['company_id'],
                'ledger_id' => $uberLedger[$validated['company_id']],
                'amount' =>  floatval($validated['uber'] ?? 0),
                'debit' => 0,
                'credit' => floatval($validated['uber'] ?? 0),
                'voucher_id' => $feesUpload->id,
            ];

            isset($grubhubLedger[$validated['company_id']]) && $ledgerVouchersArray[] = [
                'company_id' => $validated['company_id'],
                'ledger_id' => $grubhubLedger[$validated['company_id']],
                'amount' =>  floatval($validated['grubhub'] ?? 0),
                'debit' => 0,
                'credit' => floatval($validated['grubhub'] ?? 0),
                'voucher_id' => $feesUpload->id,
            ];
            if (!empty($ledgerVouchers)) {
                foreach (array_chunk($ledgerVouchersArray, 300) as $ledgerVoucherChunk) {
                    LedgerVouchers::insert(array_merge($ledgerVoucherChunk, [
                        'voucher_items_id' => null,
                        'voucher_type' => $ledgerVoucherChunk['voucher_type'] ?? 'fees_uploads',
                        'dbtable' => 'fees_uploads',
                        'check_number' => null,
                        'description' => null,
                        'date' => $validated['date'],
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]));
                }
            }

            DB::commit();
            return to_json([
                'saved' => true,
                'id' => $feesUpload->id,
                'message' => 'Fees Upload created successfully',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return to_json([
                'saved' => false,
                'message' => 'Fees Upload creation failed',
            ], 500);
        }
    }

    public function show($id)
    {
        $this->authorize('access', 'fees-upload.show');

        $model = FeesUpload::with(['company', 'createdBy', 'updatedBy'])
            ->authorizedCompanies('company_id')
            ->findOrFail($id);

        return to_json([
            'model' => $model,
        ]);
    }

    public function edit($id, Request $request)
    {
        $this->authorize('access', 'fees-upload.update');

        $form = FeesUpload::with(['company'])
            ->authorizedCompanies('company_id')
            ->findOrFail($id);

        return to_json([
            'form' => $form,
        ]);
    }

    public function update(Request $request, $id)
    {
        $this->authorize('access', 'fees-upload.update');

        $validated = $request->validate([
            'company_id' => 'required|integer|exists:company,id',
            'date' => 'required|date',
            'ddd_cash' => 'required|numeric|min:0',
            'ez_cater' => 'required|numeric|min:0',
            'meal_deal' => 'required|numeric|min:0',
            'visa' => 'required|numeric|min:0',
            'amex' => 'required|numeric|min:0',
            'doordash' => 'required|numeric|min:0',
            'ddc_doordash' => 'required|numeric|min:0',
            'uber' => 'required|numeric|min:0',
            'grubhub' => 'required|numeric|min:0',
        ]);

        DB::beginTransaction();
        try {
            $feesUpload = FeesUpload::authorizedCompanies('company_id')->findOrFail($id);

            $feesUpload->update($validated);
            $dddCashLedger = LedgerDetails::where('code', '1001.49')->pluck('ledger_id', 'company_id')->toArray();
            $ezCaterLedger = LedgerDetails::where('code', '1001.53')->pluck('ledger_id', 'company_id')->toArray();
            $mealDealLedger = LedgerDetails::where('code', '1001.04')->pluck('ledger_id', 'company_id')->toArray();
            $visaLedger = LedgerDetails::where('code', '1001.01')->pluck('ledger_id', 'company_id')->toArray();
            $amexLedger = LedgerDetails::where('code', '1001.02')->pluck('ledger_id', 'company_id')->toArray();
            $doordashLedger = LedgerDetails::where('code', '1001.52')->pluck('ledger_id', 'company_id')->toArray();
            $uberLedger = LedgerDetails::where('code', '1001.50')->pluck('ledger_id', 'company_id')->toArray();
            $grubhubLedger = LedgerDetails::where('code', '1001.51')->pluck('ledger_id', 'company_id')->toArray();
            $ledgerVouchersArray = [];
            isset($dddCashLedger[$validated['company_id']]) && $ledgerVouchersArray[] = [
                'company_id' => $validated['company_id'],
                'ledger_id' => $dddCashLedger[$validated['company_id']],
                'amount' =>  floatval($validated['ddd_cash'] ?? 0),
                'debit' => 0,
                'credit' => floatval($validated['ddd_cash'] ?? 0),
                'voucher_id' => $feesUpload->id,
            ];
            isset($ezCaterLedger[$validated['company_id']]) && $ledgerVouchersArray[] = [
                'company_id' => $validated['company_id'],
                'ledger_id' => $ezCaterLedger[$validated['company_id']],
                'amount' =>  floatval($validated['ez_cater'] ?? 0),
                'debit' => 0,
                'credit' => floatval($validated['ez_cater'] ?? 0),
                'voucher_id' => $feesUpload->id,
            ];
            isset($mealDealLedger[$validated['company_id']]) && $ledgerVouchersArray[] = [
                'company_id' => $validated['company_id'],
                'ledger_id' => $mealDealLedger[$validated['company_id']],
                'amount' =>  floatval($validated['meal_deal'] ?? 0),
                'debit' => 0,
                'credit' => floatval($validated['meal_deal'] ?? 0),
                'voucher_id' => $feesUpload->id,
            ];
            isset($visaLedger[$validated['company_id']]) && $ledgerVouchersArray[] = [
                'company_id' => $validated['company_id'],
                'ledger_id' => $visaLedger[$validated['company_id']],
                'amount' =>  floatval($validated['visa'] ?? 0),
                'debit' => 0,
                'credit' => floatval($validated['visa'] ?? 0),
                'voucher_id' => $feesUpload->id,
            ];
            isset($amexLedger[$validated['company_id']]) && $ledgerVouchersArray[] = [
                'company_id' => $validated['company_id'],
                'ledger_id' => $amexLedger[$validated['company_id']],
                'amount' =>  floatval($validated['amex'] ?? 0),
                'debit' => 0,
                'credit' => floatval($validated['amex'] ?? 0),
                'voucher_id' => $feesUpload->id,
            ];
            isset($doordashLedger[$validated['company_id']]) && $ledgerVouchersArray[] = [
                'company_id' => $validated['company_id'],
                'ledger_id' => $doordashLedger[$validated['company_id']],
                'amount' =>  floatval($validated['doordash'] ?? 0),
                'debit' => 0,
                'credit' => floatval($validated['doordash'] ?? 0),
                'voucher_id' => $feesUpload->id,
            ];
            isset($doordashLedger[$validated['company_id']]) && $ledgerVouchersArray[] = [
                'company_id' => $validated['company_id'],
                'ledger_id' => $doordashLedger[$validated['company_id']],
                'amount' =>  floatval($validated['ddc_doordash'] ?? 0),
                'debit' => 0,
                'credit' => floatval($validated['ddc_doordash'] ?? 0),
                'voucher_id' => $feesUpload->id,
                'voucher_type' => 'ddc_doordash',
            ];
            isset($uberLedger[$validated['company_id']]) && $ledgerVouchersArray[] = [
                'company_id' => $validated['company_id'],
                'ledger_id' => $uberLedger[$validated['company_id']],
                'amount' =>  floatval($validated['uber'] ?? 0),
                'debit' => 0,
                'credit' => floatval($validated['uber'] ?? 0),
                'voucher_id' => $feesUpload->id,
            ];
            isset($grubhubLedger[$validated['company_id']]) && $ledgerVouchersArray[] = [
                'company_id' => $validated['company_id'],
                'ledger_id' => $grubhubLedger[$validated['company_id']],
                'amount' =>  floatval($validated['grubhub'] ?? 0),
                'debit' => 0,
                'credit' => floatval($validated['grubhub'] ?? 0),
                'voucher_id' => $feesUpload->id,
            ];
            LedgerVouchers::where('voucher_id', $feesUpload->id)->where(function ($query) {
                $query->where('voucher_type', 'fees_uploads')
                    ->orWhere('voucher_type', 'ddc_doordash');
            })->delete();

            if (!empty($ledgerVouchersArray)) {
                foreach (array_chunk($ledgerVouchersArray, 300) as $ledgerVoucherChunk) {
                    $defaultLedgerVoucherValues = [
                        'voucher_items_id' => null,
                        'voucher_type' => $ledgerVoucherChunk['voucher_type'] ?? 'fees_uploads',
                        'dbtable' => 'fees_uploads',
                        'check_number' => null,
                        'description' => null,
                        'date' => $validated['date'],
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];

                    $preparedLedgerVoucherChunk = array_map(
                        fn ($ledgerVoucher) => array_merge($defaultLedgerVoucherValues,$ledgerVoucher ),
                        $ledgerVoucherChunk
                    );

                    LedgerVouchers::insert($preparedLedgerVoucherChunk);
                }
            }

            DB::commit();
            return to_json([
                'saved' => true,
                'id' => $feesUpload->id,
                'message' => 'Fees Upload updated successfully',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return to_json([
                'saved' => false,
                'message' => 'Fees Upload update failed',
            ], 500);
        }
    }

    public function destroy($id)
    {
        $this->authorize('access', 'fees-upload.delete');

        DB::beginTransaction();
        try {
            $feesUpload = FeesUpload::authorizedCompanies('company_id')->findOrFail($id);
            LedgerVouchers::where('voucher_id', $feesUpload->id)->where('voucher_type', 'fees_uploads')->delete();
            $feesUpload->delete();

            DB::commit();
            return to_json([
                'deleted' => true,
                'id' => $id,
                'message' => 'Fees Upload deleted successfully',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return to_json([
                'deleted' => false,
                'message' => 'Fees Upload deletion failed',
            ], 500);
        }
    }

    public function upload(Request $request)
    {
        $this->authorize('access', 'fees-upload.create');

        $request->validate([
            'file' => 'required_without:files|file|' . upload_max_file_size_rule(),
            'files' => 'required_without:file|nullable|array',
            'files.*' => 'required|file|' . upload_max_file_size_rule(),
        ]);

        return $this->processUpload($request);
    }

    public function downloadTemplate($template)
    {

        $filePath = storage_path('app/public/templates/'.$template);
        if (!file_exists($filePath)) {
            return response()->json([
                'success' => false,
                'message' => 'Template file not found. Please contact administrator.'
            ], 404);
        }

        return response()->download($filePath, $template);
    }

    public function export(Request $request)
    {
        $this->authorize('access', 'fees-upload.index');

        $query = FeesUpload::join('company', 'fees_uploads.company_id', '=', 'company.id')
            ->authorizedCompanies('company_id')
            ->select('fees_uploads.*')
            ->with('company');

        $collection = $query->export($request->all());

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $headers = [
            'No',
            'Date',
            'Company',
            'DDD Cash',
            'EZ Cater',
            'Meal Deal',
            'Visa',
            'Amex',
            'Doordash',
            'DDC Doordash',
            'Uber',
            'Grubhub',
            'Total Amount',
        ];
        $sheet->fromArray($headers, null, 'A1');
        $sheet->getStyle('A1:N1')->getFont()->setBold(true);
        $sheet->getStyle('A1:N1')->getFill()
            ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
            ->getStartColor()->setARGB('FF4472C4');
        $sheet->getStyle('A1:N1')->getFont()->getColor()->setARGB('FFFFFFFF');

        $row = 2;
        $index = 0;
        foreach ($collection as $item) {
            $index++;
            $sheet->fromArray([
                $index,
                $item->date ? \Carbon\Carbon::parse($item->date)->format('Y-m-d') : '',
                $item->company->name ?? '',
                $item->ddd_cash ?? 0,
                $item->ez_cater ?? 0,
                $item->meal_deal ?? 0,
                $item->visa ?? 0,
                $item->amex ?? 0,
                $item->doordash ?? 0,
                $item->ddc_doordash ?? 0,
                $item->uber ?? 0,
                $item->grubhub ?? 0,
                $item->total_amount ?? 0,
            ], null, 'A' . $row);
            $row++;
        }
        foreach (range('A', 'N') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
        $writer = new Xlsx($spreadsheet);
        $fileName = 'fees_uploads_export_' . date('Y-m-d_His') . '.xlsx';
        $tempFile = tempnam(sys_get_temp_dir(), $fileName);
        $writer->save($tempFile);

        return response()->download($tempFile, $fileName)->deleteFileAfterSend(true);
    }

    protected function processUpload(Request $request)
    {
        DB::beginTransaction();
        try {
            $files = $request->hasFile('files')
                ? (array) $request->file('files')
                : [$request->file('file')];
            $files = array_values(array_filter($files));
            $date = $request->input('date');

            if (empty($files)) {
                DB::rollBack();
                return to_json([
                    'success' => false,
                    'message' => 'No file(s) provided.',
                ], 422);
            }

            $message = [];
            $createdIds = [];
            $updatedIds = [];
            $companiesByStoreNumber = Company::pluck('id', 'store_number')->toArray();
            $existingFees = FeesUpload::where('date', $date)->where('is_imported', true)->get()->keyBy('company_id');
            $dddCashLedger = LedgerDetails::where('code', '1001.49')->pluck('ledger_id', 'company_id')->toArray();
            $ezCaterLedger = LedgerDetails::where('code', '1001.53')->pluck('ledger_id', 'company_id')->toArray();
            $mealDealLedger = LedgerDetails::where('code', '1001.04')->pluck('ledger_id', 'company_id')->toArray();
            $visaLedger = LedgerDetails::where('code', '1001.01')->pluck('ledger_id', 'company_id')->toArray();
            $amexLedger = LedgerDetails::where('code', '1001.02')->pluck('ledger_id', 'company_id')->toArray();
            $doordashLedger = LedgerDetails::where('code', '1001.52')->pluck('ledger_id', 'company_id')->toArray();
            $ddcDoordashLedger = LedgerDetails::where('code', '1001.54')->pluck('ledger_id', 'company_id')->toArray();
            $uberLedger = LedgerDetails::where('code', '1001.50')->pluck('ledger_id', 'company_id')->toArray();
            $grubhubLedger = LedgerDetails::where('code', '1001.51')->pluck('ledger_id', 'company_id')->toArray();

            $ledgerVouchers = [];

            foreach ($files as $the_file) {
                $excelfile = IOFactory::load($the_file->getRealPath());
                $sheet = $excelfile->getActiveSheet();
                $row_limit = $sheet->getHighestDataRow();

                for ($row = 4; $row <= $row_limit; $row++) {
                    $storeNumber = $sheet->getCell('D' . $row)->getValue();
                    $dddCash = $sheet->getCell('I' . $row)->getValue();
                    $ezCater = $sheet->getCell('J' . $row)->getValue();
                    $mealDeal = $sheet->getCell('K' . $row)->getValue();
                    $visa = $sheet->getCell('L' . $row)->getValue();
                    $amex = $sheet->getCell('M' . $row)->getValue();

                    $doordash = $sheet->getCell('F' . $row)->getValue();
                    $ddcDoordash = $sheet->getCell('N' . $row)->getValue();
                    $grubhub = $sheet->getCell('G' . $row)->getValue();
                    $uber = $sheet->getCell('H' . $row)->getValue();
                    if (!$date || !$storeNumber) {
                        continue;
                    }

                    $company = isset($companiesByStoreNumber[$storeNumber]) ? $companiesByStoreNumber[$storeNumber] : null;
                    if (!$company) {
                        $message[] = "Store number {$storeNumber} not found";
                        continue;
                    }
                    $data = [
                        'date' => Carbon::parse($date)->format('Y-m-d'),
                        'company_id' => $company,
                        'ddd_cash' => floatval($dddCash ?? 0),
                        'ez_cater' => floatval($ezCater ?? 0),
                        'meal_deal' => floatval($mealDeal ?? 0),
                        'visa' => floatval($visa ?? 0),
                        'amex' => floatval($amex ?? 0),
                        'doordash' => floatval($doordash ?? 0),
                        'ddc_doordash' => floatval($ddcDoordash ?? 0),
                        'uber' => floatval($uber ?? 0),
                        'grubhub' => floatval($grubhub ?? 0),
                        'total_amount' => floatval($dddCash ?? 0) + floatval($ezCater ?? 0) + floatval($mealDeal ?? 0) + floatval($visa ?? 0) + floatval($amex ?? 0),
                        'is_imported' => true,
                    ];

                    $existing = isset($existingFees[$company]) ? $existingFees[$company] : null;

                    if ($existing) {
                        $existing->update($data);
                        $updatedIds[] = $existing->id;
                        $message[] = "{$storeNumber} - Existing fees for {$date} was updated";
                    } else {
                        $feesUpload = FeesUpload::create($data);
                        $ledgerVouchersArray = [];

                        if (isset($dddCashLedger[$company])) {
                            $ledgerVouchersArray[] = [
                                'company_id' => $company,
                                'ledger_id' => $dddCashLedger[$company],
                                'amount' =>  floatval($dddCash ?? 0),
                                'debit' => 0,
                                'credit' => floatval($dddCash ?? 0),
                                'voucher_id' => $feesUpload->id,
                            ];
                        }
                        if (isset($ezCaterLedger[$company])) {
                            $ledgerVouchersArray[] = [
                                'company_id' => $company,
                                'ledger_id' => $ezCaterLedger[$company],
                                'amount' =>  floatval($ezCater ?? 0),
                                'debit' => 0,
                                'credit' => floatval($ezCater ?? 0),
                            ];
                        }
                        if (isset($mealDealLedger[$company])) {
                            $ledgerVouchersArray[] = [
                                'company_id' => $company,
                                'ledger_id' => $mealDealLedger[$company],
                                'amount' =>  floatval($mealDeal ?? 0),
                                'debit' => 0,
                                'credit' => floatval($mealDeal ?? 0),
                            ];
                        }
                        if (isset($visaLedger[$company])) {
                            $ledgerVouchersArray[] = [
                                'company_id' => $company,
                                'ledger_id' => $visaLedger[$company],
                                'amount' =>  floatval($visa ?? 0),
                                'debit' => 0,
                                'credit' => floatval($visa ?? 0),
                            ];
                        }
                        if (isset($amexLedger[$company])) {
                            $ledgerVouchersArray[] = [
                                'company_id' => $company,
                                'ledger_id' => $amexLedger[$company],
                                'amount' =>  floatval($amex ?? 0),
                                'debit' => 0,
                                'credit' => floatval($amex ?? 0),
                            ];
                        }
                        if (isset($doordashLedger[$company])) {
                            $ledgerVouchersArray[] = [
                                'company_id' => $company,
                                'ledger_id' => $doordashLedger[$company],
                                'amount' =>  floatval($doordash ?? 0),
                                'debit' => 0,
                                'credit' => floatval($doordash ?? 0),
                            ];
                        }
                        if (isset($ddcDoordashLedger[$company])) {
                            $ledgerVouchersArray[] = [
                                'company_id' => $company,
                                'ledger_id' => $ddcDoordashLedger[$company],
                                'amount' =>  floatval($ddcDoordash ?? 0),
                                'debit' => 0,
                                'credit' => floatval($ddcDoordash ?? 0),
                            ];
                        }
                        if (isset($uberLedger[$company])) {
                            $ledgerVouchersArray[] = [
                                'company_id' => $company,
                                'ledger_id' => $uberLedger[$company],
                                'amount' =>  floatval($uber ?? 0),
                                'debit' => 0,
                                'credit' => floatval($uber ?? 0),
                            ];
                        }
                        if (isset($grubhubLedger[$company])) {
                            $ledgerVouchersArray[] = [
                                'company_id' => $company,
                                'ledger_id' => $grubhubLedger[$company],
                                'amount' =>  floatval($grubhub ?? 0),
                                'debit' => 0,
                                'credit' => floatval($grubhub ?? 0),
                            ];
                        }

                        foreach ($ledgerVouchersArray as $ledgerVoucher) {
                            $ledgerVouchers[] = [
                                'company_id' => $company,
                                'ledger_id' => $ledgerVoucher['ledger_id'],
                                'amount' => $ledgerVoucher['amount'],
                                'debit' => $ledgerVoucher['debit'],
                                'credit' => $ledgerVoucher['credit'],
                                'voucher_id' => $feesUpload->id,
                                'voucher_items_id' => null,
                                'voucher_type' => 'fees_uploads',
                                'dbtable' => 'fees_uploads',
                                'check_number' => null,
                                'description' => null,
                                'date' => $date,
                                'created_at' => now(),
                                'updated_at' => now(),
                                'opp_ledger_id' => 0,
                            ];
                        }
                    }
                }
            }
            if (!empty($ledgerVouchers)) {
                foreach (array_chunk($ledgerVouchers, 300) as $ledgerVoucherChunk) {
                    LedgerVouchers::insert($ledgerVoucherChunk);
                }
            }

            $allIds = array_values(array_unique(array_merge($createdIds, $updatedIds)));
            if (count($allIds) > 0) {
                ActivityLogService::logBulkImport(
                    'fees_uploads',
                    [
                        'ids' => $allIds,
                        'created_ids' => array_values(array_unique($createdIds)),
                        'updated_ids' => array_values(array_unique($updatedIds)),
                    ],
                    "Fees upload bulk import"
                );
            }

            DB::commit();
            return to_json([
                'success' => true,
                'message' => 'Fees uploaded successfully',
                'missing' => $message,
            ]);
        } catch (\Exception $e) {
            dd($e);
            DB::rollBack();
            return to_json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
