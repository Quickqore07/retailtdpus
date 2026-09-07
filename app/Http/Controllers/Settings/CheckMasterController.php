<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\Settings\CheckMaster;
use App\Services\ActivityLogService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Models\Settings\Company;
use App\Models\Settings\LedgerDetails;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Barryvdh\DomPDF\Facade\Pdf;
use ZipArchive;
use App\Services\Quickqore\QuickqoreService;


class CheckMasterController extends Controller
{
    /** Maximum checks per PDF chunk to avoid memory/timeout when printing 400+ checks */
    private const PRINT_CHUNK_SIZE = 50;

    public function store(Request $request)
    {
        $this->authorize('sp-access', 'check-printing-(paychex).update');
        $rules = [
            'data' => 'required|array|min:1',
            'data.*.ledger_id' => 'required|integer',
            'data.*.company_id' => 'required|integer',
            'data.*.from_company_id' => 'required|integer',
            'data.*.employee_id' => 'required|integer',
            'data.*.check_number' => 'required|integer',
            'data.*.check_date' => 'required|date',
            'data.*.payroll_eow' => 'required|date',
            'data.*.check_amount' => 'required|numeric',
            'data.*.check_type' => 'required|string|max:255',
        ];
        
        $request->validate($rules);
        
        $data = $request->input('data');
        $firstData = $data[0];
        DB::beginTransaction();

        $userRole = Auth::user()->role->name;
        $reviewFields = [];
        if ($userRole == 'Area Manager') {
            $reviewFields = [
                'am_reviewed' => true,
                'am_reviewed_by' => Auth::id(),
                'am_reviewed_at' => now(),
            ];
        } elseif ($userRole == 'HR') {
            $reviewFields = [
                'hr_reviewed' => true,
                'hr_reviewed_by' => Auth::id(),
                'hr_reviewed_at' => now(),
            ];
        } elseif ($userRole == 'admin' || $userRole == 'superadmin') {
            $reviewFields = [
                'admin_reviewed' => true,
                'admin_reviewed_by' => Auth::id(),
                'admin_reviewed_at' => now(),
            ];
        }

        // Check number must be unique per company + ledger
        $byCompanyLedger = [];
        foreach ($data as $item) {
            $key = $item['company_id'] . '|' . $item['ledger_id'] . '|' . $item['is_1099'];
            $byCompanyLedger[$key][] = $item['check_number'];
        }
        foreach ($byCompanyLedger as $key => $checknumbers) {
            $uniqueChecknumbers = array_unique($checknumbers);
            if (count($uniqueChecknumbers) != count($checknumbers)) {
                return to_json([
                    'saved' => false,
                    'message' => 'Check number must be unique within each company and ledger',
                ], 400);
            }
        }

        try {
            if (isset($firstData['payroll_eow']) && $firstData['payroll_eow'] && isset($firstData['check_type']) && $firstData['check_type']) {
                $existingChecks = CheckMaster::where('payroll_eow', $firstData['payroll_eow'])
                    ->where('check_type', $firstData['check_type'])
                    ->get()
                    ->keyBy('id');

                $trackedFields = ['ledger_id', 'company_id', 'from_company_id', 'employee_id', 'check_number', 'check_date', 'check_amount'];
                $reviewOnlyChecks = [];

                $toInsert = [];
                foreach ($data as $item) {
                    $payload = [
                        'ledger_id' => $item['ledger_id'],
                        'company_id' => $item['company_id'],
                        'from_company_id' => $item['from_company_id'],
                        'employee_id' => $item['employee_id'],
                        'check_number' => $item['check_number'],
                        'check_date' => $item['check_date'],
                        'payroll_eow' => $item['payroll_eow'],
                        'check_amount' => $item['check_amount'],
                        'check_type' => $item['check_type'],
                        'check_memo' => $item['check_memo'] ?? null,
                        'role_id' => $item['role_id'] ?? null,
                        'updated_by' => Auth::id(),
                        'updated_at' => now(),
                        'is_1099' => $item['is_1099'],
                    ];

                    if (isset($item['check_id']) && isset($existingChecks[$item['check_id']])) {
                        $existing = $existingChecks[$item['check_id']];
                        $mergedPayload = array_merge($payload, $reviewFields);
                        
                        if (!empty($reviewFields)) {
                            $reviewOnlyChecks[] = [
                                'id' => $existing->id,
                                'check_number' => $payload['check_number'],
                                'employee_id' => $payload['employee_id'],
                                'check_amount' => $payload['check_amount'],
                                'check_date' => $payload['check_date'],
                                'is_1099' => $payload['is_1099'],
                            ];
                        }

                        $existing->update($mergedPayload);
                    } else {
                        unset($payload['check_id']);
                        $toInsert[] = array_merge($payload, [
                            'created_by' => Auth::id(),
                            'created_at' => now(),
                        ], $reviewFields);
                    }
                }

                if (!empty($reviewOnlyChecks)) {
                    ActivityLogService::logReview(
                        'check_master',
                        null,
                        null,
                        ['checks' => $reviewOnlyChecks, 'count' => count($reviewOnlyChecks), 'reviewed_by' => $userRole],
                        "{$userRole} reviewed " . count($reviewOnlyChecks) . " check(s)"
                    );
                }

                if (count($toInsert) > 0) {
                    CheckMaster::insert($toInsert);
                    ActivityLogService::logBulkImport('check_master', $toInsert, "Checks inserted for eow: {$firstData['payroll_eow']}");
                }
            } else {
                CheckMaster::where('company_id', $firstData['company_id'])->where('payroll_eow', $firstData['payroll_eow'])->delete();
            }

            DB::commit();
            return to_json([
                'saved' => true,
                'message' => 'Check numbers saved successfully',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            dd($e);
            return to_json([
                'saved' => false,
                'message' => 'Check numbers save failed',
            ], 500);
        
        }
    }

    public function getCheckMaster($id, Request $request)
    {
        $this->authorize('sp-access', 'check-printing-(paychex).print');

        $rules = [
            'payroll_eow' => 'required|date',
            'check_type' => 'required|string|max:255',
        ];

        $request->validate($rules);
        $company = $request->session()->get('company');
        $payrollEow = $request->payroll_eow;
        $checkType = $request->check_type;
        $checkMaster = CheckMaster::where('company_id', $company)->where('payroll_eow', $payrollEow)->where('check_type', $checkType)->get();
        return to_json([
            'collection' => $checkMaster,
        ]);
    }
    public function getLatestCheckNumber($ledger_id, $company_id, Request $request)
    {
        // $this->authorize('sp-access', 'check-printing-(paychex).index');
        $company = Company::find($company_id);
        if(!$company){
            return to_json([
                'check_number' => 0,
            ], 404);
        }
        $checkNumber = CheckMaster::where('ledger_id', $ledger_id)->where('company_id', $company_id)->orderBy('check_number', 'desc')->first();
        $checkNumber = isset($checkNumber->check_number) ? $checkNumber->check_number +1  :0;
        if(!$checkNumber){
            $ledgerDetails = LedgerDetails::where('ledger_id', $ledger_id)->where('company_id', $company_id)->first();
            if($ledgerDetails){
                $checkNumber = $ledgerDetails->starting_check_number ?? 0;
            }else{
                $checkNumber = 0;
            }
        }
        return to_json([
            'check_number' => $checkNumber,
        ]);
    }
    public function printChecks(Request $request)
    {
        $this->authorize('sp-access', 'check-printing-(paychex).print');
        try {
            ini_set('max_execution_time', 300); // 5 minutes
            ini_set('memory_limit', '1024M');

            $checkIds = array_values(array_filter((array) $request->check_ids));
            if (empty($checkIds)) {
                return to_json([
                    'saved' => false,
                    'message' => 'No checks found',
                ], 404);
            }

            $path = public_path('uploads/chequeprint/');
            if (! File::isDirectory($path)) {
                File::makeDirectory($path, 0777, true, true);
            }

            $baseFilename = time() . '-Check' . ($request->payroll_eow ?? '') . ($request->check_type ?? '');
            $pdfOptions = [
                'isRemoteEnabled' => true,
                'isFontSubsettingEnabled' => false,
                'isHtml5ParserEnabled' => true,
                'chroot' => public_path(),
            ];

            $chunkSize = self::PRINT_CHUNK_SIZE;
            $chunks = array_chunk($checkIds, $chunkSize);
            $pdfPaths = [];
            $pdfFullPaths = [];

            foreach ($chunks as $index => $chunkIds) {
                $checkMaster = CheckMaster::with('employee', 'company', 'ledger.ledgerDetails', 'fromCompany')
                    ->whereIn('id', $chunkIds)
                    ->orderBy('id')
                    ->get();

                if ($checkMaster->isEmpty()) {
                    continue;
                }

                $chequeData = $this->buildChequeDataFromCheckMaster($checkMaster);
                $chunkFilename = count($chunks) > 1
                    ? $baseFilename . '-part' . ($index + 1) . '.pdf'
                    : $baseFilename . '.pdf';

                $fullPath = $path . $chunkFilename;
                Pdf::loadView('chequeprint', compact('chequeData'))
                    ->setOptions($pdfOptions)
                    ->save($fullPath);
                info($fullPath);
                $pdfPaths[] = url('uploads/chequeprint/' . $chunkFilename);
                $pdfFullPaths[] = ['path' => $fullPath, 'name' => $chunkFilename];

                unset($chequeData, $checkMaster);
                if (function_exists('gc_collect_cycles')) {
                    gc_collect_cycles();
                }
            }

            if (empty($pdfPaths)) {
                return to_json([
                    'saved' => false,
                    'message' => 'No checks found',
                ], 404);
            }

            $zipFilename = $baseFilename . '.zip';
            $zipFullPath = $path . $zipFilename;
            $zip = new ZipArchive();
            if ($zip->open($zipFullPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) === true) {
                foreach ($pdfFullPaths as $item) {
                    $zip->addFile($item['path'], $item['name']);
                }
                $zip->close();
            }

            $zippath = url('uploads/chequeprint/' . $zipFilename);
            $printedChecks = CheckMaster::whereIn('id', $checkIds)
                ->select('id', 'employee_id', 'company_id', 'check_number', 'check_date', 'payroll_eow', 'check_amount', 'check_type')
                ->get();
            $eows = $printedChecks->pluck('payroll_eow')->unique()->filter()->values()->toArray();
            ActivityLogService::logPrinting('check_master', null, [
                'urls' => [
                    'zippath' => $zippath,
                    'pdf_paths' => $pdfPaths,
                ],
                'eow' => $eows,
                'check_data' => $printedChecks->toArray(),
            ], 'Check printing: ' . count($checkIds) . ' check(s), EOW: ' . implode(', ', $eows));

            return to_json([
                'saved' => true,
                'message' => 'Check printing successful',
                'zippath' => $zippath,
            ]);
        } catch (\Throwable $th) {
            Log::error('Check printing failed', [
                'message' => $th->getMessage(),
                'trace' => $th->getTraceAsString(),
            ]);
            return to_json([
                'saved' => false,
                'message' => 'Check printing failed',
            ], 500);
        }
    }

    /**
     * Build cheque payload array for PDF from a collection of CheckMaster models.
     */
    private function buildChequeDataFromCheckMaster($checkMaster): array
    {
        $chequeData = [];
        foreach ($checkMaster as $item) {
            $paymentData = [];
            $paymentData['vendorname'] = $item->employee ? $item->employee->pos_name : '';
            $paymentData['vendoraddress1'] = $item->employee->street ?? '';
            $paymentData['vendoraddress2'] = '';
            $paymentData['vendorcity'] = $item->employee->city ?? '';
            $paymentData['vendorstate'] = $item->employee->state ?? '';
            $paymentData['vendorzipcode'] = $item->employee->zipcode ?? '';
            $paymentData['bankname'] = $item->ledger->name;
            $paymentData['bank_id'] = $item->ledger->id;
            $ledgerDetails = $item->ledger->ledgerDetails ?? null;
            $paymentData['bankaddress'] = optional($ledgerDetails)->bankaddress ?? '';
            $paymentData['transitcode'] = optional($ledgerDetails)->transitcode ?? '';
            $paymentData['routing'] = optional($ledgerDetails)->routing ?? '';
            $paymentData['accountno'] = optional($ledgerDetails)->account_no ?? '';
            $company = $item->company;
            $paymentData['companyname'] = $company->name ?? '';
            $paymentData['companyaddress'] = $company->address ?? '';
            $paymentData['companycity'] = $company->city ?? '';
            $paymentData['companystate'] = optional($company->state)->name ?? '';
            $paymentData['companyzipcode'] = $company->pincode ?? '';
            $paymentData['cheque'] = $item->check_number;
            $paymentData['date'] = $item->check_date;
            $paymentData['chequeamount'] = $item->check_amount;
            $paymentData['chequeamountwords'] = ucwords(strtolower(numberTowords($item->check_amount)));
            $paymentData['memo'] = $item->check_memo;

            $routing = $paymentData['routing'] ?: '';
            $accountno = $paymentData['accountno'] ?: '';
            $checkNum = str_pad((string) $item->check_number, 3, '0', STR_PAD_LEFT);
            $text = 'C' . $checkNum . 'C A' . $routing . 'A ' . $accountno . 'C';
            $micrPath = generateMicr($text);
            $paymentData['chequeimage'] = 'data:image/png;base64,' . base64_encode(file_get_contents($micrPath));

            $paymentData['bills'][0] = [
                'date' => $item->check_date,
                'reference' => $item->check_type,
                'amount' => $item->check_amount,
                'billnumber' => $item->check_memo,
                'dueamount' => $item->check_amount,
                'payment' => $item->check_amount,
                'memo' => $item->check_memo,
            ];
            $chequeData[] = $paymentData;
        }
        return $chequeData;
    }
    
    public function importQuickqore(Request $request)
    {
        $this->authorize('sp-access', 'check-printing-(paychex).import-quickqore');

        $ids = array_values(array_filter(array_map('intval', (array) ($request->ids ?? []))));

        if (empty($ids)) {
            return response()->json(['message' => 'No check IDs provided'], 422);
        }

        $checkMaster = CheckMaster::whereIn('id', $ids)->with('company', 'ledger', 'employee.aliases')->get();
        $ledgerDetails = LedgerDetails::whereIn('ledger_id', $checkMaster->pluck('ledger_id'))->get()->keyBy(function($item) {
            return $item->ledger_id .'|'.$item->company_id;
        });

        $payload =[];
        foreach ($checkMaster as $item) {
            $ledgerDetail = isset($ledgerDetails[$item->ledger_id.'|'.$item->company_id]) ? $ledgerDetails[$item->ledger_id.'|'.$item->company_id] : null;
            $payload[] = [
                'id' => $item->id,
                'store_no' => $item->company?->store_number,
                'bank_code' => $ledgerDetail?->code,
                'pos_name' => $item->employee?->pos_name,
                'aliases' => $item->employee?->aliases?->map(fn ($a) => [
                    'alias_employee_id' => $a->alias_employee_id,
                    'alias_name' => $a->alias_name,
                ])->values()->all() ?? [],
                'alias_names' => $item->employee?->aliases
                    ? collect($item->employee->aliasNames())->implode(', ')
                    : '',
                'amount' => $item->check_amount,
                'check_no' => $item->check_number,
                'date' => $item->check_date,
            ];
        }
        try {
            $response = (new QuickqoreService())->handlePayrollCheck($payload);
            return to_json([
                'saved' => true,
                'message' => 'Imported in quickqore successfully',
                'data' => $response,
            ]);
        } catch (\Exception $e) {
            Log::error('Quickqore import failed', ['message' => $e->getMessage(), 'ids' => $ids]);
            return to_json([
                'saved' => false,
                'message' => 'Import failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
