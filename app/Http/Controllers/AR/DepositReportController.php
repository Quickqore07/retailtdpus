<?php

namespace App\Http\Controllers\AR;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Settings\LedgerController;
use App\Models\AR\PjPaymentItem;
use App\Models\DataEntry\BankEntry;
use App\Models\DataEntry\BankEntryChildAmount;
use App\Models\DataEntry\BankEntryItem;
use App\Models\DataEntry\OldDailySale;
use App\Models\LedgerVouchers;
use App\Models\Settings\LedgerDetails;
use App\Services\PjPaymentSettlementService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DepositReportController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('access', 'deposit-report.index');
        
        $validated = $request->validate([
            'ledger_id' => 'required|integer',
            'company_id' => 'nullable|integer',
            'start_date' => 'required|date',
            'end_date' => 'required|date',
        ]);

        // Get ledger details to determine which table to query
        $ledgerDetails = LedgerDetails::where('ledger_id', $validated['ledger_id'])->first();
        
        if (!$ledgerDetails) {
            return to_json([
                'success' => false,
                'message' => 'Ledger not found',
            ], 404);
        }

        $ledgerCode = $ledgerDetails->code;

        // Check if it's Visa/Mastercard ledger or other ledger
        if ($ledgerCode == LedgerController::VISA_MASTER_LEDGER_CODE) {
            // Fetch from PjPaymentItem
            return $this->getVisaMastercardDepositReport($validated);
        } else {
            // Fetch from DailySale
            return $this->getDailySaleDepositReport($validated);
        }
    }

    private function getVisaMastercardDepositReport($validated)
    {
        // Get payment items for Visa/Mastercard within the date range
        $reportData = PjPaymentItem::query()
            ->join('pj_payments', 'pj_payments_items.pj_payment_id', '=', 'pj_payments.id')
            ->leftJoin('bank_entry_child_amounts', 'pj_payments_items.bank_child_amount_id', '=', 'bank_entry_child_amounts.id')
            ->leftJoin('bank_entry_items', 'bank_entry_child_amounts.bank_entry_item_id', '=', 'bank_entry_items.id')
            ->leftJoin('bank_entries', 'bank_entry_items.bank_entry_id', '=', 'bank_entries.id')
            ->where('pj_payments.company_id', $validated['company_id'])
            ->where('pj_payments.date', '>=', $validated['start_date'])
            ->where('pj_payments.date', '<=', $validated['end_date'])
            ->where('pj_payments_items.ledger_id', $validated['ledger_id'])
            ->select([
                'pj_payments_items.id',
                'pj_payments.date as payment_date',
                'pj_payments_items.amount as payment_amount',
                'pj_payments_items.settled',
                'bank_entries.date as deposit_date',
                'bank_entry_child_amounts.deposit as deposit_amount',
                'bank_entry_child_amounts.fees as fees',
                DB::raw('(bank_entry_child_amounts.deposit + bank_entry_child_amounts.fees) as total'),
                'pj_payments_items.ledger_id',
                'pj_payments.id as pj_payment_id',
                'bank_entries.id as bank_entry_id'
            ])
            ->orderBy('pj_payments.date', 'asc')
            ->get()
            ->toArray();

        // Calculate opening balance (all payments before start date minus all deposits before start date)
        $openingBalance = PjPaymentItem::query()
            ->join('pj_payments', 'pj_payments_items.pj_payment_id', '=', 'pj_payments.id')
            ->leftJoin('bank_entry_child_amounts', 'pj_payments_items.bank_child_amount_id', '=', 'bank_entry_child_amounts.id')
            ->where('pj_payments.company_id', $validated['company_id'])
            ->where('pj_payments.date', '<', $validated['start_date'])
            ->where('pj_payments_items.ledger_id', $validated['ledger_id'])
            ->selectRaw('SUM(pj_payments_items.amount) - COALESCE(SUM(bank_entry_child_amounts.deposit), 0) - COALESCE(SUM(bank_entry_child_amounts.fees), 0) as opening_balance')
            ->first();

        $openingBalance = $openingBalance ? $openingBalance->opening_balance : 0;

        // Calculate cumulative balance for each row
        $balance = $openingBalance;
        foreach ($reportData as $key => $item) {
            $balance += $item['payment_amount'];
            if ($item['settled']) {
                $balance -= ($item['deposit_amount'] + $item['fees']);
            }
            $reportData[$key]['cumulative_balance'] = round($balance, 2);
        }

        return to_json([
            'report_data' => $reportData,
            'opening_balance' => $openingBalance,
        ]);
    }

    private function getDailySaleDepositReport($validated)
    {
        // Get daily sales within the date range
        $reportData = OldDailySale::query()
            ->leftJoin('bank_entry_child_amounts', 'old_daily_sales.bank_child_amount_id', '=', 'bank_entry_child_amounts.id')
            ->leftJoin('bank_entry_items', 'bank_entry_child_amounts.bank_entry_item_id', '=', 'bank_entry_items.id')
            ->leftJoin('bank_entries', 'bank_entry_items.bank_entry_id', '=', 'bank_entries.id')
            ->where('old_daily_sales.company_id', $validated['company_id'])
            ->where('old_daily_sales.date', '>=', $validated['start_date'])
            ->where('old_daily_sales.date', '<=', $validated['end_date'])
            ->select([
                'old_daily_sales.id',
                'old_daily_sales.date as payment_date',
                'old_daily_sales.cash_bag as payment_amount',
                'old_daily_sales.settled',
                'bank_entries.date as deposit_date',
                'bank_entry_child_amounts.deposit as deposit_amount',
                'bank_entry_child_amounts.fees as fees',
                DB::raw('(bank_entry_child_amounts.deposit + bank_entry_child_amounts.fees) as total'),
                DB::raw($validated['ledger_id'] . ' as ledger_id'),
                'old_daily_sales.id as daily_sale_id',
                'bank_entries.id as bank_entry_id'
            ])
            ->where('old_daily_sales.cash_bag', '>', 0)
            ->orderBy('old_daily_sales.date', 'asc')
            ->get()
            ->toArray();

        // Calculate opening balance (all daily sales before start date minus all deposits before start date)
        $openingBalance = OldDailySale::query()
            ->leftJoin('bank_entry_child_amounts', 'old_daily_sales.bank_child_amount_id', '=', 'bank_entry_child_amounts.id')
            ->where('old_daily_sales.company_id', $validated['company_id'])
            ->where('old_daily_sales.date', '<', $validated['start_date'])
            ->selectRaw('SUM(old_daily_sales.cash_bag) - COALESCE(SUM(bank_entry_child_amounts.deposit), 0) - COALESCE(SUM(bank_entry_child_amounts.fees), 0) as opening_balance')
            ->first();

        $openingBalance = $openingBalance ? $openingBalance->opening_balance : 0;

        // Calculate cumulative balance for each row
        $balance = $openingBalance;
        foreach ($reportData as $key => $item) {
            $balance += $item['payment_amount'];
            if ($item['settled']) {
                $balance -= ($item['deposit_amount'] + $item['fees']);
            }
            $reportData[$key]['cumulative_balance'] = round($balance, 2);
        }

        return to_json([
            'report_data' => $reportData,
            'opening_balance' => $openingBalance,
        ]);
    }

    public function missingBankAmountReport(Request $request)
    {
        $this->authorize('access', 'missing-bank-amount-report.index');
        
        $validated = $request->validate([
            'ledger_id' => 'required|integer',
            'company_id' => 'nullable|integer',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
            ]);
        $company_id = $validated['company_id'] ?? null;
        $ledger_id = $validated['ledger_id'] ?? null;
        $start_date = $validated['start_date'] ?? null;
        $end_date = $validated['end_date'] ?? null;

        $ledgerDetails = LedgerDetails::where('ledger_id', $ledger_id)->first();
        if(!$ledgerDetails) {
            return to_json([
                'success' => false,
                'message' => 'Ledger not found',
            ], 404);
        }

        $baseQuery = BankEntryChildAmount::
        join('bank_entry_items', 'bank_entry_child_amounts.bank_entry_item_id', '=', 'bank_entry_items.id')
        ->join('bank_entries', 'bank_entry_items.bank_entry_id', '=', 'bank_entries.id')
        ->join('company', 'bank_entries.company_id', '=', 'company.id')
        ->where('bank_entry_items.ledger_id', $ledger_id)
        ->when($company_id, function($query) use ($company_id) {
            $query->where('bank_entries.company_id', $company_id);
        })
        ->when($start_date, function($query) use ($start_date) {
            $query->where('bank_entries.date', '>=', $start_date);
        })
        ->when($end_date, function($query) use ($end_date) {
            $query->where('bank_entries.date', '<=', $end_date);
        })
        ->authorizedCompanies('bank_entries.company_id',false)
        ->select([
            'bank_entry_child_amounts.*',
            'bank_entry_child_amounts.id as child_amount_id',
            'bank_entry_items.id as bank_entry_item_id',
            'company.name as company_name',
            'company.store_number',
            'company.id as company_id',
            'bank_entries.id as bank_entry_id',
            'bank_entries.date as bank_date',
        ])
        ->orderBy('bank_entries.date', 'desc')
        ->orderBy('company.name')
        ->where('settled', false);

        $total = $baseQuery->count();
        $total_fees = $baseQuery->sum('bank_entry_child_amounts.fees');
        $total_deposit = $baseQuery->sum('bank_entry_child_amounts.deposit');
        $total_amount = $baseQuery->sum('bank_entry_child_amounts.total_amount');
        return to_json([
            'collection' => $baseQuery->filter(),
            'total' => $total,
            'total_fees' => $total_fees,
            'total_deposit' => $total_deposit,
            'total_amount' => $total_amount,
        ]);
        
    }

    public function getMissingCashAmountReport($args)
    {
        $companyId = $args['company_id'] ?? null;

        // Get target ledger IDs (Cash Clearing)
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
                    'total_deposit' => 0,
                ],
            ]);
        }

        $currentWorkgroup = session('workgroup');
        
        // Get unsettled child amounts for Cash Clearing ledger code
        $missingAmounts = DB::table('bank_entry_child_amounts')
            ->join('bank_entry_items', 'bank_entry_child_amounts.bank_entry_item_id', '=', 'bank_entry_items.id')
            ->join('bank_entries', 'bank_entry_items.bank_entry_id', '=', 'bank_entries.id')
            ->join('company', 'bank_entries.company_id', '=', 'company.id')
            ->join('ledgers', 'bank_entry_items.ledger_id', '=', 'ledgers.id')
            ->leftJoin('old_daily_sales', 'bank_entry_child_amounts.id', '=', 'old_daily_sales.bank_child_amount_id')
            ->whereIn('bank_entry_items.ledger_id', $targetLedgerIds)
            ->whereNull('old_daily_sales.id') // Not settled in daily sales
            ->when($companyId, function($query) use ($companyId) {
                $query->where('bank_entries.company_id', $companyId);
            })
            ->when($currentWorkgroup, function($query) use ($currentWorkgroup) {
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
                'input_fees' => (float) $item->fees,
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

    public function updateAndSettleBankAmounts(Request $request)
    {
        $this->authorize('access', 'missing-bank-amount-report.update');
        
        $validated = $request->validate([
            'ledger_id' => 'required|integer',
            'items' => 'required|array|min:1',
            'items.*.child_amount_id' => 'required|integer|exists:bank_entry_child_amounts,id',
            'items.*.fees' => 'required|numeric|min:0',
        ]);

        $ledgerDetails = LedgerDetails::where('ledger_id', $validated['ledger_id'])->first();
        if(!$ledgerDetails) {
            return to_json([
                'success' => false,
                'message' => 'Ledger not found',
            ], 404);
        }

        $ledgerCode = $ledgerDetails->code;

        if($ledgerCode == LedgerController::VISA_MASTER_LEDGER_CODE) {
            return $this->updateAndSettleVisaAmounts($validated);
        }else if($ledgerCode == LedgerController::CASH_CLEARING_LEDGER_CODE) {
            return $this->updateAndSettleCashAmounts($validated);
        }else {
            return to_json([
                'success' => false,
                'message' => 'Invalid ledger code',
            ], 400);
        }
    }

    public function updateAndSettleVisaAmounts($args)
    {

        DB::beginTransaction();
        try {
            $updatedItems = [];
            $totalAmount = 0;
            $totalFees = 0;
            $ledgerVouchersToInsert = [];

            // Get visa ledger mapping
            $visaLedger = LedgerDetails::where('code', LedgerController::VISA_MASTER_LEDGER_CODE)
                ->pluck('ledger_id', 'company_id')
                ->toArray();

            LedgerVouchers::where('voucher_type', 'pj_payment_fees')->whereIn('voucher_id', array_column($args['items'], 'child_amount_id'))->delete();
            foreach ($args['items'] as $item) {
                // Update fees in bank_entry_child_amounts table
                $childAmount = BankEntryChildAmount::with('bankEntryItem.entry')
                    ->findOrFail($item['child_amount_id']);
                
                $newTotalAmount = $childAmount->deposit + $item['fees'];
                
                $childAmount->update([
                    'fees' => $item['fees'],
                    'total_amount' => $newTotalAmount,
                ]);

                $updatedItems[] = [
                    'child_amount_id' => $childAmount->id,
                    'old_fees' => $childAmount->getOriginal('fees'),
                    'new_fees' => $item['fees'],
                    'old_total' => $childAmount->getOriginal('total_amount'),
                    'new_total' => $newTotalAmount,
                ];

                $totalAmount += $newTotalAmount;
                $totalFees += $item['fees'];

                // Create ledger voucher entry for fees
                if ($item['fees'] > 0 && $childAmount->bankEntryItem && $childAmount->bankEntryItem->entry) {
                    $bankEntry = $childAmount->bankEntryItem->entry;
                    $companyId = $bankEntry->company_id;
                    $date = $bankEntry->date;

                    if (isset($visaLedger[$companyId])) {
                        $ledgerVouchersToInsert[] = [
                            'company_id' => $companyId,
                            'ledger_id' => $visaLedger[$companyId],
                            'amount' => -1 * floatval($item['fees']),
                            'debit' => floatval($item['fees']),
                            'credit' => 0,
                            'voucher_id' => $childAmount->id,
                            'voucher_items_id' => null,
                            'voucher_type' => 'pj_payment_fees',
                            'dbtable' => 'bank_entry_child_amounts',
                            'check_number' => null,
                            'description' => 'Visa fees for PJ Payment',
                            'date' => $date,
                            'created_at' => now(),
                            'updated_at' => now(),
                            'opp_ledger_id' => 0,
                        ];
                    }
                }
            }

            // Insert ledger vouchers in chunks
            if (!empty($ledgerVouchersToInsert)) {
                foreach (array_chunk($ledgerVouchersToInsert, 300) as $ledgerVoucherChunk) {
                    \App\Models\LedgerVouchers::insert($ledgerVoucherChunk);
                }
            }

            // Run settlement service for the updated amounts
            $settlementService = new PjPaymentSettlementService();
            $childAmountIds = collect($args['items'])->pluck('child_amount_id')->toArray();
            
            // Get the date range of the updated items for settlement
            $childAmounts = \App\Models\DataEntry\BankEntryChildAmount::whereIn('id', $childAmountIds)
                ->with('bankEntryItem.entry')
                ->get();

            $dates = $childAmounts->pluck('bankEntryItem.entry.date')->filter();
            $minDate = Carbon::parse($dates->min())->subDays(5)->toDateString();
            $maxDate = $dates->max();

            if ($minDate && $maxDate) {
                $settlementResult = $settlementService->settlePjPayments($minDate, $maxDate);
            } else {
                $settlementResult = ['success' => false, 'message' => 'Could not determine date range for settlement'];
            }

            DB::commit();

            return to_json([
                'success' => true,
                'message' => 'Successfully updated ' . count($updatedItems) . ' items and attempted settlement',
                'data' => [
                    'updated_items' => $updatedItems,
                    'total_amount' => $totalAmount,
                    'total_fees' => $totalFees,
                    'ledger_vouchers_created' => count($ledgerVouchersToInsert),
                    'settlement_result' => $settlementResult,
                ],
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return to_json([
                'success' => false,
                'message' => 'Failed to update amounts: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function updateAndSettleCashAmounts($args)
    {

        DB::beginTransaction();
        try {
            $updatedItems = [];
            $totalAmount = 0;
            $totalFees = 0;
            $ledgerVouchersToInsert = [];

            // Get visa ledger mapping
            $cashLedger = LedgerDetails::where('code', LedgerController::CASH_CLEARING_LEDGER_CODE)
                ->pluck('ledger_id', 'company_id')
                ->toArray();

            LedgerVouchers::where('voucher_type', 'daily_sale_fees')->whereIn('voucher_id', array_column($args['items'], 'child_amount_id'))->delete();
            foreach ($args['items'] as $item) {
                // Update fees in bank_entry_child_amounts table
                $childAmount = BankEntryChildAmount::with('bankEntryItem.entry')
                    ->findOrFail($item['child_amount_id']);
                
                $newTotalAmount = $childAmount->deposit + $item['fees'];
                
                $childAmount->update([
                    'fees' => $item['fees'],
                    'total_amount' => $newTotalAmount,
                ]);

                $updatedItems[] = [
                    'child_amount_id' => $childAmount->id,
                    'old_fees' => $childAmount->getOriginal('fees'),
                    'new_fees' => $item['fees'],
                    'old_total' => $childAmount->getOriginal('total_amount'),
                    'new_total' => $newTotalAmount,
                ];

                $totalAmount += $newTotalAmount;
                $totalFees += $item['fees'];

                // Create ledger voucher entry for fees
                if ($item['fees'] > 0 && $childAmount->bankEntryItem && $childAmount->bankEntryItem->entry) {
                    $bankEntry = $childAmount->bankEntryItem->entry;
                    $companyId = $bankEntry->company_id;
                    $date = $bankEntry->date;

                    if (isset($cashLedger[$companyId])) {
                        $ledgerVouchersToInsert[] = [
                            'company_id' => $companyId,
                            'ledger_id' => $cashLedger[$companyId],
                            'amount' => -1 * floatval($item['fees']),
                            'debit' => floatval($item['fees']),
                            'credit' => 0,
                            'voucher_id' => $childAmount->id,
                            'voucher_items_id' => null,
                            'voucher_type' => 'daily_sale_fees',
                            'dbtable' => 'bank_entry_child_amounts',
                            'check_number' => null,
                            'description' => 'Cash fees for Daily Sale',
                            'date' => $date,
                            'created_at' => now(),
                            'updated_at' => now(),
                            'opp_ledger_id' => 0,
                        ];
                    }
                }
            }

            // Insert ledger vouchers in chunks
            if (!empty($ledgerVouchersToInsert)) {
                foreach (array_chunk($ledgerVouchersToInsert, 300) as $ledgerVoucherChunk) {
                    LedgerVouchers::insert($ledgerVoucherChunk);
                }
            }

            // Run settlement service for the updated amounts
            $settlementService = new PjPaymentSettlementService();
            $childAmountIds = collect($args['items'])->pluck('child_amount_id')->toArray();
            
            // Get the date range of the updated items for settlement
            $childAmounts = BankEntryChildAmount::whereIn('id', $childAmountIds)
                ->with('bankEntryItem.entry')
                ->get();

            $dates = $childAmounts->pluck('bankEntryItem.entry.date')->filter();
            $minDate = Carbon::parse($dates->min())->subDays(5)->toDateString();
            $maxDate = $dates->max();

            if ($minDate && $maxDate) {
                $settlementResult = $settlementService->settleDailySales($minDate, $maxDate);
            } else {
                $settlementResult = ['success' => false, 'message' => 'Could not determine date range for settlement'];
            }

            DB::commit();

            return to_json([
                'success' => true,
                'message' => 'Successfully updated ' . count($updatedItems) . ' items and attempted settlement',
                'data' => [
                    'updated_items' => $updatedItems,
                    'total_amount' => $totalAmount,
                    'total_fees' => $totalFees,
                    'ledger_vouchers_created' => count($ledgerVouchersToInsert),
                    'settlement_result' => $settlementResult,
                ],
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return to_json([
                'success' => false,
                'message' => 'Failed to update amounts: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function getAvailableItemsForManualSettlement(Request $request)
    {
        $this->authorize('access', 'missing-bank-amount-report.index');
        
        $validated = $request->validate([
            'ledger_id' => 'required|integer',
            'company_id' => 'required|integer',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'target_amount' => 'required|numeric',
        ]);

        // Ensure date range is not more than 30 days
        $startDate = Carbon::parse($validated['start_date']);
        $endDate = Carbon::parse($validated['end_date']);
        
        if ($startDate->diffInDays($endDate) > 60) {
            return to_json([
                'success' => false,
                'message' => 'Date range cannot exceed 60 days',
            ], 400);
        }

        $ledgerDetails = LedgerDetails::where('ledger_id', $validated['ledger_id'])->first();
        if (!$ledgerDetails) {
            return to_json([
                'success' => false,
                'message' => 'Ledger not found',
            ], 404);
        }

        $ledgerCode = $ledgerDetails->code;

        if ($ledgerCode == LedgerController::VISA_MASTER_LEDGER_CODE) {
            return $this->getAvailablePjPaymentItems($validated);
        } else if ($ledgerCode == LedgerController::CASH_CLEARING_LEDGER_CODE) {
            return $this->getAvailableDailySalesItems($validated);
        } else {
            return to_json([
                'success' => false,
                'message' => 'Invalid ledger code',
            ], 400);
        }
    }

    private function getAvailablePjPaymentItems($validated)
    {
        $targetAmount = $validated['target_amount'];
        
        // Get unsettled PJ Payment items within date range for the company
        $items = PjPaymentItem::query()
            ->join('pj_payments', 'pj_payments_items.pj_payment_id', '=', 'pj_payments.id')
            ->where('pj_payments.company_id', $validated['company_id'])
            ->where('pj_payments_items.ledger_id', $validated['ledger_id'])
            ->where('pj_payments_items.settled', false)
            ->whereBetween('pj_payments.date', [$validated['start_date'], $validated['end_date']])
            ->select([
                'pj_payments_items.id',
                'pj_payments.date',
                'pj_payments_items.amount',
                'pj_payments_items.name as description',
                'pj_payments.id as pj_payment_id',
            ])
            ->orderBy('pj_payments.date', 'desc')
            ->get()
            ->map(function ($item) use ($targetAmount) {
                return [
                    'id' => $item->id,
                    'date' => $item->date,
                    'amount' => (float) $item->amount,
                    'description' => $item->description,
                    'pj_payment_id' => $item->pj_payment_id,
                    'match_score' => abs($item->amount - $targetAmount),
                ];
            })
            ->values();

        return to_json([
            'success' => true,
            'items' => $items,
            'type' => 'pj_payment',
        ]);
    }

    private function getAvailableDailySalesItems($validated)
    {
        $targetAmount = $validated['target_amount'];
        
        // Get unsettled Daily Sales within date range for the company
        $items = OldDailySale::query()
            ->where('company_id', $validated['company_id'])
            ->where('settled', false)
            ->where('cash_bag', '>', 0)
            ->whereBetween('date', [$validated['start_date'], $validated['end_date']])
            ->select([
                'id',
                'date',
                'cash_bag as amount',
                'total_sales',
            ])
            ->orderBy('date', 'desc')
            ->get()
            ->map(function ($item) use ($targetAmount) {
                return [
                    'id' => $item->id,
                    'date' => $item->date,
                    'amount' => (float) $item->amount,
                    'description' => 'Cash Bag - Total Sales: $' . number_format($item->total_sales, 2),
                    'match_score' => abs($item->amount - $targetAmount),
                ];
            })
            ->values();

        return to_json([
            'success' => true,
            'items' => $items,
            'type' => 'daily_sale',
        ]);
    }

    public function manualSettlement(Request $request)
    {
        $this->authorize('access', 'missing-bank-amount-report.manual-settlement');
        
        $validated = $request->validate([
            'child_amount_id' => 'required|integer|exists:bank_entry_child_amounts,id',
            'item_type' => 'required|string|in:pj_payment,daily_sale',
            'item_id' => 'required|integer',
            'bank_entry_id' => 'required|integer',
            'ledger_id' => 'required|integer',
        ]);

        DB::beginTransaction();
        try {
            $childAmount = BankEntryChildAmount::findOrFail($validated['child_amount_id']);
            $bankEntry = BankEntry::findOrFail($validated['bank_entry_id']);
            // Mark as manually settled
           

            if ($validated['item_type'] === 'pj_payment') {
                // Link PJ Payment Item to bank child amount
                $pjPaymentItem = PjPaymentItem::findOrFail($validated['item_id']);
                $childAmount->update([
                    'manual_settlement' => true,
                    'settled' => true,
                    'source' => 'pj_payment_item',
                    'source_id' => $validated['item_id'],
                ]);
                
                $pjPaymentItem->update([
                    'bank_child_amount_id' => $childAmount->id,
                    'settled' => true,
                ]);
                if($childAmount->fees > 0) {
                    $ledgerVoucher = [
                        'company_id' => $bankEntry->company_id,
                        'ledger_id' => $validated['ledger_id'],
                        'amount' => $childAmount->fees,
                        'debit' => 0,
                        'credit' => $childAmount->fees,
                        'voucher_id' => $childAmount->id,
                        'voucher_items_id' => null,
                        'voucher_type' => 'pj_payments_fees',
                        'dbtable' => 'bank_entry_child_amounts',
                        'check_number' => null,
                        'description' => null,
                        'date' => $bankEntry->date,
                        'created_at' => now(),
                        'updated_at' => now(),
                        'opp_ledger_id' => 0,
                    ];

                    LedgerVouchers::insert($ledgerVoucher);
                }

                $description = "Manually settled with PJ Payment Item #{$pjPaymentItem->id}";
            } else {
                // Link Daily Sale to bank child amount
                $dailySale = OldDailySale::findOrFail($validated['item_id']);
                $childAmount->update([
                    'manual_settlement' => true,
                    'settled' => true,
                    'source' => 'daily_sale',
                    'source_id' => $dailySale->id,
                ]);
                
                $dailySale->update([
                    'bank_child_amount_id' => $childAmount->id,
                    'settled' => true,
                ]);

                if($childAmount->fees > 0) {

                    $ledgerVoucher = [
                        'company_id' => $bankEntry->company_id,
                        'ledger_id' => $validated['ledger_id'],
                        'amount' => $childAmount->fees,
                        'debit' => 0,
                        'credit' => $childAmount->fees,
                        'voucher_id' => $childAmount->id,
                        'voucher_items_id' => null,
                        'voucher_type' => 'daily_sale_fees',
                        'dbtable' => 'bank_entry_child_amounts',
                        'check_number' => null,
                        'description' => null,
                        'date' => $bankEntry->date,
                        'created_at' => now(),
                        'updated_at' => now(),
                        'opp_ledger_id' => 0,
                    ];
                    LedgerVouchers::insert($ledgerVoucher);
                }

                $description = "Manually settled with Daily Sale #{$dailySale->id}";
            }

            DB::commit();

            return to_json([
                'success' => true,
                'message' => 'Successfully settled manually',
                'description' => $description,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return to_json([
                'success' => false,
                'message' => 'Failed to settle manually: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function unsettleDepositItem(Request $request)
    {
        $this->authorize('access', 'deposit-report.unsettle ');
        
        $validated = $request->validate([
            'item_id' => 'required|integer',
            'ledger_id' => 'required|integer',
        ]);

        $ledgerDetails = LedgerDetails::where('ledger_id', $validated['ledger_id'])->first();
        if (!$ledgerDetails) {
            return to_json([
                'success' => false,
                'message' => 'Ledger not found',
            ], 404);
        }

        $ledgerCode = $ledgerDetails->code;

        DB::beginTransaction();
        try {
            if ($ledgerCode == LedgerController::VISA_MASTER_LEDGER_CODE) {
                $pjPaymentItem = PjPaymentItem::findOrFail($validated['item_id']);
                
                if (!$pjPaymentItem->settled) {
                    return to_json([
                        'success' => false,
                        'message' => 'Item is not settled',
                    ], 400);
                }

                $bankChildAmountId = $pjPaymentItem->bank_child_amount_id;

                $pjPaymentItem->update([
                    'settled' => false,
                    'bank_child_amount_id' => null,
                ]);

                if ($bankChildAmountId) {
                    BankEntryChildAmount::where('id', $bankChildAmountId)
                        ->where('source', 'pj_payment_item')
                        ->where('source_id', $validated['item_id'])
                        ->update([
                            'settled' => false,
                            'source' => null,
                            'source_id' => null,
                        ]);
                }

                $description = "PJ Payment Item #{$pjPaymentItem->id} unsettled successfully";
            } else if ($ledgerCode == LedgerController::CASH_CLEARING_LEDGER_CODE) {
                $dailySale = OldDailySale::findOrFail($validated['item_id']);
                
                if (!$dailySale->settled) {
                    return to_json([
                        'success' => false,
                        'message' => 'Item is not settled',
                    ], 400);
                }

                $bankChildAmountId = $dailySale->bank_child_amount_id;

                $dailySale->update([
                    'settled' => false,
                    'bank_child_amount_id' => null,
                ]);

                if ($bankChildAmountId) {
                    BankEntryChildAmount::where('id', $bankChildAmountId)
                        ->where('source', 'daily_sale')
                        ->where('source_id', $validated['item_id'])
                        ->update([
                            'settled' => false,
                            'source' => null,
                            'source_id' => null,
                        ]);
                }

                $description = "Daily Sale #{$dailySale->id} unsettled successfully";
            } else {
                return to_json([
                    'success' => false,
                    'message' => 'Invalid ledger code',
                ], 400);
            }

            DB::commit();

            return to_json([
                'success' => true,
                'message' => 'Successfully unsettled',
                'description' => $description,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return to_json([
                'success' => false,
                'message' => 'Failed to unsettle: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function transferToAccount(Request $request)
    {
        $this->authorize('access', 'missing-bank-amount-report.update');
        
        $validated = $request->validate([
            'child_amount_id' => 'required|integer|exists:bank_entry_child_amounts,id'
        ]);

        DB::beginTransaction();
        try {
            $childAmount = BankEntryChildAmount::with('bankEntryItem.entry')->findOrFail($validated['child_amount_id']);
            $bankEntryItem = $childAmount->bankEntryItem;

            if (!$bankEntryItem) {
                return to_json([
                    'success' => false,
                    'message' => 'Bank entry item not found',
                ], 404);
            }

            $bankEntry = $bankEntryItem->entry;
            if (!$bankEntry) {
                return to_json([
                    'success' => false,
                    'message' => 'Bank entry not found',
                ], 404);
            }

            $transferAmount = $childAmount->deposit;
            $newBankItemAmount = $bankEntryItem->amount - $transferAmount;

            // Get the account ledger (1001.55)
            $accountLedger = LedgerDetails::where('code', LedgerController::ACCOUNT_LEDGER_CODE)
                ->where('company_id', $bankEntry->company_id)
                ->first();

            $accountEntryItem = BankEntryItem::where('bank_entry_id', $bankEntry->id)
                ->where('ledger_id', $accountLedger->ledger_id)
                ->first();

            if($accountEntryItem) {
                $transferAmount = $accountEntryItem->amount + $transferAmount;
                $accountEntryItem->delete();
                BankEntryChildAmount::where('bank_entry_item_id', $accountEntryItem->id)
                    ->delete();
                LedgerVouchers::where('voucher_type', 'bank_entries')
                    ->where('voucher_items_id', $accountEntryItem->id)
                    ->delete();
            }

            if (!$accountLedger) {
                return to_json([
                    'success' => false,
                    'message' => 'Account ledger (' . LedgerController::ACCOUNT_LEDGER_CODE . ') not found for this company',
                ], 404);
            }

            LedgerVouchers::where('voucher_type', 'daily_sale_fees')
                ->where('voucher_id', $childAmount->id)
                ->delete();
            // If new amount is zero or less, delete the item and child amount
            if ($newBankItemAmount <= 0) {
                LedgerVouchers::where('voucher_type', 'bank_entries')
                ->where('voucher_items_id', $bankEntryItem->id)
                ->update([
                    'ledger_id' => $accountLedger->ledger_id,
                    'amount' => $transferAmount,
                    'credit' => $transferAmount,
                ]);
                $bankEntryItem->update([
                    'amount' => $transferAmount,
                    'ledger_id' => $accountLedger->ledger_id,
                ]);
            } else {
                // Update the bank entry item amount
                $bankEntryItem->update([
                    'amount' => $newBankItemAmount,
                ]);
                // Update ledger voucher for the bank entry item
                LedgerVouchers::where('voucher_type', 'bank_entries')
                    ->where('voucher_items_id', $bankEntryItem->id)
                    ->update([
                        'amount' => $newBankItemAmount,
                        'credit' => $newBankItemAmount,
                    ]);

                $accountBankEntryItem = BankEntryItem::create([
                    'bank_entry_id' => $bankEntry->id,
                    'ledger_id' => $accountLedger->ledger_id,
                    'amount' => $transferAmount,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $childAmount->update([
                    'bank_entry_item_id' => $accountBankEntryItem->id,
                    'fees' => 0,
                ]);

                LedgerVouchers::create([
                    'company_id' => $bankEntry->company_id,
                    'ledger_id' => $accountLedger->ledger_id,
                    'opp_ledger_id' => 0,
                    'amount' => $accountBankEntryItem->amount,
                    'debit' => 0,
                    'credit' => $accountBankEntryItem->amount,
                    'voucher_id' => $bankEntry->id,
                    'voucher_items_id' => $accountBankEntryItem->id,
                    'voucher_type' => 'bank_entries',
                    'dbtable' => 'bank_entries',
                    'check_number' => null,
                    'description' => 'Transfer from cash clearing to account',
                    'date' => $bankEntry->date,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            DB::commit();

            return to_json([
                'success' => true,
                'message' => 'Successfully transferred to account',
                'data' => [
                    'transferred_amount' => $transferAmount,
                    'account_ledger_id' => $accountLedger->ledger_id,
                ],
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return to_json([
                'success' => false,
                'message' => 'Failed to transfer to account: ' . $e->getMessage(),
            ], 500);
        }
    }
}
