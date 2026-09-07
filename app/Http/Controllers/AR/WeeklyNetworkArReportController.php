<?php

namespace App\Http\Controllers\AR;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Settings\LedgerController;
use App\Models\AR\FeesUpload;
use App\Models\LedgerVouchers;
use App\Models\Settings\Company;
use App\Models\Settings\Ledger;
use App\Models\Settings\LedgerDetails;
use App\Services\ActivityLogService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WeeklyNetworkArReportController extends Controller
{
    private const FEE_LEDGER_FIELD_MAP = [
        '1001.52' => 'doordash',
        '1001.50' => 'uber',
        '1001.51' => 'grubhub',
        // '1001.01' => 'visa',
        '1001.53' => 'ez_cater',

        '1001.49' => 'ddd_cash',
        '1001.04' => 'meal_deal',
        '1001.02' => 'amex',
    ];

    public function index(Request $request)
    {
        $this->authorize('access', 'weekly-network-ar-report.index');

        $validated = $request->validate([
            'year' => 'required|integer|min:2020|max:2100',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'workgroup_ids' => 'nullable|array',
            'workgroup_ids.*' => 'integer|exists:workgroup,id',
        ]);

        $workgroupIds = collect($request->input('workgroup_ids', []))
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->values();

        $startDate = Carbon::parse($validated['start_date'])->format('Y-m-d');
        $endDate = Carbon::parse($validated['end_date'])->format('Y-m-d');

        $nextWeekStartDate = Carbon::parse($startDate)->addWeek()->format('Y-m-d');
        $nextWeekEndDate = Carbon::parse($endDate)->addWeek()->format('Y-m-d');
        $pjCodes = LedgerController::FEE_LEDGER_CODES;
        $ledgerColumns = Ledger::query()
            ->join('ledger_details', 'ledger_details.ledger_id', '=', 'ledgers.id')
            ->whereIn('ledger_details.code', $pjCodes)
            ->selectRaw('ledger_details.code as code, MIN(ledgers.name) as name')
            ->groupBy('ledger_details.code')
            ->orderByRaw(
                'FIELD(ledger_details.code, "' . implode('","', $pjCodes) . '")'
            )
            ->get()
            ->values()
            ->map(function ($ledger) {
                return [
                    'code' => $ledger->code,
                    'key' => $this->ledgerKey($ledger->code),
                    'name' => $ledger->name,
                    'label' => $ledger->code . ' - ' . $ledger->name,
                ];
            });

        $companies = Company::selectRaw('id, store_number, name, CONCAT(store_number, " - ", name) as company_name')
            ->authorizedCompanies('id', false)
            ->when($workgroupIds->isNotEmpty(), function ($query) use ($workgroupIds) {
                return $query->whereIn('workgroup_id', $workgroupIds->all());
            })
            ->orderBy('store_number')
            ->get();

        if ($companies->isEmpty()) {
            return to_json([
                'ledgers' => $ledgerColumns,
                'data' => [],
                'period' => [
                    'year' => (int) $validated['year'],
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                ],
            ]);
        }

        $companyIds = $companies->pluck('id')->all();

        $companyLedgerMap = LedgerDetails::query()
            ->whereIn('company_id', $companyIds)
            ->whereIn('code', $pjCodes)
            ->select('company_id', 'code', 'ledger_id')
            ->get()
            ->groupBy('company_id');

        $ledgerIds = $companyLedgerMap
            ->flatten()
            ->pluck('ledger_id')
            ->unique()
            ->values()
            ->all();

        $weekTotals = collect();

        if (!empty($ledgerIds)) {
            $weekTotals = LedgerVouchers::query()
                ->whereBetween('date', [$startDate, $endDate])
                ->whereIn('company_id', $companyIds)
                ->whereIn('ledger_id', $ledgerIds)
                ->selectRaw("
                    company_id,
                    ledger_id,
                    SUM(CASE WHEN voucher_type = 'pj_payments' THEN debit ELSE 0 END) as payment,
                    SUM(CASE WHEN voucher_type = 'fees_uploads' OR voucher_type = 'pj_payment_fees' THEN credit ELSE 0 END) as fees,
                    SUM(CASE WHEN voucher_type = 'ddc_doordash' THEN credit ELSE 0 END) as ddc_doordash
                ")
                ->groupBy('company_id', 'ledger_id')
                ->get()
                ->keyBy(fn ($row) => $row->company_id . '_' . $row->ledger_id);

                $depositTotals = LedgerVouchers::query()
                ->whereBetween('date', [$nextWeekStartDate, $nextWeekEndDate])
                ->whereIn('company_id', $companyIds)
                ->whereIn('ledger_id', $ledgerIds)
                ->selectRaw("
                    company_id,
                    ledger_id,
                    SUM(CASE WHEN voucher_type = 'bank_entries' THEN credit ELSE 0 END) as deposit
                ")
                ->groupBy('company_id', 'ledger_id')
                ->get()
                ->keyBy(fn ($row) => $row->company_id . '_' . $row->ledger_id);
        }

        $reportData = [];
        foreach ($companies as $index => $company) {
            $row = [
                'sr_no' => $index + 1,
                'company_id' => $company->id,
                'store_number' => $company->store_number,
                'company_name' => $company->company_name,
                'ledgers' => [],
            ];

            $companyLedgers = $companyLedgerMap->get($company->id, collect())->keyBy('code');

            foreach ($ledgerColumns as $ledgerColumn) {
                $ledgerDetail = $companyLedgers->get($ledgerColumn['code']);
                $key = $ledgerColumn['key'];

                if (!$ledgerDetail) {
                    $row['ledgers'][$key] = [
                        'ledger_code' => $ledgerColumn['code'],
                        'payment' => 0,
                        'deposit' => 0,
                        'fees' => 0,
                        'balance' => 0,
                    ];
                    continue;
                }

                $mapKey = $company->id . '_' . $ledgerDetail->ledger_id;
                $week = $weekTotals[$mapKey] ?? null;
                $deposit = $depositTotals[$mapKey] ?? null;
                
                $payment = round((float) ($week->payment ?? 0), 2);
                $deposit = round((float) ($deposit->deposit ?? 0), 2); 
                $fees = round((float) ($week->fees ?? 0), 2);
                $ddc_doordash = round((float) ($week->ddc_doordash ?? 0), 2);
                $balance = round($payment - $fees - $deposit - $ddc_doordash, 2);

                $row['ledgers'][$key] = [
                    'ledger_code' => $ledgerColumn['code'],
                    'payment' => $payment,
                    'deposit' => $deposit,
                    'fees' => $fees,
                    'ddc_doordash' => $ddc_doordash,
                    'balance' => $balance,
                ];
            }

            $reportData[] = $row;
        }

        return to_json([
            'ledgers' => $ledgerColumns,
            'data' => $reportData,
            'period' => [
                'year' => (int) $validated['year'],
                'start_date' => $startDate,
                'end_date' => $endDate,
            ],
        ]);
    }

    public function dueNetworkReport(Request $request)
    {
        $this->authorize('access', 'balance-due-network-report.index');

        $validated = $request->validate([
            'year' => 'required|integer|min:2020|max:2100',
            'end_date' => 'required|date|after_or_equal:start_date',
            'workgroup_ids' => 'nullable|array',
            'workgroup_ids.*' => 'integer|exists:workgroup,id',
        ]);

        $endDate = Carbon::parse($validated['end_date'])->format('Y-m-d');

        $workgroupIds = collect($request->input('workgroup_ids', []))
        ->map(fn ($id) => (int) $id)
        ->filter()
        ->values();
        

        $companies = Company::query()
            ->authorizedCompanies('id', false)
            ->when($workgroupIds->isNotEmpty(), function ($query) use ($workgroupIds) {
                return $query->whereIn('workgroup_id', $workgroupIds->all());
            })
            ->selectRaw('id, store_number, name, CONCAT(store_number, " - ", name) as company_name')
            ->get()->keyBy('id');

        $companyIds = $companies->keys()->all();

        $pjCodes = LedgerController::FEE_LEDGER_CODES;

        $ledgerDetails = LedgerDetails::query()
            ->whereIn('company_id', $companyIds)
            ->whereIn('code', $pjCodes)
            ->get()
            ->keyBy(fn ($row) => $row->company_id . '_' . $row->code);
        $ledgerIds = $ledgerDetails->pluck('ledger_id')->unique()->values()->all();
        
        $ledgerColumns = Ledger::query()
            ->join('ledger_details', 'ledger_details.ledger_id', '=', 'ledgers.id')
            ->whereIn('ledger_details.code', $pjCodes)
            ->selectRaw('ledger_details.code as code, MIN(ledgers.name) as name')
            ->groupBy('ledger_details.code')
            ->get()
            ->sortBy(function ($ledger) use ($pjCodes) {
                $index = array_search($ledger->code, $pjCodes, true);
                return $index === false ? 999 : $index;
            })
            ->values()
            ->map(function ($ledger) {
                return [
                    'code' => $ledger->code,
                    'key' => $this->ledgerKey($ledger->code),
                    'name' => $ledger->name,
                    'label' => $ledger->code . ' - ' . $ledger->name,
                ];
            });

        $LedgerData = LedgerVouchers::query()
            ->where('date', '<=', $endDate)
            ->whereIn('company_id', $companyIds)
            ->whereIn('ledger_id', $ledgerIds)
            ->selectRaw("
                company_id,
                ledger_id,
                SUM(credit - debit) as balance
            ")
            ->groupBy('company_id', 'ledger_id')
            ->get()
            ->keyBy(fn ($row) => $row->company_id . '_' . $row->ledger_id);
        
        $reportData = [];
        foreach ($companies as $index => $company) {
            $row = [
                'sr_no' => $index + 1,
                'company_id' => $company->id,
                'store_number' => $company->store_number,
                'company_name' => $company->company_name,
                'ledgers' => [],
            ];
            foreach ($ledgerColumns as $ledgerColumn) {
                $ledgerDetail = isset($ledgerDetails[$company->id . '_' . $ledgerColumn['code']]) ? $ledgerDetails[$company->id . '_' . $ledgerColumn['code']] : null;
                if(!$ledgerDetail) {
                    $row['ledgers'][$ledgerColumn['key']] = [
                        'ledger_code' => $ledgerColumn['code'],
                        'balance' => 0,
                    ];
                    continue;
                }
                $balance = isset($LedgerData[$company->id . '_' . $ledgerDetail->ledger_id]) ? $LedgerData[$company->id . '_' . $ledgerDetail->ledger_id]->balance : 0;
                $row['ledgers'][$ledgerColumn['key']] = [
                    'ledger_code' => $ledgerColumn['code'],
                    'balance' => -1 * $balance,
                ];
            }
            $reportData[] = $row;
        }
        return to_json([
            'data' => $reportData,
            'ledgers' => $ledgerColumns,

        ]);
    }
    public function updateFees(Request $request)
    {
        $this->authorize('access', 'weekly-network-ar-report.update');

        $validated = $request->validate([
            'date' => 'required|date',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'items' => 'required|array|min:1',
            'items.*.company_id' => 'required|integer|exists:company,id',
            'items.*.ledger_code' => 'required|string',
            'items.*.fees' => 'required|numeric',
            'items.*.ddc_doordash' => 'nullable|numeric',
        ]);

        $pjCodes = LedgerController::FEE_LEDGER_CODES;
        $startDate = Carbon::parse($validated['start_date'])->format('Y-m-d');
        $endDate = Carbon::parse($validated['end_date'])->format('Y-m-d');
        $date = Carbon::parse($validated['date'])->format('Y-m-d');

        $activityChanges = [];
        $updatedCompanyIds = [];
        $affectedLedgerKeys = [];
        $preparedItems = [];

        DB::beginTransaction();

        $companyIds = array_unique(array_column($validated['items'], 'company_id'));

        $companies = Company::query()
            ->authorizedCompanies('id', false)
            ->whereIn('id', $companyIds)
            ->get(['id', 'store_number'])
            ->keyBy('id');

        $authorizedCompanyIds = $companies->keys()->all();

        $ledgerDetails = LedgerDetails::query()
            ->whereIn('company_id', $authorizedCompanyIds)
            ->whereIn('code', $pjCodes)
            ->get()
            ->keyBy(fn ($row) => $row->company_id . '_' . $row->code);

        try {
            foreach ($validated['items'] as $item) {
                if (!in_array($item['ledger_code'], $pjCodes, true)) {
                    throw new \InvalidArgumentException('Invalid ledger code: ' . $item['ledger_code']);
                }

                $company = $companies->get($item['company_id']);

                if (!$company) {
                    throw new \InvalidArgumentException('Company not found or not authorized');
                }

                $ledgerDetail = $ledgerDetails->get($item['company_id'] . '_' . $item['ledger_code']);

                if (!$ledgerDetail) {
                    throw new \InvalidArgumentException("Ledger {$item['ledger_code']} not configured for store {$company->store_number}");
                }

                $preparedItems[] = [
                    'company_id' => $item['company_id'],
                    'store_number' => $company->store_number,
                    'ledger_code' => $item['ledger_code'],
                    'ledger_id' => $ledgerDetail->ledger_id,
                    'fees' => round((float) $item['fees'], 2),
                    'fee_field' => self::FEE_LEDGER_FIELD_MAP[$item['ledger_code']] ?? null,
                    'ddc_doordash' => round((float) $item['ddc_doordash'], 2),
                    'ddc_doordash_field' => $item['ledger_code']== '1001.52' ? 'ddc_doordash' : null,
                ];
            }

            $oldFeesByLedger = $this->prefetchOldFees($preparedItems, $startDate, $endDate);

            $this->applyFeeUpdatesBatch($preparedItems, $startDate, $endDate, $date);

            foreach ($preparedItems as $item) {
                $ledgerKey = $this->ledgerKey($item['ledger_code']);
                $mapKey = $item['company_id'] . '_' . $item['ledger_id'];

                $activityChanges[] = [
                    'company_id' => $item['company_id'],
                    'store_number' => $item['store_number'],
                    'ledger_code' => $item['ledger_code'],
                    'date' => $date,
                    'old_fees' => round((float) ($oldFeesByLedger[$mapKey]['fees'] ?? 0), 2),
                    'new_fees' => $item['fees'],
                    'old_ddc_doordash' => round((float) ($oldFeesByLedger[$mapKey]['ddc_doordash'] ?? 0), 2),
                    'new_ddc_doordash' => $item['ddc_doordash'],
                ];

                $updatedCompanyIds[$item['company_id']] = true;
                $affectedLedgerKeys[$ledgerKey] = $item['ledger_code'];
            }

            if (!empty($activityChanges)) {
                $oldValues = array_map(fn ($change) => [
                    'company_id' => $change['company_id'],
                    'store_number' => $change['store_number'],
                    'ledger_code' => $change['ledger_code'],
                    'date' => $change['date'],
                    'fees' => $change['old_fees'],
                    'ddc_doordash' => $change['old_ddc_doordash'],
                ], $activityChanges);

                $newValues = array_map(fn ($change) => [
                    'company_id' => $change['company_id'],
                    'store_number' => $change['store_number'],
                    'ledger_code' => $change['ledger_code'],
                    'date' => $change['date'],
                    'fees' => $change['new_fees'],
                    'ddc_doordash' => $change['new_ddc_doordash'],
                ], $activityChanges);

                ActivityLogService::logUpdate(
                    'weekly_network_ar_report',
                    null,
                    ['changes' => $oldValues],
                    ['changes' => $newValues],
                    'Weekly Network AR Report: ' . count($activityChanges) . ' fee(s) updated for period ' . $startDate . ' to ' . $endDate
                );
            }

            $updatedRows = $this->buildUpdatedRows(
                array_keys($updatedCompanyIds),
                array_values($affectedLedgerKeys),
                $startDate,
                $endDate
            );

            DB::commit();

            return to_json([
                'saved' => true,
                'message' => count($activityChanges) . ' fee(s) updated successfully',
                'data' => $updatedRows,
            ]);
        } catch (\InvalidArgumentException $e) {
            DB::rollBack();

            return to_json([
                'saved' => false,
                'message' => $e->getMessage(),
            ], 422);
        } catch (\Exception $e) {
            DB::rollBack();

            return to_json([
                'saved' => false,
                'message' => 'Failed to update fees',
            ], 500);
        }
    }

    private function prefetchOldFees(array $preparedItems, string $startDate, string $endDate): array
    {
        if (empty($preparedItems)) {
            return [];
        }

        $companyIds = array_unique(array_column($preparedItems, 'company_id'));
        $ledgerIds = array_unique(array_column($preparedItems, 'ledger_id'));

        return LedgerVouchers::query()
            ->whereIn('company_id', $companyIds)
            ->whereIn('ledger_id', $ledgerIds)
            ->whereIn('voucher_type', ['fees_uploads', 'ddc_doordash'])
            ->whereBetween('date', [$startDate, $endDate])
            ->selectRaw("company_id, ledger_id, SUM(CASE WHEN voucher_type = 'fees_uploads' THEN debit ELSE 0 END) as fees, SUM(CASE WHEN voucher_type = 'ddc_doordash' THEN debit ELSE 0 END) as ddc_doordash")
            ->groupBy('company_id', 'ledger_id')
            ->get()
            ->keyBy(fn ($row) => $row->company_id . '_' . $row->ledger_id)
            ->map(fn ($row) => [
                'fees' => (float) $row->fees,
                'ddc_doordash' => (float) $row->ddc_doordash,
            ])
            ->all();
    }

    private function applyFeeUpdatesBatch(array $preparedItems, string $startDate, string $endDate, string $date): void
    {
        if (empty($preparedItems)) {
            return;
        }

        $ledgerDeletePairs = collect($preparedItems)
            ->map(fn ($item) => ['company_id' => $item['company_id'], 'ledger_id' => $item['ledger_id']])
            ->unique(fn ($pair) => $pair['company_id'] . '_' . $pair['ledger_id'])
            ->values();

        LedgerVouchers::query()
            ->whereIn('voucher_type', ['fees_uploads', 'ddc_doordash'])
            ->whereBetween('date', [$startDate, $endDate])
            ->where(function ($query) use ($ledgerDeletePairs) {
                foreach ($ledgerDeletePairs as $pair) {
                    $query->orWhere(function ($subQuery) use ($pair) {
                        $subQuery->where('company_id', $pair['company_id'])
                            ->where('ledger_id', $pair['ledger_id']);
                    });
                }
            })
            ->delete();

        $mappedItems = array_filter($preparedItems, fn ($item) => $item['fee_field'] !== null);
        $directItems = array_filter($preparedItems, fn ($item) => $item['fee_field'] === null);

        if (!empty($mappedItems)) {
            $this->applyMappedFeeUpdatesBatch($mappedItems, $startDate, $endDate, $date);
        }
        if (!empty($directItems)) {
            $now = now();
            $ledgerVouchers = array_map(fn ($item) => [
                'company_id' => $item['company_id'],
                'ledger_id' => $item['ledger_id'],
                'opp_ledger_id' => 0,
                'amount' => $item['fees'],
                'debit' => 0,
                'credit' => $item['fees'],
                'voucher_id' => 0,
                'voucher_items_id' => null,
                'voucher_type' => 'fees_uploads',
                'dbtable' => 'fees_uploads',
                'check_number' => null,
                'description' => 'Weekly Network AR Report fee update',
                'date' => $date,
                'created_at' => $now,
                'updated_at' => $now,
            ], $directItems);

            foreach (array_chunk($ledgerVouchers, 300) as $chunk) {
                LedgerVouchers::insert($chunk);
            }
        }
    }

    private function applyMappedFeeUpdatesBatch(array $mappedItems, string $startDate, string $endDate, string $date): void
    {
        $companyIds = array_unique(array_column($mappedItems, 'company_id'));
        $ledgerMaps = $this->feesUploadLedgerMaps();

        $weekFeesUploads = FeesUpload::query()
            ->whereIn('company_id', $companyIds)
            ->whereBetween('date', [$startDate, $endDate])
            ->get();

        if ($date < $startDate || $date > $endDate) {
            $outsideDateUploads = FeesUpload::query()
                ->whereIn('company_id', $companyIds)
                ->where('date', $date)
                ->get();

            $weekFeesUploads = $weekFeesUploads->merge($outsideDateUploads)->unique('id');
        }

        $uploadsByCompany = $weekFeesUploads->groupBy('company_id');
        $dateFeesUploads = $weekFeesUploads
            ->filter(fn ($upload) => Carbon::parse($upload->date)->toDateString() === $date)
            ->keyBy('company_id');

        $modifiedUploads = [];

        foreach ($mappedItems as $item) {
            $feeField = $item['fee_field'];
            $ddc_doordashField = $item['ddc_doordash_field'];
            $companyWeekUploads = $uploadsByCompany->get($item['company_id'], collect());

            foreach ($companyWeekUploads as $feesUpload) {
                if ((float) $feesUpload->{$feeField} === 0.0) {
                    continue;
                }

                $feesUpload->{$feeField} = 0;
                $modifiedUploads[$feesUpload->id] = $feesUpload;
            }

            $feesUpload = $dateFeesUploads->get($item['company_id']);

            if (!$feesUpload) {
                $feesUpload = new FeesUpload([
                    'company_id' => $item['company_id'],
                    'date' => $date,
                    'ddd_cash' => 0,
                    'ez_cater' => 0,
                    'meal_deal' => 0,
                    'visa' => 0,
                    'amex' => 0,
                    'doordash' => 0,
                    'ddc_doordash' => 0,
                    'uber' => 0,
                    'grubhub' => 0,
                ]);
                $dateFeesUploads->put($item['company_id'], $feesUpload);
            }
            $feesUpload->{$feeField} = $item['fees'];
            if ($item['ddc_doordash_field'] === 'ddc_doordash') {
                $feesUpload->{$ddc_doordashField} = $item['ddc_doordash'];
            }

            if ($feesUpload->exists) {
                $modifiedUploads[$feesUpload->id] = $feesUpload;
            } else {
                $modifiedUploads['new_' . $item['company_id']] = $feesUpload;
            }
        }

        foreach ($modifiedUploads as $feesUpload) {
            $feesUpload->save();
        }

        $uploadsToSync = collect($modifiedUploads)->unique('id')->values();

        if ($uploadsToSync->isNotEmpty()) {
            $this->syncFeesUploadLedgerVouchersBatch($uploadsToSync, $ledgerMaps);
        }
    }

    private function feesUploadLedgerMaps(): array
    {
        return [
            'ddd_cash' => LedgerDetails::where('code', '1001.49')->pluck('ledger_id', 'company_id'),
            'ez_cater' => LedgerDetails::where('code', '1001.53')->pluck('ledger_id', 'company_id'),
            'meal_deal' => LedgerDetails::where('code', '1001.04')->pluck('ledger_id', 'company_id'),
            'visa' => LedgerDetails::where('code', '1001.01')->pluck('ledger_id', 'company_id'),
            'amex' => LedgerDetails::where('code', '1001.02')->pluck('ledger_id', 'company_id'),
            'doordash' => LedgerDetails::where('code', '1001.52')->pluck('ledger_id', 'company_id'),
            'ddc_doordash' => LedgerDetails::where('code', '1001.54')->pluck('ledger_id', 'company_id'),
            'uber' => LedgerDetails::where('code', '1001.50')->pluck('ledger_id', 'company_id'),
            'grubhub' => LedgerDetails::where('code', '1001.51')->pluck('ledger_id', 'company_id'),
        ];
    }

    private function buildUpdatedRows(array $companyIds, array $ledgerCodes, string $startDate, string $endDate): array
    {
        if (empty($companyIds) || empty($ledgerCodes)) {
            return [];
        }

        $ledgerDetails = LedgerDetails::query()
            ->whereIn('company_id', $companyIds)
            ->whereIn('code', $ledgerCodes)
            ->get()
            ->keyBy(fn ($row) => $row->company_id . '_' . $row->code);

        $ledgerIds = $ledgerDetails->pluck('ledger_id')->unique()->values()->all();

        $weekTotals = LedgerVouchers::query()
            ->whereBetween('date', [$startDate, $endDate])
            ->whereIn('company_id', $companyIds)
            ->whereIn('ledger_id', $ledgerIds)
            ->selectRaw("
                company_id,
                ledger_id,
                SUM(CASE WHEN voucher_type = 'pj_payments' THEN debit ELSE 0 END) as payment,
                SUM(CASE WHEN voucher_type = 'bank_entries' THEN credit ELSE 0 END) as deposit,             
                SUM(CASE WHEN voucher_type = 'fees_uploads' || voucher_type = 'pj_payment_fees' THEN credit ELSE 0 END) as fees,
                SUM(CASE WHEN voucher_type = 'ddc_doordash' THEN credit ELSE 0 END) as ddc_doordash
            ")
            ->groupBy('company_id', 'ledger_id')
            ->get()
            ->keyBy(fn ($row) => $row->company_id . '_' . $row->ledger_id);

        $rows = [];
        foreach ($companyIds as $companyId) {
            $row = [
                'company_id' => $companyId,
                'ledgers' => [],
            ];

            foreach ($ledgerCodes as $ledgerCode) {
                $ledgerDetail = $ledgerDetails->get($companyId . '_' . $ledgerCode);
                if (!$ledgerDetail) {
                    continue;
                }

                $week = $weekTotals->get($companyId . '_' . $ledgerDetail->ledger_id);
                $payment = round((float) ($week->payment ?? 0), 2);
                $deposit = round((float) ($week->deposit ?? 0), 2);
                $fees = round((float) ($week->fees ?? 0), 2);
                $ddc_doordash = round((float) ($week->ddc_doordash ?? 0), 2);

                $row['ledgers'][$this->ledgerKey($ledgerCode)] = [
                    'ledger_code' => $ledgerCode,
                    'payment' => $payment,
                    'deposit' => $deposit,
                    'fees' => $fees,
                    'ddc_doordash' => $ddc_doordash,
                    'balance' => round($payment + $fees - $deposit - $ddc_doordash, 2),
                ];
            }

            if (!empty($row['ledgers'])) {
                $rows[] = $row;
            }
        }

        return $rows;
    }

    private function syncFeesUploadLedgerVouchersBatch($feesUploads, array $ledgerMaps): void
    {
        $uploadIds = $feesUploads->pluck('id')->filter()->all();

        if (empty($uploadIds)) {
            return;
        }

        LedgerVouchers::query()
            ->whereIn('voucher_id', $uploadIds)
            ->where('voucher_type', 'fees_uploads')
            ->delete();

        $now = now();
        $ledgerVouchersArray = [];

        foreach ($feesUploads as $feesUpload) {
            foreach ($ledgerMaps as $field => $ledgerByCompany) {
                if (!isset($ledgerByCompany[$feesUpload->company_id])) {
                    continue;
                }

                $amount = (float) ($feesUpload->{$field} ?? 0);
                $ledgerVouchersArray[] = [
                    'company_id' => $feesUpload->company_id,
                    'ledger_id' => $ledgerByCompany[$feesUpload->company_id],
                    'opp_ledger_id' => 0,
                    'amount' => $amount,
                    'debit' => 0,
                    'credit' => $amount,
                    'voucher_id' => $feesUpload->id,
                    'voucher_items_id' => null,
                    'voucher_type' => 'fees_uploads',
                    'dbtable' => 'fees_uploads',
                    'check_number' => null,
                    'description' => null,
                    'date' => $feesUpload->date,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];

                if($field === 'doordash' && $feesUpload->ddc_doordash > 0) {
                    $ledgerVouchersArray[] = [
                        'company_id' => $feesUpload->company_id,
                        'ledger_id' => $ledgerByCompany[$feesUpload->company_id],
                        'opp_ledger_id' => 0,
                        'amount' => $feesUpload->ddc_doordash,
                        'debit' => 0,
                        'credit' => $feesUpload->ddc_doordash,
                        'voucher_id' => $feesUpload->id,
                        'voucher_items_id' => null,
                        'voucher_type' => 'ddc_doordash',
                        'dbtable' => 'fees_uploads',
                        'check_number' => null,
                        'description' => null,
                        'date' => $feesUpload->date,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }

            }
        }

        foreach (array_chunk($ledgerVouchersArray, 300) as $chunk) {
            LedgerVouchers::insert($chunk);
        }
    }

    private function ledgerKey(string $code): string
    {
        return str_replace('.', '_', $code);
    }
}
