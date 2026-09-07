<?php

namespace App\Services;

use App\Models\AR\PjPayment;
use App\Models\AR\PjPaymentItem;
use App\Models\DataEntry\BankEntryItem;
use App\Http\Controllers\Settings\LedgerController;
use App\Models\DataEntry\BankEntryChildAmount;
use App\Models\DataEntry\DailySale;
use App\Models\Settings\LedgerDetails;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PjPaymentSettlementService
{
    /**
     * Target ledger code for settlement (Visa / Master)
     */
    const VISA_CODE = LedgerController::VISA_MASTER_LEDGER_CODE;

    const DAILY_SALE_CODE = LedgerController::CASH_CLEARING_LEDGER_CODE;
    /**
     * Date margin for settlement matching (days)
     */
    const DATE_MARGIN_DAYS_VISA = 5;
    const DATE_MARGIN_DAYS_DAILY_SALE = 15;
    /**
     * Settle PJ payments for a given date range and company
     *
     * @param string $startDate
     * @param string $endDate
     * @param int|null $companyId
     * @return array
     */
    public function settlePjPayments($startDate, $endDate, $companyId = null)
    {
        try {
            $start = Carbon::parse($startDate)->startOfDay();
            $end = Carbon::parse($endDate)->endOfDay();
            
            if ($start->gt($end)) {
                throw new \InvalidArgumentException('Start date must be before or equal to end date');
            }

            // Log::info("Starting PJ Payment settlement", [
            //     'start_date' => $start->toDateString(),
            //     'end_date' => $end->toDateString(),
            //     'company_id' => $companyId
            // ]);

            $result = $this->settlePjPaymentsBatch($start, $end, $companyId);

            // if ($result['success']) {
            //     Log::info("PJ Payment settlement completed", [
            //         'total_items_settled' => $result['data']['items_settled'],
            //         'total_amount_settled' => $result['data']['total_amount_settled']
            //     ]);
            // }

            return $result;

        } catch (\Exception $e) {
            Log::error("PJ Payment settlement failed", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'message' => 'Settlement failed: ' . $e->getMessage(),
                'data' => []
            ];
        }
    }

    /**
     * Settle PJ payments for a specific batch (single month or smaller period)
     * This is the actual settlement logic that processes one batch at a time
     *
     * @param Carbon $start
     * @param Carbon $end
     * @param int|null $companyId
     * @return array
     */
    protected function settlePjPaymentsBatch($start, $end, $companyId = null)
    {
        try {
            if ($start->gt($end)) {
                throw new \InvalidArgumentException('Start date must be before or equal to end date');
            }

            // Get PJ payment items with target ledger code (1001.01 - Visa/Master)
            $pjPaymentItems = $this->getPjPaymentItemsForSettlement($start, $end, $companyId);
            // info('pjPaymentItems: '.$pjPaymentItems->count());
            if ($pjPaymentItems->isEmpty()) {
                return [
                    'success' => true,
                    'message' => 'No PJ payment items found with ledger code ' . self::VISA_CODE . ' for the specified criteria',
                    'data' => [
                        'items_settled' => 0,
                        'total_amount' => 0
                    ]
                ];
            }

            // Get matching bank entries within date range + margin
            $minDate = $start->copy()->toDateString();
            $maxDate = $end->copy()->addDays(self::DATE_MARGIN_DAYS_VISA)->toDateString();
            $bankEntries = $this->getBankEntriesForMatching($minDate, $maxDate, $companyId);
            // info('bankEntries: '.$bankEntries->count());
            return DB::transaction(function () use ($pjPaymentItems, $bankEntries, $start, $end) {
                $totalItemsSettled = 0;
                $totalAmountSettled = 0;
                $settlementResults = [];
                $matchedBankItems = [];

                // Create lookup for PJ payment items: amount|company_id => item
                $pjLookup = $pjPaymentItems->keyBy(function($item) {
                    return floatval($item->amount) . '|' . $item->company_id;
                });
                $pjDateLookup = $pjPaymentItems->keyBy(function($item) {
                    return $item->date . '|' . $item->company_id;
                });

                $pjPaymentItems = PjPaymentItem::whereIn('id', $pjPaymentItems->pluck('id'))->update(['settled' => false]);
                // Process bank entries to find matches
                foreach ($bankEntries as $bankEntry) {
                    foreach ($bankEntry->bankEntryChildAmounts as $childAmount) {
                        // Use total_amount from child amount record
                        $amount = floatval($childAmount->total_amount);

                        if($amount == 0) continue;
                        $lookupKey = $amount . '|' . $bankEntry->company_id;
                        

                        $pjItem = isset($pjLookup[$lookupKey]) ? $pjLookup[$lookupKey] : null;
                        if (isset($pjLookup[$lookupKey]) && abs(Carbon::parse($pjItem->date)->diffInDays(Carbon::parse($bankEntry->date))) <= self::DATE_MARGIN_DAYS_VISA) {
                            
                            // Check if not already settled and within date margin
                            if ($this->isWithinDateMargin($pjItem->date, $bankEntry->date)) {
                                // Mark as settled and link to child amount
                                PjPaymentItem::where('id', $pjItem->id)->update([
                                    'settled' => true,
                                    'bank_child_amount_id' => $childAmount->id
                                ]);

                                BankEntryChildAmount::where('id', $childAmount->id)->update([
                                    'settled' => true,
                                    'source' => 'pj_payment_item',
                                    'source_id' => $pjItem->id
                                ]);
                                
                                $totalItemsSettled++;
                                $totalAmountSettled += $pjItem->amount;
                                
                                $settlementResults[] = [
                                    'company' => $pjItem->payment->company->name,
                                    'pj_payment_id' => $pjItem->pj_payment_id,
                                    'pj_item_id' => $pjItem->id,
                                    'bank_entry_id' => $bankEntry->id,
                                    'bank_child_amount_id' => $childAmount->id,
                                    'amount' => $pjItem->amount,
                                    'deposit' => $childAmount->deposit,
                                    'fees' => $childAmount->fees,
                                    'total_amount' => $childAmount->total_amount,
                                    'pj_date' => $pjItem->date,
                                    'bank_date' => $bankEntry->date,
                                    'paid_date' => $pjItem->payment->date,
                                    'date_diff' => Carbon::parse($pjItem->date)->diffInDays(Carbon::parse($bankEntry->date))
                                ];
                                
                                // Remove from lookup to avoid double matching
                                unset($pjLookup[$lookupKey]);
                            }
                        }else{
                            $bankDate = Carbon::parse($bankEntry->date);
                            $firstDate = $bankDate->copy()->subDays(2);
                            $secondDate = $bankDate->copy()->subDays(3);
                            $thirdDate = $bankDate->copy()->subDays(4);
                            $fourthDate = $bankDate->copy()->subDays(5);

                            $p1Key = $firstDate->toDateString() . '|' . $bankEntry->company_id;
                            $p2Key = $secondDate->toDateString() . '|' . $bankEntry->company_id;
                            $p3Key = $thirdDate->toDateString() . '|' . $bankEntry->company_id;
                            $p4Key = $fourthDate->toDateString() . '|' . $bankEntry->company_id;

                            $pj1 = isset($pjDateLookup[$p1Key]) ? $pjDateLookup[$p1Key] : null;
                            $pj2 = isset($pjDateLookup[$p2Key]) ? $pjDateLookup[$p2Key] : null;
                            $pj3 = isset($pjDateLookup[$p3Key]) ? $pjDateLookup[$p3Key] : null;
                            $pj4 = isset($pjDateLookup[$p4Key]) ? $pjDateLookup[$p4Key] : null;
                            
                            $twoAmount = floatval($pj1->amount ?? 0) + floatval($pj2->amount ?? 0);
                            $threeAmount = $twoAmount + floatval($pj3->amount ?? 0);
                            $fourAmount = $threeAmount + floatval($pj4->amount ?? 0);

                            $thirdIncluded = false;
                            $fourthIncluded = false;
                            $matched = false;
                            if($fourAmount == $amount && $pj4){
                                $fourthIncluded = true;
                                $matched = true;
                            }else if($threeAmount == $amount && $pj3){
                                $thirdIncluded = true;
                                $matched = true;
                            }else if($twoAmount == $amount && $pj2){
                                $matched = true;
                            }
                            if($matched){
                                $arr=[
                                    ['amount'=>$pj1->amount,'model'=>$pj1],
                                    ['amount'=>$pj2->amount,'model'=>$pj2],
                                ];
                                if($fourthIncluded){
                                    $pj4 && $arr[] = ['amount'=>$pj4->amount,'model'=>$pj4];
                                    $pj3 && $arr[] = ['amount'=>$pj3->amount,'model'=>$pj3];
                                }
                                else if($thirdIncluded){
                                    $pj3 && $arr[] = ['amount'=>$pj3->amount,'model'=>$pj3];
                                }
                                $arr = collect($arr)->sortBy('amount')->toArray();
                                BankEntryChildAmount::where('bank_entry_item_id', $childAmount->bank_entry_item_id)->delete();

                                foreach($arr as $item){
                                    $amount = floatval($item['amount']);
                                    $model = $item['model'];
                                    $c= BankEntryChildAmount::create([
                                        'bank_entry_item_id' => $childAmount->bank_entry_item_id,
                                        'deposit' => $amount,
                                        'fees' => 0,
                                        'total_amount' => $amount,
                                        'source' => 'pj_payment_item',
                                        'source_id' => $bankEntry->id,
                                        'settled' => true,
                                    ]);
                                    $model->update([
                                        'bank_child_amount_id' => $c->id,
                                        'settled' => true,
                                    ]);
                                    $settlementResults[] = [
                                        'company' => $model->payment->company->name,
                                        'pj_payment_id' => $model->pj_payment_id,
                                        'pj_item_id' => $model->id,
                                        'bank_entry_id' => $bankEntry->id,
                                        'bank_child_amount_id' => $c->id,
                                        'amount' => $model->amount,
                                        'deposit' => $c->deposit,
                                        'fees' => $c->fees,
                                        'total_amount' => $c->total_amount,
                                        'pj_date' => $model->date,
                                        'bank_date' => $bankEntry->date,
                                        'paid_date' => $model->payment->date,
                                        'date_diff' => Carbon::parse($model->date)->diffInDays(Carbon::parse($bankEntry->date))
                                    ];
                                }
                            }
                        }
                    }
                }


                return [
                    'success' => true,
                    'message' => "Successfully settled {$totalItemsSettled} PJ payment items against bank entries",
                    'data' => [
                        'items_settled' => $totalItemsSettled,
                        'total_amount_settled' => $totalAmountSettled,
                        'matched_bank_items' => count($matchedBankItems),
                        'settlement_details' => $settlementResults,
                        'unmatched_pj_items' => $pjLookup->count()
                    ]
                ];
            });

        } catch (\Exception $e) {
            Log::error("PJ Payment batch settlement failed", [
                'error' => $e->getMessage(),
                'start_date' => $start->toDateString(),
                'end_date' => $end->toDateString()
            ]);

            return [
                'success' => false,
                'message' => 'Batch settlement failed: ' . $e->getMessage(),
                'data' => [
                    'items_settled' => 0,
                    'total_amount_settled' => 0,
                    'matched_bank_items' => 0,
                    'settlement_details' => [],
                    'unmatched_pj_items' => 0
                ]
            ];
        }
    }

    /**
     * Get PJ payment items that are eligible for settlement
     * Only items with target ledger code (1001.01)
     *
     * @param Carbon $start
     * @param Carbon $end
     * @param int|null $companyId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    protected function getPjPaymentItemsForSettlement($start, $end, $companyId = null)
    {
        // Get ledger IDs that have the target code (1001.01)
        $targetLedgerIds = LedgerDetails::where('code', self::VISA_CODE)
            ->when($companyId, function($query) use ($companyId) {
                $query->where('company_id', $companyId);
            })
            ->pluck('ledger_id')
            ->toArray();

        if (empty($targetLedgerIds)) {
            return collect();
        }

        return PjPaymentItem::join('pj_payments', 'pj_payments_items.pj_payment_id', '=', 'pj_payments.id')
            ->whereIn('pj_payments_items.ledger_id', $targetLedgerIds)
            ->where('pj_payments.date', '>=', $start->toDateString())
            ->where('pj_payments.date', '<=', $end->toDateString())
            ->where('pj_payments_items.amount', '>', 0)
            ->when($companyId, function($query) use ($companyId) {
                $query->where('pj_payments.company_id', $companyId);
            })
            ->select(
                'pj_payments_items.id',
                'pj_payments_items.pj_payment_id', 
                'pj_payments_items.amount',
                'pj_payments_items.settled',
                'pj_payments.date',
                'pj_payments.company_id'
            )
            ->get();
    }

    /**
     * Get bank entries for matching within date range
     *
     * @param string $minDate
     * @param string $maxDate
     * @param int|null $companyId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    protected function getBankEntriesForMatching($minDate, $maxDate, $companyId = null)
    {   
        return BankEntryItem::query()
                ->with('bankEntryChildAmounts')
                ->join('bank_entries', 'bank_entry_items.bank_entry_id', '=', 'bank_entries.id')
                ->whereBetween('bank_entries.date', [$minDate, $maxDate])
                ->when($companyId, function ($query) use ($companyId) {
                    $query->where('bank_entries.company_id', $companyId);
                })
                ->groupBy('bank_entry_items.id')
                ->select(
                    'bank_entry_items.id',
                    'bank_entry_items.bank_entry_id',
                    'bank_entry_items.amount',
                    'bank_entries.date',
                    'bank_entries.company_id'
                )
                ->get();
    }

    /**
     * Check if two dates are within the settlement margin
     *
     * @param string $pjDate
     * @param string $bankDate
     * @return bool
     */
    protected function isWithinDateMargin($pjDate, $bankDate)
    {
        $pjCarbon = Carbon::parse($pjDate);
        $bankCarbon = Carbon::parse($bankDate);
        
        return $pjCarbon->diffInDays($bankCarbon) <= self::DATE_MARGIN_DAYS_VISA;
    }

    /**
     * Get unsettled PJ payments for a date range
     * Only includes items with target ledger code (1001.01)
     *
     * @param string $startDate
     * @param string $endDate
     * @param int|null $companyId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getUnsettledPayments($startDate, $endDate, $companyId = null)
    {
        $start = Carbon::parse($startDate)->startOfDay();
        $end = Carbon::parse($endDate)->endOfDay();

        // Get target ledger IDs (1001.01)
        $targetLedgerIds = LedgerDetails::where('code', self::VISA_CODE)
            ->when($companyId, function($query) use ($companyId) {
                $query->where('company_id', $companyId);
            })
            ->pluck('ledger_id')
            ->toArray();

        if (empty($targetLedgerIds)) {
            return collect();
        }

        return PjPayment::with([
            'items' => function ($q) use ($targetLedgerIds) {
                $q->whereIn('ledger_id', $targetLedgerIds)
                  ->where('settled', false)
                  ->where('amount', '>', 0);
            }, 
            'company'
        ])
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->whereHas('items', function ($q) use ($targetLedgerIds) {
                $q->whereIn('ledger_id', $targetLedgerIds)
                  ->where('settled', false)
                  ->where('amount', '>', 0);
            })
            ->when($companyId, function($query) use ($companyId) {
                $query->where('company_id', $companyId);
            })
            ->get();
    }

    /**
     * Get settlement summary for a date range
     * Only includes items with target ledger code (1001.01)
     *
     * @param string $startDate
     * @param string $endDate
     * @param int|null $companyId
     * @return array
     */
    public function getSettlementSummary($startDate, $endDate, $companyId = null)
    {
        $start = Carbon::parse($startDate)->startOfDay();
        $end = Carbon::parse($endDate)->endOfDay();

        // Get target ledger IDs (1001.01)
        $targetLedgerIds = LedgerDetails::where('code', self::VISA_CODE)
            ->when($companyId, function($query) use ($companyId) {
                $query->where('company_id', $companyId);
            })
            ->pluck('ledger_id')
            ->toArray();

        if (empty($targetLedgerIds)) {
            return [
                'total_payments' => 0,
                'total_items' => 0,
                'settled_items' => 0,
                'unsettled_items' => 0,
                'total_amount' => 0,
                'settled_amount' => 0,
                'unsettled_amount' => 0,
                'by_company' => []
            ];
        }

        $payments = PjPayment::with([
            'items' => function($query) use ($targetLedgerIds) {
                $query->whereIn('ledger_id', $targetLedgerIds)->where('amount', '>', 0);
            }, 
            'company'
        ])
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->whereHas('items', function($query) use ($targetLedgerIds) {
                $query->whereIn('ledger_id', $targetLedgerIds)->where('amount', '>', 0);
            })
            ->when($companyId, function($query) use ($companyId) {
                $query->where('company_id', $companyId);
            })
            ->get();

        $summary = [
            'total_payments' => $payments->count(),
            'total_items' => 0,
            'settled_items' => 0,
            'unsettled_items' => 0,
            'total_amount' => 0,
            'settled_amount' => 0,
            'unsettled_amount' => 0,
            'by_company' => [],
            'VISA_CODE' => self::VISA_CODE
        ];

        foreach ($payments as $payment) {
            $paymentCompanyId = $payment->company_id;
            $companyName = $payment->company->name ?? 'Unknown';

            if (!isset($summary['by_company'][$paymentCompanyId])) {
                $summary['by_company'][$paymentCompanyId] = [
                    'company_name' => $companyName,
                    'total_items' => 0,
                    'settled_items' => 0,
                    'unsettled_items' => 0,
                    'total_amount' => 0,
                    'settled_amount' => 0,
                    'unsettled_amount' => 0
                ];
            }

            foreach ($payment->items as $item) {
                $summary['total_items']++;
                $summary['total_amount'] += $item->amount;
                $summary['by_company'][$paymentCompanyId]['total_items']++;
                $summary['by_company'][$paymentCompanyId]['total_amount'] += $item->amount;

                if ($item->settled) {
                    $summary['settled_items']++;
                    $summary['settled_amount'] += $item->amount;
                    $summary['by_company'][$paymentCompanyId]['settled_items']++;
                    $summary['by_company'][$paymentCompanyId]['settled_amount'] += $item->amount;
                } else {
                    $summary['unsettled_items']++;
                    $summary['unsettled_amount'] += $item->amount;
                    $summary['by_company'][$paymentCompanyId]['unsettled_items']++;
                    $summary['by_company'][$paymentCompanyId]['unsettled_amount'] += $item->amount;
                }
            }
        }

        // Convert by_company array to indexed array
        $summary['by_company'] = array_values($summary['by_company']);

        return $summary;
    }

    /**
     * Reverse settlement for specific payment items
     * Only reverses items that were previously settled and have target ledger code
     *
     * @param array $itemIds
     * @return array
     */
    public function reverseSettlement(array $itemIds)
    {
        try {
            return DB::transaction(function () use ($itemIds) {
                $itemsReversed = 0;
                $amountReversed = 0;

                // Get target ledger IDs (1001.01)
                $targetLedgerIds = LedgerDetails::where('code', self::VISA_CODE)
                    ->pluck('ledger_id')
                    ->toArray();

                foreach ($itemIds as $itemId) {
                    $item = PjPaymentItem::find($itemId);
                    
                    // Only reverse if item exists, is settled, has amount > 0, and has target ledger code
                    if ($item && 
                        $item->settled && 
                        $item->amount > 0 && 
                        in_array($item->ledger_id, $targetLedgerIds)) {
                        
                        // Mark as unsettled
                        $item->update(['settled' => false]);
                        
                        $itemsReversed++;
                        $amountReversed += $item->amount;
                    }
                }

                Log::info("Settlement reversed", [
                    'items_reversed' => $itemsReversed,
                    'amount_reversed' => $amountReversed,
                    'VISA_CODE' => self::VISA_CODE
                ]);

                return [
                    'success' => true,
                    'message' => "Successfully reversed settlement for {$itemsReversed} items with ledger code " . self::VISA_CODE,
                    'data' => [
                        'items_reversed' => $itemsReversed,
                        'amount_reversed' => $amountReversed,
                        'VISA_CODE' => self::VISA_CODE
                    ]
                ];
            });

        } catch (\Exception $e) {
            Log::error("Settlement reversal failed", [
                'error' => $e->getMessage(),
                'item_ids' => $itemIds
            ]);

            return [
                'success' => false,
                'message' => 'Settlement reversal failed: ' . $e->getMessage(),
                'data' => []
            ];
        }
    }

    /**
     * Get settlement matching details for debugging
     *
     * @param string $startDate
     * @param string $endDate
     * @param int|null $companyId
     * @return array
     */
    public function getSettlementMatchingDetails($startDate, $endDate, $companyId = null)
    {
        $start = Carbon::parse($startDate)->startOfDay();
        $end = Carbon::parse($endDate)->endOfDay();

        // Get PJ payment items
        $pjPaymentItems = $this->getPjPaymentItemsForSettlement($start, $end, $companyId);

        // Get bank entries
        $minDate = $start->copy()->toDateString();
        $maxDate = $end->copy()->addDays(self::DATE_MARGIN_DAYS_VISA)->toDateString();
        $bankEntries = $this->getBankEntriesForMatching($minDate, $maxDate, $companyId);
        $matchingDetails = [
            'pj_items_count' => $pjPaymentItems->count(),
            'bank_entries_count' => $bankEntries->count(),
            'date_range' => [
                'pj_start' => $start->toDateString(),
                'pj_end' => $end->toDateString(),
                'bank_start' => $minDate,
                'bank_end' => $maxDate,
                'date_margin_days_VISA' => self::DATE_MARGIN_DAYS_VISA
            ],
            'VISA_CODE' => self::VISA_CODE,
            'pj_items' => $pjPaymentItems->map(function($item) {
                return [
                    'id' => $item->id,
                    'amount' => $item->amount,
                    'company_id' => $item->company_id,
                    'date' => $item->date,
                    'settled' => $item->settled
                ];
            }),
            'bank_entries_with_child_amounts' => $bankEntries->map(function($entry) {
                return [
                    'id' => $entry->id,
                    'company_id' => $entry->company_id,
                    'date' => $entry->date,
                    'items_with_child_amounts' => $entry->items->map(function($item) {
                        return [
                            'id' => $item->id,
                            'amount' => $item->amount,
                            'child_amounts' => json_decode($item->child_amounts, true) ?? []
                        ];
                    })
                ];
            })
        ];

        return $matchingDetails;
    }

    /**
     * Settle Daily Sales cash clearing for a given date range and company
     *
     * @param string $startDate
     * @param string $endDate
     * @param int|null $companyId
     * @return array
     */
    public function settleDailySales($startDate, $endDate, $companyId = null)
    {
        try {
            $start = Carbon::parse($startDate)->startOfDay();
            $end = Carbon::parse($endDate)->endOfDay();
            
            if ($start->gt($end)) {
                throw new \InvalidArgumentException('Start date must be before or equal to end date');
            }

            // Log::info("Starting Daily Sale settlement", [
            //     'start_date' => $start->toDateString(),
            //     'end_date' => $end->toDateString(),
            //     'company_id' => $companyId
            // ]);

            $result = $this->settleDailySalesBatch($start, $end, $companyId);

            // if ($result['success']) {
            //     Log::info("Daily Sale settlement completed", [
            //         'total_items_settled' => $result['data']['items_settled'],
            //         'total_amount_settled' => $result['data']['total_amount_settled']
            //     ]);
            // }

            return $result;

        } catch (\Exception $e) {
            Log::error("Daily Sale settlement failed", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'message' => 'Settlement failed: ' . $e->getMessage(),
                'data' => []
            ];
        }
    }

    /**
     * Settle Daily Sales for a specific batch (single period)
     * This is the actual settlement logic that processes one batch at a time
     *
     * @param Carbon $start
     * @param Carbon $end
     * @param int|null $companyId
     * @return array
     */
    protected function settleDailySalesBatch($start, $end, $companyId = null)
    {
        try {
            if ($start->gt($end)) {
                throw new \InvalidArgumentException('Start date must be before or equal to end date');
            }

            // Get Daily Sales for settlement
            $dailySales = $this->getDailySalesForSettlement($start, $end, $companyId);
            if ($dailySales->isEmpty()) {
                return [
                    'success' => true,
                    'message' => 'No Daily Sales found for the specified criteria',
                    'data' => [
                        'items_settled' => 0,
                        'total_amount_settled' => 0,
                        'unmatched_daily_sales' => 0
                    ]
                ];
            }

            // Get matching bank entries within date range + margin
            $minDate = $start->copy()->toDateString();
            $maxDate = $end->copy()->addDays(self::DATE_MARGIN_DAYS_DAILY_SALE)->toDateString();
            $bankEntries = $this->getBankEntriesForMatching($minDate, $maxDate, $companyId);

            return DB::transaction(function () use ($dailySales, $bankEntries, $start, $end) {
                $totalItemsSettled = 0;
                $totalAmountSettled = 0;
                $settlementResults = [];

                // Create lookup for Daily Sales: cash_bag|company_id => daily_sale
                $dailySaleLookup = $dailySales->keyBy(function($item) {
                    return floatval($item->cash_bag) . '|' . $item->company_id;
                });
                
                // Reset settled status for the date range
                DailySale::whereIn('id', $dailySales->pluck('id'))->update(['settled' => false]);

                // Process bank entries to find matches
                foreach ($bankEntries as $bankEntry) {
                    foreach ($bankEntry->bankEntryChildAmounts as $childAmount) {
                        // Use total_amount from child amount record
                        $amount = floatval($childAmount->total_amount);
                        $lookupKey = $amount . '|' . $bankEntry->company_id;

                        if($amount == 0) continue;
                        
                        $dailySale = isset($dailySaleLookup[$lookupKey]) ? $dailySaleLookup[$lookupKey] : null;
                        if (isset($dailySaleLookup[$lookupKey]) && abs(Carbon::parse($dailySale->date)->diffInDays(Carbon::parse($bankEntry->date))) <= self::DATE_MARGIN_DAYS_DAILY_SALE) {
                            
                            // Check if not already settled and within date margin
                            if ($this->isWithinDateMarginDailySale($dailySale->date, $bankEntry->date)) {
                                // Mark as settled and link to child amount
                                DailySale::where('id', $dailySale->id)->update([
                                    'settled' => true,
                                    'bank_child_amount_id' => $childAmount->id
                                ]);

                                BankEntryChildAmount::where('id', $childAmount->id)->update([
                                    'settled' => true,
                                    'source' => 'daily_sale',
                                    'source_id' => $dailySale->id
                                ]);
                                
                                $totalItemsSettled++;
                                $totalAmountSettled += $dailySale->cash_bag;
                                
                                $settlementResults[] = [
                                    'daily_sale_id' => $dailySale->id,
                                    'bank_entry_id' => $bankEntry->id,
                                    'bank_child_amount_id' => $childAmount->id,
                                    'amount' => $dailySale->cash_bag,
                                    'deposit' => $childAmount->deposit,
                                    'fees' => $childAmount->fees,
                                    'total_amount' => $childAmount->total_amount,
                                    'company' => $dailySale->company->name,
                                    'daily_sale_date' => $dailySale->date,
                                    'bank_date' => $bankEntry->date,
                                    'paid_date' => $dailySale->date,
                                    'date_diff' => Carbon::parse($dailySale->date)->diffInDays(Carbon::parse($bankEntry->date))
                                ];
                                
                                // Remove from lookup to avoid double matching
                                unset($dailySaleLookup[$lookupKey]);
                            }
                        }
                    }
                }

                return [
                    'success' => true,
                    'message' => "Successfully settled {$totalItemsSettled} Daily Sales against bank entries",
                    'data' => [
                        'items_settled' => $totalItemsSettled,
                        'total_amount_settled' => $totalAmountSettled,
                        'settlement_details' => $settlementResults,
                        'unmatched_daily_sales' => $dailySaleLookup->count()
                    ]
                ];
            });

        } catch (\Exception $e) {
            Log::error("Daily Sale batch settlement failed", [
                'error' => $e->getMessage(),
                'start_date' => $start->toDateString(),
                'end_date' => $end->toDateString()
            ]);

            return [
                'success' => false,
                'message' => 'Batch settlement failed: ' . $e->getMessage(),
                'data' => [
                    'items_settled' => 0,
                    'total_amount_settled' => 0,
                    'settlement_details' => [],
                    'unmatched_daily_sales' => 0
                ]
            ];
        }
    }

    /**
     * Get Daily Sales that are eligible for settlement
     *
     * @param Carbon $start
     * @param Carbon $end
     * @param int|null $companyId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    protected function getDailySalesForSettlement($start, $end, $companyId = null)
    {
        return DailySale::whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->where('cash_bag', '>', 0)
            ->when($companyId, function($query) use ($companyId) {
                $query->where('company_id', $companyId);
            })
            ->select('id', 'date', 'company_id', 'cash_bag', 'settled', 'bank_child_amount_id')
            ->get();
    }

    /**
     * Check if two dates are within the settlement margin for daily sales
     *
     * @param string $dailySaleDate
     * @param string $bankDate
     * @return bool
     */
    protected function isWithinDateMarginDailySale($dailySaleDate, $bankDate)
    {
        $dailySaleCarbon = Carbon::parse($dailySaleDate);
        $bankCarbon = Carbon::parse($bankDate);
        
        return $dailySaleCarbon->diffInDays($bankCarbon) <= self::DATE_MARGIN_DAYS_DAILY_SALE;
    }

    /**
     * Get unsettled Daily Sales for a date range
     *
     * @param string $startDate
     * @param string $endDate
     * @param int|null $companyId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getUnsettledDailySales($startDate, $endDate, $companyId = null)
    {
        $start = Carbon::parse($startDate)->startOfDay();
        $end = Carbon::parse($endDate)->endOfDay();

        return DailySale::with('company')
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->where('settled', false)
            ->where('cash_bag', '>', 0)
            ->when($companyId, function($query) use ($companyId) {
                $query->where('company_id', $companyId);
            })
            ->get();
    }

    /**
     * Get settlement summary for Daily Sales
     *
     * @param string $startDate
     * @param string $endDate
     * @param int|null $companyId
     * @return array
     */
    public function getDailySaleSettlementSummary($startDate, $endDate, $companyId = null)
    {
        $start = Carbon::parse($startDate)->startOfDay();
        $end = Carbon::parse($endDate)->endOfDay();

        $dailySales = DailySale::with('company')
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->where('cash_bag', '>', 0)
            ->when($companyId, function($query) use ($companyId) {
                $query->where('company_id', $companyId);
            })
            ->get();

        $summary = [
            'total_sales' => $dailySales->count(),
            'settled_sales' => 0,
            'unsettled_sales' => 0,
            'total_amount' => 0,
            'settled_amount' => 0,
            'unsettled_amount' => 0,
            'by_company' => [],
            'DAILY_SALE_CODE' => self::DAILY_SALE_CODE
        ];

        foreach ($dailySales as $sale) {
            $companyId = $sale->company_id;
            $companyName = $sale->company->name ?? 'Unknown';

            if (!isset($summary['by_company'][$companyId])) {
                $summary['by_company'][$companyId] = [
                    'company_name' => $companyName,
                    'total_sales' => 0,
                    'settled_sales' => 0,
                    'unsettled_sales' => 0,
                    'total_amount' => 0,
                    'settled_amount' => 0,
                    'unsettled_amount' => 0
                ];
            }

            $summary['total_amount'] += $sale->cash_bag;
            $summary['by_company'][$companyId]['total_sales']++;
            $summary['by_company'][$companyId]['total_amount'] += $sale->cash_bag;

            if ($sale->settled) {
                $summary['settled_sales']++;
                $summary['settled_amount'] += $sale->cash_bag;
                $summary['by_company'][$companyId]['settled_sales']++;
                $summary['by_company'][$companyId]['settled_amount'] += $sale->cash_bag;
            } else {
                $summary['unsettled_sales']++;
                $summary['unsettled_amount'] += $sale->cash_bag;
                $summary['by_company'][$companyId]['unsettled_sales']++;
                $summary['by_company'][$companyId]['unsettled_amount'] += $sale->cash_bag;
            }
        }

        // Convert by_company array to indexed array
        $summary['by_company'] = array_values($summary['by_company']);

        return $summary;
    }

    /**
     * Reverse settlement for specific Daily Sales
     *
     * @param array $dailySaleIds
     * @return array
     */
    public function reverseDailySaleSettlement(array $dailySaleIds)
    {
        try {
            return DB::transaction(function () use ($dailySaleIds) {
                $itemsReversed = 0;
                $amountReversed = 0;

                foreach ($dailySaleIds as $dailySaleId) {
                    $dailySale = DailySale::find($dailySaleId);
                    
                    // Only reverse if item exists, is settled, and has cash_bag > 0
                    if ($dailySale && $dailySale->settled && $dailySale->cash_bag > 0) {
                        
                        // Get the associated bank child amount before unsettling
                        $bankChildAmountId = $dailySale->bank_child_amount_id;
                        
                        // Mark as unsettled
                        $dailySale->update([
                            'settled' => false,
                            'bank_child_amount_id' => null
                        ]);

                        // Also unsettle the bank child amount if it exists
                        if ($bankChildAmountId) {
                            BankEntryChildAmount::where('id', $bankChildAmountId)
                                ->where('source', 'daily_sale')
                                ->where('source_id', $dailySaleId)
                                ->update([
                                    'settled' => false,
                                    'source' => null,
                                    'source_id' => null
                                ]);
                        }
                        
                        $itemsReversed++;
                        $amountReversed += $dailySale->cash_bag;
                    }
                }

                Log::info("Daily Sale settlement reversed", [
                    'items_reversed' => $itemsReversed,
                    'amount_reversed' => $amountReversed
                ]);

                return [
                    'success' => true,
                    'message' => "Successfully reversed settlement for {$itemsReversed} Daily Sales",
                    'data' => [
                        'items_reversed' => $itemsReversed,
                        'amount_reversed' => $amountReversed
                    ]
                ];
            });

        } catch (\Exception $e) {
            Log::error("Daily Sale settlement reversal failed", [
                'error' => $e->getMessage(),
                'daily_sale_ids' => $dailySaleIds
            ]);

            return [
                'success' => false,
                'message' => 'Settlement reversal failed: ' . $e->getMessage(),
                'data' => []
            ];
        }
    }
}
