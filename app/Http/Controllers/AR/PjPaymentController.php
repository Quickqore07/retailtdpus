<?php

namespace App\Http\Controllers\AR;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Settings\LedgerController;
use App\Models\AR\PjPayment;
use App\Models\AR\PjPaymentItem;
use App\Models\DataEntry\OldDailySale;
use App\Models\LedgerVouchers;
use App\Models\Settings\Company;
use App\Models\Settings\Ledger;
use App\Models\Settings\LedgerDetails;
use App\Services\ActivityLogService;
use App\Services\PjPaymentSettlementService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class PjPaymentController extends Controller
{
    public const ledgerMapping = [
        'American Express'   => '1001.02',
        'Cash'               => '1003.01',
        'Check'              => '1003.01',
        'DDDCash'            => '1001.49',
        'Doordash'           => '1001.52',
        'DDC Doordash'       => '1001.54',
        'EZCater'            => '1001.53',
        'EZCater Manual'     => '1001.53',
        'Grubhub'            => '1001.51',
        'Meal Deal'          => '1001.04',
        'Papa Card'          => '1001.05',
        'EZ Cater'          => '1001.53',
        'Uber Eats'          => '1001.50',
        'Visa / Master'      => '1001.01',
        'Account'            => '1001.55',
    ];
    protected $companyMapping = [
        'bear pj llc' => 15,
        'bechtelsville pj llc' => 36,
        'bensalem pj llc' => 33,
        'berwyn pj llc' => 41,
        'broad st pj llc' => 40,
        'brookhaven pj llc' => 59,
        'cc philadelphia pj llc' => 37,
        'deptford pj llc' => 32,
        'dover pj llc' => 16,
        'doylestown pj llc' => 6,
        'east brunswick pj llc' => 61,
        'em newark pj llc' => 11,
        'englishtown pj llc' => 68,
        'exton evb llc' => 26,
        'feasterville pj llc' => 58,
        'folsom pj llc' => 57,
        'freehold pj llc' => 63,
        'hillsborough pj llc' => 62,
        'hockessin pj llc' => 14,
        'king of prussia pj llc' => 39,
        'lancaster pike pj llc' => 12,
        'lansdowne pj llc' => 7,
        'levittown pj llc' => 3,
        'limerick pj llc' => 65,
        'maple shade pj llc' => 1,
        'marsh road pj llc' => 13,
        'medford pj llc' => 44,
        'middeltown pj llc' => 35, // typo preserved
        'milford pj llc' => 50,
        'millsboro pj llc' => 54,
        'mount holly pj llc' => 19,
        'mount laurel pj llc' => 30,
        'ne philadelphia pj llc' => 29,
        'new castle pj llc' => 9,
        'newark pj llc' => 17,
        'norristown rp llc' => 28,
        'north wales pj llc' => 22,
        'ocean view pj llc' => 34,
        'ogletown pj llc' => 10,
        'pennsauken pj llc' => 48,
        'philadelphia pj llc' => 24,
    
        'pie investments edison llc' => 51,
        'pie investments franklin park llc' => 47,
        'pie investments haddon ave llc' => 5,
        'pie investments highland park llc' => 43,
        'pie investments kearny llc' => 45,
        'pie investments north brunswick llc' => 38,
        'pie investments pine hill llc' => 21,
        'pie investments plainfield llc' => 46,
        'pie investments somerdale llc' => 20,
        'pie investments union ave llc' => 42,
        'pie investments williamstown llc' => 49,
    
        'plymouth meeting pj llc' => 56,
        'pottstown sr llc' => 31,
        'rehoboth pj llc' => 52,
        'seaford pj llc' => 53,
        'soudertown pj llc' => 60,
        'thorndale lh llc' => 55,
        'vineland pj llc' => 8,
        'wadsworth pj llc' => 67,
        'warminster yr llc' => 18,
        'welsh pj llc' => 64,
        'west chester pj llc' => 25,
        'west philadelphia pj llc' => 66,
        'west windsor pj llc' => 27,
        'willingboro pj llc' => 4,
        'willow grove pj llc' => 2,
        'wrightstown pj llc' => 23,
    ];
    public function index()
    {
        $this->authorize('access', 'pj-payment.index');

        $collection = PjPayment::join('company', 'pj_payments.company_id', '=', 'company.id')
            ->withCount('items')
            ->authorizedCompanies('company_id')
            ->select('pj_payments.*')
            ->with('createdBy', 'updatedBy', 'company')
            ->filter();

        return to_json([
            'collection' => $collection,
        ]);
    }

    public function create()
    {
        $this->authorize('access', 'pj-payment.create');

        $company_id = request()->session()->get('company');
        $pjLedgers = getLedgersByCode('pj', $company_id);

        $items = [];
        foreach ($pjLedgers as $ledger) {
            $items[] = [
                'ledger_id' => $ledger->id,
                'ledger' => $ledger,
                'name' => $ledger->ledger_name,
                'amount' => 0.00,
            ];
        }

        $company = Company::select('id', 'name', 'store_number')->where('id', request()->session()->get('company'))->selectRaw('CONCAT(store_number, " - ", name) as name')->first();
        return to_json([
            'form' => [
                'company_id' => $company->id,
                'company' => $company,
                'date' => now()->toDateString(),
                'items' => $items,
                'total_amount' => 0,
            ],
        ]);
    }

    public function store(Request $request)
    {
        $this->authorize('access', 'pj-payment.create');

        $validated = $request->validate([
            'company_id' => 'required|integer|exists:company,id',
            'date' => 'required|date',
            'items' => 'required|array|min:1',
            'items.*.ledger_id' => 'required|integer|exists:ledgers,id',
            'items.*.name' => 'required|string|max:255',
            'items.*.amount' => 'required|numeric|min:0',
        ]);

        DB::beginTransaction();
        try {
            $totalAmount = collect($validated['items'])->sum(function ($item) {
                return (float) $item['amount'];
            });

            $payment = PjPayment::create([
                'company_id' => $validated['company_id'],
                'date' => $validated['date'],
                'total_amount' => $totalAmount,
            ]);

            $items = [];
            $ledgerVouchers = [];

            foreach ($validated['items'] as $item) {
                if ($item['amount'] == 0) continue;
                $pjPaymentItem = new PjPaymentItem();
                $pjPaymentItem->pj_payment_id = $payment->id;
                $pjPaymentItem->ledger_id = $item['ledger_id'];
                $pjPaymentItem->name = $item['name'];
                $pjPaymentItem->amount = $item['amount'];
                $pjPaymentItem->save();

                $ledgerVouchers[] = [
                    'company_id' => $validated['company_id'],
                    'ledger_id' => $item['ledger_id'],
                    'opp_ledger_id' => 0,
                    'amount' => -1 * $item['amount'],
                    'debit' => $item['amount'],
                    'credit' => 0,
                    'voucher_id' => $payment->id,
                    'voucher_items_id' => $pjPaymentItem->id,
                    'voucher_type' => 'pj_payments',
                    'dbtable' => 'pj_payments',
                    'check_number' => null,
                    'description' => null,
                    'date' => $payment->date,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            LedgerVouchers::insert($ledgerVouchers);

            DB::commit();
            return to_json([
                'saved' => true,
                'id' => $payment->id,
                'message' => 'PJ Payment created successfully',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return to_json([
                'saved' => false,
                'message' => 'PJ Payment creation failed',
            ], 500);
        }
    }

    public function show($id)
    {
        $this->authorize('access', 'pj-payment.show');

        $model = PjPayment::with(['company', 'items.ledger'])
            ->authorizedCompanies('company_id')
            ->findOrFail($id);

        return to_json([
            'model' => $model,
        ]);
    }

    public function edit($id, Request $request)
    {
        $this->authorize('access', 'pj-payment.update');

        $form = PjPayment::with(['company', 'items.ledger.ledgerDetails' => function ($query) use ($request) {
            $query->where('company_id', $request->session()->get('company'));
        }])
        ->authorizedCompanies('company_id')
        ->findOrFail($id);

        $pjLedgers = getLedgersByCode('pj', $form->company_id);

        foreach ($pjLedgers as $ledger) {
            $item = $form->items->firstWhere('ledger_id', $ledger->id);
            if ($item) {
                $item->amount = $item->amount ?? 0.00;
            } else {
                $form->items[] = [
                    'ledger_id' => $ledger->id,
                    'ledger' => $ledger,
                    'name' => $ledger->ledger_name,
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
        $this->authorize('access', 'pj-payment.update');

        $validated = $request->validate([
            'company_id' => 'required|integer|exists:company,id',
            'date' => 'required|date',
            'items' => 'required|array|min:1',
            'items.*.ledger_id' => 'required|integer|exists:ledgers,id',
            'items.*.name' => 'required|string|max:255',
            'items.*.amount' => 'required|numeric|min:0',
            'items.*.settled' => 'required|boolean',
        ]);

        DB::beginTransaction();
        try {
            $payment = PjPayment::authorizedCompanies('company_id')->findOrFail($id);

            $totalAmount = collect($validated['items'])->sum(function ($item) {
                return (float) $item['amount'];
            });

            $payment->update([
                'company_id' => $validated['company_id'],
                'date' => $validated['date'],
                'total_amount' => $totalAmount,
            ]);

            PjPaymentItem::where('pj_payment_id', $payment->id)->delete();
            LedgerVouchers::where('voucher_id', $payment->id)->where('voucher_type', 'pj_payments')->delete();
            $items = [];
            $ledgerVouchers = [];
            foreach ($validated['items'] as $item) {
                if ($item['amount'] == 0) continue;

                
                $pjPaymentItem = new PjPaymentItem();
                $pjPaymentItem->pj_payment_id = $payment->id;
                $pjPaymentItem->ledger_id = $item['ledger_id'];
                $pjPaymentItem->name = $item['name'];
                $pjPaymentItem->amount = $item['amount'];
                $pjPaymentItem->settled = isset($item['settled']) ? $item['settled'] : false;
                $pjPaymentItem->save();


                $ledgerVouchers[] = [
                    'company_id' => $validated['company_id'],
                    'ledger_id' => $item['ledger_id'],
                    'opp_ledger_id' => 0,
                    'amount' => -1 * $item['amount'],
                    'debit' => $item['amount'],
                    'credit' => 0,
                    'voucher_id' => $payment->id,
                    'voucher_items_id' => $pjPaymentItem->id,
                    'voucher_type' => 'pj_payments',
                    'dbtable' => 'pj_payments',
                    'check_number' => null,
                    'description' => null,
                    'date' => $payment->date,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            LedgerVouchers::insert($ledgerVouchers);

            DB::commit();
            return to_json([
                'saved' => true,
                'id' => $payment->id,
                'message' => 'PJ Payment updated successfully',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            dd($e);
            return to_json([
                'saved' => false,
                'message' => 'PJ Payment update failed',
            ], 500);
        }
    }

    public function destroy($id)
    {
        $this->authorize('access', 'pj-payment.delete');

        DB::beginTransaction();
        try {
            $payment = PjPayment::authorizedCompanies('company_id')->findOrFail($id);
            PjPaymentItem::where('pj_payment_id', $payment->id)->delete();
            LedgerVouchers::where('voucher_id', $payment->id)->where('voucher_type', 'pj_payments')->delete();
            $payment->delete();

            DB::commit();
            return to_json([
                'deleted' => true,
                'id' => $id,
                'message' => 'PJ Payment deleted successfully',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return to_json([
                'deleted' => false,
                'message' => 'PJ Payment deletion failed',
            ], 500);
        }
    }

    public function upload(Request $request)
    {
        $this->authorize('access', 'pj-payment.create');
        $request->validate([
            'file' => 'required_without:files|file|' . upload_max_file_size_rule(),
            'files' => 'required_without:file|nullable|array',
            'files.*' => 'required|file|' . upload_max_file_size_rule(),
            'confirmed' => 'nullable|boolean',
        ]);
        return $this->processUpload($request);
    }

    /**
     * Process upload (shared with DailySaleController for same file/functionality).
     */
    public function processUpload(Request $request)
    {
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
                    $excelfile = IOFactory::load($the_file->getRealPath());
                    $sheet = $excelfile->getActiveSheet();
                    $parsedDate = $this->extractUploadSheetDate($sheet);

                    if ($parsedDate) {
                        $confirmationDates[$parsedDate] = Carbon::parse($parsedDate)->format('m/d/Y');
                    }
                }

                ksort($confirmationDates);
                $confirmationDates = array_values($confirmationDates);

                if (empty($confirmationDates)) {
                    return to_json([
                        'success' => false,
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
                    'success' => false,
                    'message' => $e->getMessage(),
                ], 500);
            }
        }

        DB::beginTransaction();
        try {
            $message = [];
            foreach ($files as $the_file) {
                $excelfile = IOFactory::load($the_file->getRealPath());
                $sheet     = $excelfile->getActiveSheet();
                $row_limit = $sheet->getHighestDataRow();
                $row_range = range(5, $row_limit);

                $importFunction = $this->handlePJPaymentImport($sheet);
                if (is_callable($importFunction)) {
                    $importFunction($sheet, $message);
                    continue;
                }
                $cell_details = $this->getCellDetails($sheet);
                if(!$cell_details['success']){
                    DB::rollBack();
                    return to_json([
                        'success' => false,
                        'message' => $cell_details['message'],
                    ], 422);
                }
                $cell_details = $cell_details['data'];
                $companiesByStoreNumber = Company::all()->select('id', 'name', 'store_number')->keyBy('store_number');
                $compniesById = Company::all()->select('id', 'name', 'store_number')->keyBy('id');

                $masterCard           = ['Mastercard', 'PayPal MC', 'Venmo', 'Visa Debit', 'Discover', 'Visa', 'JCB', 'Mastercard Debit', 'Other Debit'];
                $EZCater            = ['Postmates', 'EZCater', 'EZCater Manual'];

                $ledgers = Ledger::pluck('id', 'name')->toArray();
                $ledgerDetails = LedgerDetails::all();
                $companyWiseCodes = [];
                foreach ($ledgerDetails as $ledgerDetail) {
                    $companyWiseCodes[$ledgerDetail->company_id][$ledgerDetail->code] = $ledgerDetail->ledger_id;
                }



                $date = $cell_details['date'];
                // if ($date != $request->date) {
                //     return ([
                //         'success' => false,
                //         'message' => ['Date is not same'],
                //     ]);
                // }

                $companies = [];
                $debit_cells          = collect($cell_details['debit_cells']);
                $current_company = null;

                $pjPayments = PjPayment::where('date', $date)->where('is_imported', true)->pluck('company_id')->toArray();
                $mastercardTotal = 0;
                $EZCaterTotal = 0;
                $tipsTotal = 0;
                $dailySales = OldDailySale::where('date', $date)->pluck('company_id')->toArray();



                foreach ($row_range as $rowKey => $row) {


                    $nextValue = false;
                    $label_cell    = $sheet->getCell($cell_details['label_cell'] . $row)->getFormattedValue();
                    $netsales_cell = $sheet->getCell($cell_details['netsales_cell'] . $row)->getFormattedValue();
                    $food_tax_cell = $sheet->getCell($cell_details['sales_tax_cell'][0] . $row)->getFormattedValue();
                    $beverage_tax_cell = $sheet->getCell($cell_details['sales_tax_cell'][1] . $row)->getFormattedValue();


                    if (isset($row_range[$rowKey + 1])) {
                        $nextValue = $sheet->getCell($cell_details['label_cell'] . $row_range[$rowKey + 1])->getValue();
                    }
                    if ($label_cell == 'ALL' && isset($current_company['id'])) {
                        $companies[$current_company['id']]['net_sales'] = floatval(str_replace(['$', ',', '₹', '?', ' '], '', $netsales_cell));
                        $companies[$current_company['id']]['food_tax'] = floatval(str_replace(['$', ',', '₹', '?', ' '], '', $food_tax_cell));
                        $companies[$current_company['id']]['beverage_tax'] = floatval(str_replace(['$', ',', '₹', '?', ' '], '', $beverage_tax_cell));
                    } else if ($current_company && !Str::contains($label_cell, 'Total') && (($nextValue && (is_numeric($nextValue))) || !$nextValue || $nextValue == 'Grand Total' || Str::contains($nextValue, 'Total'))) {

                        $amount = $debit_cells->reduce(function ($sum, $item) use ($sheet, $row) {
                            $cellValue    = $sheet->getCell($item . $row)->getValue();
                            $cleanedValue = str_replace(['$', ',', '₹', '?', ' '], '', $cellValue);
                            $numericValue = floatval($cleanedValue);

                            return $sum + $numericValue;
                        });
                        $tipamount =  $sheet->getCell($cell_details['tips_cell'] . $row)->getValue();
                        $tipamount = str_replace(['$', ',', '₹', '?', ' '], '', $tipamount);
                        $tipsTotal += floatval($tipamount);

                        if ($amount && in_array($label_cell, $masterCard)) {
                            $mastercardTotal += $amount;
                        } else if ($amount && in_array($label_cell, $EZCater)) {
                            $EZCaterTotal += $amount;
                        }
                        $companies[$current_company['id']]['tips'] = $tipsTotal;
                        $companies[$current_company['id']]['data'][] = [
                            'ledger_id' => $ledgers['Visa / Master'],
                            'name'        => 'Visa / Master',
                            'amount'      => $mastercardTotal,
                        ];
                        $companies[$current_company['id']]['data'][] = [
                            'ledger_id' => $ledgers['EZ Cater'],
                            'name'        => 'EZ Cater',
                            'amount'      => $EZCaterTotal,
                        ];
                        $mastercardTotal=0;
                        $EZCaterTotal=0;
                        $tipsTotal=0;
                    }else if ($netsales_cell == '' && $label_cell) {
                        $current_company = isset($companiesByStoreNumber[$label_cell]) ? $companiesByStoreNumber[$label_cell] : null;
                        if (!$current_company) {
                            $current_company_id = $this->companyMapping[Str::lower($label_cell)] ?? null;
                            if($current_company_id) $current_company = isset($compniesById[$current_company_id]) ? $compniesById[$current_company_id] : null;
                        }
                        if ($current_company) {
                            $companies[$current_company['id']] = [
                                'net_sales' => 0,
                                'food_tax' => 0,
                                'beverage_tax' => 0,
                                'tips' => 0,
                                'data' => [],
                            ];
                        } else {
                            $message[] = "{$label_cell} not found !!";
                        }
                    } else if ($current_company && ($netsales_cell || $netsales_cell == 0)) {
                        $amount = $debit_cells->reduce(function ($sum, $item) use ($sheet, $row) {
                            $cellValue    = $sheet->getCell($item . $row)->getValue();
                            $cleanedValue = str_replace(['$', ',', '₹', '?', ' '], '', $cellValue);
                            $numericValue = floatval($cleanedValue);

                            return $sum + $numericValue;
                        });

                        $tipamount =  $sheet->getCell($cell_details['tips_cell'] . $row)->getValue();
                        $tipamount = str_replace(['$', ',', '₹', '?', ' '], '', $tipamount);
                        $tipsTotal += floatval($tipamount);

                        $ledger_code = self::ledgerMapping[$label_cell] ?? null;
                        if ($amount && in_array($label_cell, $masterCard)) {
                            $mastercardTotal += $amount;
                        } else if ($amount && in_array($label_cell, $EZCater)) {
                            $EZCaterTotal += $amount;
                        } else if ($ledger_code && isset($companyWiseCodes[$current_company['id']][$ledger_code])) {
                            $companies[$current_company['id']]['data'][] = [
                                'ledger_id' => $companyWiseCodes[$current_company['id']][$ledger_code],
                                'name'        => $label_cell,
                                'amount'      => $amount,
                            ];
                        }
                    }
                }
                $pj_payment_items = [];
                $ledgerVouchers = [];
                $createdPaymentIds = [];
                $updatedPaymentIds = [];

                $dailySalesArray = [];

                $createdBy = Auth::user()->id;
                foreach ($companies as $company_id => $data) {
                    if(collect($data['data'])->sum('amount') ==0 ) continue;

                    if (in_array($company_id, $pjPayments)) {
                        $pjPayment = PjPayment::where('company_id', $company_id)->where('date', $date)->first();
                        $pjPayment->update([
                            'total_amount' => collect($data['data'])->sum('amount'),
                            'is_imported' => true,
                        ]);
                        PjPaymentItem::where('pj_payment_id', $pjPayment->id)->delete();
                        LedgerVouchers::where('voucher_id', $pjPayment->id)->where('voucher_type', 'pj_payments')->delete();
                        $companyName = $compniesById[$company_id]['name'] ?? null;
                        $message[] = "{$companyName} - Existing payment on {$pjPayment->date} was updated";
                        $updatedPaymentIds[] = $pjPayment->id;
                    } else {
                        $pjPayment = PjPayment::create([
                            'company_id' => $company_id,
                            'date' => $date,
                            'total_amount' => collect($data['data'])->sum('amount'),
                            'is_imported' => true,
                            'created_by' => $createdBy,
                            'updated_by' => $createdBy,
                        ]);

                        $createdPaymentIds[] = $pjPayment->id;
                    }
                    $cash_received = 0;
                    foreach ($data['data'] as $item) {

                        if($item['name'] == 'Cash' || $item['name'] == 'Check'){
                            $cash_received += $item['amount'];
                        }
                        $pjPaymentItem = new PjPaymentItem();
                        $pjPaymentItem->pj_payment_id = $pjPayment->id;
                        $pjPaymentItem->ledger_id = $item['ledger_id'];
                        $pjPaymentItem->name = $item['name'];
                        $pjPaymentItem->amount = $item['amount'];
                        $pjPaymentItem->save();


                        $ledgerVouchers[] = [
                            'company_id' => $company_id,
                            'ledger_id' => $item['ledger_id'],
                            'opp_ledger_id' => 0,
                            'amount' => -1 * $item['amount'],
                            'debit' => $item['amount'],
                            'credit' => 0,
                            'voucher_id' => $pjPayment->id,
                            'voucher_items_id' => $pjPaymentItem->id,
                            'voucher_type' => 'pj_payments',
                            'dbtable' => 'pj_payments',
                            'check_number' => null,
                            'description' => null,
                            'date' => $pjPayment->date,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ];
                        // $ledgerVouchers[] = [
                        //     'company_id' => $company_id,
                        //     'ledger_id' => $item['ledger_id'],
                        //     'opp_ledger_id' => 0,
                        //     'amount' => -1 * $item['amount'],
                        //     'debit' => $item['amount'],
                        //     'credit' => 0,
                        //     'voucher_id' => $pjPayment->id,
                        //     'voucher_items_id' => $pjPaymentItem->id,
                        //     'voucher_type' => 'pj_payments',
                        //     'dbtable' => 'pj_payments',
                        //     'check_number' => null,
                        //     'description' => null,
                        //     'date' => $pjPayment->date,
                        //     'created_at' => now(),
                        //     'updated_at' => now(),
                        // ];
                    }

                    if (in_array($company_id, $dailySales)) {
                        $dailySale = OldDailySale::where('company_id', $company_id)->where('date', $date)->first();
                        $total_cash = $dailySale->partial_void + $cash_received;

                        $total_tips_mileage = $data['tips'] + $dailySale->mileage;
                        $cash_payment = $total_tips_mileage - $dailySale->total_e_and_dd_tips;
                        $total_cash_payment = $cash_payment + $dailySale->other_payments_total;
                        $net_cash_due = $total_cash - $total_cash_payment;
                        $dailySale->update([
                            'net_sales' => $data['net_sales'],
                            'food_tax' => $data['food_tax'],
                            'beverage_tax' => $data['beverage_tax'],
                            'total_sales' => $data['net_sales'] + $data['food_tax'] + $data['beverage_tax'],

                            'cash_received' => $cash_received,
                            'total_cash' => $total_cash,


                            'tips' => $data['tips'],
                            'total_tips_mileage' => $total_tips_mileage,
                            'cash_payment' => $cash_payment,
                            'total_cash_payment' => $cash_payment + $dailySale->other_payments_total,
                            'net_cash_due' => $net_cash_due,
                            'short_over' => $dailySale->cash_bag - $net_cash_due,
                        ]);
                    } else {
                        $dailySalesArray[] = [
                            'company_id' => $company_id,
                            'date' => $date,
                            'net_sales' => $data['net_sales'],
                            'food_tax' => $data['food_tax'],
                            'beverage_tax' => $data['beverage_tax'],
                            'total_sales' => $data['net_sales'] + $data['food_tax'] + $data['beverage_tax'],

                            'cash_received' => $cash_received,
                            'partial_void' => 0,
                            'total_cash' => $cash_received,

                            'tips' => $data['tips'],
                            'mileage' => 0,
                            'total_tips_mileage' => $data['tips'],

                            'dd_tips' => 0,
                            'e_tips' => 0,
                            'e_tips_payroll' => 0,
                            'total_e_and_dd_tips' => 0,

                            'cash_payment' => $data['tips'],
                            'total_cash_payment' => $data['tips'],
                            'net_cash_due' => $cash_received - $data['tips'],
                            'short_over' => -1 * ($cash_received - $data['tips']),
                        ];
                    }
                }
                // array_map(function($chunk){
                //     PjPaymentItem::insert($chunk);
                // }, array_chunk($pj_payment_items, 300));

                // Create ledger vouchers with proper voucher_items_id after payment items are inserted
                // foreach ($companies as $company_id => $data) {
                //     if(collect($data['data'])->sum('amount') == 0) continue;
                    
                //     $pjPayment = PjPayment::where('company_id', $company_id)->where('date', $date)->first();
                //     if (!$pjPayment) continue;
                    
                //     $paymentItems = PjPaymentItem::where('pj_payment_id', $pjPayment->id)->get();
                    
                //     foreach ($data['data'] as $item) {
                //         $paymentItem = $paymentItems->where('ledger_id', $item['ledger_id'])
                //             ->where('name', $item['name'])
                //             ->where('amount', $item['amount'])
                //             ->first();
                            
                //         if ($paymentItem) {
                //             $ledgerVouchers[] = [
                //                 'company_id' => $company_id,
                //                 'ledger_id' => $item['ledger_id'],
                //                 'opp_ledger_id' => 0,
                //                 'amount' => -1 * $item['amount'],
                //                 'debit' => $item['amount'],
                //                 'credit' => 0,
                //                 'voucher_id' => $pjPayment->id,
                //                 'voucher_items_id' => $paymentItem->id,
                //                 'voucher_type' => 'pj_payments',
                //                 'dbtable' => 'pj_payments',
                //                 'check_number' => null,
                //                 'description' => null,
                //                 'date' => $pjPayment->date,
                //                 'created_at' => now(),
                //                 'updated_at' => now(),
                //             ];
                //         }
                //     }
                // }

                array_map(function($chunk){
                    LedgerVouchers::insert($chunk);
                }, array_chunk($ledgerVouchers, 300));

                array_map(function($chunk){
                    OldDailySale::insert($chunk);
                }, array_chunk($dailySalesArray, 300));

                $allPaymentIds = array_values(array_unique(array_merge($createdPaymentIds, $updatedPaymentIds)));
                if (count($allPaymentIds) > 0) {
                    ActivityLogService::logBulkImport(
                        'pj_payments',
                        [
                            'ids' => $allPaymentIds,
                            'created_ids' => array_values(array_unique($createdPaymentIds)),
                            'updated_ids' => array_values(array_unique($updatedPaymentIds)),
                            'date' => $date,
                        ],
                        "PJ payment bulk upload for {$date}"
                    );
                }
            }

            DB::commit();
            return to_json([
                'success' => true,
                'message' => 'PJ Payment uploaded successfully',
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


    public function getCellDetails($sheet)
    {
        $cell_details = [];
        if (!$sheet->getCell('A5')->getFormattedValue()) {
            $maxRow=5;
            
            for($i = 1; $i <= $maxRow; $i++){
                $date = $sheet->getCell('C' . $i)->getFormattedValue();
                if(!$date)continue;

                if(Str::contains($date, 'My Date - ')){
                    $date = str_replace('My Date - ', '', $date);
                    $cell_details['date'] = $date;
                }else{
                    try {
                        $date = Carbon::createFromFormat('m/d/y', $date)->format('Y-m-d');
                        $cell_details['date'] = $date;
                        break;
                    } catch (\Throwable $th) {
                        continue;
                    }
                }
            }
            if(!$cell_details['date']){
                return [
                    'success' => false,
                    'message' => 'Date not found in the sheet',
                ];
            }
            
            $cell_details['debit_cells'] = ['H', 'I'];
            $cell_details['label_cell'] = 'B';
            $cell_details['netsales_cell'] = 'C';
            $cell_details['sales_tax_cell'] = ['D', 'E'];
            $cell_details['delivery_cell'] = 'G';
            $cell_details['tips_cell'] = 'I';
        } else {
            $cell_details['date'] = $sheet->getCell('B3')->getValue();
            try {
                $cell_details['date'] = Carbon::createFromFormat('m/d/y', $cell_details['date'])->format('Y-m-d');
            } catch (\Throwable $th) {
                return [
                    'success' => false,
                    'message' => 'Date format should be in the format of YYYY-MM-DD in cell B3',
                ];
            }

            $cell_details['debit_cells'] = ['G', 'H'];
            $cell_details['label_cell'] = 'A';
            $cell_details['netsales_cell'] = 'B';
            $cell_details['sales_tax_cell'] = ['C', 'D'];
            $cell_details['delivery_cell'] = 'F';
            $cell_details['tips_cell'] = 'H';
        }
        return [
            'success' => true,
            'data' => $cell_details,
        ];
    }

    /**
     * Extract Y-m-d date from a daily sales / PJ payment sheet for confirmation.
     */
    protected function extractUploadSheetDate($sheet): ?string
    {
        $importFunction = $this->handlePJPaymentImport($sheet);
        if (is_callable($importFunction)) {
            $date = $sheet->getCell('B2')->getFormattedValue();
            if ($date == '') {
                $date = $sheet->getCell('B1')->getFormattedValue();
            }

            return $this->parseUploadSheetDate($date);
        }

        $cell_details = $this->getCellDetails($sheet);
        if (! ($cell_details['success'] ?? false)) {
            return null;
        }

        return $this->parseUploadSheetDate($cell_details['data']['date'] ?? null);
    }

    protected function parseUploadSheetDate($date): ?string
    {
        if ($date === null || $date === '') {
            return null;
        }

        $date = trim((string) $date);
        if (Str::contains($date, 'My Date - ')) {
            $date = trim(str_replace('My Date - ', '', $date));
        }

        $formats = ['Y-m-d', 'm/d/y', 'm/d/Y', 'm-d-y', 'm-d-Y'];
        foreach ($formats as $format) {
            try {
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

    function handlePJPaymentImport($sheet)
    {
        for($i = 0; $i <= 8; $i++){
            $A = $sheet->getCell('A' . $i)->getFormattedValue();
            $B = $sheet->getCell('B' . $i)->getFormattedValue();
            $C = $sheet->getCell('C' . $i)->getFormattedValue();
            $D = $sheet->getCell('D' . $i)->getFormattedValue();
            if(trim($A) == 'Row Labels' && trim($B) == 'Tips Amount USD' && trim($C) == 'Cash Paid In' && trim($D) == 'Cash Paid Out' ){
                return [$this, 'cashPaymentImport'];
            }
        }
        return false;
    }
    function cashPaymentImport($sheet)
    {
        $row_limit = $sheet->getHighestDataRow();
        $row_range = range(3, $row_limit);
        $date = $sheet->getCell('B2')->getFormattedValue();
        if($date == '') $date = $sheet->getCell('B1')->getFormattedValue();
        $date = Carbon::createFromFormat('m/d/y', $date)->format('Y-m-d');
        
        $companiesByStoreNumber = Company::all()->select('id', 'name', 'store_number')->keyBy('store_number');

        $current_company = null;
        $companyData = [];
        $cashCells = $this->getCashCells();
        foreach ($row_range as $row) {
            $A = $sheet->getCell('A' . $row)->getFormattedValue();
            $nextValue =null;
            if(isset($row_range[$row + 1])){
                $nextValue = $sheet->getCell('A' . $row + 1)->getFormattedValue();
            }
            if(!$current_company && is_numeric($A) && $A != 0){
                $current_company = isset($companiesByStoreNumber[$A]) ? $companiesByStoreNumber[$A] : null;
                if(!$current_company){
                    $message[] = "{$A} not found !!";
                    continue;
                }
                $companyData[$current_company['id']] = array_fill_keys(array_keys($cashCells), 0);
            }else if($current_company){

                foreach($cashCells as $fieldKey => $cell){
                    if(trim($A) == trim($cell[0])){
                        $companyData[$current_company['id']][$fieldKey] = floatval(str_replace(['$', ',', '₹', '?', ' '], '', $sheet->getCell($cell[1] . $row)->getFormattedValue()));
                    }
                }

                if($nextValue && is_numeric($nextValue) && $nextValue != 0){
                    $current_company = null;
                }
            }

        }
        $dailysalesData =[];
        $createdCashIds = [];
        $updatedCashIds = [];
        foreach($companyData as $company_id => $data){
            $dailySale = OldDailySale::where('company_id', $company_id)->where('date', $date)->first();
            if($dailySale){
                $updatedCashIds[] = $dailySale->id;

                $total_sales = $data['net_sales'] + $data['food_tax'] + $data['beverage_tax'];
                $total_cash = $dailySale->total_cash + $data['partial_void'];

                $total_tips_mileage = $data['tips'] + $data['mileage'];
                $total_e_and_dd_tips = $data['dd_tips'] + $data['e_tips'] + $data['e_tips_payroll'];


                $cash_payment = $total_tips_mileage - $total_e_and_dd_tips;
                $total_cash_payment = $cash_payment + $dailySale->other_payments_total;
                $net_cash_due = $total_cash - $total_cash_payment;

                $dailySale->update([
                    'net_sales' => $data['net_sales'],
                    'food_tax' => $data['food_tax'],
                    'beverage_tax' => $data['beverage_tax'],
                    'total_sales' => $total_sales,

                    'partial_void' => $data['partial_void'],
                    'total_cash' => $total_cash,

                    'tips' => $data['tips'],
                    'mileage' => $data['mileage'],
                    'total_tips_mileage' => $total_tips_mileage,

                    'dd_tips' => $data['dd_tips'],
                    'e_tips' => $data['e_tips'],
                    'e_tips_payroll' => $data['e_tips_payroll'],
                    'total_e_and_dd_tips' => $total_e_and_dd_tips,

                    'cash_payment' => $cash_payment,
                    'total_cash_payment' => $total_cash_payment,
                    'net_cash_due' => $net_cash_due,

                    'short_over' => $dailySale->cash_bag - $net_cash_due,
                ]);

                $updatedCashIds[] = $dailySale->id;


            }else{
                $total_sales = $data['net_sales'] + $data['food_tax'] + $data['beverage_tax'];
                $total_cash = $data['partial_void'];

                $total_tips_mileage = $data['tips'] + $data['mileage'];
                $total_e_and_dd_tips = $data['dd_tips'] + $data['e_tips'] + $data['e_tips_payroll'];


                $cash_payment = $total_tips_mileage - $total_e_and_dd_tips;
                $total_cash_payment = $cash_payment;
                $net_cash_due = $total_cash - $total_cash_payment;

                $dailysalesData[] = [
                    'company_id' => $company_id,
                    'date' => $date,
                    'net_sales' => $data['net_sales'],
                    'food_tax' => $data['food_tax'],
                    'beverage_tax' => $data['beverage_tax'],
                    'total_sales' => $total_sales,

                    'cash_received' => 0,
                    'partial_void' => $data['partial_void'],
                    'total_cash' => $total_cash,

                    'tips' => $data['tips'],
                    'mileage' => $data['mileage'],
                    'total_tips_mileage' => $total_tips_mileage,

                    'dd_tips' => $data['dd_tips'],
                    'e_tips' => $data['e_tips'],
                    'e_tips_payroll' => $data['e_tips_payroll'],
                    'total_e_and_dd_tips' => $total_e_and_dd_tips,

                    'cash_payment' => $cash_payment,
                    'total_cash_payment' => $total_cash_payment,
                    'net_cash_due' => $net_cash_due,

                    'short_over' => -1 * $net_cash_due,
                    'created_at' => now(),
                    'updated_at' => now(),
                    'created_by' => 1,
                    'updated_by' => 1,
                ];
            }
        }
        $maxIdBefore = OldDailySale::max('id') ?? 0;
        foreach(array_chunk($dailysalesData, 300) as $chunk){
            OldDailySale::insert($chunk);
        }

        $createdCashIds = OldDailySale::where('id', '>', $maxIdBefore)->pluck('id')->toArray();


        $allCashIds = array_values(array_unique(array_merge($createdCashIds, $updatedCashIds)));
        if (count($allCashIds) > 0) {
            ActivityLogService::logBulkImport(
                'daily_sales',
                [
                    'ids' => $allCashIds,
                    'created_ids' => array_values(array_unique($createdCashIds)),
                    'updated_ids' => array_values(array_unique($updatedCashIds)),
                    'date' => $date,
                ],
                "Daily sales bulk upload for {$date}"
            );
        }
        
    }
    function getCashCells()
    {
        $cashCells = [];
        $cashCells['tips'] = ['Payment Type Metrics','B'];
        $cashCells['dd_tips'] = ['Door Dash Credit Cards Tips','C'];
        $cashCells['e_tips'] = ['E-tips & Mileage Paycard','C'];
        $cashCells['e_tips_payroll'] = ['E-tips & Mileage Payroll','C'];
        $cashCells['partial_void'] = ['Order Header Metrics','J'];
        $cashCells['mileage'] = ['Mileage','D'];
        $cashCells['net_sales'] = ['Order Header Metrics','K'];
        $cashCells['beverage_tax'] = ['Order Header Metrics','L'];
        $cashCells['food_tax'] = ['Order Header Metrics','M'];
        return $cashCells;
    }

    public function export(Request $request)
    {
        $this->authorize('access', 'pj-payment.index');

        $query = PjPayment::join('company', 'pj_payments.company_id', '=', 'company.id')
            ->authorizedCompanies('company_id')
            ->select('pj_payments.*')
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
        foreach ($collection as $payment) {
            $paymentDate = $payment->date ? \Carbon\Carbon::parse($payment->date)->format('Y-m-d') : '';
            $companyName = $payment->company->name ?? '';
            foreach ($payment->items as $item) {
                $index++;
                $sheet->fromArray([
                    $index,
                    $companyName,
                    $item->ledger->name ?? '',
                    $item->name ?? '',
                    $paymentDate,
                    $item->amount ?? 0,
                ], null, 'A' . $row);
                $row++;
            }
        }
        foreach (range('A', 'F') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
        $writer = new Xlsx($spreadsheet);
        $fileName = 'pj_payments_export_' . date('Y-m-d_His') . '.xlsx';
        $tempFile = tempnam(sys_get_temp_dir(), $fileName);
        $writer->save($tempFile);
        return response()->download($tempFile, $fileName)->deleteFileAfterSend(true);
    }

    /**
     * Get settlement matching details for debugging
     */
    public function getSettlementMatchingDetails(Request $request)
    {
        $this->authorize('access', 'pj-payment.index');

        $validated = $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'company_id' => 'nullable|integer|exists:company,id',
        ]);

        $settlementService = new PjPaymentSettlementService();
        $details = $settlementService->getSettlementMatchingDetails(
            $validated['start_date'],
            $validated['end_date'],
            $validated['company_id'] ?? null
        );

        return to_json([
            'matching_details' => $details,
        ]);
    }

    /**
     * Get missing visa amount report for AR tab
     * Shows all child amounts for ledger code 1001.01 that are not settled in PJ items
     */
    public function getMissingBankAmountReport($args)
    {
       

        // Default to last 30 days if no date range provided
        $companyId = $args['company_id'] ?? null;

        // Get target ledger IDs (1001.01 - Visa/Master)
        $targetLedgerIds = LedgerDetails::where('ledger_id', $args['ledger_id'])
            ->when($companyId, function($query) use ($companyId) {
                $query->where('company_id', $companyId);
            })
            ->pluck('ledger_id')
            ->toArray();

        if (empty($targetLedgerIds)) {
            return to_json([
                'collection' => collect(),
                'summary' => [
                    'total_records' => 0,
                    'total_amount' => 0,
                    'total_fees' => 0,
                ],
            ]);
        }
        $currentWorkgroup = session('workgroup');
        // Get unsettled child amounts for Visa/Master ledger code
        $missingAmounts = DB::table('bank_entry_child_amounts')
            ->join('bank_entry_items', 'bank_entry_child_amounts.bank_entry_item_id', '=', 'bank_entry_items.id')
            ->join('bank_entries', 'bank_entry_items.bank_entry_id', '=', 'bank_entries.id')
            ->join('company', 'bank_entries.company_id', '=', 'company.id')
            ->join('ledgers', 'bank_entry_items.ledger_id', '=', 'ledgers.id')
            ->leftJoin('pj_payments_items', 'bank_entry_child_amounts.id', '=', 'pj_payments_items.bank_child_amount_id')
            ->whereIn('bank_entry_items.ledger_id', $targetLedgerIds)
            ->whereNull('pj_payments_items.id') // Not settled in PJ items
            // ->whereBetween('bank_entries.date', [$startDate, $endDate])
            ->when($companyId, function($query) use ($companyId) {
                $query->where('bank_entries.company_id', $companyId);
            })->when($currentWorkgroup, function($query) use ($currentWorkgroup) {
                $query->where('company.workgroup_id', $currentWorkgroup);
            })
            ->select([
                'bank_entry_child_amounts.id as child_amount_id',
                'bank_entry_child_amounts.deposit',
                'bank_entry_child_amounts.fees',
                'bank_entry_child_amounts.total_amount',
                'bank_entries.id as bank_entry_id',
                'bank_entries.date as bank_date',
                'bank_entries.company_id',
                'company.name as company_name',
                'company.store_number',
                'ledgers.name as ledger_name',
                'bank_entry_items.id as bank_entry_item_id',
                'bank_entry_items.amount as bank_item_amount',
            ])
            ->orderBy('bank_entries.date', 'desc')
            ->orderBy('company.name')
            ->get();

        // Add input field for fees to each record
        $collection = $missingAmounts->map(function ($item) {
            return [
                'child_amount_id' => $item->child_amount_id,
                'bank_entry_id' => $item->bank_entry_id,
                'bank_entry_item_id' => $item->bank_entry_item_id,
                'bank_date' => $item->bank_date,
                'company_id' => $item->company_id,
                'company_name' => $item->company_name,
                'store_number' => $item->store_number,
                'company_display' => $item->store_number . ' - ' . $item->company_name,
                'ledger_name' => $item->ledger_name,
                'deposit' => (float) $item->deposit,
                'fees' => (float) $item->fees,
                'total_amount' => (float) $item->total_amount,
                'bank_item_amount' => (float) $item->bank_item_amount,
                'input_fees' => (float) $item->fees, // Input field for updating fees
            ];
        });

        // Calculate summary
        $summary = [
            'total_records' => $collection->count(),
            'total_amount' => $collection->sum('total_amount'),
            'total_fees' => $collection->sum('fees'),
            'total_deposit' => $collection->sum('deposit'),
        ];

        return to_json([
            'collection' => $collection,
            'summary' => $summary,
            'target_ledger_code' => $args['ledger_id'],
        ]);
    }

    /**
     * Update fees for bank entry child amounts and settle them
     */
  

}
