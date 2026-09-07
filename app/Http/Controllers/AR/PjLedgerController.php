<?php

namespace App\Http\Controllers\AR;

use App\Http\Controllers\Controller;
use App\Models\AR\PjPaymentItem;
use App\Models\DataEntry\BankEntry;
use App\Models\DataEntry\BankEntryChildAmount;
use App\Models\DataEntry\OldDailySale;
use App\Models\LedgerVouchers;
use Illuminate\Http\Request;

class PjLedgerController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('access', 'pj-ledger.index');
        $validated = $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date',
            'ledger_id' => 'required|integer',
            'company_id' => 'required|integer',
        ]);

        $openingBalance = LedgerVouchers::
            where('date', '<', $validated['start_date'])
            ->where('ledger_id', $validated['ledger_id'])
            ->where('company_id', $validated['company_id'])
            ->selectRaw('SUM(debit) - SUM(credit) as opening_balance')
            ->first();

        if($openingBalance) {
            $openingBalance = $openingBalance->opening_balance;
        } else {
            $openingBalance = 0;
        }

        $ledger_data = LedgerVouchers::where('date', '>=', $validated['start_date'])
            ->where('date', '<=', $validated['end_date'])
            ->where('ledger_id', $validated['ledger_id'])
            ->where('company_id', $validated['company_id'])
            ->groupBy('date')

            ->selectRaw("
                date,
                SUM(CASE WHEN voucher_type = 'ddc_doordash' THEN credit ELSE 0 END) as ddc_doordash,
                SUM(CASE WHEN voucher_type = 'fees_uploads' OR voucher_type = 'pj_payment_fees' THEN credit ELSE 0 END) as fees,
                SUM(CASE WHEN voucher_type != 'fees_uploads' AND voucher_type != 'pj_payment_fees' AND voucher_type != 'ddc_doordash' THEN credit ELSE 0 END) as credit,
                SUM(debit) as debit,
                GROUP_CONCAT(voucher_id) as voucher_ids,
                GROUP_CONCAT(voucher_items_id) as voucher_items_id,
                GROUP_CONCAT(dbtable) as dbtables,
                GROUP_CONCAT(voucher_type) as voucher_type
            ")->orderBy('date', 'asc')
            ->get()->toArray();

        //     $pjPaymentIds = [];
        //     $bankEntryIds = [];
            
        //     foreach ($ledger_data as $row) {
            
        //         $voucherIds = explode(',', $row['voucher_ids']);
        //         $voucherItemsIds = explode(',', $row['voucher_items_id']);
        //         $dbtables = explode(',', $row['dbtables']);
            
        //         foreach ($voucherIds as $index => $id) {
            
        //             $table = $dbtables[$index] ?? null;
            
        //             if ($table == 'pj_payments') {
        //                 $pjPaymentIds[] = intval($voucherItemsIds[$index]);
        //             }
            
        //             if($table == 'bank_entries') {
        //                 $bankEntryIds[] = intval($voucherItemsIds[$index]);
        //             }
        //         }
        //     }
            
        //     $pjPaymentItems = PjPaymentItem::whereIn('id', $pjPaymentIds)
        //         ->get()
        //         ->keyBy('id');
            

        //     $bankEntryChildAmounts = BankEntryChildAmount::whereIn('bank_entry_item_id', $bankEntryIds)
        //         ->get()->groupBy('bank_entry_item_id');

        // foreach ($ledger_data as $key => $item) {
        //     $voucher_items_id = explode(',', $item['voucher_items_id']);
        //     $voucher_type = explode(',', $item['voucher_type']);
        //     if($item['debit'] > 0 && in_array('pj_payments', $voucher_type)) {
        //         $index = array_search('pj_payments', $voucher_type);
        //         $ledger_data[$key]['settled'] = isset($pjPaymentItems[$voucher_items_id[$index]]) ? $pjPaymentItems[$voucher_items_id[$index]]->settled : false;
        //     }
        //     if($item['credit'] > 0 && in_array('bank_entries', $voucher_type)) {
        //         $index = array_search('bank_entries', $voucher_type);
        //         $ledger_data[$key]['child_amounts'] = isset($bankEntryChildAmounts[$voucher_items_id[$index]]) ? $bankEntryChildAmounts[$voucher_items_id[$index]] : [];
        //     }
        // }

        $balance = $openingBalance;
        foreach ($ledger_data as $key => $item) {

            $balance += $item['debit'] - $item['fees'] - $item['credit'] - $item['ddc_doordash'];
            $ledger_data[$key]['balance'] = round($balance, 2);
        }

        return to_json([
            'ledger_data' => $ledger_data,
            'opening_balance' => $openingBalance,
        ]);
    }

}
