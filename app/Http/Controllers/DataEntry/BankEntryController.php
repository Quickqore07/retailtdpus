<?php

namespace App\Http\Controllers\DataEntry;

use App\Http\Controllers\Controller;
use App\Models\AR\PjPaymentItem;
use App\Models\DataEntry\BankEntry;
use App\Models\DataEntry\BankEntryChildAmount;
use App\Models\DataEntry\BankEntryItem;
use App\Models\LedgerVouchers;
use App\Models\Settings\BankRule;
use App\Models\Settings\Company;
use App\Models\Settings\Ledger;
use App\Models\Settings\LedgerDetails;
use App\Services\PjPaymentSettlementService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;


class BankEntryController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('access', 'bank-entry.index');
        $currentCompany = $request->session()->get('company');

        $collection = BankEntry::join('company', 'bank_entries.company_id', '=', 'company.id')
            ->withCount(['items'])
            ->where('company_id', $currentCompany)
            ->authorizedCompanies('company_id')
            ->select('bank_entries.*')
            ->with('bankLedger', 'createdBy', 'updatedBy', 'company')
            ->filter();

        return to_json([
            'collection' => $collection,
        ]);
    }

    public function create(Request $request)
    {
        $this->authorize('access', 'bank-entry.create');

        $companyId = $request->session()->get('company');
        $pjLedgers = getLedgersByCode('pj', $companyId);

        $items = [];
        $company = Company::select('id', 'name', 'store_number')->where('id', request()->session()->get('company'))->selectRaw('CONCAT(store_number, " - ", name) as name')->first();
        
        foreach ($pjLedgers as $ledger) {
            $items[] = [
                'ledger_id' => $ledger->id,
                'ledger' => $ledger,
                'amount' => 0.00,
            ];
        }

        return to_json([
            'form' => [
                'company_id' => $companyId,
                'company' => $company,
                'date' => now()->toDateString(),
                'bank' => null,
                'items' => $items,
                'total_amount' => 0,
            ],
        ]);
    }

    public function store(Request $request)
    {
        $this->authorize('access', 'bank-entry.create');

        $validated = $request->validate([
            'company_id' => 'required|integer|exists:company,id',
            'date' => 'required|date',
            'bank' => 'required|integer|exists:ledgers,id',
            'items' => 'required|array|min:1',
            'items.*.ledger_id' => 'required|integer|exists:ledgers,id',
            'items.*.amount' => 'required|numeric|min:0'
        ]);

        DB::beginTransaction();
        try {
            $totalAmount = collect($validated['items'])->sum(function ($item) {
                return (float) $item['amount'];
            });

            $entry = BankEntry::create([
                'company_id' => $validated['company_id'],
                'date' => $validated['date'],
                'bank' => $validated['bank'],
                'total_amount' => $totalAmount,
            ]);

            $childAmounts = [];
            $items = [];
            $ledgerVouchers = [];
            foreach ($validated['items'] as $item) {
                if ((float) $item['amount'] == 0) {
                    continue;
                }
                $bankEntryItem = BankEntryItem::create([
                    'bank_entry_id' => $entry->id,
                    'ledger_id' => $item['ledger_id'],
                    'amount' => $item['amount'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $childAmounts[] = [
                    'bank_entry_item_id' => $bankEntryItem->id,
                    'deposit' => $item['amount'],
                    'fees' => 0,
                    'total_amount' => $item['amount'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ];

                $ledgerVouchers[] = [
                    'company_id' => $validated['company_id'],
                    'ledger_id' => $item['ledger_id'],
                    'opp_ledger_id' => 0,
                    'amount' => $item['amount'],
                    'debit' => 0,
                    'credit' => $item['amount'],
                    'voucher_id' => $entry->id,
                    'voucher_items_id' => $bankEntryItem->id, 
                    'voucher_type' => 'bank_entries',
                    'dbtable' => 'bank_entries',
                    'check_number' => null,
                    'description' => null,
                    'date' => $entry->date,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
            $ledgerVouchers[] = [
                'company_id' => $validated['company_id'],
                'ledger_id' => $entry->bank,
                'opp_ledger_id' => 0,
                'amount' => -1 *$entry->total_amount,
                'debit' => $entry->total_amount,
                'credit' => 0,
                'voucher_id' => $entry->id,
                'voucher_items_id' => null,
                'voucher_type' => 'bank_entries',
                'dbtable' => 'bank_entries',
                'check_number' => null,
                'description' => null,
                'date' => $entry->date,
                'created_at' => now(),
                'updated_at' => now(),
            ];

            if (!empty($items)) {
                BankEntryItem::insert($items);
            }

            if (!empty($ledgerVouchers)) {
                LedgerVouchers::insert($ledgerVouchers);
            }
            if (!empty($childAmounts)) {
                BankEntryChildAmount::insert($childAmounts);
            }
            DB::commit();
            return to_json([
                'saved' => true,
                'id' => $entry->id,
                'message' => 'Bank Entry created successfully',
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            return to_json([
                'saved' => false,
                'message' => 'Bank Entry creation failed',
            ], 500);
        }
    }

    public function show($id, Request $request)
    {
        $this->authorize('access', 'bank-entry.show');

        $model = BankEntry::with(['company', 'items.ledger', 'bankLedger.ledgerDetails'=>function($query) use ($request){
            $query->select('id', 'code','ledger_id');
        }])
            ->findOrFail($id);

        return to_json([
            'model' => $model,
        ]);
    }

    public function edit($id, Request $request)
    {
        $this->authorize('access', 'bank-entry.update');

        $form = BankEntry::with([
            'company',
            'bankLedger',
            'items.ledger.ledgerDetails' => function ($query) use ($request) {
                $query->select('id', 'code','ledger_id')->where('company_id', $request->session()->get('company'));
            }
        ])
            ->authorizedCompanies('company_id')
            ->findOrFail($id);

        $pjLedgers = getLedgersByCode('pj', $form->company_id);
        foreach ($pjLedgers as $ledger) {
            $item = $form->items->firstWhere('ledger_id', $ledger->id);
            if ($item) {
                $item->amount = $item->amount ?? 0.00;
                if(is_null($item->ledger->ledgerDetails) && !is_null($item->ledger)) {
                    $item->ledger->name = $item->ledger->name;
                } else if(!is_null($item->ledger->ledgerDetails) && !is_null($item->ledger)) {
                    $item->ledger->name = "{$item->ledger->ledgerDetails->code} - {$item->ledger->name}";
                }
            } else {
                $form->items[] = [
                    'ledger_id' => $ledger->id,
                    'ledger' => $ledger,
                    'amount' => 0.00,
                ];
            }
        }

        return to_json([
            'form' => $form,
        ]);
    }

    public function update(Request $request, $id)
    {
        $this->authorize('access', 'bank-entry.update');

        $validated = $request->validate([
            'company_id' => 'required|integer|exists:company,id',
            'date' => 'required|date',
            'bank' => 'required|integer|exists:ledgers,id',
            'items' => 'required|array|min:1',
            'items.*.ledger_id' => 'required|integer|exists:ledgers,id',
            'items.*.amount' => 'required|numeric|min:0',
        ]);

        DB::beginTransaction();
        try {
            $entry = BankEntry::authorizedCompanies('company_id')->findOrFail($id);

            $totalAmount = collect($validated['items'])->sum(function ($item) {
                return (float) $item['amount'];
            });

            $entry->update([
                'company_id' => $validated['company_id'],
                'date' => $validated['date'],
                'bank' => $validated['bank'],
                'total_amount' => $totalAmount,
            ]);

            $bankEntryItems = BankEntryItem::where('bank_entry_id', $entry->id)->pluck('id')->toArray();
            BankEntryChildAmount::whereIn('bank_entry_item_id', $bankEntryItems)->delete();
            BankEntryItem::whereIn('id', $bankEntryItems)->delete();
            LedgerVouchers::where('voucher_id', $entry->id)->where('voucher_type', 'bank_entries')->delete();
            $ledgerVouchers = [];
            $items = [];
            $childAmounts = [];
            foreach ($validated['items'] as $item) {
                if ((float) $item['amount'] == 0) {
                    continue;
                }

                $bankEntryItem = BankEntryItem::create([
                    'bank_entry_id' => $entry->id,
                    'ledger_id' => $item['ledger_id'],
                    'amount' => $item['amount'],
                    'created_at' => now()
                ]);
                $childAmounts[] = [
                    'bank_entry_item_id' => $bankEntryItem->id,
                    'deposit' => $item['amount'],
                    'fees' => 0,
                    'total_amount' => $item['amount'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
                $ledgerVouchers[] = [
                    'company_id' => $validated['company_id'],
                    'ledger_id' => $item['ledger_id'],
                    'opp_ledger_id' => 0,
                    'amount' => $item['amount'],
                    'debit' => 0,
                    'credit' => $item['amount'],
                    'voucher_id' => $entry->id,
                    'voucher_items_id' => $bankEntryItem->id,
                    'voucher_type' => 'bank_entries',
                    'dbtable' => 'bank_entries',
                    'check_number' => null,
                    'description' => null,
                    'date' => $entry->date,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
            $ledgerVouchers[] = [
                'company_id' => $validated['company_id'],
                'ledger_id' => $entry->bank,
                'opp_ledger_id' => 0,
                'amount' => -1 * $entry->total_amount,
                'debit' => $entry->total_amount,
                'credit' => 0,
                'voucher_id' => $entry->id,
                'voucher_items_id' => null,
                'voucher_type' => 'bank_entries',
                'dbtable' => 'bank_entries',
                'check_number' => null,
                'description' => null,
                'date' => $entry->date,
                'created_at' => now(),
                'updated_at' => now(),
            ];

            if (!empty($items)) {
                BankEntryItem::insert($items);
            }

            if (!empty($ledgerVouchers)) {
                LedgerVouchers::insert($ledgerVouchers);
            }
            if (!empty($childAmounts)) {
                BankEntryChildAmount::insert($childAmounts);
            }


            DB::commit();
            return to_json([
                'saved' => true,
                'id' => $entry->id,
                'message' => 'Bank Entry updated successfully',
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            return to_json([
                'saved' => false,
                'message' => 'Bank Entry update failed',
            ], 500);
        }
    }

    public function destroy($id)
    {
        $this->authorize('access', 'bank-entry.delete');

        DB::beginTransaction();
        try {
            $entry = BankEntry::authorizedCompanies('company_id')->findOrFail($id);
            $bankEntryItems = BankEntryItem::where('bank_entry_id', $entry->id)->pluck('id')->toArray();
            BankEntryChildAmount::whereIn('bank_entry_item_id', $bankEntryItems)->delete();
            BankEntryItem::whereIn('id', $bankEntryItems)->delete();
            LedgerVouchers::where('voucher_id', $entry->id)->where('voucher_type', 'bank_entries')->delete();
            $entry->delete();

            DB::commit();
            return to_json([
                'deleted' => true,
                'id' => $id,
                'message' => 'Bank Entry deleted successfully',
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            return to_json([
                'deleted' => false,
                'message' => 'Bank Entry deletion failed',
            ], 500);
        }
    }

    public function destroyMultiple(Request $request)
    {
        $this->authorize('access', 'bank-entry.delete');

        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'required|integer|exists:bank_entries,id',
        ]);

        DB::beginTransaction();
        try {
            $entries = BankEntry::authorizedCompanies('company_id')->whereIn('id', $request->ids)->pluck('id')->toArray();
            $bankEntryItems = BankEntryItem::whereIn('bank_entry_id', $entries)->pluck('id')->toArray();
            BankEntryChildAmount::whereIn('bank_entry_item_id', $bankEntryItems)->delete();
            BankEntryItem::whereIn('id', $bankEntryItems)->delete();
            LedgerVouchers::whereIn('voucher_id', $entries)->where('voucher_type', 'bank_entries')->delete();
            BankEntry::whereIn('id', $entries)->delete();
            DB::commit();
            return to_json([
                'deleted' => true,
                'message' => 'Bank entries deleted successfully',
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            return to_json([
                'deleted' => false,
                'message' => 'Bank entry deletion failed',
            ], 500);
        }
    }

    public function upload(Request $request){
        $this->authorize('access', 'bank-entry.create');

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
            return to_json([
                'success' => false,
                'message' => 'No file(s) provided.',
            ], 422);
        }

        // First pass: return dates for user confirmation before importing
        if (! $request->boolean('confirmed')) {
            try {
                $confirmationDates = [];

                foreach ($files as $the_file) {
                    $excelfile = $this->loadSpreadsheetFromUpload($the_file);
                    $cell_details = $this->getCellDetails($excelfile->getActiveSheet());

                    foreach ($cell_details as $cell_detail) {
                        $parsedDate = $this->parseBankEntryDate($cell_detail['date'] ?? null);
                        if ($parsedDate) {
                            $confirmationDates[$parsedDate] = Carbon::parse($parsedDate)->format('m/d/Y');
                        }
                    }
                }

                ksort($confirmationDates);
                $confirmationDates = array_values($confirmationDates);

                if (empty($confirmationDates)) {
                    return to_json([
                        'saved' => false,
                        'message' => 'No valid dates found in the uploaded sheet(s).',
                    ], 422);
                }

                return to_json([
                    'needs_confirmation' => true,
                    'dates' => $confirmationDates,
                    'message' => 'Please confirm the dates found in the uploaded sheet(s).',
                ]);
            } catch (\Throwable $e) {
                info($e);
                return to_json([
                    'saved' => false,
                    'message' => 'Bank Entry upload failed: ' . $e->getMessage(),
                ], 500);
            }
        }

        DB::beginTransaction();
        try{
            $ledgerDetails = LedgerDetails::whereNotNull('account_no')->where('account_no','!=', '')->get()->keyBy('account_no');
            $ledgers = Ledger::all()->select('id', 'name')->keyBy('id');
            $bankRules = BankRule::with('conditions')->get();
            $companies = Company::all()->select('id', 'name', 'store_number')->keyBy('id');

            $message = [];

            foreach ($files as $the_file) {
                $dateWiseBankEntries = [];

                $excelfile = $this->loadSpreadsheetFromUpload($the_file);
                $sheet     = $excelfile->getActiveSheet();

                $cell_details = $this->getCellDetails($sheet);

                $dates = array_unique(array_column($cell_details, 'date'));
                $dates = array_values(array_filter(array_map(function ($date) {
                    return $this->parseBankEntryDate($date);
                }, $dates)));

                if (empty($dates)) {
                    DB::rollBack();
                    return to_json([
                        'saved' => false,
                        'message' => 'No valid dates found in the uploaded sheet(s).',
                    ], 422);
                }

                $minDate = min($dates);
                $maxDate = max($dates);

                foreach ($cell_details as $key => &$cell_detail) {
                    $row = $key + 2;
                    $company_id = isset($ledgerDetails[$cell_detail['account_number']]) ? $ledgerDetails[$cell_detail['account_number']]->company_id : null;
                    $bank_id = isset($ledgerDetails[$cell_detail['account_number']]) ? $ledgerDetails[$cell_detail['account_number']]->ledger_id : null;

                    if(is_null($company_id) || is_null($bank_id)){
                        $message[] = "Account number {$cell_detail['account_number']} not found in any company";
                        continue;
                    }

                    $date = $this->parseBankEntryDate($cell_detail['date'] ?? null);
                    if(!$date){
                        DB::rollBack();
                        return to_json([
                            'saved' => false,
                            'message' => 'Invalid date format in cell '.$cell_detail['date'],
                        ], 400);
                    }
                    $credit_amount = $this->parseAmount($cell_detail['credit_amount'] ?? null);
                    $debit_amount = $this->parseAmount($cell_detail['debit_amount'] ?? null);
                    $amount = $this->parseAmount($cell_detail['amount'] ?? null);
                    $description = trim($cell_detail['description'] ?? '');
                    if ($description === '' && ! empty($cell_detail['transaction_type'])) {
                        $description = trim($cell_detail['transaction_type']);
                    }

                    if ($amount != 0) {
                        // signed amount column (credits and debits)
                    } elseif ($credit_amount != 0) {
                        $amount = $credit_amount;
                    } elseif ($debit_amount != 0) {
                        $amount = -1 * $debit_amount;
                    } else {
                        continue;
                    }

                    if ($amount == 0) {
                        continue;
                    }
                    $ledger_id = $this->matchLedgerIdFromDescription($description, $bankRules);
                    if(is_null($ledger_id)){
                        $amount>0 && $message[] = "Description {$description} not found in any ledger";
                        continue;
                    }



                    $amount = round($amount, 2);

                    if(isset($dateWiseBankEntries[$date][$company_id][$bank_id][$ledger_id])){
                        $dateWiseBankEntries[$date][$company_id][$bank_id][$ledger_id]['amount'] = round(
                            $dateWiseBankEntries[$date][$company_id][$bank_id][$ledger_id]['amount'] + $amount,
                            2
                        );
                        $dateWiseBankEntries[$date][$company_id][$bank_id][$ledger_id]['child_amounts'][] = [
                            'deposit' => $amount,
                            'fees' => 0,
                            'total_amount' => $amount
                        ];
                    }else{
                        $dateWiseBankEntries[$date][$company_id][$bank_id][$ledger_id]['amount'] = $amount;
                        $dateWiseBankEntries[$date][$company_id][$bank_id][$ledger_id]['child_amounts'] = [[
                            'deposit' => $amount,
                            'fees' => 0,
                            'total_amount' => $amount
                        ]];
                    }
                }

                $ledgerVouchers = [];
                $existingBankEntries = BankEntry::select('id', 'company_id', 'date', 'bank', 'total_amount')->where('is_imported', true)->get()->keyBy(function($entry){
                    return $entry->company_id . '_' . $entry->date . '_' . $entry->bank;
                });
                foreach($dateWiseBankEntries as $date => $companyData){
                    foreach($companyData as $company_id => $bankData){
                        foreach($bankData as $bank_id => $ledgerData){
                            $items = [];
                            $totalAmount = 0;
                            foreach($ledgerData as $ledger_id => $amountData){
                                $totalAmount += $amountData['amount'];
                                $items[] = [
                                    'bank_entry_id' => null,
                                    'ledger_id' => $ledger_id,
                                    'amount' => $amountData['amount'],
                                    'child_amounts_data' => $amountData['child_amounts'],
                                ];
                            }
                            if(empty($items)){
                                continue;
                            }

                            if(isset($existingBankEntries[$company_id . '_' . $date . '_' . $bank_id])){
                                $bankEntry = $existingBankEntries[$company_id . '_' . $date . '_' . $bank_id];
                                $bankEntry->update([
                                    'total_amount' => $totalAmount,
                                ]);
                                $bankEntryItems = BankEntryItem::where('bank_entry_id', $bankEntry->id)->pluck('id')->toArray();
                                BankEntryChildAmount::whereIn('bank_entry_item_id', $bankEntryItems)->delete();
                                BankEntryItem::whereIn('id', $bankEntryItems)->delete();
                                LedgerVouchers::where('voucher_id', $bankEntry->id)->where('voucher_type', 'bank_entries')->delete();
                                $message[] = "Existing bank entry on {$date} was updated in bank {$ledgers[$bank_id]['name']} for company {$companies[$company_id]['name']} ({$companies[$company_id]['store_number']})";
                            }else{
                                $bankEntry = BankEntry::create([
                                    'company_id' => $company_id,
                                    'date' => $date,
                                    'bank' => $bank_id,
                                    'total_amount' => $totalAmount,
                                    'is_imported' => true,
                                ]);
                            }
                            $ledgerVouchers[]=[
                                'company_id' => $company_id,
                                'ledger_id' => $bank_id,
                                'opp_ledger_id' => 0,
                                'amount' => -1 * $totalAmount,
                                'debit' => $totalAmount,
                                'credit' => 0,
                                'voucher_id' => $bankEntry->id,
                                'voucher_items_id' => null,
                                'voucher_type' => 'bank_entries',
                                'dbtable' => 'bank_entries',
                                'check_number' => null,
                                'description' => null,
                                'date' => $date,
                                'created_at' => now(),
                                'updated_at' => now(),
                            ];

                            $childAmountsToInsert = [];
                            foreach($items as $item){
                                $bankEntryItem = BankEntryItem::create([
                                    'bank_entry_id' => $bankEntry->id,
                                    'ledger_id' => $item['ledger_id'],
                                    'amount' => $item['amount'],
                                    'created_at' => now(),
                                    'updated_at' => now(),
                                ]);

                                foreach($item['child_amounts_data'] as $childAmount){
                                    $childAmountsToInsert[] = [
                                        'bank_entry_item_id' => $bankEntryItem->id,
                                        'deposit' => $childAmount['deposit'],
                                        'fees' => $childAmount['fees'],
                                        'total_amount' => $childAmount['total_amount'],
                                    ];
                                }

                                $ledgerVouchers[] = [
                                    'company_id' => $company_id,
                                    'ledger_id' => $item['ledger_id'],
                                    'opp_ledger_id' => 0,
                                    'amount' => $item['amount'],
                                    'debit' => 0,
                                    'credit' => $item['amount'],
                                    'voucher_id' => $bankEntry->id,
                                    'voucher_items_id' => $bankEntryItem->id,
                                    'voucher_type' => 'bank_entries',
                                    'dbtable' => 'bank_entries',
                                    'check_number' => null,
                                    'description' => null,
                                    'date' => $date,
                                    'created_at' => now(),
                                    'updated_at' => now(),
                                ];
                            }

                            if(!empty($childAmountsToInsert)){
                                foreach(array_chunk($childAmountsToInsert, 300) as $childAmountChunk){
                                    BankEntryChildAmount::insert($childAmountChunk);
                                }
                            }
                        }
                    }
                }
                // Merge all ledger vouchers
                $allLedgerVouchers = $ledgerVouchers;
                if(!empty($allLedgerVouchers)){
                    foreach(array_chunk($allLedgerVouchers, 300) as $ledgerVoucherChunk){
                        LedgerVouchers::insert($ledgerVoucherChunk);
                    }
                }
                $settlementService = new PjPaymentSettlementService();
                $settlementService->settlePjPayments($minDate, $maxDate);
                $settlementService->settleDailySales($minDate, $maxDate);
            }
            DB::commit();
            return to_json([
                'saved' => true,
                'message' => 'Bank Entry uploaded successfully',
                'messages' => array_values(array_unique($message)),
            ]);

        }catch(\Throwable $e){
            info($e);
            DB::rollBack();
            return to_json([
                'saved' => false,
                'message' => 'Bank Entry upload failed',
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
    protected function parseBankEntryDate($date): ?string
    {
        if ($date === null || $date === '') {
            return null;
        }

        $date = trim((string) $date);
        $dateFormats = ['m/d/Y' => '/', 'm-d-Y' => '-', 'Y-m-d' => '-', 'd/m/Y' => '/'];

        foreach ($dateFormats as $dateFormat => $delimiter) {
            try {
                $arr = explode($delimiter, $date);
                $format = $dateFormat;
                if (count($arr) == 3 && strlen($arr[2]) == 2) {
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

    /**
     * Match bank rules against description and return the first matching rule's ledger_id.
     */
    protected function matchLedgerIdFromDescription(string $description, Collection $bankRules): ?int
    {
        $description = trim($description);
        $descriptionLower = mb_strtolower($description);
        $descriptionLower = preg_replace('/\s+/', ' ', trim($descriptionLower));
        foreach ($bankRules as $rule) {
            $conditions = $rule->conditions;
            if ($conditions->isEmpty() || ! $rule->ledger_id) {
                continue;
            }

            $matchAll = strtolower((string) $rule->condition) === 'all';
            $matched = 0;
            $totalWithValue = 0;

            foreach ($conditions as $cond) {
                $value = trim((string) $cond->value);
                if ($value === '') {
                    continue;
                }
                $totalWithValue++;
                $valueLower = mb_strtolower($value);
                $valueLower = preg_replace('/\s+/', ' ', trim($valueLower));
                $pass = false;
                switch ($cond->condition_type) {
                    case 'Contains':
                        $pass = str_contains($descriptionLower, $valueLower);
                        break;
                    case 'Matches':
                        $pass = str_contains($descriptionLower, $valueLower);
                        break;
                    case 'Starts With':
                        $pass = str_starts_with($descriptionLower, $valueLower);
                        break;
                    case 'Ends With':
                        $pass = str_ends_with($descriptionLower, $valueLower);
                        break;
                    case 'Is Equal To':
                        $pass = $descriptionLower === $valueLower;
                       
                        break;
                    default:
                        $pass = str_contains($descriptionLower, $valueLower);
                }
                if ($pass) {
                    $matched++;
                } elseif ($matchAll) {
                    break;
                }
            }

            if ($totalWithValue === 0) {
                continue;
            }
            if ($matchAll && $matched === $totalWithValue) {
                return (int) $rule->ledger_id;
            }
            if (! $matchAll && $matched > 0) {
                return (int) $rule->ledger_id;
            }
        }

        return null;
    }

    protected function parseAmount($value): float
    {
        if ($value === null || $value === '') {
            return 0.0;
        }

        $normalized = str_replace(['$', ',', '₹', '?', ' '], '', (string) $value);
        if ($normalized === '' || ! is_numeric($normalized)) {
            return 0.0;
        }

        return round((float) $normalized, 2);
    }

    protected function sanitizeDescription(?string $value): string
    {
        if (! is_string($value) || $value === '') {
            return '';
        }

        $value = str_replace(["\r\n", "\r"], "\n", $value);
        if (preg_match('/\n\d{2}[-\/]\d{2}[-\/]\d{2,4},/', $value, $matches, PREG_OFFSET_CAPTURE)) {
            $value = substr($value, 0, $matches[0][1]);
        }

        return trim($value);
    }

    public function getCellDetails($sheet)
    {
        $wantedHeaders = [
            'date' => ['date', 'txn date', 'transaction date', 'trans date','ledger date'],
            'account_number' => ['account', 'account number', 'a/c no','account #', 'account no'],
            'credit_amount' => ['cr amount', 'credit', 'credit amount', 'deposits'],
            'debit_amount' => ['db amount', 'debit', 'debit amount', 'withdrawals'],
            'description' => ['text field', 'description', 'narration', 'details', 'transaction details', 'memo', 'text'],
            'transaction_type' => ['transaction type', 'type', 'txn type', 'trans type','description'],
            'amount' => ['amount'],
            'dc' => ['debit / credit indicator'],
            'value' => ['value'],
        ];
        $headerMap = [];
        
        $highestColumn = $sheet->getHighestColumn();
        $highestRow = $sheet->getHighestRow();
        $highestColumnIndex = Coordinate::columnIndexFromString($highestColumn);
        for($row = 1; $row <= $highestRow; $row++){
            for ($col = 1; $col <= $highestColumnIndex; $col++) {
                $columnLetter = Coordinate::stringFromColumnIndex($col);
                $header = strtolower(trim(
                    $sheet->getCell($columnLetter . $row)->getFormattedValue()
                ));        
                foreach ($wantedHeaders as $key => $aliases) {
                    if (in_array($header, $aliases)) {
                        $headerMap[$key] = $col; 
                        break;
                    }
                }
            }
            if(
                isset($headerMap['date']) &&
                isset($headerMap['account_number']) &&
                (isset($headerMap['description']) || isset($headerMap['transaction_type'])) &&
                (isset($headerMap['credit_amount']) || isset($headerMap['debit_amount']) || isset($headerMap['amount']) || (isset($headerMap['dc']) && isset($headerMap['value'])))
            ){
                break;
            }
        }
        $cell_details = [];
        $highestRow = $sheet->getHighestRow();

        for ($row = $row + 1; $row <= $highestRow; $row++) {
            $rowData = [];

            foreach ($headerMap as $field => $colIndex) {
                $columnLetter = Coordinate::stringFromColumnIndex($colIndex);

                $value = $sheet
                    ->getCell($columnLetter . $row)
                    ->getFormattedValue();
                if (is_string($value)) {
                    $value = str_replace(["\r\n", "\r"], "\n", $value);
                    $value = trim($value);
                }
                $rowData[$field] = $value;
            }

            if(isset($rowData['dc']) && isset($rowData['value'])){
                $rowData['amount'] = $rowData['dc'] == 'C' ? $rowData['value'] : -1 * $rowData['value'];
            }

            $rowData['description'] = $this->sanitizeDescription($rowData['description'] ?? null);
            if ($rowData['description'] === '' && !empty($rowData['transaction_type'])) {
                $rowData['description'] = trim($rowData['transaction_type']);
            }else if($rowData['description'] === '' ){
                $rowData['description'] = 'DEPOSIT';
            }

            $hasDescription = $rowData['description'] !== '' || ! empty($rowData['transaction_type']);

            if (array_filter($rowData) && !is_null($rowData['date']) && !str_contains($rowData['date'], 'Total') && !is_null($rowData['account_number']) && $hasDescription) {
                $cell_details[] = $rowData;
            }
        }
        return $cell_details;
    }

    public function export(Request $request)
    {
        $this->authorize('access', 'bank-entry.index');

        $query = BankEntry::join('company', 'bank_entries.company_id', '=', 'company.id')
            ->authorizedCompanies('company_id')
            ->select('bank_entries.*')
            ->with('company', 'items.ledger');

        $collection = $query->export($request->all());

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $headers = ['No', 'Company Name', 'Ledger Name', 'Name', 'Date', 'Amount'];
        $sheet->fromArray($headers, null, 'A1');
        $sheet->getStyle('A1:F1')->getFont()->setBold(true);
        $sheet->getStyle('A1:F1')->getFill()
            ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
            ->getStartColor()->setARGB('FF4472C4');
        $sheet->getStyle('A1:F1')->getFont()->getColor()->setARGB('FFFFFFFF');

        $row = 2;
        $index = 0;
        foreach ($collection as $entry) {
            $entryDate = $entry->date ? \Carbon\Carbon::parse($entry->date)->format('Y-m-d') : '';
            $companyName = $entry->company->name ?? '';
            foreach ($entry->items as $item) {
                $index++;
                $sheet->fromArray([
                    $index,
                    $companyName,
                    $item->ledger->name ?? '',
                    $item->name ?? '',
                    $entryDate,
                    $item->amount ?? 0,
                ], null, 'A' . $row);
                $row++;
            }
        }
        foreach (range('A', 'F') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
        $writer = new Xlsx($spreadsheet);
        $fileName = 'bank_entries_export_' . date('Y-m-d_His') . '.xlsx';
        $tempFile = tempnam(sys_get_temp_dir(), $fileName);
        $writer->save($tempFile);
        return response()->download($tempFile, $fileName)->deleteFileAfterSend(true);
    }
}

















































